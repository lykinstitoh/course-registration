<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            $table->dropForeign(['programme_id']);
            $table->foreignId('programme_id')->nullable()->change();
            $table->foreign('programme_id')->references('id')->on('programmes')->nullOnDelete();
            
            $table->string('award_level')->nullable()->after('programme_id');
        });
    }

    public function down(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            $table->dropColumn('award_level');
            
            $table->dropForeign(['programme_id']);
            $table->foreignId('programme_id')->nullable(false)->change();
            $table->foreign('programme_id')->references('id')->on('programmes')->cascadeOnDelete();
        });
    }
};
