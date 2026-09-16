<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Client review: the Competitive Analysis tab had three separate
 * "Competitive ..." free-text fields (Analysis, Discriminators, Next
 * Action) that are being merged into one field (competitive_analysis
 * survives; the other two are dropped). Any existing discriminators/next
 * action content is folded into competitive_analysis as extra bullets
 * first, so no previously-recorded client data is silently lost.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('opportunities')
            ->where(function ($query) {
                $query->whereNotNull('competitive_discriminators')
                    ->orWhereNotNull('competitive_next_action');
            })
            ->select(['id', 'competitive_analysis', 'competitive_discriminators', 'competitive_next_action'])
            ->orderBy('id')
            ->chunkById(200, function (Collection $rows): void {
                foreach ($rows as $row) {
                    $bullets = [];

                    if (trim((string) $row->competitive_analysis) !== '') {
                        $bullets[] = trim((string) $row->competitive_analysis);
                    }

                    if (trim((string) $row->competitive_discriminators) !== '') {
                        $bullets[] = '<ul><li>'.e(trim((string) $row->competitive_discriminators)).'</li></ul>';
                    }

                    if (trim((string) $row->competitive_next_action) !== '') {
                        $bullets[] = '<ul><li>'.e(trim((string) $row->competitive_next_action)).'</li></ul>';
                    }

                    DB::table('opportunities')
                        ->where('id', $row->id)
                        ->update(['competitive_analysis' => implode("\n", $bullets)]);
                }
            });

        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropColumn(['competitive_discriminators', 'competitive_next_action']);
        });
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->text('competitive_discriminators')->nullable()->after('competitive_analysis');
            $table->text('competitive_next_action')->nullable()->after('competitive_discriminators');
        });
    }
};
