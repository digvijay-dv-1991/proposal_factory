<div class="vehicle-detail-grid">
    @forelse ($this->vehicles() as $vehicle)
        <article class="vehicle-detail-card">
            <div class="vehicle-detail-title">
                <div>
                    <span class="vehicle-detail-label">{{ $vehicle->type }}</span>
                    <h3>{{ $vehicle->name }}</h3>
                    <p>{{ $vehicle->full_name }}</p>
                </div>
                <a href="{{ $vehicle->url }}" target="_blank" rel="noopener noreferrer">Vehicle Website &#8599;</a>
            </div>
            <div class="vehicle-detail-row"><span>Managing Agency</span><strong>{{ $vehicle->agency }}</strong></div>
            <div class="vehicle-detail-row"><span>Vehicle Description</span><p>{{ $vehicle->description }}</p></div>
            <div class="vehicle-detail-row vehicle-alqimi-use"><span>ALQIMI Use / Alignment</span><p>{{ $vehicle->alqimi_use }}</p></div>
            <div class="vehicle-detail-row vehicle-status"><span>Status</span><strong>{{ $vehicle->status }}</strong></div>
        </article>
    @empty
        <div class="directory-empty">No contract vehicles recorded yet.</div>
    @endforelse
</div>
