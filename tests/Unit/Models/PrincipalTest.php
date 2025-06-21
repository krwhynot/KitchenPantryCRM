<?php

namespace Tests\Unit\Models;

use App\Models\Principal;
use App\Models\ProductLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrincipalTest extends TestCase
{
    use RefreshDatabase;

    public function test_principal_has_many_product_lines()
    {
        $principal = Principal::factory()->create();
        $productLines = ProductLine::factory()->count(3)->for($principal)->create();

        $this->assertCount(3, $principal->productLines);
        $this->assertTrue($principal->productLines->first() instanceof ProductLine);
    }

    public function test_contact_display_accessor()
    {
        $principal = Principal::factory()->create(['contact_name' => 'John Smith']);
        $this->assertEquals('John Smith', $principal->contact_display);

        $principal = Principal::factory()->create(['contact_name' => null]);
        $this->assertEquals('No contact assigned', $principal->contact_display);
    }

    public function test_active_scope()
    {
        $activePrincipal = Principal::factory()->create(['name' => 'Active Principal']);
        $inactivePrincipal = Principal::factory()->create(['name' => '']);

        $results = Principal::active()->get();

        $this->assertTrue($results->contains($activePrincipal));
        $this->assertFalse($results->contains($inactivePrincipal));
    }

    public function test_fillable_attributes()
    {
        $data = [
            'name' => 'Test Principal',
            'contact_name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '555-1234',
            'unauthorized_field' => 'should not be set'
        ];

        $principal = Principal::create($data);
        
        $this->assertEquals('Test Principal', $principal->name);
        $this->assertEquals('John Doe', $principal->contact_name);
        $this->assertEquals('john@example.com', $principal->email);
        $this->assertNull($principal->unauthorized_field);
    }

    public function test_model_exists_in_database()
    {
        $principal = Principal::factory()->create();

        $this->assertModelExists($principal);
    }
}