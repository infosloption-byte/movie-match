<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the movies table to store core movie information from TMDB.
     */
    public function up(): void
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->integer('tmdb_id')->unique()->comment('TMDB movie ID');
            $table->string('title');
            $table->text('plot_summary')->nullable()->comment('Overview/plot from TMDB');
            $table->integer('release_year')->nullable();
            $table->string('poster_url')->nullable()->comment('Full URL to movie poster');
            $table->decimal('popularity', 10, 2)->default(0)->comment('TMDB popularity score');
            $table->timestamps();

            // Indexes for common queries
            $table->index('tmdb_id');
            $table->index('popularity');
        });

        // Create title index with prefix for MySQL compatibility
        // MySQL with utf8mb4 has a 1000 byte limit on index keys
        // Using 191 characters (191 * 4 bytes = 764 bytes) to stay within limit
        if (DB::getDriverName() === 'mysql') {
            DB::statement('CREATE INDEX movies_title_index ON movies (title(191))');
        } else {
            Schema::table('movies', function (Blueprint $table) {
                $table->index('title');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
