<?php

namespace App\Http\Controllers\SiteArchitect;

use App\Http\Controllers\Controller;
use App\Services\Bulk\BulkActionService;
use Illuminate\Http\Request;

class BulkExportController extends Controller
{
    public function __invoke(Request $request, BulkActionService $bulk)
    {
        $resource = (string) $request->query('resource', '');
        $ids = array_values(array_filter(array_map('intval', explode(',', (string) $request->query('ids', '')))));
        $format = (string) $request->query('format', 'json');

        abort_if($resource === '' || $ids === [], 404);

        return $bulk->export($resource, $ids, $format);
    }
}
