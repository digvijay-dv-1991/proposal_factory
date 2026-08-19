<?php

namespace Database\Seeders;

use App\Models\Opportunity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OpportunitySeeder extends Seeder
{
    /**
     * Child relations to hydrate for each opportunity, keyed by the JSON
     * property holding the child rows.
     *
     * @var array<string, string>
     */
    private const CHILD_RELATIONS = [
        'contacts' => 'contacts',
        'partners' => 'partners',
        'updates' => 'updates',
        'milestones' => 'milestones',
        'evidence' => 'evidence',
        'decision_history' => 'decisionHistory',
        'relationships' => 'relationships',
    ];

    /**
     * Mime type -> file extension, for the base64 attachments embedded in
     * the reference seed data.
     *
     * @var array<string, string>
     */
    private const MIME_EXTENSIONS = [
        'application/pdf' => 'pdf',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'application/msword' => 'doc',
        'application/vnd.ms-excel' => 'xls',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = database_path('seeders/data/opportunities.json');
        $records = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        foreach ($records as $record) {
            $childData = [];
            foreach (array_keys(self::CHILD_RELATIONS) as $key) {
                $childData[$key] = $record[$key] ?? [];
                unset($record[$key]);
            }
            $attachments = $record['attachments'] ?? [];
            unset($record['attachments']);

            $opportunity = Opportunity::create($record);

            foreach (self::CHILD_RELATIONS as $jsonKey => $relation) {
                if ($childData[$jsonKey] !== []) {
                    $opportunity->{$relation}()->createMany($childData[$jsonKey]);
                }
            }

            foreach ($attachments as $attachment) {
                $this->createAttachment($opportunity, $attachment);
            }
        }

        $this->command->info(sprintf('Seeded %d opportunities.', count($records)));
    }

    /**
     * @param  array{original_name: string, type: ?string, url: ?string, data_uri: ?string}  $attachment
     */
    private function createAttachment(Opportunity $opportunity, array $attachment): void
    {
        $dataUri = $attachment['data_uri'] ?? null;

        if ($dataUri === null) {
            $opportunity->attachments()->create([
                'original_name' => $attachment['original_name'],
                'type' => $attachment['type'],
                'url' => $attachment['url'],
            ]);

            return;
        }

        if (! preg_match('/^data:([^;]+);base64,(.+)$/s', $dataUri, $matches)) {
            return;
        }

        [, $mimeType, $base64] = $matches;
        $binary = base64_decode($base64, true);

        if ($binary === false) {
            return;
        }

        $extension = self::MIME_EXTENSIONS[$mimeType] ?? 'bin';
        $storagePath = sprintf(
            'opportunity-attachments/%s/%s.%s',
            $opportunity->external_id,
            Str::slug(pathinfo($attachment['original_name'], PATHINFO_FILENAME)),
            $extension,
        );

        Storage::disk('public')->put($storagePath, $binary);

        $opportunity->attachments()->create([
            'original_name' => $attachment['original_name'],
            'type' => $attachment['type'],
            'path' => $storagePath,
            'mime_type' => $mimeType,
            'size' => strlen($binary),
        ]);
    }
}
