<?php

namespace Database\Seeders;

use App\Models\Opportunity;
use Illuminate\Database\Seeder;

/**
 * Real client opportunities handed over via Slack/Teams threads, going
 * forward the client's opportunities live here rather than in the ported
 * reference data (OpportunitySeeder). Upserts by external_id so re-running
 * this after a later batch is added doesn't duplicate earlier rows.
 */
class ClientOpportunityImportSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/client_import_2026_09.json');
        $records = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        $created = 0;
        $updated = 0;
        $newUpdates = 0;

        foreach ($records as $record) {
            $updates = $record['updates'] ?? [];
            unset($record['updates']);

            $opportunity = Opportunity::query()->where('external_id', $record['external_id'])->first();
            $wasNew = $opportunity === null;

            $opportunity = Opportunity::query()->updateOrCreate(
                ['external_id' => $record['external_id']],
                $record,
            );

            $wasNew ? $created++ : $updated++;

            foreach ($updates as $update) {
                $exists = $opportunity->updates()
                    ->where('date', $update['date'])
                    ->where('text', $update['text'])
                    ->exists();

                if (! $exists) {
                    $opportunity->updates()->create($update);
                    $newUpdates++;
                }
            }
        }

        $this->command->info(sprintf(
            'Client import: %d opportunities created, %d updated, %d log entries added.',
            $created,
            $updated,
            $newUpdates,
        ));
    }
}
