<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\NewCouponData;
use App\Enums\CouponType;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class CreateCouponRequest extends FormRequest
{
    /**
     * @return array<string, list<string|ValidationRule|Enum>>
     */
    public function rules(): array
    {
        return [
            // Codes are case-insensitive: prepareForValidation uppercases them, so uniqueness ignores case too.
            'code' => ['required', 'string', 'max:32', 'regex:/^[A-Z0-9_-]+$/', 'unique:coupons,code'],
            'description' => ['nullable', 'string', 'max:200'],
            'type' => ['required', Rule::enum(CouponType::class)],
            'value' => ['required', 'integer', 'min:1', ...($this->input('type') === CouponType::Percent->value ? ['max:100'] : ['max:2000000000'])],
            'minSubtotal' => ['nullable', 'integer', 'min:0', 'max:2000000000'],
            'startsAt' => ['nullable', 'date'],
            'endsAt' => ['nullable', 'date', 'after:startsAt'],
            'maxRedemptions' => ['nullable', 'integer', 'min:1', 'max:2000000000'],
            'maxRedemptionsPerCustomer' => ['nullable', 'integer', 'min:1', 'max:2000000000'],
        ];
    }

    public function toData(): NewCouponData
    {
        return new NewCouponData(
            code: $this->string('code')->toString(),
            type: $this->enum('type', CouponType::class) ?? CouponType::Fixed,
            value: $this->integer('value'),
            description: $this->filled('description') ? $this->string('description')->trim()->toString() : null,
            minSubtotal: $this->integer('minSubtotal'),
            startsAt: $this->filled('startsAt') ? CarbonImmutable::parse($this->string('startsAt')->toString())->utc() : null,
            endsAt: $this->filled('endsAt') ? CarbonImmutable::parse($this->string('endsAt')->toString())->utc() : null,
            maxRedemptions: $this->filled('maxRedemptions') ? $this->integer('maxRedemptions') : null,
            maxRedemptionsPerCustomer: $this->filled('maxRedemptionsPerCustomer') ? $this->integer('maxRedemptionsPerCustomer') : null,
        );
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('code'))) {
            $this->merge(['code' => mb_strtoupper(mb_trim($this->string('code')->toString()))]);
        }
    }
}
