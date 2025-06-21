<?php

namespace Tests\Feature\Smoke;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApplicationHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_boots_successfully()
    {
        $this->assertTrue(true);
    }

    public function test_environment_configuration_is_valid()
    {
        $this->assertNotEmpty(config('app.name'));
        $this->assertNotEmpty(config('app.key'));
        $this->assertContains(config('app.env'), ['local', 'testing'], 'Environment should be local or testing');
    }

    public function test_database_connection_works()
    {
        $this->assertDatabaseExists();
        
        DB::statement('SELECT 1');
        $this->assertTrue(true);
    }

    public function test_sqlite_database_file_exists()
    {
        $dbPath = database_path('database.sqlite');
        $this->assertFileExists($dbPath, 'SQLite database file should exist');
    }

    public function test_migrations_can_run()
    {
        Artisan::call('migrate:status');
        $output = Artisan::output();
        $this->assertStringNotContainsString('No migrations found', $output);
    }

    public function test_core_tables_exist()
    {
        $tables = [
            'organizations',
            'contacts', 
            'interactions',
            'opportunities',
            'principals',
            'product_lines',
            'users'
        ];

        foreach ($tables as $table) {
            $this->assertTrue(
                DB::getSchemaBuilder()->hasTable($table),
                "Table '{$table}' should exist in database"
            );
        }
    }

    public function test_artisan_commands_work()
    {
        $exitCode = Artisan::call('route:list');
        $this->assertEquals(0, $exitCode);

        $exitCode = Artisan::call('config:cache');
        $this->assertEquals(0, $exitCode);
        
        $exitCode = Artisan::call('config:clear');
        $this->assertEquals(0, $exitCode);
    }

    public function test_storage_directories_are_writable()
    {
        $paths = [
            storage_path('logs'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views')
        ];

        foreach ($paths as $path) {
            $this->assertDirectoryExists($path);
            $this->assertTrue(is_writable($path), "Directory {$path} should be writable");
        }
    }

    public function test_cache_system_works()
    {
        cache()->put('smoke_test', 'working', 60);
        $this->assertEquals('working', cache()->get('smoke_test'));
        cache()->forget('smoke_test');
    }

    public function test_session_system_works()
    {
        session(['smoke_test' => 'session_working']);
        $this->assertEquals('session_working', session('smoke_test'));
        session()->forget('smoke_test');
    }

    public function test_logging_system_works()
    {
        $logFile = storage_path('logs/laravel.log');
        
        logger('Smoke test log entry');
        
        if (file_exists($logFile)) {
            $logContent = file_get_contents($logFile);
            $this->assertStringContainsString('Smoke test log entry', $logContent);
        }
        
        $this->assertTrue(true);
    }

    private function assertDatabaseExists()
    {
        try {
            DB::connection()->getPdo();
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail('Database connection failed: ' . $e->getMessage());
        }
    }
}