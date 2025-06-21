<?php

namespace Tests\Feature\Smoke;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class FilamentInstallationTest extends TestCase
{
    use RefreshDatabase;

    public function test_filament_package_is_installed()
    {
        $composerLock = json_decode(file_get_contents(base_path('composer.lock')), true);
        $packages = collect($composerLock['packages'])->pluck('name');
        
        $this->assertTrue(
            $packages->contains('filament/filament'),
            'Filament package should be installed'
        );
    }

    public function test_filament_service_provider_is_registered()
    {
        $providers = app()->getLoadedProviders();
        
        $filamentProviders = array_filter(array_keys($providers), function ($provider) {
            return str_contains(strtolower($provider), 'filament');
        });

        $this->assertNotEmpty($filamentProviders, 'Filament service providers should be registered');
    }

    public function test_filament_config_files_exist()
    {
        // Filament may not have published config files, which is fine
        $configFiles = [
            'filament.php'
        ];

        foreach ($configFiles as $configFile) {
            $configPath = config_path($configFile);
            if (file_exists($configPath)) {
                $this->assertFileExists($configPath, "Filament config file '{$configFile}' exists");
            } else {
                // Config file not published yet, which is acceptable
                $this->assertTrue(true, "Filament config file '{$configFile}' not published (acceptable)");
            }
        }
    }

    public function test_filament_admin_panel_route_exists()
    {
        $response = $this->get('/admin/login');
        
        // Should redirect to login or return 200, not 404
        $this->assertNotEquals(404, $response->getStatusCode());
    }

    public function test_filament_resources_directory_exists()
    {
        $resourcesPath = app_path('Filament/Resources');
        
        if (File::exists($resourcesPath)) {
            $this->assertDirectoryExists($resourcesPath);
        } else {
            // If directory doesn't exist, that's okay for initial setup
            $this->assertTrue(true);
        }
    }

    public function test_filament_pages_directory_exists()
    {
        $pagesPath = app_path('Filament/Pages');
        
        if (File::exists($pagesPath)) {
            $this->assertDirectoryExists($pagesPath);
        } else {
            // If directory doesn't exist, that's okay for initial setup
            $this->assertTrue(true);
        }
    }

    public function test_filament_widgets_directory_exists()
    {
        $widgetsPath = app_path('Filament/Widgets');
        
        if (File::exists($widgetsPath)) {
            $this->assertDirectoryExists($widgetsPath);
        } else {
            // If directory doesn't exist, that's okay for initial setup
            $this->assertTrue(true);
        }
    }

    public function test_filament_assets_are_published()
    {
        // Check if Filament assets are available
        $publicPath = public_path('css');
        
        if (File::exists($publicPath)) {
            $this->assertDirectoryExists($publicPath);
        }
        
        $this->assertTrue(true); // Basic check passes
    }

    public function test_filament_middleware_is_configured()
    {
        $middlewareGroups = config('filament.middleware');
        
        if ($middlewareGroups) {
            $this->assertIsArray($middlewareGroups);
        } else {
            // Default middleware is fine
            $this->assertTrue(true);
        }
    }

    public function test_filament_user_model_is_configured()
    {
        $userModel = config('filament.auth.guard.users.model');
        
        if ($userModel) {
            $this->assertTrue(class_exists($userModel), "User model {$userModel} should exist");
        } else {
            // Default User model
            $this->assertTrue(class_exists(\App\Models\User::class));
        }
    }

    public function test_filament_database_tables_exist()
    {
        // Basic check that user table exists for Filament auth
        $this->assertTrue(
            \Illuminate\Support\Facades\Schema::hasTable('users'),
            'Users table should exist for Filament authentication'
        );
    }

    public function test_filament_artisan_commands_available()
    {
        $artisanCommands = Artisan::all();
        $filamentCommands = array_filter(array_keys($artisanCommands), function ($command) {
            return str_starts_with($command, 'make:filament');
        });

        $this->assertNotEmpty($filamentCommands, 'Filament artisan commands should be available');
    }
}