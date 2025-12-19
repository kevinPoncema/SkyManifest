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
        Schema::table('deploys', function (Blueprint $table) {
            $table->foreignId('git_config_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('source_type', ['git', 'zip']);
            $table->string('commit_hash')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deploys', function (Blueprint $table) {
            $table->dropForeign(['git_config_id']);
            $table->dropColumn(['git_config_id', 'source_type', 'commit_hash']);
        });
    }
};
