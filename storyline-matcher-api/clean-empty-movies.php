#!/usr/bin/env php
<?php

/**
 * Clean up movies without plot summaries
 *
 * Usage: php clean-empty-movies.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Movie;

echo "\n🔍 Checking for movies without plot summaries...\n\n";

// Find movies without plot summaries
$emptyMovies = Movie::whereNull('plot_summary')
    ->orWhere('plot_summary', '')
    ->get();

$count = $emptyMovies->count();

if ($count === 0) {
    echo "✅ No movies with empty plot summaries found. Database is clean!\n\n";
    exit(0);
}

echo "Found {$count} movies without plot summaries:\n";
echo str_repeat('-', 60) . "\n";

foreach ($emptyMovies as $movie) {
    echo "  - ID: {$movie->id} | Title: {$movie->title}\n";
}

echo str_repeat('-', 60) . "\n\n";

// Ask for confirmation
echo "⚠️  These movies cannot have embeddings generated (no plot text).\n";
echo "Would you like to delete them? (yes/no): ";

$handle = fopen("php://stdin", "r");
$line = fgets($handle);
$answer = trim(strtolower($line));

if ($answer !== 'yes' && $answer !== 'y') {
    echo "\n❌ Cancelled. No movies were deleted.\n\n";
    exit(0);
}

// Delete the movies
echo "\n🗑️  Deleting movies...\n";

$deleted = Movie::whereNull('plot_summary')
    ->orWhere('plot_summary', '')
    ->delete();

echo "✅ Deleted {$deleted} movies without plot summaries.\n\n";

// Show stats
$remaining = Movie::count();
$withEmbeddings = Movie::whereHas('embedding')->count();
$withoutEmbeddings = Movie::whereDoesntHave('embedding')
    ->whereNotNull('plot_summary')
    ->where('plot_summary', '!=', '')
    ->count();

echo "📊 Database Statistics:\n";
echo "  - Total movies: {$remaining}\n";
echo "  - Movies with embeddings: {$withEmbeddings}\n";
echo "  - Movies needing embeddings: {$withoutEmbeddings}\n\n";

echo "🎉 Cleanup complete!\n\n";
