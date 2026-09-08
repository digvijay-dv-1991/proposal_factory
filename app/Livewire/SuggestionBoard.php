<?php

namespace App\Livewire;

use App\Models\Suggestion;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class SuggestionBoard extends Component
{
    use WithFileUploads, WithPagination;

    private const IMAGE_MIME_TYPES = 'png,jpg,jpeg,gif,webp';

    private const IMAGE_MAX_KB = 5120;

    public string $title = '';

    public string $body = '';

    /**
     * @var TemporaryUploadedFile|null
     */
    public $image = null;

    public string $sort = 'newest';

    public function postSuggestion(): void
    {
        $rules = [
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'mimes:'.self::IMAGE_MIME_TYPES, 'max:'.self::IMAGE_MAX_KB],
        ];

        $this->validate($rules);

        Suggestion::query()->create([
            'user_id' => auth()->id(),
            'title' => $this->title !== '' ? $this->title : null,
            'body' => $this->body,
            'image_path' => $this->image?->store('suggestion-images', 'public'),
        ]);

        $this->reset(['title', 'body', 'image']);
        $this->resetPage();
    }

    /**
     * Upvoting your own suggestion would be a hollow signal, so it's not
     * allowed — the button is also disabled for the author in the view.
     */
    public function toggleUpvote(int $suggestionId): void
    {
        $suggestion = Suggestion::query()->findOrFail($suggestionId);

        if ($suggestion->user_id === auth()->id()) {
            return;
        }

        $existing = $suggestion->upvotes()->where('user_id', auth()->id())->first();

        if ($existing !== null) {
            $existing->delete();

            return;
        }

        $suggestion->upvotes()->create(['user_id' => auth()->id()]);
    }

    public function setSort(string $sort): void
    {
        $this->sort = in_array($sort, ['newest', 'oldest', 'top'], true) ? $sort : 'newest';
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Suggestion>
     */
    #[Computed]
    public function suggestions(): LengthAwarePaginator
    {
        $query = Suggestion::query()
            ->with(['user', 'upvotes.user'])
            ->withCount('upvotes')
            ->withExists(['upvotes as upvoted_by_me' => fn (Builder $q) => $q->where('user_id', auth()->id())]);

        return (match ($this->sort) {
            'oldest' => $query->oldest(),
            'top' => $query->orderByDesc('upvotes_count')->latest(),
            default => $query->latest(),
        })->paginate(10);
    }

    public function render(): View
    {
        return view('livewire.suggestion-board');
    }
}
