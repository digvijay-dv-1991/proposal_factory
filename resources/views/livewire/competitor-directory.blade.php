<div class="priority-competitor-grid">
    @forelse ($this->competitors() as $competitor)
        <article class="priority-competitor-card">
            <div class="priority-competitor-title">
                <div>
                    <span class="priority-competitor-label">{{ $competitor->label }}</span>
                    <h4>{{ $competitor->name }}</h4>
                </div>
                <a href="{{ $competitor->url }}" target="_blank" rel="noopener noreferrer">Website &#8599;</a>
            </div>
            <div class="comparison-row"><span>ALQIMI Products</span><strong>{{ $competitor->alqimi_products }}</strong></div>
            <div class="comparison-row"><span>Competitor Offering</span><p>{{ $competitor->competitor_offering }}</p></div>
            <div class="comparison-row"><span>Competitive Overlap</span><p>{{ $competitor->overlap }}</p></div>
            <div class="comparison-row alqimi-edge"><span>ALQIMI Advantage</span><p>{{ $competitor->alqimi_advantage }}</p></div>
            <div class="comparison-row competitor-edge"><span>Competitor Advantage</span><p>{{ $competitor->competitor_advantage }}</p></div>
            <div class="comparison-row strategy-row"><span>Capture Strategy</span><p>{{ $competitor->strategy }}</p></div>
        </article>
    @empty
        <div class="directory-empty">No competitors recorded yet.</div>
    @endforelse
</div>
