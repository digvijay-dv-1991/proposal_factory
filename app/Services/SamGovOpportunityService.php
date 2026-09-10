<?php

namespace App\Services;

use App\Models\Opportunity;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Pulls real, publicly-posted federal opportunities from the SAM.gov
 * Opportunities API and inserts any not already in the pipeline. Mirrors the
 * client's own capture playbook: broad keyword-bucket retrieval here, with
 * mission classification / bid-fit scoring left for a later AI pass — this
 * service only ever writes fields SAM.gov itself reports, never a guess.
 */
class SamGovOpportunityService
{
    private const BASE_URL = 'https://api.sam.gov/opportunities/v2/search';

    private const PAGE_SIZE = 100;

    /**
     * How far back to search on each run. Wider than the 24-hour "Added
     * Today" window on purpose, so a notice SAM was slow to index still gets
     * picked up.
     */
    private const LOOKBACK_DAYS = 14;

    /**
     * The same mission keyword buckets as the client's playbook (data/AI,
     * intelligence, CBRN, digitization, health, FOCI, plus general
     * modernization terms) — kept broad here; fit filtering happens later
     * once AI classification is wired up.
     *
     * @var array<int, string>
     */
    private const SEARCH_TERMS = [
        // Data
        'data fabric', 'data mesh', 'data lake', 'data lakehouse', 'data warehouse',
        'data integration', 'data ingestion', 'data orchestration', 'data transformation',
        'ETL', 'ELT', 'data management', 'data governance', 'metadata', 'cataloging',
        'lineage', 'provenance', 'data quality', 'observability', 'federated data',
        // Intelligence / decision support
        'data fusion', 'multi-source fusion', 'all-source intelligence', 'ISR', 'OSINT', 'PAI',
        'GEOINT', 'entity resolution', 'knowledge graph', 'relationship intelligence',
        'situational awareness', 'common operating picture', 'decision support', 'mission analytics',
        'threat analysis', 'anomaly detection', 'knowledge discovery', 'information retrieval',
        // CBRN
        'CBRN', 'CWMD', 'chemical warfare agent', 'biological threat', 'biosurveillance',
        'biodefense', 'bioaerosol', 'integrated early warning', 'hazard prediction',
        'sensor fusion', 'sensor data', 'chemical detection', 'biological detection',
        'environmental surveillance',
        // Digitization
        'OCR', 'document intelligence', 'document conversion', 'technical manual', 'IETM',
        'XML conversion', 'metadata extraction', 'entity extraction', 'semantic search', 'NLP',
        'legacy documents', 'unstructured data', 'structured data', 'knowledge extraction',
        'digital thread', 'MBSE', 'SysML',
        // Health
        'clinical data', 'health data', 'EHR', 'EMR', 'claims', 'encounters',
        'health interoperability', 'HL7', 'FHIR', 'population health', 'health surveillance',
        'medical readiness', 'public health', 'epidemiology', 'clinical analytics',
        // FOCI / economic security
        'FOCI', 'CFIUS', 'Team Telecom', 'beneficial ownership', 'foreign ownership',
        'foreign influence', 'technology transfer', 'supply chain risk', 'SCRM', 'vendor risk',
        'economic security',
        // General modernization
        'artificial intelligence', 'machine learning', 'LLM', 'API interoperability',
        'cloud native', 'microservices', 'Kubernetes', 'DevSecOps', 'analytics',
    ];

    /**
     * SAM.gov notice types that are never a live capture opportunity
     * (already decided/closed out), so they're never worth inserting.
     *
     * @var array<int, string>
     */
    private const SKIP_TYPES = ['Award Notice', 'Justification', 'Sale of Surplus Property'];

    /**
     * SAM.gov's `type` values mapped onto our fixed 6-phase board. Anything
     * not listed here (including an empty/unrecognized type) falls back to
     * Pre-Solicitation, the safest default for an early-stage notice.
     *
     * @var array<string, string>
     */
    private const PHASE_MAP = [
        'Presolicitation' => 'Pre-Solicitation',
        'Special Notice' => 'Pre-Solicitation',
        'Intent to Bundle Requirements (DoD-Funded)' => 'Pre-Solicitation',
        'Sources Sought' => 'RFI',
        'Solicitation' => 'RFP Released',
        'Combined Synopsis/Solicitation' => 'RFP Released',
    ];

    /**
     * How many of a run's newly-created opportunities get their full
     * description text fetched (a second, per-notice API call each), when
     * no explicit limit is passed to sync().
     */
    private const DEFAULT_DETAIL_LIMIT = 8;

