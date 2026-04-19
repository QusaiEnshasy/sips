<?php

namespace App\Http\Controllers\student;

use App\Http\Controllers\Controller;
use App\Models\SkillTest;
use App\Models\SkillTestAttempt;
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
        $activeAttempt = $this->activeAttemptFor($user->id);
        $selectedSpecialization = $activeAttempt?->specialization_code ?: $request->query('specialization');
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
                'active_attempt' => $this->attemptPayload($activeAttempt),
                'user_state' => $this->userStatePayload($user),
            ],
        ]);
    }

    public function start(Request $request): JsonResponse
    {
        $this->ensureRole();

        $user = Auth::user();
        $data = $request->validate([
            'test_id' => ['required', 'integer', 'exists:skill_tests,id'],
            'specialization_code' => ['required', 'string'],
        ]);

        $test = SkillTest::query()->with('questions')->findOrFail($data['test_id']);

        abort_unless(
            $test->specialization_code === $data['specialization_code'],
            422,
            'Selected specialization does not match the test.'
        );

        $activeAttempt = $this->activeAttemptFor($user->id);

        if ($activeAttempt) {
            abort_unless(
                (int) $activeAttempt->skill_test_id === (int) $test->id,
                422,
                'You already have an active skill test attempt.'
            );

            return response()->json([
                'status' => 'success',
                'data' => [
                    'attempt' => $this->attemptPayload($activeAttempt),
                ],
            ]);
        }

        $now = now();
        $attempt = SkillTestAttempt::create([
            'user_id' => $user->id,
            'skill_test_id' => $test->id,
            'specialization_code' => $test->specialization_code,
            'specialization_name' => $test->specialization_name,
            'status' => 'in_progress',
            'answers' => [],
            'started_at' => $now,
            'expires_at' => $now->copy()->addMinutes((int) $test->duration_minutes),
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'attempt' => $this->attemptPayload($attempt),
            ],
        ]);
    }

    public function saveProgress(Request $request): JsonResponse
    {
        $this->ensureRole();

        $user = Auth::user();
        $data = $request->validate([
            'attempt_id' => ['required', 'integer', 'exists:skill_test_attempts,id'],
            'answers' => ['nullable', 'array'],
        ]);

        $attempt = SkillTestAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->findOrFail($data['attempt_id']);

        if ($this->remainingSeconds($attempt) <= 0) {
            $this->finalizeAttempt(
                $user,
                $attempt->skillTest()->with('questions')->firstOrFail(),
                $data['answers'] ?? ($attempt->answers ?: []),
                $attempt,
                'expired'
            );

            return response()->json([
                'status' => 'expired',
                'data' => [
                    'attempt' => $this->attemptPayload($attempt->fresh()),
                ],
            ], 409);
        }

        $attempt->forceFill([
            'answers' => $data['answers'] ?? [],
        ])->save();

        return response()->json([
            'status' => 'success',
            'data' => [
                'attempt' => $this->attemptPayload($attempt->fresh()),
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
            'attempt_id' => ['nullable', 'integer', 'exists:skill_test_attempts,id'],
        ]);

        $test = SkillTest::query()
            ->with('questions')
            ->findOrFail($data['test_id']);

        abort_unless(
            $test->specialization_code === $data['specialization_code'],
            422,
            'Selected specialization does not match the test.'
        );

        $attempt = null;
        if (! empty($data['attempt_id'])) {
            $attempt = SkillTestAttempt::query()
                ->where('user_id', $user->id)
                ->where('skill_test_id', $test->id)
                ->findOrFail($data['attempt_id']);

            if (in_array($attempt->status, ['submitted', 'expired'], true) && $attempt->submitted_at) {
                $result = SkillTestResult::query()
                    ->where('user_id', $user->id)
                    ->where('skill_test_id', $test->id)
                    ->latest('completed_at')
                    ->firstOrFail();

                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'score' => $result->score,
                        'passed' => $result->passed,
                        'specialization_code' => $test->specialization_code,
                        'specialization_name' => $test->specialization_name,
                        'recommended_path' => $result->passed ? '/student/dashboard' : '/student/jisr',
                        'user_state' => $this->userStatePayload($user->fresh()),
                    ],
                ]);
            }
        }

        $attemptStatus = $attempt && $this->remainingSeconds($attempt) <= 0 ? 'expired' : 'submitted';
        $result = $this->finalizeAttempt($user, $test, $data['answers'], $attempt, $attemptStatus);

        return response()->json([
            'status' => 'success',
            'data' => [
                'score' => $result->score,
                'passed' => $result->passed,
                'specialization_code' => $test->specialization_code,
                'specialization_name' => $test->specialization_name,
                'recommended_path' => $result->passed ? '/student/dashboard' : '/student/jisr',
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

    private function activeAttemptFor(int $userId): ?SkillTestAttempt
    {
        $attempt = SkillTestAttempt::query()
            ->with('skillTest.questions')
            ->where('user_id', $userId)
            ->where('status', 'in_progress')
            ->latest('started_at')
            ->first();

        if ($attempt && $this->remainingSeconds($attempt) <= 0) {
            $this->finalizeAttempt(
                $attempt->user,
                $attempt->skillTest,
                $attempt->answers ?: [],
                $attempt,
                'expired'
            );

            return null;
        }

        return $attempt;
    }

    private function attemptPayload(?SkillTestAttempt $attempt): ?array
    {
        if (! $attempt) {
            return null;
        }

        return [
            'id' => $attempt->id,
            'test_id' => $attempt->skill_test_id,
            'specialization_code' => $attempt->specialization_code,
            'specialization_name' => $attempt->specialization_name,
            'status' => $attempt->status,
            'answers' => $attempt->answers ?: [],
            'started_at' => optional($attempt->started_at)->toISOString(),
            'expires_at' => optional($attempt->expires_at)->toISOString(),
            'remaining_seconds' => $this->remainingSeconds($attempt),
        ];
    }

    private function remainingSeconds(SkillTestAttempt $attempt): int
    {
        if (! $attempt->expires_at) {
            return 0;
        }

        return max(0, $attempt->expires_at->timestamp - now()->timestamp);
    }

    private function finalizeAttempt($user, SkillTest $test, array $answers, ?SkillTestAttempt $attempt, string $attemptStatus): SkillTestResult
    {
        $correctCount = 0;

        foreach ($test->questions as $index => $question) {
            $submittedAnswer = $answers[$index] ?? $answers[(string) $index] ?? null;
            if ($submittedAnswer !== null && (int) $submittedAnswer === (int) $question->correct_answer) {
                $correctCount++;
            }
        }

        $totalQuestions = max($test->questions->count(), 1);
        $score = (int) round(($correctCount / $totalQuestions) * 100);
        $passed = $score >= (int) $test->passing_score;

        $result = SkillTestResult::create([
            'user_id' => $user->id,
            'skill_test_id' => $test->id,
            'specialization_code' => $test->specialization_code,
            'specialization_name' => $test->specialization_name,
            'score' => $score,
            'passed' => $passed,
            'answers' => $answers,
            'completed_at' => now(),
        ]);

        if ($attempt) {
            $attempt->forceFill([
                'status' => $attemptStatus,
                'answers' => $answers,
                'submitted_at' => now(),
            ])->save();
        }

        $user->forceFill([
            'skill_test_required' => ! $passed,
            'skill_test_passed' => $passed,
            'is_in_jisr' => ! $passed,
            'skill_test_completed_at' => now(),
            'jisr_completed_at' => $passed ? $user->jisr_completed_at : null,
        ])->save();

        return $result;
    }
}
