<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            // Rename tanggal to tgl_masuk if it exists
            if (Schema::hasColumn('absensis', 'tanggal') && !Schema::hasColumn('absensis', 'tgl_masuk')) {
                $table->renameColumn('tanggal', 'tgl_masuk');
            }

            // Add missing columns if they don't exist
            if (!Schema::hasColumn('absensis', 'tgl_pulang')) {
                $table->date('tgl_pulang')->nullable()->after('tgl_masuk');
            }
            if (!Schema::hasColumn('absensis', 'predikat')) {
                $table->string('predikat')->nullable();
            }
            if (!Schema::hasColumn('absensis', 'ttd')) {
                $table->string('ttd')->nullable();
            }
            if (!Schema::hasColumn('absensis', 'shift')) {
                $table->string('shift')->nullable();
            }

            // Drop coordinate columns if they exist (per user request: coordinates not saved in DB)
            $table->dropColumn(array_filter([
                Schema::hasColumn('absensis', 'latitude_masuk') ? 'latitude_masuk' : null,
                Schema::hasColumn('absensis', 'longitude_masuk') ? 'longitude_masuk' : null,
                Schema::hasColumn('absensis', 'latitude_pulang') ? 'latitude_pulang' : null,
                Schema::hasColumn('absensis', 'longitude_pulang') ? 'longitude_pulang' : null,
                Schema::hasColumn('absensis', 'latitude') ? 'latitude' : null,
                Schema::hasColumn('absensis', 'longitude') ? 'longitude' : null,
            ]));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            // Revert changes if necessary
            if (Schema::hasColumn('absensis', 'tgl_masuk') && !Schema::hasColumn('absensis', 'tanggal')) {
                $table->renameColumn('tgl_masuk', 'tanggal');
            }
            $table->dropColumn(['tgl_pulang', 'predikat', 'ttd', 'shift']);
        });
    }
};
