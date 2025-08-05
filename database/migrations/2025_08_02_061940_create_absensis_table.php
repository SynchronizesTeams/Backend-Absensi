<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('absensis', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->string('photo_masuk')->nullable();
            $table->string('photo_keluar')->nullable();
            $table->string('photo_izin')->nullable();
            // $table->decimal('latitude', 10, 8)->nullable();
            // $table->decimal('longitude', 11, 8)->nullable();
            $table->date('tanggal');
            $table->time('jam_masuk')->nullable();
            $table->time('jam_pulang')->nullable();
            $table->enum('keterangan_masuk', ['hadir', 'izin', 'sakit', 'cuti', 'tidak hadir'])->default('hadir')->nullable();
            $table->enum('keterangan_pulang', ['normal', 'lembur'])->default('normal')->nullable();
            $table->string('keterangan')->nullable();
            $table->timestamps();

            //FK
            $table->index('user_id');
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensis');
    }
};
