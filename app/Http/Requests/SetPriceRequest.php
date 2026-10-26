<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Brick\Money\Money;
use Illuminate\Foundation\Http\FormRequest;

final class SetPriceRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:0', 'max:2000000000'],
            'currency' => ['required', 'string', 'in:'.config()->string('pricing.currency')],
        ];
    }

    public function price(): Money
    {
        return Money::ofMinor($this->integer('amount'), $this->string('currency')->toString());
    }
}
