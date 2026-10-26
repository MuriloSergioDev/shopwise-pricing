<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateQuoteRequest;
use App\Http\Resources\QuoteResource;
use App\Queries\PriceQuote;
use Illuminate\Http\JsonResponse;

final class CreateQuoteController
{
    /**
     * 200, not 201: a quote is computed, never stored.
     */
    public function __invoke(CreateQuoteRequest $request, PriceQuote $priceQuote): JsonResponse
    {
        return QuoteResource::make($priceQuote->handle($request->toData()))->response()->setStatusCode(200);
    }
}
