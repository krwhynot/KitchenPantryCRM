<?php

namespace Tests\Unit\Models;

use App\Models\Principal;
use App\Models\ProductLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductLineTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_line_belongs_to_principal()
    {
        $principal = Principal::factory()->create();
        $productLine = ProductLine::factory()->for($principal)->create();

        $this->assertInstanceOf(Principal::class, $productLine->principal);
        $this->assertEquals($principal->id, $productLine->principal->id);
    }

    public function test_status_label_accessor()
    {
        $activeProductLine = ProductLine::factory()->create(['is_active' => true]);
        $this->assertEquals('Active', $activeProductLine->status_label);

        $inactiveProductLine = ProductLine::factory()->create(['is_active' => false]);
        $this->assertEquals('Inactive', $inactiveProductLine->status_label);
    }

    public function test_active_scope()
    {
        $activeProductLine = ProductLine::factory()->create(['is_active' => true]);
        $inactiveProductLine = ProductLine::factory()->create(['is_active' => false]);

        $results = ProductLine::active()->get();

        $this->assertTrue($results->contains($activeProductLine));
        $this->assertFalse($results->contains($inactiveProductLine));
    }

    public function test_for_principal_scope()
    {
        $principal1 = Principal::factory()->create();
        $principal2 = Principal::factory()->create();
        
        $productLine1 = ProductLine::factory()->for($principal1)->create();
        $productLine2 = ProductLine::factory()->for($principal2)->create();

        $results = ProductLine::forPrincipal($principal1->id)->get();

        $this->assertTrue($results->contains($productLine1));
        $this->assertFalse($results->contains($productLine2));
    }

    public function test_fillable_attributes()
    {
        $principal = Principal::factory()->create();
        $data = [
            'principal_id' => $principal->id,
            'name' => 'Frozen Foods',
            'description' => 'High-quality frozen food products',
            'is_active' => true,
            'unauthorized_field' => 'should not be set'
        ];

        $productLine = ProductLine::create($data);
        
        $this->assertEquals('Frozen Foods', $productLine->name);
        $this->assertEquals('High-quality frozen food products', $productLine->description);
        $this->assertTrue($productLine->is_active);
        $this->assertNull($productLine->unauthorized_field);
    }

    public function test_casts_are_applied()
    {
        $productLine = ProductLine::factory()->create(['is_active' => 1]);

        $this->assertIsBool($productLine->is_active);
        $this->assertTrue($productLine->is_active);
    }

    public function test_model_exists_in_database()
    {
        $productLine = ProductLine::factory()->create();

        $this->assertModelExists($productLine);
    }
}