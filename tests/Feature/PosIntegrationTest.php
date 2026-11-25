<?php

namespace Tests\Feature;

use App\Models\{Ingredient, Post, Sale, Supplier, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PosIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        $owner = User::factory()->create(); $owner->role = 'owner'; $owner->save();
        return $owner;
    }
    private function ingredient(string $name = 'Teh seduh', string $unit = 'liter', float $stock = 10): Ingredient
    {
        return Ingredient::create(['name' => $name, 'unit' => $unit, 'stock' => $stock, 'minimum_stock' => 1, 'max_stock' => 100]);
    }
    private function menu(Ingredient $ingredient, float $amount = 200, string $unit = 'ml'): Post
    {
        $post = Post::create(['title' => 'Es teh', 'category' => 'Non-Kopi', 'content' => '', 'price' => 5000, 'stock_type' => 'bom', 'is_active' => true]);
        $post->ingredients()->attach($ingredient->id, ['quantity' => $amount, 'unit' => $unit]); return $post;
    }
    private function payload(array $products, ?string $id = null): array
    {
        return ['request_id' => $id ?? (string) Str::uuid(), 'order_type' => 'Dine In', 'payment_method' => 'Tunai', 'expected_tax_percent' => 0,
            'items' => collect($products)->map(fn ($quantity, $id) => ['product_id' => $id, 'quantity' => $quantity, 'expected_price' => (int) Post::findOrFail($id)->price])->values()->all()];
    }
    private function staff(): User { $user = User::factory()->create(); Sanctum::actingAs($user); return $user; }

    public function test_web_recipe_is_visible_to_mobile_and_sale_deducts_converted_units(): void
    {
        $owner = $this->owner(); $tea = $this->ingredient(); $sugar = $this->ingredient('Gula', 'kg', 2);
        $this->actingAs($owner)->post('/posts', ['title' => 'Es teh', 'category' => 'Non-Kopi', 'price' => 5000, 'stock_type' => 'bom', 'status' => 1,
            'recipe' => [['ingredient_id' => $tea->id, 'quantity' => 200, 'unit' => 'ml'], ['ingredient_id' => $sugar->id, 'quantity' => 20, 'unit' => 'gram']]])->assertRedirect('/posts');
        $product = Post::firstOrFail();
        $this->staff();
        $this->getJson('/api/mobile/pos/catalog')->assertOk()->assertJsonPath('products.0.name', 'Es teh')->assertJsonPath('products.0.available_quantity', 50);
        $this->postJson('/api/mobile/pos/checkout', $this->payload([$product->id => 2]))->assertCreated()->assertJsonPath('sale.total', 10000);
        $this->assertEqualsWithDelta(9.6, $tea->fresh()->stock, 0.000001);
        $this->assertEqualsWithDelta(1.96, $sugar->fresh()->stock, 0.000001);
        $this->assertDatabaseCount('sales', 1); $this->assertDatabaseCount('inventory_movements', 2);
        $this->assertDatabaseHas('inventory_movements', ['ingredient_id' => $tea->id, 'quantity' => -0.4, 'balance_before' => 10, 'balance_after' => 9.6]);
    }

    public function test_retries_are_idempotent_even_after_recipe_changes(): void
    {
        $this->staff(); $tea = $this->ingredient(); $product = $this->menu($tea); $payload = $this->payload([$product->id => 2]);
        $first = $this->postJson('/api/mobile/pos/checkout', $payload)->assertCreated()->json('sale.id');
        $product->ingredients()->updateExistingPivot($tea->id, ['quantity' => 500]);
        $this->postJson('/api/mobile/pos/checkout', $payload)->assertCreated()->assertJsonPath('sale.id', $first);
        $this->assertEqualsWithDelta(9.6, $tea->fresh()->stock, 0.000001); $this->assertDatabaseCount('sales', 1);
    }

    public function test_insufficient_shared_ingredient_rolls_back_all_items(): void
    {
        $this->staff(); $tea = $this->ingredient(stock: 0.3); $a = $this->menu($tea); $b = $this->menu($tea);
        $static = Post::create(['title' => 'Air botol', 'category' => 'Umum', 'content' => '', 'price' => 4000, 'stock_type' => 'static', 'stock' => 5, 'is_active' => true]);
        $this->postJson('/api/mobile/pos/checkout', $this->payload([$a->id => 1, $b->id => 1, $static->id => 1]))->assertUnprocessable();
        $this->assertEquals(0.3, $tea->fresh()->stock); $this->assertEquals(5, $static->fresh()->stock);
        $this->assertDatabaseCount('sales', 0); $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_stale_price_or_tax_does_not_make_a_sale(): void
    {
        $this->staff(); $product = $this->menu($this->ingredient()); $payload = $this->payload([$product->id => 1]);
        $product->update(['price' => 6000]);
        $this->postJson('/api/mobile/pos/checkout', $payload)->assertConflict();
        $payload = $this->payload([$product->id => 1]); DB::table('pos_settings')->where('id', 1)->update(['tax_percent' => 10]);
        $this->postJson('/api/mobile/pos/checkout', $payload)->assertConflict(); $this->assertDatabaseCount('sales', 0);
    }

    public function test_server_calculates_totals_and_ignores_client_totals(): void
    {
        $this->staff(); $product = $this->menu($this->ingredient());
        DB::table('pos_settings')->where('id', 1)->update(['tax_percent' => 10]);
        $payload = $this->payload([$product->id => 2]); $payload['expected_tax_percent'] = 10; $payload['total'] = 1;
        $this->postJson('/api/mobile/pos/checkout', $payload)->assertCreated()->assertJsonPath('sale.total', 11000)->assertJsonPath('sale.tax', 1000);
    }

    public function test_static_and_untracked_products_and_missing_recipe(): void
    {
        $this->staff();
        $static = Post::create(['title' => 'Botol', 'content' => '', 'category' => 'Umum', 'price' => 4000, 'stock_type' => 'static', 'stock' => 2, 'is_active' => true]);
        $none = Post::create(['title' => 'Jasa', 'content' => '', 'category' => 'Umum', 'price' => 1000, 'stock_type' => 'none', 'is_active' => true]);
        $this->postJson('/api/mobile/pos/checkout', $this->payload([$static->id => 2, $none->id => 1]))->assertCreated();
        $this->assertEquals(0, $static->fresh()->stock); $this->assertDatabaseCount('inventory_movements', 1);
        $this->postJson('/api/mobile/pos/checkout', $this->payload([$static->id => 1]))->assertUnprocessable();
        $none->update(['stock_type' => 'bom']);
        $this->postJson('/api/mobile/pos/checkout', $this->payload([$none->id => 1]))->assertUnprocessable();
    }

    public function test_inactive_product_duplicate_lines_and_zero_quantity_are_rejected(): void
    {
        $this->staff(); $product = $this->menu($this->ingredient()); $payload = $this->payload([$product->id => 1]);
        $payload['items'][] = $payload['items'][0]; $this->postJson('/api/mobile/pos/checkout', $payload)->assertUnprocessable();
        $payload = $this->payload([$product->id => 0]); $this->postJson('/api/mobile/pos/checkout', $payload)->assertUnprocessable();
        $product->update(['is_active' => false]); $this->postJson('/api/mobile/pos/checkout', $this->payload([$product->id => 1]))->assertUnprocessable();
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_owner_web_and_api_are_protected_and_owner_can_read_all_sections(): void
    {
        $this->get('/posts')->assertRedirect('/login'); $this->getJson('/api/mobile/pos/catalog')->assertUnauthorized();
        $staff = $this->staff(); $this->actingAs($staff)->get('/posts')->assertForbidden();
        $this->getJson('/api/mobile/owner/overview')->assertForbidden(); $this->postJson('/api/mobile/owner/web-ticket', ['target' => 'dashboard'])->assertForbidden();
        $owner = $this->owner(); Sanctum::actingAs($owner); $this->actingAs($owner);
        foreach (array_keys(\App\Services\OwnerData::SECTIONS) as $section) {
            $this->get('/owner?section='.$section)->assertOk(); $this->getJson('/api/mobile/owner/data/'.$section)->assertOk()->assertJsonStructure(['data', 'current_page', 'last_page']);
        }
        foreach (['/posts/create', '/ingredients', '/purchases', '/reports', '/notifications', '/settings'] as $path) $this->get($path)->assertOk();
    }

    public function test_purchase_adds_converted_stock_once_and_adjustments_are_audited(): void
    {
        $owner = $this->owner(); $tea = $this->ingredient(stock: 1); $supplier = Supplier::create(['name' => 'Supplier teh', 'is_active' => true]);
        $payload = ['supplier_id' => $supplier->id, 'nomor_invoice' => 'INV-001', 'tanggal_beli' => now()->toDateString(), 'items' => [['ingredient_id' => $tea->id, 'quantity' => 500, 'unit' => 'ml', 'cost' => 10000]]];
        $this->actingAs($owner)->post('/purchases', $payload)->assertSessionHasNoErrors();
        $this->assertEquals(1.5, $tea->fresh()->stock);
        $this->post('/purchases', $payload)->assertSessionHasErrors('nomor_invoice'); $this->assertEquals(1.5, $tea->fresh()->stock);
        $this->put('/ingredients/'.$tea->id, ['name' => $tea->name, 'unit' => 'liter', 'expected_stock' => 1.5, 'stock' => 1.25, 'minimum_stock' => 1, 'max_stock' => 10])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('inventory_movements', ['kind' => 'adjustment', 'quantity' => -0.25]);
        $this->put('/ingredients/'.$tea->id, ['name' => $tea->name, 'unit' => 'kg', 'expected_stock' => 1.25, 'stock' => 1.25, 'minimum_stock' => 1, 'max_stock' => 10])->assertSessionHasErrors('unit');
    }

    public function test_incompatible_recipe_units_cannot_be_saved(): void
    {
        $tea = $this->ingredient();
        $this->actingAs($this->owner())->post('/posts', ['title' => 'Teh', 'category' => 'Non-Kopi', 'price' => 5000, 'stock_type' => 'bom', 'status' => 1,
            'recipe' => [['ingredient_id' => $tea->id, 'quantity' => 10, 'unit' => 'kg']]])->assertSessionHasErrors('recipe');
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_owner_ticket_is_single_use_and_rechecks_owner_role(): void
    {
        $owner = $this->owner(); Sanctum::actingAs($owner);
        $url = $this->postJson('/api/mobile/owner/web-ticket', ['target' => 'products'])->assertOk()->json('url');
        $this->get($url)->assertRedirect('/posts'); $this->get($url)->assertForbidden();
        $url = $this->postJson('/api/mobile/owner/web-ticket', ['target' => 'dashboard'])->json('url');
        $this->travel(61)->seconds(); $this->get($url)->assertForbidden(); $this->travelBack();
        $url = $this->postJson('/api/mobile/owner/web-ticket', ['target' => 'dashboard'])->json('url');
        $owner->role = 'karyawan'; $owner->save(); $this->get($url)->assertForbidden();
    }

    public function test_receipt_is_private_and_owner_sees_consumption_and_reports(): void
    {
        $staff = $this->staff(); $tea = $this->ingredient(); $product = $this->menu($tea);
        $id = $this->postJson('/api/mobile/pos/checkout', $this->payload([$product->id => 2]))->json('sale.id');
        $this->staff(); $this->getJson('/api/mobile/pos/sales/'.$id)->assertForbidden();
        $owner = $this->owner(); Sanctum::actingAs($owner);
        $this->getJson('/api/mobile/pos/sales/'.$id)->assertOk()->assertJsonPath('usage.0.item_name', 'Teh seduh');
        $this->getJson('/api/mobile/owner/overview')->assertJsonPath('revenue_today', 10000);
        $this->actingAs($owner)->get('/reports')->assertOk()->assertSee('10.000');
        $this->get('/owner/sales/'.$id)->assertOk()->assertSee('Teh seduh');
    }

    public function test_small_fractional_doses_keep_precision(): void
    {
        $this->staff(); $tea = $this->ingredient(stock: 1); $product = $this->menu($tea, 5, 'ml');
        $this->postJson('/api/mobile/pos/checkout', $this->payload([$product->id => 3]))->assertCreated();
        $this->assertEqualsWithDelta(0.985, $tea->fresh()->stock, 0.000001);
    }

    public function test_stale_stock_forms_do_not_overwrite_recent_sales(): void
    {
        $this->actingAs($this->owner());
        $tea = $this->ingredient();
        $tea->update(['stock' => 9.8]);
        $this->put('/ingredients/'.$tea->id, ['name' => $tea->name, 'unit' => 'liter', 'expected_stock' => 10, 'stock' => 12, 'minimum_stock' => 1, 'max_stock' => 100])->assertSessionHasErrors('stock');
        $this->assertEqualsWithDelta(9.8, $tea->fresh()->stock, 0.000001);
        $product = Post::create(['title' => 'Botol', 'content' => '', 'category' => 'Umum', 'price' => 4000, 'stock_type' => 'static', 'stock' => 4, 'is_active' => true]);
        $this->put('/posts/'.$product->id, ['title' => $product->title, 'category' => 'Umum', 'price' => 4000, 'stock_type' => 'static', 'expected_stock' => 5, 'stock' => 7, 'status' => 1])->assertSessionHasErrors('stock');
        $this->assertEquals(4, $product->fresh()->stock);
    }
}
