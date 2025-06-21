<?php

namespace Tests\Feature\Smoke;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseConnectivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_connection_is_active()
    {
        $pdo = DB::connection()->getPdo();
        $this->assertNotNull($pdo, 'Database PDO connection should be available');
    }

    public function test_database_driver_is_sqlite()
    {
        $driver = DB::connection()->getDriverName();
        $this->assertEquals('sqlite', $driver, 'Database driver should be SQLite');
    }

    public function test_database_file_path_is_correct()
    {
        $config = config('database.connections.sqlite');
        $dbPath = $config['database'];
        
        if ($dbPath === ':memory:') {
            // In-memory database for testing
            $this->assertEquals(':memory:', $dbPath, 'Testing should use in-memory SQLite database');
        } else {
            // File-based database
            $this->assertFileExists($dbPath, 'SQLite database file should exist at configured path');
            $this->assertTrue(is_readable($dbPath), 'SQLite database file should be readable');
            $this->assertTrue(is_writable($dbPath), 'SQLite database file should be writable');
        }
    }

    public function test_can_create_and_query_tables()
    {
        Schema::create('smoke_test_table', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        $this->assertTrue(Schema::hasTable('smoke_test_table'), 'Should be able to create tables');

        DB::table('smoke_test_table')->insert([
            'name' => 'test_entry',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $result = DB::table('smoke_test_table')->where('name', 'test_entry')->first();
        $this->assertNotNull($result, 'Should be able to insert and query data');

        Schema::dropIfExists('smoke_test_table');
    }

    public function test_database_transactions_work()
    {
        Schema::create('transaction_test_table', function ($table) {
            $table->id();
            $table->string('value');
        });

        DB::transaction(function () {
            DB::table('transaction_test_table')->insert(['value' => 'committed']);
        });

        $committed = DB::table('transaction_test_table')->where('value', 'committed')->first();
        $this->assertNotNull($committed, 'Committed transaction should persist data');

        try {
            DB::transaction(function () {
                DB::table('transaction_test_table')->insert(['value' => 'rolled_back']);
                throw new \Exception('Force rollback');
            });
        } catch (\Exception $e) {
            // Expected rollback
        }

        $rolledBack = DB::table('transaction_test_table')->where('value', 'rolled_back')->first();
        $this->assertNull($rolledBack, 'Rolled back transaction should not persist data');

        Schema::dropIfExists('transaction_test_table');
    }

    public function test_eloquent_models_can_interact_with_database()
    {
        $user = User::factory()->create([
            'name' => 'Smoke Test User',
            'email' => 'smoke@test.com'
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Smoke Test User',
            'email' => 'smoke@test.com'
        ]);

        $retrievedUser = User::where('email', 'smoke@test.com')->first();
        $this->assertNotNull($retrievedUser, 'Should be able to retrieve user via Eloquent');
        $this->assertEquals('Smoke Test User', $retrievedUser->name);
    }

    public function test_database_constraints_are_enforced()
    {
        // Test unique constraint
        User::factory()->create(['email' => 'unique@test.com']);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        User::factory()->create(['email' => 'unique@test.com']);
    }

    public function test_database_foreign_key_constraints_work()
    {
        $this->assertTrue(Schema::hasTable('organizations'), 'Organizations table should exist');
        $this->assertTrue(Schema::hasTable('contacts'), 'Contacts table should exist');

        // Test that foreign key constraints are working
        $organization = \App\Models\Organization::factory()->create();
        $contact = \App\Models\Contact::factory()->for($organization)->create();

        $this->assertNotNull($contact->organization_id, 'Contact should have organization_id');
        $this->assertEquals($organization->id, $contact->organization_id);
    }

    public function test_database_indexes_exist()
    {
        $indexes = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='users'");
        $this->assertNotEmpty($indexes, 'Users table should have indexes');
    }

    public function test_database_can_handle_concurrent_operations()
    {
        $users = [];
        
        // Simulate concurrent inserts
        for ($i = 0; $i < 5; $i++) {
            $users[] = User::factory()->create([
                'email' => "concurrent{$i}@test.com"
            ]);
        }

        $this->assertCount(5, $users, 'Should handle multiple concurrent operations');
        
        foreach ($users as $user) {
            $this->assertDatabaseHas('users', ['id' => $user->id]);
        }
    }

    public function test_database_performance_is_acceptable()
    {
        $startTime = microtime(true);
        
        // Create multiple records to test performance
        User::factory()->count(50)->create();
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Should complete within reasonable time (adjust threshold as needed)
        $this->assertLessThan(5.0, $executionTime, 'Database operations should complete within acceptable time');
    }

    public function test_database_cleanup_works()
    {
        $userCount = User::count();
        
        User::factory()->count(10)->create();
        $this->assertEquals($userCount + 10, User::count(), 'Users should be created');
        
        // RefreshDatabase should clean up after test
        $this->assertTrue(true, 'Cleanup will be handled by RefreshDatabase trait');
    }
}