<div>
    <header class="masthead">
        <div class="brand-row">
            <div class="brand">
                <x-brand-mark :size="30" />
                <span class="brand-tag">Capture Deck</span>
            </div>
            <div class="date">{{ now()->format('l, F j, Y') }}</div>
        </div>

        <div class="metrics-row">
            <div class="metrics">
                <div class="metric">
                    <strong>{{ $this->metrics()['active'] }}</strong>
                    <span>Active Opportunities</span>
                </div>
                <div class="metric">
                    <strong>{{ $this->metrics()['validated'] }}/{{ $this->metrics()['active'] }}</strong>
                    <span>Validated Opportunities</span>
                </div>
                <div class="metric">
                    <strong>{{ \Illuminate\Support\Number::currency($this->metrics()['pipeline_value'], in: 'USD', precision: 0) }}</strong>
                    <span>Active Pipeline Value</span>
                </div>
                <div class="metric">
                    <strong>{{ $this->metrics()['due_soon'] }}</strong>
                    <span>Due &lt;14 Days</span>
                </div>
            </div>
            @auth
                <div class="notification-bell" x-data x-on:click.outside="$wire.showNotifications && $wire.toggleNotifications()">
                    <button type="button" class="bell-btn" wire:click="toggleNotifications">
                        &#128276;
                        @if ($this->unreadNotificationCount() > 0)
                            <span class="bell-count">{{ $this->unreadNotificationCount() }}</span>
                        @endif
                    </button>
                    @if ($showNotifications)
                        <div class="notification-dropdown">
                            <div class="notification-dropdown-head">
                                <strong>Notifications</strong>
                                <button type="button" class="btn" wire:click="markAllNotificationsRead">Mark all read</button>
                            </div>
                            @forelse ($this->notifications() as $notification)
                                <button type="button" class="notification-item {{ $notification->read_at ? '' : 'unread' }}" wire:click="openNotification('{{ $notification->id }}')">
                                    <div><strong>{{ $notification->data['title'] }}</strong></div>
                                    <div>{{ $notification->data['body'] }}</div>
                                    <div class="notification-time">{{ $notification->created_at->diffForHumans() }}</div>
                                </button>
                            @empty
                                <div class="notice">No notifications yet.</div>
                            @endforelse
                        </div>
                    @endif
                </div>
            @endauth
            <button type="button" class="new-opportunity-top" wire:click="newOpportunity" wire:loading.attr="disabled" wire:target="newOpportunity">
                <span wire:loading.remove wire:target="newOpportunity">+ New Opportunity</span>
                <span wire:loading wire:target="newOpportunity">Opening&hellip;</span>
            </button>
        </div>
    </header>

    <div class="controls">
        <div class="search-filter-row">
            <div class="search">
                <input type="search" wire:model.live.debounce.400ms="search" placeholder="Search Opportunity, Solicitation #, Agency...">
            </div>
            <select wire:model.live="agencyFilter">
                <option value="">All Agencies</option>
                @foreach ($this->agencyOptions() as $agency)
                    <option value="{{ $agency }}">{{ $agency }}</option>
                @endforeach
            </select>
            <select wire:model.live="phaseFilter">
                <option value="">All Statuses</option>
                @foreach (\App\Livewire\OpportunityBoard::PHASES as $phase)
                    <option value="{{ $phase }}">{{ $phase }}</option>
                @endforeach
            </select>
            <select wire:model.live="decisionFilter">
                <option value="">All Decisions</option>
                @foreach (\App\Livewire\OpportunityModal::DECISIONS as $decision)
                    <option value="{{ $decision }}">{{ $decision }}</option>
                @endforeach
            </select>
            <select wire:model.live="focusFilter">
                <option value="">All Focus Areas</option>
                @foreach ($this->focusOptions() as $focus)
                    <option value="{{ $focus }}">{{ $focus }}</option>
                @endforeach
            </select>
            <select wire:model.live="fitFilter">
                <option value="">Any Fit</option>
                @foreach (\App\Livewire\OpportunityBoard::FIT_LEVELS as $fitLevel)
                    <option value="{{ $fitLevel }}">{{ $fitLevel }}</option>
                @endforeach
            </select>
            <select wire:model.live="bidTypeFilter">
                <option value="">All Bid Types</option>
                @foreach ($this->bidTypeOptions() as $bidType)
                    <option value="{{ $bidType }}">{{ $bidType }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <nav class="view-tabs">
        @foreach (\App\Livewire\OpportunityBoard::TABS as $key => [$kicker, $label])
            <button type="button" class="{{ $this->activeTab() === $key ? 'active' : '' }}" data-view="{{ $key }}" wire:click="selectTab('{{ $key }}')">
                <span class="view-kicker">{{ $kicker }}</span>
                <span class="view-label">{{ $label }} <span class="bubble">{{ $this->tabCounts()[$key] ?? 0 }}</span></span>
            </button>
        @endforeach
    </nav>

    <div class="opportunity-subnav">
        <div class="subview-tabs">
            <button type="button" class="{{ $activeView === 'daily_brief' ? 'active' : '' }}" wire:click="showDailyBrief">
                <span class="subview-kicker">Briefing</span>
                <span class="subview-label">Daily Brief</span>
            </button>
            <button type="button" wire:click="openOverlay('contract_vehicles')">
                <span class="subview-kicker">Market Access</span>
                <span class="subview-label">Contract Vehicles</span>
            </button>
            <button type="button" wire:click="openOverlay('competitors')">
                <span class="subview-kicker">Market Intelligence</span>
                <span class="subview-label">Competitors</span>
            </button>
            <button type="button" wire:click="openOverlay('partners')">
                <span class="subview-kicker">Teaming Network</span>
                <span class="subview-label">Partners</span>
            </button>
            <button type="button" class="{{ $activeView === 'solicitation' ? 'active' : '' }}" data-calendar="true" wire:click="showCalendar('solicitation')">
                <span class="subview-kicker">Calendar 1</span>
                <span class="subview-label">Solicitation Calendar</span>
            </button>
            <button type="button" class="{{ $activeView === 'events' ? 'active' : '' }}" data-calendar="true" wire:click="showCalendar('events')">
                <span class="subview-kicker">Calendar 2</span>
                <span class="subview-label">Events Calendar</span>
            </button>
        </div>
    </div>

    @unless ($activeView === 'daily_brief' || $this->isRowListTab())
        <div class="section-tabs-wrap">
            <div class="section-tabs">
                @foreach (\App\Livewire\OpportunityBoard::SECTIONS as $section)
                    <button type="button" class="{{ $sectionFilter === $section ? 'active' : '' }}" wire:click="filterBySection('{{ $section }}')">
                        {{ $section }} <span class="count">{{ $this->sectionCounts()[$section] ?? 0 }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    @endunless

    @if ($activeView === 'board' && $this->isRowListTab())
        <div class="board-shell">
            <div class="submitted-list {{ $this->isNoBidTab() ? 'nobid-list' : '' }}">
                <div class="submitted-list-head">
                    <span>Opportunity</span>
                    <span>Agency</span>
                    <span>Solicitation</span>
                    <span>{{ $this->isNoBidTab() ? 'No Bid' : 'Submitted' }}</span>
                    @if ($this->isNoBidTab())
                        <span>Authorized By</span>
                        <span>Reason</span>
                    @endif
                    <span>Source</span>
                </div>

                @forelse ($this->opportunities() as $opportunity)
                    <article class="submitted-row {{ $this->isNoBidTab() ? 'nobid-row' : '' }}" wire:click="openOpportunity({{ $opportunity->id }})" wire:key="row-{{ $opportunity->id }}">
                        <div>
                            <div class="row-name">{{ $opportunity->name }}</div>
                            <div class="row-sub">{{ $opportunity->external_id }}</div>
                        </div>
                        <div>{{ $opportunity->agency }}</div>
                        <div>{{ $opportunity->solicitation ?: '—' }}</div>
                        @if ($this->isNoBidTab())
                            <div>{{ $opportunity->decision_date?->format('M j, Y') ?? $opportunity->date_added?->format('M j, Y') ?? '—' }}</div>
                            <div class="nobid-authorized">{{ $opportunity->decision_by ?: 'Not recorded' }}</div>
                            <div class="nobid-reason">{{ $opportunity->decision_comment ?: 'Not recorded' }}</div>
                        @else
                            <div>{{ $opportunity->response_due?->format('M j, Y') ?? $opportunity->date_added?->format('M j, Y') ?? '—' }}</div>
                        @endif
                        <div>
                            @if ($opportunity->source_url)
                                <a class="source-link" href="{{ $opportunity->source_url }}" target="_blank" rel="noopener noreferrer" x-data x-on:click.stop>&#8599; Source</a>
                            @else
                                <span class="source-link disabled">&#8599; Source</span>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="submitted-empty">{{ $this->isNoBidTab() ? 'No No Bid opportunities.' : 'No submitted opportunities.' }}</div>
                @endforelse
            </div>
        </div>
    @elseif ($activeView === 'board')
        <div class="board-shell">
            <div
                class="board {{ $phaseFilter !== '' ? 'phase-filtered' : '' }}"
                @if ($phaseFilter === '')
                    style="{{ $this->boardGridStyle() }}"
                @endif
            >
                @foreach ($this->visiblePhases() as $phase)
                    <div class="column">
                        <button type="button" class="phase-filter-btn {{ $phaseFilter === $phase ? 'active' : '' }}" wire:click="filterByPhase('{{ $phase }}')">
                            <span class="column-title"><span class="dot"></span>{{ $phase }}</span>
                            <span class="column-meta">
                                <strong>{{ $this->boardColumn($phase)['items']->count() }}</strong>
                                {{ \Illuminate\Support\Number::currency($this->boardColumn($phase)['total'], in: 'USD', precision: 0) }}
                            </span>
                        </button>

                        <div class="stack">
                            @forelse ($this->boardColumn($phase)['items'] as $opportunity)
                                <x-opportunity-card :opportunity="$opportunity" wire:key="opportunity-{{ $opportunity->id }}" />
                            @empty
                                <div class="empty">No opportunities</div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div style="padding: 14px 28px 0">
            <button type="button" class="phase-filter-btn" style="border: 1px solid #d4dce1; border-radius: 8px; padding: 8px 14px; display: inline-flex; width: auto" wire:click="showBoard">
                &larr; Back to board
            </button>
        </div>

        @if ($activeView === 'daily_brief')
            <livewire:daily-brief :key="'view-daily-brief'" />
        @else
            <livewire:opportunity-calendar :mode="$activeView" :key="'calendar-'.$activeView" />
        @endif
    @endif

    @if ($activeOverlay !== '')
        <div class="app-overlay" wire:click.self="closeOverlay">
            <div class="app-dialog wide">
                <div class="app-dialog-head">
                    <h2>{{ $this->overlayTitle() }}</h2>
                    <button type="button" class="app-dialog-close" wire:click="closeOverlay">&times;</button>
                </div>
                <div class="app-dialog-body">
                    @if ($activeOverlay === 'contract_vehicles')
                        <livewire:contract-vehicle-directory :key="'overlay-contract-vehicles'" />
                    @elseif ($activeOverlay === 'competitors')
                        <livewire:competitor-directory :key="'overlay-competitors'" />
                    @elseif ($activeOverlay === 'partners')
                        <livewire:partner-directory :key="'overlay-partners'" />
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if ($activeOpportunityId !== null || $creatingOpportunity)
        <livewire:opportunity-modal :opportunity-id="$activeOpportunityId" :key="'opportunity-modal-'.($activeOpportunityId ?? 'new')" />
    @endif
</div>
