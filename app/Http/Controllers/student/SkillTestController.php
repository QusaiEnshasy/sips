<?php

namespace App\Http\Controllers\student;

use App\Http\Controllers\Controller;
use App\Models\SkillTest;
use App\Models\SkillTestResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SkillTestController extends Controller
{
    private function ensureRole(): void
    {
        abort_unless(Auth::check() && Auth::user()->role === 'student', 403);
    }

    public function show(Request $request): JsonResponse
    {
        $this->ensureRole();

        $user = Auth::user();
        $selectedSpecialization = $request->query('specialization');
        $tests = SkillTest::query()
            ->where('is_active', true)
            ->with('questions')
            ->orderBy('specialization_name')
            ->get();

        if ($tests->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active skill test was found.',
            ], 404);
        }

        $test = null;

        if ($selectedSpecialization) {
            $test = $tests->firstWhere('specialization_code', $selectedSpecialization);

            if (! $test) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The selected specialization test was not found.',
                ], 404);
            }
        }

        $lastResult = SkillTestResult::query()
            ->where('user_id', $user->id)
            ->latest('completed_at')
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'specializations' => $tests->map(fn ($skillTest) => [
                    'code' => $skillTest->specialization_code ?: 'general',
                    'name' => $skillTest->specialization_name ?: $skillTest->title,
                    'description' => $skillTest->description,
                    'questions_count' => $skillTest->questions->count(),
                    'duration_minutes' => $skillTest->duration_minutes,
                    'passing_score' => $skillTest->passing_score,
                    'test_id' => $skillTest->id,
                ])->values(),
                'selected_specialization' => $test?->specialization_code,
                'test' => $test ? [
                    'id' => $test->id,
                    'title' => $test->title,
                    'description' => $test->description,
                    'specialization_code' => $test->specialization_code,
                    'specialization_name' => $test->specialization_name,
                    'duration_minutes' => $test->duration_minutes,
                    'passing_score' => $test->passing_score,
                ] : null,
                'questions' => $test
                    ? $test->questions->map(fn ($question) => [
                        'id' => $question->id,
                        'question' => $question->question,
                        'options' => $question->options,
                        'correct_answer' => $question->correct_answer,
                    ])->values()
                    : [],
                'last_result' => $lastResult ? [
                    'score' => $lastResult->score,
                    'passed' => $lastResult->passed,
                    'completed_at' => optional($lastResult->completed_at)->toISOString(),
                    'specialization_code' => $lastResult->specialization_code,
                    'specialization_name' => $lastResult->specialization_name,
                ] : null,
                'user_state' => $this->userStatePayload($user),
            ],
        ]);
    }

    public function submit(Request $request): JsonResponse
    {
        $this->ensureRole();

        $user = Auth::user();
        $data = $request->validate([
            'test_id' => ['required', 'integer', 'exists:skill_tests,id'],
            'specialization_code' => ['required', 'string'],
            'answers' => ['required', 'array'],
        ]);

        $test = SkillTest::query()
            ->with('questions')
            ->findOrFail($data['test_id']);

        abort_unless(
            $test->specialization_code === $data['specialization_code'],
            422,
            'Selected specialization does not match the test.'
        );

        $correctCount = 0;
        $answers = $data['answers'];

        foreach ($test->questions as $index => $question) {
            $submittedAnswer = $answers[$index] ?? $answers[(string) $index] ?? null;
            if ($submittedAnswer !== null && (int) $submittedAnswer === (int) $question->correct_answer) {
                $correctCount++;
            }
        }

        $totalQuestions = max($test->questions->count(), 1);
        $score = (int) round(($correctCount / $totalQuestions) * 100);
        $passed = $score >= (int) $test->passing_score;

        SkillTestResult::create([
            'user_id' => $user->id,
            'skill_test_id' => $test->id,
            'specialization_code' => $test->specialization_code,
            'specialization_name' => $test->specialization_name,
            'score' => $score,
            'passed' => $passed,
            'answers' => $answers,
            'completed_at' => now(),
        ]);

        $user->forceFill([
            'skill_test_required' => ! $passed,
            'skill_test_passed' => $passed,
            'is_in_jisr' => ! $passed,
            'skill_test_completed_at' => now(),
            'jisr_completed_at' => $passed ? $user->jisr_completed_at : null,
        ])->save();

        return response()->json([
            'status' => 'success',
            'data' => [
                'score' => $score,
                'passed' => $passed,
                'specialization_code' => $test->specialization_code,
                'specialization_name' => $test->specialization_name,
                'recommended_path' => $passed ? '/student/dashboard' : '/student/jisr',
                'user_state' => $this->userStatePayload($user->fresh()),
            ],
        ]);
    }

    private function userStatePayload($user): array
    {
        return [
            'skill_test_required' => (bool) $user->skill_test_required,
            'skill_test_passed' => (bool) $user->skill_test_passed,
            'is_in_jisr' => (bool) $user->is_in_jisr,
            'skill_test_completed_at' => optional($user->skill_test_completed_at)->toISOString(),
            'jisr_completed_at' => optional($user->jisr_completed_at)->toISOString(),
        ];
    }
}
