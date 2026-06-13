<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Services\MasterSpec\MedicalReviewWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MedicalReviewController extends Controller
{
    public function submit(Request $request, MedicalReviewWorkflowService $workflow): RedirectResponse
    {
        $validated = $request->validate([
            'entity_type' => ['required', 'string', 'in:service,category,sub_service'],
            'entity_id' => ['required', 'integer', 'min:1'],
        ]);

        $entity = $workflow->resolveModel($validated['entity_type'], (int) $validated['entity_id']);
        if ($entity === null) {
            return back()->withErrors(['entity' => __('Record not found.')]);
        }

        $workflow->submitForMedicalReview($entity);

        return back()->with('medical_review_submitted', true);
    }

    public function approve(Request $request, MedicalReviewWorkflowService $workflow): RedirectResponse
    {
        if (! $workflow->canReview($request->user())) {
            abort(403);
        }

        $validated = $request->validate([
            'entity_type' => ['required', 'string', 'in:service,category,sub_service'],
            'entity_id' => ['required', 'integer', 'min:1'],
        ]);

        $entity = $workflow->resolveModel($validated['entity_type'], (int) $validated['entity_id']);
        if ($entity === null) {
            return back()->withErrors(['entity' => __('Record not found.')]);
        }

        $workflow->approve($entity, $request->user());

        return back()->with('medical_review_approved', true);
    }

    public function reject(Request $request, MedicalReviewWorkflowService $workflow): RedirectResponse
    {
        if (! $workflow->canReview($request->user())) {
            abort(403);
        }

        $validated = $request->validate([
            'entity_type' => ['required', 'string', 'in:service,category,sub_service'],
            'entity_id' => ['required', 'integer', 'min:1'],
        ]);

        $entity = $workflow->resolveModel($validated['entity_type'], (int) $validated['entity_id']);
        if ($entity === null) {
            return back()->withErrors(['entity' => __('Record not found.')]);
        }

        $workflow->reject($entity, $request->user());

        return back()->with('medical_review_rejected', true);
    }
}
