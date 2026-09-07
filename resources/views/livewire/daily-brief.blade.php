<div class="daily-brief">
    <div class="brief-masthead">
        <div>
            <div class="brief-kicker">ALQIMI Market &amp; Capture Intelligence</div>
            <h1 class="brief-title">Daily Brief</h1>
            <div class="brief-date">{{ now()->format('l, F j, Y') }} &middot; Updates across priority mission areas</div>
        </div>
        <div class="brief-actions">
            <button type="button" x-on:click="window.print()">Print / PDF</button>
            <button type="button" wire:click="$refresh">Refresh brief</button>
        </div>
    </div>

    <div class="brief-grid">
        <article class="brief-lead">
            <span class="brief-label">Company &amp; Market Update</span>
            <h2>DarkALQIMI awarded a position on NASA SEWP VI</h2>
            <p>DarkALQIMI, the joint venture between DarkStar Intelligence and ALQIMI, has announced an award position on the NASA SEWP VI Government-Wide Acquisition Contract. The vehicle expands access to federal technology customers and strengthens the path for ALQIMI's AI, data engineering, software-platform, and mission-focused technology capabilities.</p>
            <div class="brief-meta">
                <span>Strategic vehicle update</span>
                <span>Company announcement</span>
                <span>Cross-cutting relevance: DFaaS, Intelligence, Health, CBRN</span>
            </div>
        </article>

        <aside class="brief-side">
            <div class="brief-card">
                <h3>Today at a Glance</h3>
                <div class="brief-stat"><span>New in last 24 hours</span><strong>{{ $this->stats()['recent'] }}</strong></div>
                <div class="brief-stat"><span>Monitoring</span><strong>{{ $this->stats()['monitoring'] }}</strong></div>
                <div class="brief-stat"><span>14&ndash;31 day capture window</span><strong>{{ $this->stats()['capture_window'] }}</strong></div>
                <div class="brief-stat"><span>Bid items needing action</span><strong>{{ $this->stats()['needs_action'] }}</strong></div>
                <div class="brief-stat"><span>Pending decisions</span><strong>{{ $this->stats()['pending'] }}</strong></div>
            </div>
            <div class="brief-note"><strong>Monitoring rule:</strong> Watch-list opportunities are reviewed daily. Material changes are added to the opportunity record and surfaced here.</div>
        </aside>
    </div>

    <div class="brief-body">
        <section class="brief-section">
            <div class="brief-section-head"><h3>New and Materially Changed</h3></div>
            <div class="brief-list">
                @forelse ($this->recentlyAdded() as $opportunity)
                    <div class="brief-item">
                        <h4>{{ $opportunity->name }}</h4>
                        <p>{{ $opportunity->agency }}</p>
                        <div class="brief-item-meta"><span>{{ $opportunity->origin }}</span><span>{{ $opportunity->decision }}</span></div>
                    </div>
                @empty
                    <p class="brief-empty">Nothing new in the last 24 hours.</p>
                @endforelse
            </div>
        </section>

        <section class="brief-section">
            <div class="brief-section-head"><h3>Capture Window</h3><span class="tag blue">Due within 31 days</span></div>
            <div class="brief-list">
                @forelse ($this->captureWindow() as $opportunity)
                    <div class="brief-item">
                        <h4>{{ $opportunity->name }}</h4>
                        <p>{{ $opportunity->agency }}</p>
                        <div class="brief-item-meta"><span>Due {{ $opportunity->response_due?->format('M j, Y') }}</span><span>{{ $opportunity->decision }}</span></div>
                    </div>
                @empty
                    <p class="brief-empty">Nothing due in the next 31 days.</p>
                @endforelse
            </div>
        </section>

        @foreach ($this->sectionUpdates() as $section => $opportunities)
            @if ($opportunities->isNotEmpty())
                <section class="brief-section">
                    <div class="brief-section-head"><h3>{{ $section }}</h3><span class="tag">Section update</span></div>
                    <div class="brief-list">
                        @foreach ($opportunities as $opportunity)
                            <div class="brief-item">
                                <h4>{{ $opportunity->name }}</h4>
                                <p>{{ $opportunity->agency }}</p>
                                <div class="brief-item-meta"><span>{{ $opportunity->phase }}</span><span>Due {{ $opportunity->response_due?->format('M j, Y') ?? '—' }}</span></div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach

        <section class="brief-section">
            <div class="brief-section-head"><h3>Management Attention</h3><span class="tag red">Action required</span></div>
            <div class="brief-list">
                @forelse ($this->needsManagementAttention() as $opportunity)
                    <div class="brief-item">
                        <h4>{{ $opportunity->name }}</h4>
                        <p>{{ $opportunity->agency }}</p>
                        <div class="brief-item-meta"><span>Bid &mdash; missing next action or owner</span></div>
                    </div>
                @empty
                    <p class="brief-empty">No Bid items are missing a next action or capture owner.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
