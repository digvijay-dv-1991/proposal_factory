<?php

namespace App\Services;

use App\Models\Opportunity;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Writes the AI Analysis tab's four sections from the opportunity's own
 * already-recorded fields. Pure synthesis — no web search, no external
 * facts, so nothing here needs source citations.
 */
class OpenAiAnalysisService
{
    /**
     * @return array{executive_summary: string, why_it_matters: string, red_team_critique: string, competitive_outlook: string}
     */
    public function generate(Opportunity $opportunity): array
    {
        $apiKey = config('services.openai.key');

        if (blank($apiKey)) {
            throw new RuntimeException('OpenAI is not configured yet — add OPENAI_API_KEY to .env.');
        }

        $response = Http::withToken($apiKey)
            ->timeout(60)
            ->post('https://api.openai.com/v1/responses', [
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
                            'required' => ['executive_summary', 'why_it_matters', 'red_team_critique', 'competitive_outlook'],
                            'properties' => [
                                'executive_summary' => ['type' => 'string'],
                                'why_it_matters' => ['type' => 'string'],
                                'red_team_critique' => ['type' => 'string'],
                                'competitive_outlook' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
            ])
            ->throw();

        /** @var array{executive_summary: string, why_it_matters: string, red_team_critique: string, competitive_outlook: string} */
        return json_decode((string) $response->json('output_text'), true, flags: JSON_THROW_ON_ERROR);
    }

    private function buildPrompt(Opportunity $opportunity): string
    {
        $focus = implode(', ', $opportunity->focus ?? []) ?: 'none recorded';

        return <<<PROMPT
            You are a capture-management analyst writing an internal briefing for a
            government-contracting bid team, in ALQIMI's house style: neutral,
            third-person, factual, government-requirement language — the same tone
            used in this opportunity's own Description and Gap / Risk fields. Using
            ONLY the information given below about this one opportunity, write four
            short sections. Do not invent any fact that is not present below — if
            something isn't known, write an honest hedge like "Unknown" or "Not yet
            assessed" instead of guessing.

            Opportunity: {$opportunity->name}
            Agency: {$opportunity->agency}
            Description: {$opportunity->description}
            ALQIMI focus areas: {$focus}
            Bid Strength score: {$opportunity->go_strength}% ({$opportunity->fit_label})
            Gap / Risk: {$opportunity->gap}
            Incumbent: {$opportunity->incumbent}
            Competitive position: {$opportunity->competitive_position}
            Competitive analysis notes: {$opportunity->competitive_analysis}

            Sections to write (each one dense paragraph, 2-4 sentences, no bullet points):
            - executive_summary: what this opportunity is and what the customer actually needs, written the way a government-requirement summary reads (see Description above for the expected tone).
            - why_it_matters: why this is worth ALQIMI's attention — tie it explicitly to the focus areas and Bid Strength score above, naming the specific alignment rather than speaking generically.
            - red_team_critique: an honest, critical read of the Gap / Risk above — what could go wrong and why it matters to the bid decision, in the same voice as a "Mitigation approach" write-up.
            - competitive_outlook: our competitive position using only the incumbent/competitive fields above — name the incumbent and position plainly, and hedge honestly ("Unknown", "not yet assessed") wherever those fields are empty rather than inventing a read.
            PROMPT;
    }
}
