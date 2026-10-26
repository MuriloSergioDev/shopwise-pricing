<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\QuoteLineData;
use App\Data\QuoteRequestData;
use Illuminate\Foundation\Http\FormRequest;

final class CreateQuoteRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            // An empty cart is a valid quote (all zeros).
            'lines' => ['present', 'array', 'list', 'max:50'],
            'lines.*.sku' => ['required', 'string', 'max:64'],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'couponCode' => ['nullable', 'string', 'max:32'],
        ];
    }

    public function toData(): QuoteRequestData
    {
        /** @var list<array{sku: string, quantity: int|string}> $lines */
        $lines = $this->validated('lines');

        return new QuoteRequestData(
            lines: array_map(fn (array $line): QuoteLineData => new QuoteLineData($line['sku'], (int) $line['quantity']), $lines),
            couponCode: $this->filled('couponCode') ? $this->string('couponCode')->trim()->toString() : null,
        );
    }
}
