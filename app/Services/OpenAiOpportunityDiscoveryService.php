<?php

namespace App\Services;

use App\Livewire\OpportunityBoard;
use App\Services\Concerns\ExtractsOpenAiOutputText;
use App\Services\Concerns\SendsOpenAiRequests;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Replicates the client's own manual daily ChatGPT search — broad web search
 * across real public federal-opportunity sources (SAM.gov, agency forecast
 * pages, etc.) to surface brand-new candidate opportunities. Same house
 * rules as the other OpenAI services: real web_search grounding, no
 * invented facts, every candidate anchored to a real source link.
 *
 * Mission classification, Bid/No-Bid gating, and scoring are deliberately
 * out of scope here — this only fetches and shapes candidate records, the
 * same way SamGovOpportunityService leaves AI-derived fields for a later
 * pass.
 */
class OpenAiOpportunityDiscoveryService
{
    use ExtractsOpenAiOutputText;
    use SendsOpenAiRequests;

    /**
     * @param  array<int, string>  $exclude  Titles/solicitation numbers already found earlier in this same run — never repeat them, so an earlier batch's tokens aren't wasted on a duplicate.
     * @return array<int, array<string, mixed>>
     */
    public function discover(int $limit, array $exclude = []): array
    {
        $apiKey = config('services.openai.key');

        if (blank($apiKey)) {
            throw new RuntimeException('OpenAI is not configured yet — add OPENAI_API_KEY to .env.');
        }

        $response = $this->callOpenAi($apiKey, [
            'model' => config('services.openai.model'),
            'tools' => [['type' => 'web_search']],
            'input' => $this->buildPrompt($limit, $exclude),
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'opportunity_discovery',
                    'strict' => true,
                    'schema' => $this->schema(),
                ],
            ],
        ], timeout: 120);

        $outputText = $this->extractOutputText($response->json() ?? []);

        if (blank($outputText)) {
            Log::error('OpenAI opportunity discovery: could not find a message output_text.', ['response' => $response->json()]);

            throw new RuntimeException('OpenAI returned no readable output for the opportunity discovery call — see the log for the raw response.');
        }

        $decoded = json_decode($outputText, true, flags: JSON_THROW_ON_ERROR);

        /** @var array<int, array<string, mixed>> */
        return $decoded['opportunities'];
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        $nullableString = ['type' => ['string', 'null']];
        $nullableNumber = ['type' => ['number', 'null']];

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['opportunities'],
            'properties' => [
                'opportunities' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => [
                            'name', 'agency', 'agency_subsection', 'solicitation', 'naics', 'phase',
                            'response_due', 'release_date', 'value', 'value_is_estimated', 'vehicle',
                            'set_aside', 'link', 'description',
                        ],
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'agency' => ['type' => 'string'],
                            'agency_subsection' => $nullableString,
                            'solicitation' => $nullableString,
                            'naics' => $nullableString,
                            'phase' => ['type' => 'string', 'enum' => OpportunityBoard::PHASES],
                            'response_due' => $nullableString,
                            'release_date' => $nullableString,
                            'value' => $nullableNumber,
                            'value_is_estimated' => ['type' => 'boolean'],
                            'vehicle' => $nullableString,
                            'set_aside' => $nullableString,
                            'link' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<int, string>  $exclude
     */
    private function buildPrompt(int $limit, array $exclude): string
    {
        $today = now()->toDateString();
        $exclusionBlock = $exclude === [] ? '' : <<<BLOCK


            Already found earlier in this same search — do NOT include any of
            these again, find genuinely different opportunities instead:
            {$this->excludeList($exclude)}
            BLOCK;

        return <<<PROMPT
            You are a government-contracting capture analyst performing the same
            daily search a human analyst currently runs by hand in ChatGPT: find
            brand-new, currently-open federal contracting opportunities using real
            web search, across broad mission areas — data/AI and data engineering,
            intelligence/decision-support and OSINT, CBRN/CWMD and biodefense,
            document digitization and NLP, health-data interoperability, FOCI/economic
            security, and general IT/cloud modernization.

            Today's date is {$today}. Search real, trusted, reputable sources only
            — not limited to any single site. Official government posting sites are
            the preferred and most trustworthy source, including but not limited to:
            SAM.gov, DHS APFS, SBIR.gov, the CWMD Consortium, Tradewinds, DIU,
            SOFWERX, the Army/Navy/Air Force innovation portals, JPEO-CBRND, DTRA,
            DHA, and HHS/CDC/BARDA/NIH forecast sites. Established secondary
            aggregators (e.g. GovWin, HigherGov, GovTribe) may be used to help
            discover a lead, but never as the link you report — always trace a
            candidate back to its official government source page before including
            it. Return at most {$limit} opportunities: the most relevant,
            currently-open, real notices you can verify via search.
            {$exclusionBlock}
            Hard rules:
            - Never invent a title, agency, solicitation number, dollar value, or
              URL. Only report opportunities you can actually verify via search
              results.
            - Every opportunity MUST have a real, working link to its official
              government posting (not an aggregator page). If you cannot find a
              real official link for a candidate, drop that candidate entirely
              rather than guessing a URL or linking to an aggregator.
            - Skip anything already closed, cancelled, or awarded — only
              currently-actionable notices.
            - For any field you cannot confirm (agency_subsection, solicitation,
              naics, response_due, release_date, vehicle, set_aside), return null
              rather than guessing.
            - "value": if the notice publishes an actual dollar figure or ceiling,
              report it and set "value_is_estimated" to false. If nothing is
              published (common for RFI/Sources Sought-stage notices), you may
              give a reasoned estimate based on comparable real past contracts for
              this agency and type of work — set "value_is_estimated" to true when
              you do. If you cannot ground even an estimate in something real
              (no comparable contracts found), return null for "value" and false
              for "value_is_estimated" rather than inventing a number.
            - "phase" must be exactly one of: {$this->phaseList()} — pick the
              closest match to the notice's actual current stage.
            - "description" is a factual synopsis only (max ~130 words): what the
              government needs, in plain requirement language. Do not mention any
              company name, fit assessment, or bid recommendation — this is a
              neutral capture-board synopsis, not an analysis.
            - "response_due" and "release_date", when known, must be plain dates in
              YYYY-MM-DD format.

            Return JSON with a single top-level key "opportunities": an array of
            up to {$limit} objects, each with: name, agency, agency_subsection,
            solicitation, naics, phase, response_due, release_date, value,
            value_is_estimated, vehicle, set_aside, link, description.
            PROMPT;
    }

    private function phaseList(): string
    {
        return implode(', ', OpportunityBoard::PHASES);
    }

    /**
     * @param  array<int, string>  $exclude
     */
    private function excludeList(array $exclude): string
    {
        return implode("\n", array_map(fn (string $item): string => "- {$item}", $exclude));
    }
}
