<?php

namespace App\Http\Controllers\communication;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CommunicationController extends Controller
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function page()
    {
        return view('spa');
    }

    public function contacts(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && in_array($user->role, ['student', 'supervisor', 'company'], true), 403);

        return response()->json([
            'status' => 'success',
            'data' => [
                'contacts' => $this->availableContacts($user)->values(),
            ],
        ]);
    }

    public function send(Request $request): JsonResponse
    {
        $sender = $request->user();
        abort_unless($sender && in_array($sender->role, ['student', 'supervisor', 'company'], true), 403);

        $validated = $request->validate([
            'recipient_id' => ['required', 'integer', 'exists:users,id'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $recipientId = (int) $validated['recipient_id'];
        abort_if($recipientId === (int) $sender->id, 422, 'لا يمكنك إرسال رسالة لنفسك.');

        $contact = $this->availableContacts($sender)->firstWhere('id', $recipientId);
        abort_unless($contact, 403, 'لا يمكنك إرسال رسالة لهذا المستخدم.');

        $senderName = $this->displayName($sender);
        $message = trim((string) $validated['message']);

        $this->notifications->notifyUser(
            userId: $recipientId,
            title: 'رسالة من ' . $senderName,
            description: $message,
            type: 'info',
            meta: [
                'category' => 'message',
                'sender_id' => (int) $sender->id,
                'sender_name' => $senderName,
                'sender_role' => $sender->role,
                'sender_role_label' => $this->roleLabel($sender->role),
                'relationship' => $contact['relationship'] ?? null,
                'context' => $contact['context'] ?? null,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'تم إرسال الرسالة بنجاح.',
        ]);
    }

    private function availableContacts(User $user): Collection
    {
        $contacts = collect();

        if ($user->role === 'student') {
            $this->studentContacts($user, $contacts);
        } elseif ($user->role === 'supervisor') {
            $this->supervisorContacts($user, $contacts);
        } elseif ($user->role === 'company') {
            $this->companyContacts($user, $contacts);
        }

        return $contacts
            ->filter(fn (array $contact) => (int) $contact['id'] !== (int) $user->id)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    private function studentContacts(User $student, Collection $contacts): void
    {
        if ($student->supervisor_code) {
            $supervisor = User::query()
                ->where('role', 'supervisor')
                ->where('supervisor_code', $student->supervisor_code)
                ->first();

            $this->putContact($contacts, $supervisor, 'المشرف الأكاديمي');
        }

        Application::with(['opportunity.companyUser'])
            ->where('student_id', $student->id)
            ->latest()
            ->get()
            ->each(function (Application $application) use ($contacts) {
                $this->putContact(
                    contacts: $contacts,
                    contact: $application->opportunity?->companyUser,
                    relationship: 'شركة التدريب',
                    context: $application->opportunity?->title,
                    applicationId: (int) $application->id
                );
            });
    }

    private function supervisorContacts(User $supervisor, Collection $contacts): void
    {
        User::query()
            ->where('role', 'student')
            ->where('supervisor_code', $supervisor->supervisor_code)
            ->orderBy('name')
            ->get()
            ->each(fn (User $student) => $this->putContact($contacts, $student, 'طالب تابع لك'));

        Application::with(['student', 'opportunity.companyUser'])
            ->whereHas('student', function ($query) use ($supervisor) {
                $query->where('supervisor_code', $supervisor->supervisor_code);
            })
            ->whereHas('opportunity.companyUser')
            ->latest()
            ->get()
            ->each(function (Application $application) use ($contacts) {
                $studentName = $application->student?->name;
                $context = trim(($application->opportunity?->title ?: '') . ($studentName ? ' - ' . $studentName : ''));

                $this->putContact(
                    contacts: $contacts,
                    contact: $application->opportunity?->companyUser,
                    relationship: 'شركة مرتبطة بطلابك',
                    context: $context !== '' ? $context : null,
                    applicationId: (int) $application->id
                );
            });
    }

    private function companyContacts(User $company, Collection $contacts): void
    {
        $supervisorCodes = collect();

        Application::with(['student', 'opportunity'])
            ->whereHas('opportunity', function ($query) use ($company) {
                $query->where('company_user_id', $company->id);
            })
            ->latest()
            ->get()
            ->each(function (Application $application) use ($contacts, $supervisorCodes) {
                $this->putContact(
                    contacts: $contacts,
                    contact: $application->student,
                    relationship: 'طالب متقدم أو متدرب',
                    context: $application->opportunity?->title,
                    applicationId: (int) $application->id
                );

                if ($application->student?->supervisor_code) {
                    $supervisorCodes->push($application->student->supervisor_code);
                }
            });

        User::query()
            ->where('role', 'supervisor')
            ->whereIn('supervisor_code', $supervisorCodes->filter()->unique()->values())
            ->orderBy('name')
            ->get()
            ->each(fn (User $supervisor) => $this->putContact($contacts, $supervisor, 'مشرف طالب مرتبط بالشركة'));
    }

    private function putContact(
        Collection $contacts,
        ?User $contact,
        string $relationship,
        ?string $context = null,
        ?int $applicationId = null
    ): void {
        if (! $contact) {
            return;
        }

        $existing = $contacts->get($contact->id, [
            'id' => (int) $contact->id,
            'name' => $this->displayName($contact),
            'email' => $contact->email,
            'phone' => $contact->phone_number,
            'role' => $contact->role,
            'role_label' => $this->roleLabel($contact->role),
            'relationship' => $relationship,
            'context' => null,
            'application_id' => null,
        ]);

        if ($context && empty($existing['context'])) {
            $existing['context'] = $context;
        }

        if ($applicationId && empty($existing['application_id'])) {
            $existing['application_id'] = $applicationId;
        }

        if (! str_contains($existing['relationship'], $relationship)) {
            $existing['relationship'] .= ' / ' . $relationship;
        }

        $contacts->put($contact->id, $existing);
    }

    private function displayName(User $user): string
    {
        return trim((string) ($user->company_name ?: $user->name)) ?: ('User #' . $user->id);
    }

    private function roleLabel(?string $role): string
    {
        return match ($role) {
            'student' => 'طالب',
            'supervisor' => 'مشرف',
            'company' => 'شركة',
            default => 'مستخدم',
        };
    }
}
