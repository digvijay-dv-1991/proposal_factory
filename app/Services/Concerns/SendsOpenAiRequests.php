<?php

namespace App\Services\Concerns;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Every OpenAI Responses API call in this app goes through here so a 429
 * (rate limit) backs off and retries instead of failing immediately —
 * without this, a burst of calls (e.g. discovery's follow-on analysis jobs
 * firing for several opportunities at once) would fail outright and force
 * a full, separately-billed job retry for what was only ever a timing
 * problem.
 */
trait SendsOpenAiRequests
{
    private const OPENAI_RESPONSES_URL = 'https://api.openai.com/v1/responses';

    private const MAX_RATE_LIMIT_ATTEMPTS = 3;

    /**
     * @param  array<string, mixed>  $payload
     */
    private function callOpenAi(string $apiKey, array $payload, int $timeout): Response
    {
        $attempt = 0;

        while (true) {
            $attempt++;

            $response = Http::withToken($apiKey)
                ->timeout($timeout)
                ->post(self::OPENAI_RESPONSES_URL, $payload);

            if ($response->status() !== 429 || $attempt >= self::MAX_RATE_LIMIT_ATTEMPTS) {
                return $response->throw();
            }

            $retryAfter = (int) ($response->header('Retry-After') ?: (2 ** $attempt));
            sleep(min($retryAfter, 30));
        }
    }
}
