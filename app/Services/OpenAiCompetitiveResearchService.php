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
        'competitive_position', 'competitive_analysis',
        'competitors', 'teaming', 'rfp_instructions', 'rfp_sections', 'rfp_format', 'evaluation_factors',
        'incumbent', 'incumbent_contract', 'incumbent_award_value', 'incumbent_period', 'incumbent_brief',
        'incumbent_performance', 'incumbent_strengths', 'incumbent_weaknesses', 'incumbent_customer_relationship',
        'incumbent_source',
    ];

    /**
     * @return array{fields: array<string, string>, sources: array<int, array{label: string, url: string}>, competitor_profiles: array<int, array{name: string, url: string|null}>, teaming_recommendation: array{company: string|null, contact_email: string|null, contact_phone: string|null, rationale: string}}
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

        /** @var array{fields: array<string, string>, sources: array<int, array{label: string, url: string}>, competitor_profiles: array<int, array{name: string, url: string|null}>, teaming_recommendation: array{company: string|null, contact_email: string|null, contact_phone: string|null, rationale: string}} */
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
            'required' => ['fields', 'sources', 'competitor_profiles', 'teaming_recommendation'],
            'properties' => [
                'fields' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => self::FIELDS,
                    'properties' => $fieldProperties,
                ],
                'teaming_recommendation' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['company', 'contact_email', 'contact_phone', 'rationale'],
                    'properties' => [
                        'company' => ['type' => ['string', 'null']],
                        'contact_email' => ['type' => ['string', 'null']],
                        'contact_phone' => ['type' => ['string', 'null']],
                        'rationale' => ['type' => 'string'],
                    ],
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
            - Never put contact details (email, phone) anywhere in "fields" or
              "sources" — contact info may ONLY appear inside
              "teaming_recommendation", never in the competitive_analysis prose or
              anywhere else. This keeps competitor/incumbent research and teaming
              contact info in clearly separate places.

            House style examples (content is from other opportunities, for style
            reference only — do not reuse any of these facts):

            competitive_analysis (an HTML bullet list, `<ul><li>...</li></ul>`, of
            3-5 short bullets covering: who the likely competitors are and why
            they're positioned well, ALQIMI's specific named differentiators, the
            recommended next action, then a credibility caveat — each bullet a terse
            fragment, not a full paragraph, so a reader grasps the competitive
            picture in one glance):
            "<ul><li>Sancorp Consulting is the reported incumbent, with direct ICD
            731 execution experience, customer familiarity, and cleared staff</li>
            <li>ALQIMI has strong technical/analytical relevance but a weaker
            contractual position due to the SDVOSB restriction</li><li>Most credible
            path: specialized subcontractor offering scalable OSINT,
            entity-resolution, FOCI/SCRM, and risk-scoring capabilities</li>
            <li>Next action: confirm prime eligibility and contact qualified SDVOSB
            partners</li></ul>"

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

            Return JSON with four top-level keys: "fields" (an object with one
            entry per field below), "sources" (an array of {label, url} — every
            source page you actually used to write a specific fact, not a hedge),
            "competitor_profiles" (see below), and "teaming_recommendation" (see
            below).

            Fields to fill:
            - competitive_position, competitive_analysis (see the HTML-bullet-list format and example above — it now also covers what used to be separate "discriminators" and "next action" fields, as their own bullets within the same list)
            - competitors (names of known/likely competitors, one per line)
            - teaming (a recommended teaming strategy, strategy/rationale ONLY — never a company's contact details, those belong only in teaming_recommendation below — only if the evidence supports one, otherwise an honest hedge)
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

            teaming_recommendation: a single structured recommendation, separate
            from the "teaming" prose field above, used to auto-populate ALQIMI's
            actual Teaming tab (not just describe a strategy):
            - company: the one specific company name your "teaming" strategy
              recommends teaming with, ONLY if the evidence genuinely supports a
              specific named company — otherwise null. Never invent a plausible-
              sounding company; a generic "look for a qualified partner" strategy
              with no specific evidenced company must return null here.
            - contact_email / contact_phone: a real business-development or capture
              contact for that company, ONLY if you actually found one via search
              (e.g. on the company's own site) — otherwise null. Do not guess a
              likely-looking email/phone format; most solicitations won't surface
              this, and null is the expected, honest answer far more often than not.
            - rationale: one short sentence on why this company, for context on the
              Teaming tab entry it creates.
            PROMPT;
    }
}
