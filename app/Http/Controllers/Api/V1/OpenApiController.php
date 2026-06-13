<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;

class OpenApiController extends Controller
{
    public function show(): JsonResponse
    {
        $path = base_path('docs/openapi-v1.json');

        if (! File::isFile($path)) {
            return response()->json(['message' => 'OpenAPI specification not found'], 404);
        }

        $decoded = json_decode(File::get($path), true);

        if (! is_array($decoded)) {
            return response()->json(['message' => 'OpenAPI specification is invalid JSON'], 500);
        }

        return response()->json($decoded);
    }
}
