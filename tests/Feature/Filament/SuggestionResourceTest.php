<?php

use App\Filament\Resources\Suggestions\Pages\ManageSuggestions;
use App\Models\Suggestion;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

test('admin can delete a suggestion from the admin panel', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::firstOrCreate(['name' => 'Admin']));

    $author = User::factory()->create();
    $suggestion = Suggestion::query()->create([
        'user_id' => $author->id,
        'body' => 'This one needs to go.',
    ]);

    Livewire::actingAs($admin)
        ->test(ManageSuggestions::class)
        ->callTableAction('delete', $suggestion);

    expect(Suggestion::query()->find($suggestion->id))->toBeNull();
});
