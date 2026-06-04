<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreLeadRequest;
use App\Services\Leads\LeadIngestionService;
use Illuminate\Http\JsonResponse;

class LeadController extends Controller
{
    public function __construct(
        private readonly LeadIngestionService $ingestion,
    ) {}

    public function store(StoreLeadRequest $request): JsonResponse
    {
        $result = $this->ingestion->ingest($request->validated(), $request);

        return response()->json($result['body'], $result['status']);
    }
}
