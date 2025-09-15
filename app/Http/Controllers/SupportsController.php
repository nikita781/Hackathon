<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnswerSupportRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\StoreSupportRequest;
use App\Models\Hackathon;
use App\Models\Project;
use App\Models\Role;
use App\Models\Support;
use App\Models\SupportMessage;
use App\Models\User;
use App\Notifications\NewSupportNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SupportsController extends Controller
{
    public function index(Hackathon $hackathon): JsonResponse
    {
        if (!Gate::check('viewAny', [Support::class, $hackathon])) {
            abort(404);
        }

        $user = auth()->user();

        if ($user->isHackathonStaff($hackathon)) {
            $receivedSupportIds = $hackathon->support()->where('type', Support::QUESTION)->orWhere('type', Support::SUGGESTION)->select('supports.id');
            $supportIds = $user->support()->where('hackathon_id', $hackathon->id)->select('supports.id');

            $receivedSupportGoing = Support::query()
                ->whereIn('id', $receivedSupportIds)
                ->where('is_completed', false)
                ->with('messages.user')
                ->latest()
                ->paginate(6);

            $receivedSupportCompleted = Support::query()
                ->whereIn('id', $receivedSupportIds)
                ->where('is_completed', true)
                ->with('messages.user')
                ->latest()
                ->paginate(6);

            $going = Support::query()
                ->whereIn('id', $supportIds)
                ->where('is_completed', false)
                ->with('messages.user')
                ->latest()
                ->paginate(6);

            $completed = Support::query()
                ->whereIn('id', $supportIds)
                ->where('is_completed', true)
                ->with('messages.user')
                ->latest()
                ->paginate(6);

            return response()->json([
                'receivedSupportGoing' => $receivedSupportGoing,
                'receivedSupportCompleted' => $receivedSupportCompleted,
                'going' => $going,
                'completed' => $completed,
            ]);
        }

        $supportIds = $user->support()
            ->where('hackathon_id', $hackathon->id)
            ->select('supports.id');

        $going = Support::query()
            ->whereIn('id', $supportIds)
            ->where('is_completed', false)
            ->with('messages.user')
            ->latest()
            ->paginate(6);

        $completed = Support::query()
            ->whereIn('id', $supportIds)
            ->where('is_completed', true)
            ->with('messages.user')
            ->latest()
            ->paginate(6);

        return response()->json([
            'going' => $going,
            'completed' => $completed,
        ]);
    }

    public function store(StoreSupportRequest $request, Hackathon $hackathon): RedirectResponse
    {
        if (!Gate::check('createSupport', [Support::class, $hackathon])) {
            abort(404);
        }

        $user = auth()->user();

        $type = $request->input('type');
        $message = $request->input('message');

        if ($type !== Support::BUG && $user->isHackathonStaff($hackathon)) {
            return back()->with('error', 'Персонал хакатона не может создать такое обращение');
        }

        $support = $user->support()->create([
            'hackathon_id' => $hackathon->id,
            'type' => $type,
        ]);

        $support->messages()->create([
            'user_id' => $user->id,
            'message_type' => SupportMessage::USER,
            'message' => $message,
        ]);

        switch ($type) {
            case Support::BUG:
                $admins = User::whereHas('roles', function ($query) {
                    $query->whereIn('role_id', Role::ADMINS);
                })->get();

                foreach ($admins as $admin) {
                    $admin->notify(new NewSupportNotification($support));
                }
                break;

            case Support::QUESTION:
            case Support::SUGGESTION:
                $staff = $hackathon->users()
                    ->wherePivotIn('role_id', Role::STAFF)
                    ->get();

                foreach ($staff as $staffMember) {
                    $staffMember->notify(new NewSupportNotification($support));
                }
                break;
        }

        return back()->with('status', 'Обращение создано');
    }

    public function answer(AnswerSupportRequest $request, Support $support): RedirectResponse
    {
        if(!Gate::check('answer', $support)) {
            abort(404);
        }

        $user = auth()->user();

        $message = $request->input('message');

        $support->messages()->create([
            'user_id' => $user->id,
            'message_type' => SupportMessage::SUPPORT,
            'message' => $message,
        ]);

        $support->update([
            'is_completed' => true,
            'closed_by' => $user->id,
            'closed_at' => now(),
        ]);

        return back()->with('status', 'Вопрос закрыт');
    }
}
