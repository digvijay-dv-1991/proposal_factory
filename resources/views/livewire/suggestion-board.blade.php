<div class="suggestion-board">
    <div class="suggestion-board-head">
        <div>
            <h3>Suggestions</h3>
            <span class="suggestion-count">{{ $this->suggestions()->total() }} suggestion{{ $this->suggestions()->total() === 1 ? '' : 's' }}</span>
        </div>
        <div class="suggestion-sort">
            <button type="button" class="{{ $sort === 'newest' ? 'active' : '' }}" wire:click="setSort('newest')">Newest</button>
            <button type="button" class="{{ $sort === 'oldest' ? 'active' : '' }}" wire:click="setSort('oldest')">Oldest</button>
            <button type="button" class="{{ $sort === 'top' ? 'active' : '' }}" wire:click="setSort('top')">Most Upvoted</button>
        </div>
    </div>

    <div class="suggestion-form">
        <div class="field">
            <label>Title (optional)</label>
            <input type="text" wire:model="title" placeholder="Give your suggestion a short title...">
            @error('title') <div class="source-required-note">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label>Suggestion</label>
            <textarea wire:model="body" placeholder="What would you like to see improved or added?"></textarea>
            @error('body') <div class="source-required-note">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label>Attach an image (optional)</label>
            <input type="file" wire:model="image" accept="image/*">
            <div wire:loading wire:target="image" class="suggestion-image-uploading">Uploading&hellip;</div>
            @error('image') <div class="source-required-note">{{ $message }}</div> @enderror
            @if ($image)
                <div class="suggestion-image-preview">
                    <img src="{{ $image->temporaryUrl() }}" alt="Attachment preview">
                    <button type="button" wire:click="$set('image', null)">Remove</button>
                </div>
            @endif
        </div>
        <button type="button" class="btn primary suggestion-post" wire:click="postSuggestion" wire:loading.attr="disabled" wire:target="postSuggestion,image">
            <span wire:loading.remove wire:target="postSuggestion">Post Suggestion</span>
            <span wire:loading wire:target="postSuggestion">Posting&hellip;</span>
        </button>
    </div>

    <div class="suggestion-list">
        @forelse ($this->suggestions() as $suggestion)
            <article class="suggestion-card" wire:key="suggestion-{{ $suggestion->id }}">
                @php $isOwnSuggestion = $suggestion->user_id === auth()->id(); @endphp
                <button
                    type="button"
                    class="suggestion-upvote {{ $suggestion->upvoted_by_me ? 'active' : '' }}"
                    wire:click="toggleUpvote({{ $suggestion->id }})"
                    @disabled($isOwnSuggestion)
                    title="{{ $isOwnSuggestion ? "You can't upvote your own suggestion" : 'Upvote this suggestion' }}"
                >
                    <span class="suggestion-upvote-arrow">&#9650;</span>
                    <span class="suggestion-upvote-count">{{ $suggestion->upvotes_count }}</span>
                </button>
                <div class="suggestion-card-body">
                    <div class="suggestion-card-meta">
                        <span class="suggestion-avatar">{{ \Illuminate\Support\Str::of($suggestion->user->name)->substr(0, 1)->upper() }}</span>
                        <span class="suggestion-name">{{ $suggestion->user->name }}</span>
                        <span class="suggestion-time" title="{{ $suggestion->created_at->format('M j, Y g:i A') }}">{{ $suggestion->created_at->diffForHumans() }}</span>
                    </div>
                    @if ($suggestion->title)
                        <h4 class="suggestion-title">{{ $suggestion->title }}</h4>
                    @endif
                    <p class="suggestion-text">{{ $suggestion->body }}</p>
                    @if ($suggestion->imageUrl())
                        <img src="{{ $suggestion->imageUrl() }}" class="suggestion-image" alt="Suggestion attachment">
                    @endif
                    @if ($suggestion->upvotes->isNotEmpty())
                        <div class="suggestion-upvoters" title="Upvoted by {{ $suggestion->upvotes->pluck('user.name')->join(', ') }}">
                            @foreach ($suggestion->upvotes->take(5) as $upvote)
                                <span class="suggestion-upvoter-avatar">{{ \Illuminate\Support\Str::of($upvote->user->name)->substr(0, 1)->upper() }}</span>
                            @endforeach
                            @if ($suggestion->upvotes->count() > 5)
                                <span class="suggestion-upvoter-more">+{{ $suggestion->upvotes->count() - 5 }}</span>
                            @endif
                        </div>
                    @endif
                </div>
            </article>
        @empty
            <div class="directory-empty">No suggestions yet &mdash; be the first to share one.</div>
        @endforelse
    </div>

    @if ($this->suggestions()->hasPages())
        <div class="suggestion-pagination">
            <button type="button" wire:click="previousPage" @disabled($this->suggestions()->onFirstPage())>&larr; Previous</button>
            <span>Page {{ $this->suggestions()->currentPage() }} of {{ $this->suggestions()->lastPage() }}</span>
            <button type="button" wire:click="nextPage" @disabled(! $this->suggestions()->hasMorePages())>Next &rarr;</button>
        </div>
    @endif
</div>