    /**
     * @return array{terms_searched: int, fetched: int, created: int, duplicate: int, not_actionable: int, described: int}
     */
    public function sync(?int $termLimit = null, ?int $detailLimit = self::DEFAULT_DETAIL_LIMIT): array
    {
        $apiKey = config('services.sam_gov.key');

        if (blank($apiKey)) {
            throw new RuntimeException('SAM.gov is not configured yet — add SAM_GOV_API_KEY to .env.');
        }

        $terms = $termLimit === null ? self::SEARCH_TERMS : array_slice(self::SEARCH_TERMS, 0, $termLimit);

        /** @var array<string, array<string, mixed>> $byNoticeId */
        $byNoticeId = [];
        foreach ($terms as $term) {
            foreach ($this->searchTerm($apiKey, $term) as $record) {
                if (isset($record['noticeId'])) {
                    $byNoticeId[$record['noticeId']] = $record;
                }
            }
        }

        $created = 0;
        $duplicate = 0;
        $notActionable = 0;
        $newlyCreated = [];

        foreach ($byNoticeId as $record) {
            $outcome = $this->upsert($record);

            match ($outcome) {
                'created' => $created++,
                'duplicate' => $duplicate++,
                'not_actionable' => $notActionable++,
            };

            if ($outcome === 'created') {
                $newlyCreated[] = $record;
            }
        }

        $described = $this->describeFirst($apiKey, $newlyCreated, $detailLimit ?? self::DEFAULT_DETAIL_LIMIT);

        return [
            'terms_searched' => count($terms),
            'fetched' => count($byNoticeId),
            'created' => $created,
            'duplicate' => $duplicate,
            'not_actionable' => $notActionable,
            'described' => $described,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchTerm(string $apiKey, string $term): array
    {
        $records = [];
        $offset = 0;

        do {
            $page = Http::retry(3, 2000)
                ->timeout(45)
                ->get(self::BASE_URL, [
                    'api_key' => $apiKey,
                    'q' => $term,
                    'postedFrom' => now()->subDays(self::LOOKBACK_DAYS)->format('m/d/Y'),
                    'postedTo' => now()->format('m/d/Y'),
                    'limit' => self::PAGE_SIZE,
                    'offset' => $offset,
                ])
                ->throw()
                ->json('opportunitiesData', []);

            $records = [...$records, ...$page];
            $offset += self::PAGE_SIZE;
        } while (count($page) === self::PAGE_SIZE);

        return $records;
    }

    /**
     * Inserts a brand-new notice only. An existing `external_id` is left
     * completely untouched — capture managers may already be working the
     * record, and a daily SAM.gov sync must never overwrite their edits.
     *
     * @param  array<string, mixed>  $record
     * @return 'created'|'duplicate'|'not_actionable'
     */
    private function upsert(array $record): string
    {
        if (($record['active'] ?? 'No') !== 'Yes') {
            return 'not_actionable';
        }

        if (in_array($record['type'] ?? '', self::SKIP_TYPES, true)) {
            return 'not_actionable';
        }

        $deadline = $this->parseDate($record['responseDeadLine'] ?? null);

        if ($deadline !== null && $deadline->isPast()) {
            return 'not_actionable';
        }

        if (Opportunity::query()->where('external_id', $record['noticeId'])->exists()) {
            return 'duplicate';
        }

        Opportunity::query()->create([
            'external_id' => $record['noticeId'],
            'discovered_at' => now(),
            'date_added' => now()->toDateString(),
            ...$this->mapRecord($record, $deadline),
        ]);

        return 'created';
    }

    /**
     * Fetches real description text for up to $limit of this run's
     * brand-new notices — a second, per-notice API call each, deliberately
     * capped since it's the expensive part of a sync against a low daily
     * quota.
     *
     * @param  array<int, array<string, mixed>>  $records
     */
    private function describeFirst(string $apiKey, array $records, int $limit): int
    {
        $described = 0;

        foreach (array_slice($records, 0, max(0, $limit)) as $record) {
            $url = $record['description'] ?? null;

            if (! is_string($url) || $url === '') {
                continue;
            }

            $text = $this->fetchDescription($apiKey, $url);

            if ($text === null) {
                continue;
            }

            Opportunity::query()
                ->where('external_id', $record['noticeId'])
                ->update(['description' => $text]);

            $described++;
        }

        return $described;
    }

    /**
     * SAM.gov's search results only give a link to each notice's
     * description (see the `description` field); the real text lives behind
     * this second authenticated call, and comes back as HTML.
     */
    private function fetchDescription(string $apiKey, string $url): ?string
    {
        try {
            $response = Http::retry(2, 2000)
                ->timeout(30)
                ->get($url, ['api_key' => $apiKey])
                ->throw();
        } catch (Throwable) {
            return null;
        }

        $body = $response->json('description') ?? $response->body();

        if (! is_string($body) || $body === '' || str_contains($body, 'Description not found')) {
            return null;
        }

        $text = trim(preg_replace('/\s+/', ' ', strip_tags($body)) ?? '');

        return $text === '' ? null : $text;
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    private function mapRecord(array $record, ?Carbon $deadline): array
    {
        [$agency, $subsection] = $this->splitAgencyPath((string) ($record['fullParentPathName'] ?? ''));

        return [
            'name' => $record['title'] ?? 'Untitled opportunity',
            'agency' => $agency,
            'agency_subsection' => $subsection,
            'solicitation' => $record['solicitationNumber'] ?? null,
            'naics' => $record['naicsCode'] ?? null,
            'psc' => $record['classificationCode'] ?? null,
            'set_aside' => $record['typeOfSetAsideDescription'] ?? null,
            'phase' => self::PHASE_MAP[$record['type'] ?? ''] ?? 'Pre-Solicitation',
            'response_due' => $deadline?->toDateString(),
            'link' => $record['uiLink'] ?? $record['additionalInfoLink'] ?? null,
        ];
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    private function splitAgencyPath(string $path): array
    {
        $segments = array_values(array_filter(explode('.', $path)));

        return [$segments[0] ?? 'Unknown Agency', $segments[1] ?? null];
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
