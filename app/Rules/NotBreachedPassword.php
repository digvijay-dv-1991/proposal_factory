<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Checks a password against the Have I Been Pwned breached-password list via
 * its k-anonymity API (only a 5-character SHA-1 prefix ever leaves the
 * server — the full password never does). Fails open: if the API is slow or
 * unreachable, the password is allowed rather than blocking account
 * creation on a third-party outage.
 */
class NotBreachedPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $hash = strtoupper(sha1($value));
        $prefix = substr($hash, 0, 5);
        $suffix = substr($hash, 5);

        try {
            $response = Http::timeout(2)->get("https://api.pwnedpasswords.com/range/{$prefix}");
        } catch (Throwable) {
            return;
        }

        if ($response->failed()) {
            return;
        }

        foreach (explode("\r\n", $response->body()) as $line) {
            [$candidateSuffix] = explode(':', $line, 2);

            if (Str::upper($candidateSuffix) === $suffix) {
                $fail('This password has appeared in a known data breach. Please choose a different password.');

                return;
            }
        }
    }
}
