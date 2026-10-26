<?php

declare(strict_types=1);

use App\Models\Coupon;

it('creates a coupon with an uppercase code', function (): void {
    $this->postJson('/api/admin/coupons', [
        'code' => 'spring-15',
        'description' => 'Spring sale',
        'type' => 'percent',
        'value' => 15,
        'minSubtotal' => 2000,
        'startsAt' => '2026-11-01T00:00:00Z',
        'endsAt' => '2026-12-01T00:00:00Z',
    ], bearer())
        ->assertCreated()
        ->assertJsonPath('data.code', 'SPRING-15')
        ->assertJsonPath('data.description', 'Spring sale')
        ->assertJsonPath('data.type', 'percent')
        ->assertJsonPath('data.value', 15)
        ->assertJsonPath('data.minSubtotal', 2000)
        ->assertJsonPath('data.startsAt', '2026-11-01T00:00:00Z')
        ->assertJsonPath('data.endsAt', '2026-12-01T00:00:00Z')
        ->assertJsonPath('data.active', true);

    expect(Coupon::query()->sole()->code)->toBe('SPRING-15');
});

it('defaults optional fields', function (): void {
    $this->postJson('/api/admin/coupons', ['code' => 'FLAT', 'type' => 'fixed', 'value' => 500], bearer())
        ->assertCreated()
        ->assertJsonMissingPath('data.description')
        ->assertJsonPath('data.minSubtotal', 0)
        ->assertJsonPath('data.startsAt', null)
        ->assertJsonPath('data.endsAt', null);
});

it('rejects duplicate codes regardless of case, even deactivated ones', function (): void {
    Coupon::factory()->inactive()->create(['code' => 'WELCOME10']);

    $this->postJson('/api/admin/coupons', ['code' => 'welcome10', 'type' => 'percent', 'value' => 10], bearer())
        ->assertUnprocessable()
        ->assertJsonValidationErrors('code');
});

it('validates coupons', function (): void {
    $this->postJson('/api/admin/coupons', ['code' => 'BAD CODE', 'type' => 'bogus', 'value' => 0], bearer())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['code', 'type', 'value']);
    $this->postJson('/api/admin/coupons', ['code' => 'P', 'type' => 'percent', 'value' => 101], bearer())
        ->assertUnprocessable()
        ->assertJsonValidationErrors('value');
    $this->postJson('/api/admin/coupons', ['code' => 'P', 'type' => 'fixed', 'value' => 1, 'startsAt' => '2026-12-01T00:00:00Z', 'endsAt' => '2026-11-01T00:00:00Z'], bearer())
        ->assertUnprocessable()
        ->assertJsonValidationErrors('endsAt');
});

it('lists every coupon newest first', function (): void {
    Coupon::factory()->create(['code' => 'OLD']);
    Coupon::factory()->inactive()->create(['code' => 'NEW']);

    $this->getJson('/api/admin/coupons', bearer())
        ->assertOk()
        ->assertJsonPath('data.*.code', ['NEW', 'OLD'])
        ->assertJsonPath('data.0.active', false);
});

it('deactivates a coupon by code, case-insensitively', function (): void {
    $coupon = Coupon::factory()->create(['code' => 'WELCOME10']);

    $this->deleteJson('/api/admin/coupons/welcome10', [], bearer())->assertNoContent();

    expect($coupon->refresh()->active)->toBeFalse();
    $this->deleteJson('/api/admin/coupons/NOPE', [], bearer())->assertNotFound();
});

it('needs an admin token for every coupon endpoint', function (): void {
    $this->getJson('/api/admin/coupons')->assertUnauthorized();
    $this->getJson('/api/admin/coupons', bearer(['customer']))->assertForbidden();
    $this->postJson('/api/admin/coupons', ['code' => 'X', 'type' => 'fixed', 'value' => 1], bearer(['customer']))->assertForbidden();
    $this->deleteJson('/api/admin/coupons/X', [], bearer(['customer']))->assertForbidden();
});
