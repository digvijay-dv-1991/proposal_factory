<?php

use App\Livewire\SuggestionBoard;
use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('a logged-in user can post a suggestion', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(SuggestionBoard::class)
        ->set('title', 'Add dark mode')
        ->set('body', 'It would help late-night proposal writing sessions.')
        ->call('postSuggestion')
        ->assertHasNoErrors();

    $suggestion = Suggestion::query()->sole();

    expect($suggestion->user_id)->toBe($user->id)
        ->and($suggestion->title)->toBe('Add dark mode')
        ->and($suggestion->body)->toBe('It would help late-night proposal writing sessions.');
});

test('an empty suggestion body is rejected', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(SuggestionBoard::class)
        ->set('body', '')
        ->call('postSuggestion')
        ->assertHasErrors(['body' => 'required']);

    expect(Suggestion::query()->count())->toBe(0);
});

test('a user can upvote a suggestion and toggle their upvote back off', function () {
    $author = User::factory()->create();
    $voter = User::factory()->create();
    $suggestion = Suggestion::query()->create([
        'user_id' => $author->id,
        'body' => 'Ship it faster.',
    ]);

    $component = Livewire::actingAs($voter)->test(SuggestionBoard::class);

    $component->call('toggleUpvote', $suggestion->id);
    expect($suggestion->upvotes()->where('user_id', $voter->id)->exists())->toBeTrue();

    $component->call('toggleUpvote', $suggestion->id);
    expect($suggestion->upvotes()->where('user_id', $voter->id)->exists())->toBeFalse();
});

test('a user cannot upvote their own suggestion', function () {
    $author = User::factory()->create();
    $suggestion = Suggestion::query()->create([
        'user_id' => $author->id,
        'body' => 'My own idea.',
    ]);

    Livewire::actingAs($author)
        ->test(SuggestionBoard::class)
        ->call('toggleUpvote', $suggestion->id);

    expect($suggestion->upvotes()->where('user_id', $author->id)->exists())->toBeFalse();
});

test('posting a suggestion with an image stores it on the public disk', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $image = UploadedFile::fake()->image('screenshot.png');

    Livewire::actingAs($user)
        ->test(SuggestionBoard::class)
        ->set('body', 'Here is what I mean, see the screenshot.')
        ->set('image', $image)
        ->call('postSuggestion')
        ->assertHasNoErrors();

    $suggestion = Suggestion::query()->sole();

    expect($suggestion->image_path)->not->toBeNull();
    Storage::disk('public')->assertExists($suggestion->image_path);
});

test('deleting the author cascades and removes their suggestions', function () {
    $author = User::factory()->create();
    Suggestion::query()->create([
        'user_id' => $author->id,
        'body' => 'Feedback that should not outlive its author.',
    ]);

    $author->delete();

    expect(Suggestion::query()->count())->toBe(0);
});
