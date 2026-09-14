<?php

namespace App\Services;

use App\Models\Opportunity;
use App\Services\Concerns\ExtractsOpenAiOutputText;
use App\Services\Concerns\SendsOpenAiRequests;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Researches and fills the Competitive Analysis tab (including its
 * Incumbent panel) using real web search — every factual claim must come
 * with a source link, and anything the search can't verify gets an honest
 * hedge sentence ("Unknown", "None identified", "TBD") rather than a
 * fabricated answer, matching ALQIMI's existing house style for this data.
 */
class OpenAiCompetitiveResearchService
{
    use ExtractsOpenAiOutputText;
    use SendsOpenAiRequests;

    /**
     * @var array<int, string>
     */
    private const FIELDS = [
        'competitive_position', 'competitive_analysis', 'competitive_discriminators', 'competitive_next_action',
        'competitors', 'teaming', 'rfp_instructions', 'rfp_sections', 'rfp_format', 'evaluation_factors',
        'incumbent', 'incumbent_contract', 'incumbent_award_value', 'incumbent_period', 'incumbent_brief',
        'incumbent_performance', 'incumbent_strengths', 'incumbent_weaknesses', 'incumbent_customer_relationship',
        'incumbent_source',
    ];

    /**
     * @return array{fields: array<string, string>, sources: array<int, array{label: string, url: string}>, competitor_profiles: array<int, array{name: string, url: string|null}>}
     */
    public function generate(Opportunity $opportunity): array
    {
        $apiKey = config('services.openai.key');

        if (blank($apiKey)) {
            throw new RuntimeException('OpenAI is not configured yet — add OPENAI_API_KEY to .env.');
        }

        $response = $this->callOpenAi($apiKey, [
            'model' => config('services.openai.model'),
            'tools' => [['type' => 'web_search']],
            'input' => $this->buildPrompt($opportunity),
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'opportunity_competitive_research',
                    'strict' => true,
                    'schema' => $this->schema(),
                ],
            ],
        ], timeout: 120);

        $outputText = $this->extractOutputText($response->json() ?? []);

        if (blank($outputText)) {
            Log::error('OpenAI competitive research: could not find a message output_text.', ['response' => $response->json()]);

            throw new RuntimeException('OpenAI returned no readable output for the competitive research call — see the log for the raw response.');
        }

        /** @var array{fields: array<string, string>, sources: array<int, array{label: string, url: string}>, competitor_profiles: array<int, array{name: string, url: string|null}>} */
        return json_decode($outputText, true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        $fieldProperties = array_fill_keys(self::FIELDS, ['type' => 'string']);

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['fields', 'sources', 'competitor_profiles'],
            'properties' => [
                'fields' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => self::FIELDS,
                    'properties' => $fieldProperties,
                ],
                'sources' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['label', 'url'],
                        'properties' => [
                            'label' => ['type' => 'string'],
                            'url' => ['type' => 'string'],
                        ],
                    ],
                ],
                'competitor_profiles' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['name', 'url'],
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'url' => ['type' => ['string', 'null']],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function buildPrompt(Opportunity $opportunity): string
    {
        return <<<PROMPT
            You are researching a real government-contracting opportunity using web
            search, writing in ALQIMI's established house style for this exact kind of
            record (examples of that style are given below — match their tone, length,
            and structure, not just their content).

            Opportunity: {$opportunity->name}
            Agency: {$opportunity->agency}
            Solicitation number: {$opportunity->solicitation}
            NAICS: {$opportunity->naics}
            Description: {$opportunity->description}
            Official source link: {$opportunity->link}

            Hard rules:
            - Never invent a company name, contract number, dollar figure, or
              requirement. Only report what you can verify via search results.
            - If something cannot be verified, DO NOT leave it blank and DO NOT guess
              — write an honest hedge in the field itself, exactly like the house
              style examples do: "Unknown", "None identified", "None identified - new
              requirement", "TBD", or "<partial fact>, reportedly — requires
              validation" when you found something but can't fully confirm it.
            - Every specific company name, contract number, or dollar figure you do
              report must be backed by at least one real URL in the "sources" array.
            - "competitive_position" must be exactly one of: Strong, Moderate, Weak,
              Unknown — pick Unknown if the evidence doesn't clearly support Strong,
              Moderate, or Weak.

            House style examples (content is from other opportunities, for style
            reference only — do not reuse any of these facts):

            competitive_analysis (one dense paragraph, ~500-650 characters: who the
            likely competitors are and why they're positioned well, ALQIMI's specific
            named differentiators, then a credibility caveat):
            "Sancorp Consulting is the reported incumbent and appears to benefit from
            direct ICD 731 execution experience, customer familiarity, cleared staff,
            and established assessment workflows. ALQIMI has strong technical and
            analytical relevance but a weaker contractual position because of the
            SDVOSB restriction and the likely importance of direct FBI or IC
            acquisition-security past performance. The most credible competitive
            position is as a specialized subcontractor strengthening an eligible prime
            with scalable OSINT, entity-resolution, FOCI/SCRM, and risk-scoring
            capabilities."

            competitive_discriminators (paragraph ending with how the proposal should
            frame the capability, not just stating it):
            "ALQIMI can support the prime and FBI ASU with a governed, source-traceable
            analytical workflow that connects corporate ownership, beneficial
            ownership, key management personnel, foreign affiliations, sanctions,
            litigation, financial, research, intellectual-property, cyber,
            supply-chain, adverse-media, and other PAI/CAI indicators. The value
            proposition is to help analysts conduct deeper and more consistent company
            threat assessments while preserving human review and Government control of
            final threat scoring."

            competitive_next_action (one sentence, imperative comma-separated action
            list — concrete B&P-stage next steps):
            "Confirm prime eligibility, identify and contact qualified SDVOSB
            partners, validate clearance requirements, obtain the official
            solicitation or acquisition forecast, and prepare a concise
            partner-facing capability package tied to the CTA workflow."

            competitors (one short sentence — plain company names if a specific one is
            known, otherwise a generic category list):
            "Sancorp Consulting and other qualified SDVOSB firms with FBI,
            Intelligence Community, counterintelligence, acquisition-security,
            cleared analytical, and ICD 731 experience."

            incumbent (extremely short — a name, or an honest hedge):
            "Sancorp Consulting" / "Unknown" / "None identified" / "None identified -
            new requirement"

            incumbent_brief / incumbent_performance / incumbent_strengths /
            incumbent_weaknesses / incumbent_customer_relationship (each ONE sentence,
            60-220 characters; hedge honestly rather than fabricate when there's no
            incumbent):
            "Sancorp Consulting is identified in the provided market-research record
            as the incumbent under contract 15F06722C0004269, reportedly expiring
            March 13, 2027."
            "Not applicable; however, firms already supporting similar environments
            may have customer and integration advantages."

            incumbent_contract / incumbent_award_value / incumbent_period (terse data
            fields — real numbers with a "reportedly"/"requires validation" hedge when
            not fully confirmed, or "TBD"/"Unknown" when nothing was found):
            "15F06722C0004269" / "Approximately $874,000 annual reported spending;
            requires validation" / "Reported through 2027-03-13" / "TBD"

            incumbent_source (a citation of where the info came from):
            "GovWin Opportunity 234282" or the actual URL/page name you used.

            Return JSON with three top-level keys: "fields" (an object with one
            entry per field below), "sources" (an array of {label, url} — every
            source page you actually used to write a specific fact, not a hedge),
            and "competitor_profiles" (see below).

            Fields to fill:
            - competitive_position, competitive_analysis, competitive_discriminators, competitive_next_action
            - competitors (names of known/likely competitors, one per line)
            - teaming (a recommended teaming strategy, only if the evidence supports one — otherwise an honest hedge)
            - rfp_instructions, rfp_sections, rfp_format, evaluation_factors (from the actual solicitation, if found — otherwise an honest hedge)
            - incumbent, incumbent_contract, incumbent_award_value, incumbent_period
            - incumbent_brief, incumbent_performance, incumbent_strengths, incumbent_weaknesses, incumbent_customer_relationship
            - incumbent_source (the URL that verifies the incumbent)

            competitor_profiles: one entry for every specific, named company you
            reported above (the incumbent plus each named competitor — skip generic
            category descriptions with no named company). Each entry has:
            - name: the exact company name as used in the fields above.
            - url: that company's real official website homepage, ONLY if you
              actually found and can verify one via search — otherwise null. Never
              guess a company's website from its name.
            PROMPT;
    }
}
