<?php

namespace App\Http\Controllers\Api\V1;

use App\GraphQL\CatalogGraphqlExecutor;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GraphqlController extends Controller
{
    public function __invoke(Request $request, CatalogGraphqlExecutor $executor): JsonResponse
    {
        $query = $request->string('query')->toString();
        if ($query === '') {
            return response()->json(['errors' => [['message' => 'Query is required.']]], 400);
        }

        $variables = $request->input('variables');
        if (! is_array($variables)) {
            $variables = null;
        }

        return response()->json($executor->execute($query, $variables));
    }
}
