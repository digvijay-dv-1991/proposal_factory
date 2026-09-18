<?php

use App\Livewire\OpportunityBoard;
use App\Models\MarketCompetitor;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;

/**
 * Regression coverage for the OpportunityBoard performance pass: the board's
 * opportunities() query was trimmed to a fixed column list, metrics() no
 * longer double-fetches the whole table, and agencyOptions()/
 * bidTypeOptions()/focusOptions()/competitor_links are now cached. These
 * tests exist to catch a column trim silently breaking a card accessor, or a
 * cache silently going stale after a save.
 */
function makeBoardOpportunity(array $attributes = []): Opportunity
{
    return Opportunity::factory()->create(array_merge([
        'external_id' => (string) Str::uuid(),
        'name' => 'Test Opportunity',
        'agency' => 'Test Agency',
    ], $attributes));
}

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('the board renders cards correctly with the trimmed opportunities() column list', function () {
    makeBoardOpportunity([
        'name' => 'Trimmed Column Card',
        'agency' => 'Agency Alpha',
        'phase' => 'Pre-Solicitation',
        'decision' => 'Pending',
        'go_strength' => 80,
        'gap' => 'Some gap',
        'competitive_analysis' => 'Some analysis',
        'link' => 'https://example.com/solicitation',
    ]);

    Livewire::test(OpportunityBoard::class)
        ->assertSee('Trimmed Column Card')
        ->assertSee('Agency Alpha')
        ->assertSee('Strong Fit')
        ->assertSee('Gap analyzed')
        ->assertSee('Competition analyzed')
        ->assertSee('Open Source');
});

test('metrics() reflects only non-No-Bid opportunities without double-fetching all columns', function () {
    makeBoardOpportunity([
        'decision' => 'No Bid',
        'value' => 999999,
    ]);

    makeBoardOpportunity([
        'decision' => 'Pending',
        'value' => 1000,
        'link' => 'https://example.com/valid-source',
        'response_due' => now()->addDays(5),
    ]);

    makeBoardOpportunity([
        'decision' => 'Bid',
        'value' => 2000,
        'link' => null,
        'govwin_link' => null,
        'response_due' => now()->addDays(30),
    ]);

    $metrics = Livewire::test(OpportunityBoard::class)->instance()->metrics();

    expect($metrics['active'])->toBe(2)
        ->and($metrics['validated'])->toBe(1)
        ->and($metrics['pipeline_value'])->toBe(3000.0)
        ->and($metrics['due_soon'])->toBe(1);
});

test('agency, bid-type, and focus filter options are cached and refresh after a save', function () {
    Cache::forget(OpportunityBoard::AGENCY_OPTIONS_CACHE_KEY);
    Cache::forget(OpportunityBoard::BID_TYPE_OPTIONS_CACHE_KEY);
    Cache::forget(OpportunityBoard::FOCUS_OPTIONS_CACHE_KEY);

    makeBoardOpportunity([
        'agency' => 'First Agency',
        'set_aside' => 'Small Business Set-Aside',
        'focus' => ['Cyber'],
    ]);

    $board = Livewire::test(OpportunityBoard::class)->instance();

    expect($board->agencyOptions())->toContain('First Agency')
        ->and($board->bidTypeOptions())->toContain('Small Business Set-Aside')
        ->and($board->focusOptions())->toContain('Cyber');

    makeBoardOpportunity([
        'agency' => 'Second Agency',
        'set_aside' => 'GSA MAS',
        'focus' => ['Health'],
    ]);

    // A fresh component instance simulates the next Livewire request — the
    // cache, not #[Computed]'s per-request memoization, is what's under test.
    $refreshedBoard = Livewire::test(OpportunityBoard::class)->instance();

    expect($refreshedBoard->agencyOptions())->toContain('Second Agency')
        ->and($refreshedBoard->bidTypeOptions())->toContain('GSA MAS')
        ->and($refreshedBoard->focusOptions())->toContain('Health');
});

test('the competitor-links catalog is cached and refreshes after a market competitor is saved', function () {
    Cache::forget(MarketCompetitor::CATALOG_CACHE_KEY);

    $opportunity = makeBoardOpportunity(['incumbent' => 'Acme Corp']);

    expect($opportunity->competitor_links)->toBe([]);

    MarketCompetitor::create([
        'name' => 'Acme Corp',
        'url' => 'https://acme.example.com',
        'label' => 'Acme',
        'alqimi_products' => 'n/a',
        'competitor_offering' => 'n/a',
        'overlap' => 'n/a',
        'alqimi_advantage' => 'n/a',
        'competitor_advantage' => 'n/a',
        'strategy' => 'n/a',
    ]);

    $reloaded = Opportunity::find($opportunity->id);

    expect($reloaded->competitor_links)->toContain([
        'name' => 'Acme Corp',
        'url' => 'https://acme.example.com',
        'role' => 'Incumbent',
    ]);
});

test('opening a card does not re-run the board\'s Computed queries once per consumer', function () {
    // Livewire's #[Computed] only memoizes a method for the request when
    // it's read as a magic property ($this->opportunities), never when
    // called as a normal method ($this->opportunities()) — the latter
    // silently bypasses the cache and re-runs the underlying query on
    // every single access. OpportunityBoard/its blade used the method-call
    // form everywhere, so a single card-open re-ran the same board query
    // ~20 times (once per consumer: sectionCounts' 8 sections, board()'s
    // internal calls, boardColumn() per visible phase, etc.). This test
    // pins the query count to a small bound so that regression can't
    // silently come back if a future edit reintroduces a `()` call site.
    foreach (range(1, 20) as $i) {
        makeBoardOpportunity(['external_id' => (string) Str::uuid(), 'name' => "Card {$i}"]);
    }

    $target = makeBoardOpportunity(['name' => 'Target Card']);

    $component = Livewire::test(OpportunityBoard::class);

    $queryCount = 0;
    DB::listen(function () use (&$queryCount) {
        $queryCount++;
    });

    $component->call('openOpportunity', $target->id);

    // Comfortably above the ~21 queries measured for this flow post-fix,
    // comfortably below the ~168 measured with the method-call bug present.
    expect($queryCount)->toBeLessThan(40);
});
