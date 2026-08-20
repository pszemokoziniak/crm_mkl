<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Pobyty wskazujące na pracowników usuniętych (soft-delete) lub w ogóle
        // nieistniejących — martwe dane, które blokowały archiwizację budów i
        // zawyżały obsadę w prognozie.
        DB::table('contact_work_dates')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('contacts')
                    ->whereColumn('contacts.id', 'contact_work_dates.contact_id')
                    ->whereNull('contacts.deleted_at');
            })
            ->delete();
    }

    public function down(): void
    {
        // Nieodwracalne — usunięte osierocone pobyty (dane martwe).
    }
};
