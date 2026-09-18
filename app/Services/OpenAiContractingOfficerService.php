<?php

namespace App\Services;

use App\Models\Opportunity;
use App\Services\Concerns\ExtractsOpenAiOutputText;
use App\Services\Concerns\SendsOpenAiRequests;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Client review: Contracting Officers should be AI-populated from the real
 * solicitation instead of always manually typed. Researches the official
 * source notice via web search for the published point(s) of contact —
 * name, email, phone ONLY (title/organization/role were dropped as
 * "useless fields" per the client's review). Never invents a name, email,
 * or phone — a notice with no published POC yields an empty list, not a
 * guess, matching the same "never invent facts" rule already used by
 * OpenAiCompetitiveResearchService.
 */
class OpenAiContractingOfficerService
{
    use ExtractsOpenAiOutputText;
    use SendsOpenAiRequests;

    /**
     * @return array{officers: array<int, array{name: string, email: string|null, phone: string|null}>}
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
                    'name' => 'opportunity_contracting_officers',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['officers'],
                        'properties' => [
                            'officers' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'additionalProperties' => false,
                                    'required' => ['name', 'email', 'phone'],
                                    'properties' => [
                                        'name' => ['type' => 'string'],
                                        'email' => ['type' => ['string', 'null']],
                                        'phone' => ['type' => ['string', 'null']],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], timeout: 120);

        $outputText = $this->extractOutputText($response->json() ?? []);

        if (blank($outputText)) {
            Log::error('OpenAI contracting officer discovery: could not find a message output_text.', ['response' => $response->json()]);

            throw new RuntimeException('OpenAI returned no readable output for the contracting officer call — see the log for the raw response.');
        }

        /** @var array{officers: array<int, array{name: string, email: string|null, phone: string|null}>} */
        return json_decode($outputText, true, flags: JSON_THROW_ON_ERROR);
    }

    private function buildPrompt(Opportunity $opportunity): string
    {
        return <<<PROMPT
            You are researching the point(s) of contact published on a real
            government-contracting solicitation, using web search.

            Opportunity: {$opportunity->name}
            Agency: {$opportunity->agency}
            Solicitation number: {$opportunity->solicitation}
            Official source link: {$opportunity->link}

            Find the Contracting Officer / Contract Specialist / point-of-contact
            name, email, and phone number as actually published on the official
            source link above (or another verifiable official page for this exact
            solicitation, e.g. SAM.gov). A notice often lists more than one contact
            — return one entry per distinct person found.

            Hard rules:
            - Never invent a name, email, or phone number. Only report what you can
              verify via search results from an official source for this exact
              solicitation.
            - If no point of contact is published or found, return an empty
              "officers" array — do not guess, and do not return a generic agency
              contact that isn't specific to this solicitation.
            - "email" and "phone" are each independently optional — if a found
              contact's name is published but their email or phone isn't, return
              that field as null rather than omitting the person entirely.

            Return JSON with one top-level key "officers": an array of
            {name, email, phone}.
            PROMPT;
    }
}
