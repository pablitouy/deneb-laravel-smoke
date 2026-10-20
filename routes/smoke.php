<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

// Registered without the web middleware group so it answers even when the
// database has no tables yet (sessions would otherwise need the sessions table).

Route::get('/', fn () => redirect('/smoke'));

Route::get('/smoke', function () {
    $database = ['connection' => config('database.default')];

    try {
        $connection = DB::connection();
        $connection->getPdo();
        $tables = array_column(Schema::getTables(), 'name');

        $database += [
            'connected' => true,
            'name' => $connection->getDatabaseName(),
            'tables' => count($tables),
            'migrations_table' => in_array('migrations', $tables, true),
            'ran_migrations' => in_array('migrations', $tables, true)
                ? DB::table('migrations')->orderBy('id')->pluck('migration')
                : [],
            'smoke_marker' => in_array('deneb_smoke_markers', $tables, true)
                ? DB::table('deneb_smoke_markers')->value('note')
                : null,
        ];
    } catch (Throwable $exception) {
        $database += [
            'connected' => false,
            'error' => class_basename($exception).': '.Str::limit($exception->getMessage(), 200),
        ];
    }

    return response()->json([
        'app' => 'deneb-laravel-smoke',
        'laravel' => app()->version(),
        'php' => PHP_VERSION,
        'environment' => app()->environment(),
        'release' => basename(base_path()),
        'checked_at' => now()->toIso8601String(),
        'database' => $database,
    ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
});
