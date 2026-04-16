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

    public function show(): JsonResponse
    {
        $this->ensureRole();

        $user = Auth::user();
        $test = SkillTest::query()
            ->where('is_active', true)
            ->with('questions')
            ->first();

        if (! $test) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active skill test was found.',
            ], 404);
        }

        $lastResult = SkillTestResult::query()
            ->where('user_id', $user->id)
            ->latest('completed_at')
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'test' => [
                    'id' => $test->id,
                    'title' => $test->title,
                    'description' => $test->description,
                    'duration_minutes' => $test->duration_minutes,
                    'passing_score' => $test->passing_score,
                ],
                'questions' => $test->questions->map(fn ($question) => [
                    'id' => $question->id,
                    'question' => $question->question,
                    'options' => $question->options,
                    'correct_answer' => $question->correct_answer,
                ])->values(),
                'last_result' => $lastResult ? [
                    'score' => $lastResult->score,
                    'passed' => $lastResult->passed,
                    'completed_at' => optional($lastResult->completed_at)->toISOString(),
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
            'answers' => ['required', 'array'],
        ]);

        $test = SkillTest::query()
            ->with('questions')
            ->findOrFail($data['test_id']);

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
