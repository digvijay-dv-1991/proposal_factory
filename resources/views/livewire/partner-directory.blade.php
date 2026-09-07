<div class="partner-detail-grid">
    @forelse ($this->partners() as $partner)
        <article class="partner-detail-card">
            <div class="partner-detail-title">
                <div>
                    <span class="partner-detail-label">{{ $partner->label }}</span>
                    <h3>{{ $partner->name }}</h3>
                </div>
                <a href="{{ $partner->url }}" target="_blank" rel="noopener noreferrer">Website &#8599;</a>
            </div>
            <div class="partner-detail-row"><span>What They Do</span><p>{{ $partner->what_they_do }}</p></div>
            <div class="partner-detail-row client-align"><span>ALQIMI Client Alignment</span><p>{{ $partner->client_alignment }}</p></div>
            <div class="partner-detail-row product-align"><span>ALQIMI Product Areas</span><strong>{{ $partner->product_areas }}</strong></div>
            <div class="partner-detail-row alqimi-value"><span>Partnership Value</span><p>{{ $partner->partnership_value }}</p></div>
            <div class="partner-detail-row use-together"><span>Best Use Together</span><p>{{ $partner->use_together }}</p></div>
        </article>
    @empty
        <div class="directory-empty">No partners recorded yet.</div>
    @endforelse
</div>
