<?php

namespace App\Services;

use App\Models\Opportunity;
use App\Models\ScoringSetting;
use App\Services\Concerns\ExtractsOpenAiOutputText;
use App\Services\Concerns\SendsOpenAiRequests;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Writes the AI Analysis tab's four sections, a first-draft Gap Analysis
 * (gap / gap_mitigation), first-draft focus-area tags, and a first-draft
 * Bid Strength score (go_strength) — all when the capture manager hasn't
 * entered their own yet — from the opportunity's own already-recorded
 * fields. Pure synthesis — no web search, no external facts, so nothing
 * here needs source citations. Focus tags matter beyond display: they're
 * the dominant input to the Bid Strength rubric's Capability Fit factor, so
 * an untagged opportunity would otherwise score low across the board for
 * lack of signal, not lack of fit.
 */
class OpenAiAnalysisService
{
    use ExtractsOpenAiOutputText;
    use SendsOpenAiRequests;

    /**
     * @return array{focus: array<int, string>, gap: string, gap_mitigation: string, go_strength: int, executive_summary: string, why_it_matters: string, red_team_critique: string, competitive_outlook: string}
     */
    public function generate(Opportunity $opportunity): array
    {
        $apiKey = config('services.openai.key');

        if (blank($apiKey)) {
            throw new RuntimeException('OpenAI is not configured yet — add OPENAI_API_KEY to .env.');
        }

        $response = $this->callOpenAi($apiKey, [
            'model' => config('services.openai.model'),
            'input' => $this->buildPrompt($opportunity),
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'opportunity_ai_analysis',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => [
                            'focus', 'gap', 'gap_mitigation', 'go_strength', 'executive_summary', 'why_it_matters',
                            'red_team_critique', 'competitive_outlook',
                        ],
                        'properties' => [
                            'focus' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                                'minItems' => 2,
                                'maxItems' => 4,
                            ],
                            'gap' => ['type' => 'string'],
                            'gap_mitigation' => ['type' => 'string'],
                            'go_strength' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
                            'executive_summary' => ['type' => 'string'],
                            'why_it_matters' => ['type' => 'string'],
                            'red_team_critique' => ['type' => 'string'],
                            'competitive_outlook' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ], timeout: 60);

        $outputText = $this->extractOutputText($response->json() ?? []);

        if (blank($outputText)) {
            Log::error('OpenAI AI analysis: could not find a message output_text.', ['response' => $response->json()]);

            throw new RuntimeException('OpenAI returned no readable output for the AI analysis call — see the log for the raw response.');
        }

        /** @var array{focus: array<int, string>, gap: string, gap_mitigation: string, go_strength: int, executive_summary: string, why_it_matters: string, red_team_critique: string, competitive_outlook: string} */
        return json_decode($outputText, true, flags: JSON_THROW_ON_ERROR);
    }

