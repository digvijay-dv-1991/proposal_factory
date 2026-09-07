@props(['opportunity'])

<article
    class="opp-card {{ $opportunity->strength_class }} {{ $opportunity->has_required_source ? '' : 'source-missing-card' }}"
    data-id="{{ $opportunity->external_id }}"
    data-decision="{{ $opportunity->decision }}"
    wire:click="openOpportunity({{ $opportunity->id }})"
    wire:loading.class="opp-card-loading"
    wire:target="openOpportunity({{ $opportunity->id }})"
>
    <div class="card-loading-spinner" wire:loading wire:target="openOpportunity({{ $opportunity->id }})"></div>

    <div class="card-top">
        <div class="card-mission-block">
            <span class="card-mission">{{ $opportunity->origin ?: 'MISC' }}</span>
            <span class="card-phase">{{ $opportunity->phase ?: 'Phase TBD' }}</span>
        </div>
        <div class="card-top-right">
            <span class="tag {{ $opportunity->fit_class }} card-fit">{{ $opportunity->fit_label }}</span>
            <div class="score" title="Bid Strength">
                <span class="pwin-badge {{ $opportunity->strength_class }}">{{ $opportunity->go_strength }}%</span>
            </div>
        </div>
    </div>

    <div class="agency">{{ $opportunity->agency }}</div>

    <div class="card-core">
        <h3>{{ $opportunity->name }}</h3>
    </div>

    <p class="desc card-summary">{{ $opportunity->description }}</p>

    <div class="analysis-flags">
        <span class="analysis-flag risk">{{ $opportunity->has_gap ? 'Gap analyzed' : 'Gap needed' }}</span>
        <span class="analysis-flag comp">{{ $opportunity->has_competitive_analysis ? 'Competition analyzed' : 'Competition needed' }}</span>
    </div>

    <div class="stat-row">
        <span class="mini money">{{ \Illuminate\Support\Number::currency($opportunity->value, in: 'USD', precision: 0) }}</span>
        <span class="mini go">{{ $opportunity->decision }}</span>
        <span class="mini blue">{{ $opportunity->set_aside ?: 'TBD' }}</span>
    </div>

    <div class="progress"><span style="width: {{ $opportunity->go_strength }}%"></span></div>

    <div class="card-source-row">
        @if ($opportunity->source_url)
            <a class="source-link card-source" href="{{ $opportunity->source_url }}" target="_blank" rel="noopener noreferrer" x-data x-on:click.stop>
                &#8599; Open Source
            </a>
        @else
            <span class="source-link disabled card-source">&#8599; Source not recorded</span>
        @endif
    </div>

    <div class="footer-meta date-footer">
        <span>
            <small>Release Date</small>
            {{ ($opportunity->release_date ?? $opportunity->date_added)?->format('M j, Y') ?? '—' }}
        </span>
        <span class="due-date">
            <small>Due Date</small>
            {{ $opportunity->response_due?->format('M j, Y') ?? '—' }}
        </span>
    </div>
</article>
