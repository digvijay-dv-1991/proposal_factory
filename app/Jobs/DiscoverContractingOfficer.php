<?php

namespace App\Jobs;

use App\Models\Opportunity;
use App\Models\OpportunityContact;
use App\Services\OpenAiContractingOfficerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class DiscoverContractingOfficer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    /**
     * A pause before Laravel's own automatic retry (on top of the internal
     * rate-limit backoff in SendsOpenAiRequests) — a retry fired instantly
     * after a failure is likely to hit the exact same transient condition
     * again. Gives whatever caused it a real chance to clear first.
     */
    public int $backoff = 60;

    /**
     * Covers the web-search call's own up-to-3 rate-limit-backoff attempts
     * (see SendsOpenAiRequests) at worst case, not typical runtime.
     */
    public int $timeout = 450;

    /**
     * Bump whenever OpenAiContractingOfficerService's output shape changes
     * — see GenerateAiAnalysis::CACHE_VERSION for why this matters: the
     * cache key is a hash of the opportunity's own content, not of the
     * code, so an unchanged opportunity re-processed after a schema change
     * would otherwise silently get served a stale, incompatible result.
     */
    private const CACHE_VERSION = 1;

    public function __construct(private readonly int $opportunityId) {}

    public function handle(OpenAiContractingOfficerService $service): void
    {
        $opportunity = Opportunity::query()->find($this->opportunityId);

        if ($opportunity === null) {
            return;
        }

        $cacheKey = 'contracting-officer:v'.self::CACHE_VERSION.':'.hash('sha256', (string) json_encode($opportunity->only([
            'name', 'agency', 'solicitation', 'link',
        ])));

        $result = Cache::remember($cacheKey, now()->addDay(), fn () => $service->generate($opportunity));

        foreach ($result['officers'] as $officer) {
            $this->recordOfficer($opportunity, $officer);
        }
    }

    /**
     * Only ever adds a contact — never overwrites one a capture manager
     * already entered or edited, and skips a re-run's duplicate of a
     * contact already recorded under the same name.
     *
     * @param  array{name: string, email: string|null, phone: string|null}  $officer
     */
    private function recordOfficer(Opportunity $opportunity, array $officer): void
    {
        $name = trim($officer['name']);

        if ($name === '') {
            return;
        }

        $alreadyExists = OpportunityContact::query()
            ->where('opportunity_id', $opportunity->id)
            ->where('name', $name)
            ->exists();

        if ($alreadyExists) {
            return;
        }

        OpportunityContact::query()->create([
            'opportunity_id' => $opportunity->id,
            'name' => $name,
            'email' => $officer['email'],
            'phone' => $officer['phone'],
        ]);
    }
}
