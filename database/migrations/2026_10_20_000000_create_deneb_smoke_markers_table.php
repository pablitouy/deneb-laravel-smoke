<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deneb_smoke_markers', function (Blueprint $table) {
            $table->id();
            $table->string('note');
            $table->timestamps();
        });

        DB::table('deneb_smoke_markers')->insert([
            'note' => 'Created by the Deneb migration test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('deneb_smoke_markers');
    }
};
