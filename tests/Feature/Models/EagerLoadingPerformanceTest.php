<?php

namespace Tests\Feature\Models;

use App\Models\Contact;
use App\Models\Interaction;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\Principal;
use App\Models\ProductLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EagerLoadingPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_eager_loading_prevents_n_plus_one_queries()
    {
        // Create test data
        $organizations = Organization::factory()->count(3)->create();
        
        foreach ($organizations as $organization) {
            Contact::factory()->count(2)->for($organization)->create();
            Interaction::factory()->count(3)->for($organization)->create();
            Opportunity::factory()->count(2)->for($organization)->create();
        }

        // Test N+1 problem exists without eager loading
        $this->expectsDatabaseQueryCount(10); // 1 + 3 + 3 + 3 queries
        
        $organizations = Organization::all(); // 1 query
        
        foreach ($organizations as $organization) {
            $organization->contacts->count(); // 3 queries (N+1)
            $organization->interactions->count(); // 3 queries (N+1)
            $organization->opportunities->count(); // 3 queries (N+1)
        }
    }

    public function test_eager_loading_reduces_query_count()
    {
        // Create test data
        $organizations = Organization::factory()->count(3)->create();
        
        foreach ($organizations as $organization) {
            Contact::factory()->count(2)->for($organization)->create();
            Interaction::factory()->count(3)->for($organization)->create();
            Opportunity::factory()->count(2)->for($organization)->create();
        }

        // Test eager loading reduces queries
        $this->expectsDatabaseQueryCount(4); // 1 + 1 + 1 + 1 queries
        
        $organizations = Organization::with(['contacts', 'interactions', 'opportunities'])->get();
        
        foreach ($organizations as $organization) {
            $organization->contacts->count(); // No additional queries
            $organization->interactions->count(); // No additional queries
            $organization->opportunities->count(); // No additional queries
        }
    }

    public function test_nested_relationship_eager_loading()
    {
        // Create nested relationship data
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $contacts = Contact::factory()->count(2)->for($organization)->create();
        
        foreach ($contacts as $contact) {
            Interaction::factory()
                ->count(2)
                ->for($organization)
                ->for($contact)
                ->for($user)
                ->create();
        }

        // Test nested eager loading efficiency
        $this->expectsDatabaseQueryCount(4); // organizations + contacts + interactions + users
        
        $organizations = Organization::with('contacts.interactions.user')->get();
        
        foreach ($organizations as $organization) {
            foreach ($organization->contacts as $contact) {
                foreach ($contact->interactions as $interaction) {
                    $userName = $interaction->user->name; // No additional queries
                }
            }
        }
    }

    public function test_conditional_eager_loading()
    {
        $organizations = Organization::factory()->count(5)->create();
        
        // Add contacts to some organizations
        $organizations->take(3)->each(function ($org) {
            Contact::factory()->count(2)->for($org)->create();
        });

        // Test conditional eager loading
        $this->expectsDatabaseQueryCount(2); // organizations + contacts where needed
        
        $organizations = Organization::with([
            'contacts' => function ($query) {
                $query->where('isPrimary', true);
            }
        ])->get();

        // Access relationships without additional queries
        $organizations->each(function ($organization) {
            $primaryContacts = $organization->contacts; // No additional queries
        });
    }

    public function test_load_missing_prevents_redundant_queries()
    {
        $user = User::factory()->create();
        $organizations = Organization::factory()->count(3)->create();
        
        foreach ($organizations as $organization) {
            Opportunity::factory()->count(2)->for($organization)->for($user)->create();
        }

        // First load organizations
        $organizations = Organization::all();
        
        // Load some relationships
        $organizations->load('opportunities');
        
        // Test loadMissing doesn't reload already loaded relationships
        $this->expectsDatabaseQueryCount(1); // Only for interactions, not opportunities
        
        $organizations->loadMissing(['opportunities', 'interactions']);
    }

    public function test_cursor_for_memory_efficient_iteration()
    {
        // Create large dataset
        Organization::factory()->count(100)->create();

        // Test cursor uses minimal memory
        $count = 0;
        
        foreach (Organization::cursor() as $organization) {
            $count++;
            if ($count >= 10) break; // Just test first 10 for performance
        }

        $this->assertEquals(10, $count);
    }

    public function test_lazy_eager_loading_performance()
    {
        $users = User::factory()->count(3)->create();
        $organization = Organization::factory()->create();
        
        foreach ($users as $user) {
            Opportunity::factory()
                ->count(2)
                ->for($organization)
                ->for($user)
                ->create();
        }

        // Get users without relationships first
        $users = User::all();
        
        // Test lazy eager loading is efficient
        $this->expectsDatabaseQueryCount(1); // Only opportunities query
        
        // Conditionally load relationships
        if ($users->count() > 0) {
            $users->load('opportunities');
        }

        // Access without additional queries
        $users->each(function ($user) {
            $opportunityCount = $user->opportunities->count();
        });
    }

    public function test_principal_product_line_eager_loading()
    {
        $principals = Principal::factory()->count(3)->create();
        
        foreach ($principals as $principal) {
            ProductLine::factory()->count(4)->for($principal)->create();
        }

        // Test eager loading for principal-product line relationships
        $this->expectsDatabaseQueryCount(2); // principals + product_lines
        
        $principals = Principal::with('productLines')->get();
        
        foreach ($principals as $principal) {
            $activeLines = $principal->productLines->where('is_active', true);
        }
    }

    public function test_scoped_eager_loading_with_constraints()
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        
        // Create opportunities with different stages
        Opportunity::factory()->for($organization)->for($user)->create(['stage' => 'lead']);
        Opportunity::factory()->for($organization)->for($user)->create(['stage' => 'proposal']);
        Opportunity::factory()->for($organization)->for($user)->create(['stage' => 'closed']);

        // Test eager loading with constraints
        $this->expectsDatabaseQueryCount(2); // organizations + filtered opportunities
        
        $organizations = Organization::with([
            'opportunities' => function ($query) {
                $query->where('stage', '!=', 'closed');
            }
        ])->get();

        $organization = $organizations->first();
        $activeOpportunities = $organization->opportunities; // Should only have 2, not 3
        
        $this->assertCount(2, $activeOpportunities);
    }

    public function test_count_loading_performance()
    {
        $organizations = Organization::factory()->count(5)->create();
        
        foreach ($organizations as $organization) {
            Contact::factory()->count(rand(1, 5))->for($organization)->create();
            Opportunity::factory()->count(rand(1, 3))->for($organization)->create();
        }

        // Test loading counts efficiently
        $this->expectsDatabaseQueryCount(3); // organizations + contacts count + opportunities count
        
        $organizations = Organization::withCount(['contacts', 'opportunities'])->get();
        
        foreach ($organizations as $organization) {
            $contactCount = $organization->contacts_count; // No additional queries
            $opportunityCount = $organization->opportunities_count; // No additional queries
        }
    }
}