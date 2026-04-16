<?php

namespace App\Http\Controllers\company;

use App\Http\Controllers\Controller;
use App\Models\InternshipOpportunity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class JobController extends Controller
{
    private function actionResponse(Request $request, string $message, array $data = []): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => $message,
                'data' => $data,
            ]);
        }

        return redirect()->route('company.programs.index')->with('success', $message);
    }

    public function createJob(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless($request->user() && $request->user()->role === 'company', 403);

        if (is_array($request->input('requirements'))) {
            $request->merge([
                'requirements' => collect($request->input('requirements'))
                    ->filter(fn ($line) => trim((string) $line) !== '')
                    ->implode("\n"),
            ]);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'type' => ['nullable', 'in:training,job'],
            'field' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'work_type' => ['nullable', 'in:onsite,remote,hybrid'],
            'requirements' => ['nullable', 'string'],
            'education_level' => ['nullable', 'string', 'max:255'],
            'duration' => ['nullable', 'integer', 'min:1'],
            'duration_weeks' => ['nullable', 'integer', 'min:1'],
            'deadline' => ['nullable', 'date'],
            'status' => ['nullable', 'in:open,closed,active,draft,completed'],
        ]);

        $status = $validated['status'] ?? 'open';
        if (in_array($status, ['active', 'draft'], true)) {
            $status = 'open';
        } elseif ($status === 'completed') {
            $status = 'closed';
        }

        $program = InternshipOpportunity::create([
            'company_user_id' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'type' => $validated['type'] ?? 'training',
            'field' => $validated['field'] ?? null,
            'city' => $validated['city'] ?? null,
            'work_type' => $validated['work_type'] ?? null,
            'requirements' => $validated['requirements'] ?? null,
            'education_level' => $validated['education_level'] ?? null,
            'duration' => $validated['duration'] ?? ($validated['duration_weeks'] ?? null),
            'deadline' => $validated['deadline'] ?? null,
            'status' => $status,
        ]);

        return $this->actionResponse($request, 'Job created successfully.', ['id' => $program->id]);
    }

    public function updateJob(Request $request, int $id): JsonResponse|RedirectResponse
    {
        abort_unless($request->user() && $request->user()->role === 'company', 403);

        $job = InternshipOpportunity::findOrFail($id);
        abort_unless((int) $job->company_user_id === (int) $request->user()->id, 403);

        if (is_array($request->input('requirements'))) {
            $request->merge([
                'requirements' => collect($request->input('requirements'))
                    ->filter(fn ($line) => trim((string) $line) !== '')
                    ->implode("\n"),
            ]);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'type' => ['sometimes', 'required', 'in:training,job'],
            'field' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'work_type' => ['nullable', 'in:onsite,remote,hybrid'],
            'requirements' => ['nullable', 'string'],
            'education_level' => ['nullable', 'string', 'max:255'],
            'duration' => ['nullable', 'integer', 'min:1'],
            'duration_weeks' => ['nullable', 'integer', 'min:1'],
            'deadline' => ['nullable', 'date'],
            'status' => ['nullable', 'in:open,closed,active,draft,completed'],
        ]);

        if (array_key_exists('duration_weeks', $validated) && ! array_key_exists('duration', $validated)) {
            $validated['duration'] = $validated['duration_weeks'];
        }
        unset($validated['duration_weeks']);

        if (isset($validated['status'])) {
            if (in_array($validated['status'], ['active', 'draft'], true)) {
                $validated['status'] = 'open';
            } elseif ($validated['status'] === 'completed') {
                $validated['status'] = 'closed';
            }
        }

        $job->update($validated);

        return $this->actionResponse($request, 'Job updated successfully.', ['id' => $job->id]);
    }

    public function deleteJob(Request $request, int $id): JsonResponse|RedirectResponse
    {
        abort_unless($request->user() && $request->user()->role === 'company', 403);

        $job = InternshipOpportunity::findOrFail($id);
        abort_unless((int) $job->company_user_id === (int) $request->user()->id, 403);

        $job->delete();

        return $this->actionResponse($request, 'Job deleted successfully.');
    }
}
