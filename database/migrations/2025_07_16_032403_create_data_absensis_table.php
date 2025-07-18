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
        Schema::create('data_absensis', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->date('tanggal');
            $table->time('jam_masuk')->nullable();
            $table->time('jam_pulang')->nullable();
            $table->enum('keterangan_masuk', ['hadir', 'izin', 'sakit', 'cuti', 'tidak hadir'])->default('hadir')->nullable();
            $table->enum('keterangan_pulang', ['normal', 'lembur'])->default('normal')->nullable();
            $table->string('predikat')->nullable();
            $table->string('keterangan')->nullable();
            $table->timestamps();

            //fK
            $table->index('user_id');
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_absensis');
    }
};
