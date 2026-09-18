<div class="calendar-shell">
    <div class="calendar-toolbar">
        <div class="calendar-title">{{ $mode === 'solicitation' ? 'Solicitation Calendar' : 'Events Calendar' }} — {{ $this->title() }}</div>
        <div class="calendar-nav">
            <button type="button" wire:click="previousMonth">&larr; Prev</button>
            <button type="button" wire:click="nextMonth">Next &rarr;</button>
        </div>
    </div>

    <div class="calendar-grid">
        @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $weekday)
            <div class="calendar-weekday">{{ $weekday }}</div>
        @endforeach

        @foreach ($this->days() as $day)
            <div class="calendar-day {{ $day['inMonth'] ? '' : 'outside' }} {{ $day['isToday'] ? 'today' : '' }}">
                <div class="calendar-date">{{ $day['date']->day }}</div>
                @if ($day['inMonth'])
                    @foreach ($this->eventsByDay()->get($day['date']->day, collect()) as $event)
                        <div class="calendar-event {{ $event['className'] ?? '' }}">
                            <strong>{{ $event['title'] }}</strong>
                            <span>{{ $event['subtitle'] }}</span>
                        </div>
                    @endforeach
                @endif
            </div>
        @endforeach
    </div>
</div>
