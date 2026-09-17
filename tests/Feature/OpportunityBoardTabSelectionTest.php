<?php

use App\Livewire\OpportunityBoard;
use App\Models\User;
use Livewire\Livewire;

/**
 * Regression coverage for a real bug: selectTab() used to read the cached
 * #[Computed] activeTab property to decide whether the clicked tab was
 * already active, then mutate the very properties activeTab is derived
 * from. Livewire caches a Computed property for the rest of the request the
 * first time it's read, so every later read in that same request — the tab
 * bar's own "active" highlighting, the board query's tab filter — kept
 * seeing the tab from before the click. In the browser this looked like a
 * tab click doing nothing (or needing a second click), and a second click
 * would then incorrectly trigger the "reselecting the active tab" reset.
 * Fixed by having selectTab() check a plain, always-fresh method instead of
 * the cached property.
 */
test('selecting a tab takes effect immediately, in a single click', function () {
    $this->actingAs(User::factory()->create());

    $component = Livewire::test(OpportunityBoard::class);

    $component->call('selectTab', 'bid');

    expect($component->get('decisionFilter'))->toBe('Bid')
        ->and($component->html())->toContain('class="active" data-view="bid"');
});

test('switching directly from one tab to another takes effect in a single click', function () {
    $this->actingAs(User::factory()->create());

    $component = Livewire::test(OpportunityBoard::class);

    $component->call('selectTab', 'bid');
    $component->call('selectTab', 'submitted');

    expect($component->get('decisionFilter'))->toBe('')
        ->and($component->get('phaseFilter'))->toBe('Submitted')
        ->and($component->html())->toContain('class="active" data-view="submitted"')
        ->and($component->html())->not->toContain('class="active" data-view="bid"');
});

test('re-clicking the already-active tab resets back to the Pipeline tab', function () {
    $this->actingAs(User::factory()->create());

    $component = Livewire::test(OpportunityBoard::class);

    $component->call('selectTab', 'submitted');
    $component->call('selectTab', 'submitted');

    expect($component->get('decisionFilter'))->toBe('')
        ->and($component->get('phaseFilter'))->toBe('')
        ->and($component->html())->toContain('class="active" data-view="pipeline"');
});
