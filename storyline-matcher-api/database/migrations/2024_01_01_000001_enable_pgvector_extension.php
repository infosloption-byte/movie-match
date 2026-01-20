<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration enables the pgvector extension in PostgreSQL.
     * For MySQL, this migration does nothing as MySQL doesn't support vector extensions.
     * MySQL will store embeddings as JSON and calculate similarity in PHP.
     */
    public function up(): void
    {
        // Only enable pgvector for PostgreSQL
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
        }
        // MySQL doesn't require any setup - embeddings will be stored as JSON
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP EXTENSION IF EXISTS vector');
        }
    }
};
