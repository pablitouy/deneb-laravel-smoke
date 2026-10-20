<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

// Registered without the web middleware group so it answers even when the
// database has no tables yet (sessions would otherwise need the sessions table).

// Directories checked for persistence across deployments and rollbacks.
$markerLocations = fn (): array => [
    'storage' => storage_path('app/deneb-marker.txt'),
    'data' => base_path('data/deneb-marker.txt'),
];

$persistence = function () use ($markerLocations): array {
    $result = [];
    foreach ($markerLocations() as $name => $file) {
        // Deneb links the top-level directory (storage/ or data/) into shared state.
        $result[$name] = [
            'directory_is_link' => is_link(base_path($name)),
            'marker' => is_file($file) ? trim((string) file_get_contents($file)) : null,
        ];
    }

    return $result;
};

Route::get('/', fn () => redirect('/smoke'));

Route::get('/smoke', function () use ($persistence) {
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
        'persistence' => $persistence(),
    ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
});

// Writes a marker in each location once; later calls keep the original so a
// deployment or rollback that loses the directory is obvious.
Route::get('/smoke/mark', function () use ($markerLocations, $persistence) {
    $written = [];
    foreach ($markerLocations() as $name => $file) {
        try {
            if (is_file($file)) {
                $written[$name] = 'already present';
                continue;
            }
            if (! is_dir(dirname($file)) && ! mkdir(dirname($file), 0775, true) && ! is_dir(dirname($file))) {
                throw new RuntimeException('cannot create '.basename(dirname($file)));
            }
            $content = 'written at '.now()->toIso8601String().' by release '.basename(base_path());
            if (file_put_contents($file, $content.PHP_EOL, LOCK_EX) === false) {
                throw new RuntimeException('cannot write marker');
            }
            $written[$name] = 'written';
        } catch (Throwable $exception) {
            $written[$name] = 'error: '.Str::limit($exception->getMessage(), 200);
        }
    }

    return response()->json([
        'release' => basename(base_path()),
        'written' => $written,
        'persistence' => $persistence(),
    ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
});