    private function buildPrompt(Opportunity $opportunity): string
    {
        $existingFocus = blank($opportunity->focus)
            ? 'not yet tagged — determine 2-4 tags yourself from the Description below, see the focus field instructions'
            : implode(', ', $opportunity->focus).' — already tagged by a capture manager, return this exact same list unchanged';
        $existingGap = blank($opportunity->gap)
            ? 'none recorded yet — draft an honest first assessment below'
            : $opportunity->gap;
        $weights = ScoringSetting::current();
        $existingScore = $opportunity->go_strength > 0
            ? "{$opportunity->go_strength}% ({$opportunity->fit_label}) — already scored by a capture manager, do not recompute it, just reference it as given"
            : 'not yet scored — compute your own first-pass go_strength below using the rubric';

        return <<<PROMPT
            You are a capture-management analyst writing an internal briefing for a
            government-contracting bid team, in ALQIMI's house style: neutral,
            third-person, factual, government-requirement language — the same tone
            used in this opportunity's own Description and Gap / Risk fields. Using
            ONLY the information given below about this one opportunity, write seven
            short sections. Do not invent any fact that is not present below — if
            something isn't known, write an honest hedge like "Unknown" or "Not yet
            assessed" instead of guessing.

            Opportunity: {$opportunity->name}
            Agency: {$opportunity->agency}
            Description: {$opportunity->description}
            ALQIMI focus areas: {$existingFocus}
            Bid Strength score: {$existingScore}
            Gap / Risk: {$existingGap}
            Incumbent: {$opportunity->incumbent}
            Competitive position: {$opportunity->competitive_position}
            Competitive analysis notes: {$opportunity->competitive_analysis}

            Bid Strength (go_strength) rubric — weights an admin has configured,
            reason through each in this order and proportion when the score above
            says "not yet scored":
            - Capability Fit ({$weights->capability_fit_weight}%): does the Description above actually match the focus tags (existing or the ones you just determined)? This is the dominant factor — a strong opportunity with no real ALQIMI capability match should score low regardless of the rest. If focus was "not yet tagged," base this on the tags you yourself determined from the real Description text, not on the absence of a pre-existing tag — a genuinely on-mission opportunity should score well even if nobody had tagged it yet.
            - Competitive Position ({$weights->competitive_position_weight}%): an entrenched incumbent lowers the score; no incumbent or an open field raises it.
            - Mission Fit ({$weights->mission_fit_weight}%): how central this is to the focus tags (existing or just-determined), not just tangentially related.
            - Timing / Stage ({$weights->timing_weight}%): early-stage (RFI/Sources Sought) with real runway scores better than a notice already due imminently with no prior positioning.
            - Vehicle Accessibility ({$weights->vehicle_accessibility_weight}%): an open full-and-open or small-business set-aside ALQIMI qualifies for scores better than a vehicle ALQIMI has no access to.
            A weak-fit opportunity should honestly score low, even 0 — the point is a reasoned, opportunity-specific number, not a flattering default.

            Sections to write (each one dense paragraph, 2-4 sentences, no bullet points, except go_strength and focus):
            - focus: if the ALQIMI focus areas above are already tagged, return that exact same list unchanged. Otherwise determine 2-4 tags yourself from the real Description text — one broad mission-area tag plus 1-3 more specific technical/domain tags, matching the style ALQIMI already uses elsewhere in this system, e.g.: "AI & Advanced Analytics", "CBRN & CWMD", "DoD Intelligence & Ops", "Digitization", "Modernization", "Health", "FOCI", "Enterprise Data Management", "ISR Platforms", "Unmanned Systems", "Knowledge Graphs", "Rapid Acquisition", "Sensor Data Fusion", "Space Systems". Only use tags that genuinely describe what the Description actually asks for — do not force a fit that isn't there; a truly generic notice can validly get generic tags like "Modernization" alone.
            - gap: if a Gap / Risk is already recorded above, restate it faithfully — do not contradict or replace a capture manager's own assessment. If none is recorded yet, draft an honest first-pass capability/competitive gap assessment using only the fields above, hedging clearly ("further capture assessment needed") wherever the evidence here is thin.
            - gap_mitigation: a suggested mitigation approach for the gap above, in ALQIMI's "Mitigation approach" voice — concrete next steps, not generic advice.
            - go_strength: an integer 0-100. If the Bid Strength score above is already set by a capture manager, return that exact same number unchanged. Otherwise compute it yourself using the rubric above.
            - executive_summary: what this opportunity is and what the customer actually needs, written the way a government-requirement summary reads (see Description above for the expected tone).
            - why_it_matters: why this is worth ALQIMI's attention — tie it explicitly to the focus areas and the go_strength value above, naming the specific alignment rather than speaking generically.
            - red_team_critique: an honest, critical read of the gap you wrote above — what could go wrong and why it matters to the bid decision.
            - competitive_outlook: our competitive position using only the incumbent/competitive fields above — name the incumbent and position plainly, and hedge honestly ("Unknown", "not yet assessed") wherever those fields are empty rather than inventing a read.
            PROMPT;
    }
}
