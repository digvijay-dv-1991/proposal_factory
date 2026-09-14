<?php

namespace App\Services\Concerns;

/**
 * The Responses API's raw HTTP JSON has no top-level "output_text" field —
 * that convenience property only exists in the official SDKs, computed
 * client-side. Calling the API directly over HTTP (as every OpenAI service
 * in this app does), the real text lives inside the "output" array, on the
 * "message"-type item's first "output_text" content block (the array also
 * carries "reasoning" and "web_search_call" items, which this skips over).
 */
trait ExtractsOpenAiOutputText
{
    /**
     * @param  array<string, mixed>  $response
     */
    private function extractOutputText(array $response): string
    {
        foreach ($response['output'] ?? [] as $item) {
            if (($item['type'] ?? null) !== 'message') {
                continue;
            }

            foreach ($item['content'] ?? [] as $content) {
                if (($content['type'] ?? null) === 'output_text' && is_string($content['text'] ?? null)) {
                    return $content['text'];
                }
            }
        }

        return '';
    }
}
