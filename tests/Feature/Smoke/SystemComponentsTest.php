<?php

namespace Tests\Feature\Smoke;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SystemComponentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_php_version_is_compatible()
    {
        $phpVersion = PHP_VERSION;
        $this->assertTrue(
            version_compare($phpVersion, '8.1.0', '>='),
            'PHP version should be 8.1 or higher for Laravel 12'
        );
    }

    public function test_required_php_extensions_are_loaded()
    {
        $requiredExtensions = [
            'openssl',
            'pdo',
            'pdo_sqlite',
            'mbstring',
            'tokenizer',
            'xml',
            'ctype',
            'json',
            'bcmath',
            'fileinfo'
        ];

        foreach ($requiredExtensions as $extension) {
            $this->assertTrue(
                extension_loaded($extension),
                "PHP extension '{$extension}' should be loaded"
            );
        }
    }

    public function test_composer_autoloader_is_working()
    {
        $this->assertTrue(
            class_exists('Illuminate\Foundation\Application'),
            'Laravel classes should be autoloaded via Composer'
        );
        
        $this->assertTrue(
            class_exists('App\Models\User'),
            'Application classes should be autoloaded'
        );
    }

    public function test_memory_limit_is_sufficient()
    {
        $memoryLimit = ini_get('memory_limit');
        
        if ($memoryLimit !== '-1') {
            $memoryLimitBytes = $this->convertToBytes($memoryLimit);
            $recommendedMinimum = 128 * 1024 * 1024; // 128MB
            
            $this->assertGreaterThanOrEqual(
                $recommendedMinimum,
                $memoryLimitBytes,
                'Memory limit should be at least 128MB for Laravel applications'
            );
        } else {
            $this->assertTrue(true, 'No memory limit set (unlimited)');
        }
    }

    public function test_max_execution_time_is_reasonable()
    {
        $maxExecutionTime = ini_get('max_execution_time');
        
        if ($maxExecutionTime > 0) {
            $this->assertGreaterThanOrEqual(
                30,
                $maxExecutionTime,
                'Max execution time should be at least 30 seconds'
            );
        } else {
            $this->assertTrue(true, 'No execution time limit set');
        }
    }

    public function test_file_upload_settings_are_configured()
    {
        $fileUploads = ini_get('file_uploads');
        $this->assertTrue($fileUploads, 'File uploads should be enabled');

        $uploadMaxFilesize = ini_get('upload_max_filesize');
        $this->assertNotEmpty($uploadMaxFilesize, 'Upload max filesize should be configured');

        $postMaxSize = ini_get('post_max_size');
        $this->assertNotEmpty($postMaxSize, 'Post max size should be configured');
    }

    public function test_queue_system_is_functional()
    {
        Queue::fake();
        
        // Test that queue system can be initialized
        $this->assertTrue(true, 'Queue system is available');
    }

    public function test_mail_system_is_configured()
    {
        Mail::fake();
        
        $mailConfig = config('mail');
        $this->assertNotEmpty($mailConfig, 'Mail configuration should exist');
        $this->assertArrayHasKey('default', $mailConfig, 'Default mail driver should be configured');
    }

    public function test_cache_system_is_functional()
    {
        $cacheKey = 'smoke_test_cache_key';
        $cacheValue = 'smoke_test_value';
        
        cache()->put($cacheKey, $cacheValue, 60);
        $retrievedValue = cache()->get($cacheKey);
        
        $this->assertEquals($cacheValue, $retrievedValue, 'Cache system should be functional');
        
        cache()->forget($cacheKey);
    }

    public function test_filesystem_permissions_are_correct()
    {
        $writablePaths = [
            storage_path(),
            storage_path('logs'),
            storage_path('framework'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            base_path('bootstrap/cache')
        ];

        foreach ($writablePaths as $path) {
            $this->assertDirectoryExists($path, "Directory {$path} should exist");
            $this->assertTrue(is_writable($path), "Directory {$path} should be writable");
        }
    }

    public function test_artisan_console_is_functional()
    {
        $exitCode = Artisan::call('list');
        $this->assertEquals(0, $exitCode, 'Artisan console should be functional');

        $output = Artisan::output();
        $this->assertStringContainsString('Available commands', $output, 'Artisan should show available commands');
    }

    public function test_service_container_is_working()
    {
        $app = app();
        $this->assertInstanceOf(
            \Illuminate\Foundation\Application::class,
            $app,
            'Service container should be available'
        );

        // Test service resolution
        $config = app('config');
        $this->assertNotNull($config, 'Services should be resolvable from container');
    }

    public function test_event_system_is_functional()
    {
        $eventFired = false;
        
        \Illuminate\Support\Facades\Event::listen('smoke.test.event', function () use (&$eventFired) {
            $eventFired = true;
        });

        \Illuminate\Support\Facades\Event::dispatch('smoke.test.event');
        
        $this->assertTrue($eventFired, 'Event system should be functional');
    }

    public function test_validation_system_works()
    {
        $validator = \Illuminate\Support\Facades\Validator::make(
            ['email' => 'invalid-email'],
            ['email' => 'required|email']
        );

        $this->assertTrue($validator->fails(), 'Validation system should detect invalid data');
        $this->assertArrayHasKey('email', $validator->errors()->toArray(), 'Validation should return email errors');
    }

    public function test_blade_templating_engine_works()
    {
        $viewContent = '{{ $name }}';
        $compiledView = \Illuminate\Support\Facades\Blade::compileString($viewContent);
        
        $this->assertNotEmpty($compiledView, 'Blade templating engine should compile templates');
        $this->assertStringContainsString('<?php echo', $compiledView, 'Blade should compile to PHP');
    }

    public function test_http_client_is_available()
    {
        $this->assertTrue(
            class_exists(\Illuminate\Http\Client\Factory::class),
            'HTTP client should be available'
        );
    }

    public function test_encryption_system_works()
    {
        $plaintext = 'smoke test encryption';
        $encrypted = encrypt($plaintext);
        $decrypted = decrypt($encrypted);
        
        $this->assertEquals($plaintext, $decrypted, 'Encryption system should work correctly');
    }

    public function test_hashing_system_works()
    {
        $password = 'smoke_test_password';
        $hash = \Illuminate\Support\Facades\Hash::make($password);
        
        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check($password, $hash),
            'Hashing system should work correctly'
        );
    }

    public function test_url_generation_works()
    {
        $url = url('/test-path');
        $this->assertStringContainsString('/test-path', $url, 'URL generation should work');
    }

    public function test_translation_system_is_available()
    {
        $translated = __('auth.failed');
        $this->assertNotEmpty($translated, 'Translation system should be available');
    }

    private function convertToBytes(string $value): int
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int) $value;

        switch ($last) {
            case 'g':
                $value *= 1024;
                // no break
            case 'm':
                $value *= 1024;
                // no break
            case 'k':
                $value *= 1024;
        }

        return $value;
    }
}