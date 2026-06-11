<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Integrations\Exotel\CallEventIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExotelWebhookController extends Controller
{
    public function __construct(
        private readonly CallEventIngestionService $ingestion,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $result = $this->ingestion->ingest($request);

        return response()->json([
            'ok' => true,
            'recorded' => $result['recorded'],
            'duplicate' => $result['duplicate'],
            'id' => $result['id'],
        ]);
    }
}
