<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnswerSupportRequest;
use App\Http\Requests\StoreSupportRequest;
use App\Http\Resources\SupportResource;
use App\Models\Hackathon;
use App\Models\Role;
use App\Models\Support;
use App\Models\SupportMessage;
use App\Models\User;
use App\Notifications\NewSupportNotification;
use App\Notifications\SupportAnsweredNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class SupportsController extends Controller
{
    public function index(Hackathon $hackathon): JsonResponse
    {
        if (!Gate::check('viewAny', [Support::class, $hackathon])) {
            abort(403);
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
                'receivedSupportGoing' => SupportResource::collection($receivedSupportGoing),
                'receivedSupportCompleted' => SupportResource::collection($receivedSupportCompleted),
                'going' => SupportResource::collection($going),
                'completed' => SupportResource::collection($completed),
            ]);
        }

        $supportIds = $user
            ->support()
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
            'going' => SupportResource::collection($going),
            'completed' => SupportResource::collection($completed),
        ]);
    }

    public function store(StoreSupportRequest $request, Hackathon $hackathon): RedirectResponse
    {
        if (!Gate::check('createSupport', [Support::class, $hackathon])) {
            abort(403);
        }

        $user = auth()->user();

        $request->validated();

        $type = $request->input('type');
        $message = $request->input('message');

        if ($type !== Support::BUG && $user->isHackathonStaff($hackathon)) {
            return back()->with('error', __('staff_cannot_create_ticket'));
        }

        $support = $user->support()->create([
            'hackathon_id' => $hackathon->id,
            'type' => $type,
        ]);

        $support->messages()->create([
            'user_id' => $user->id,
            'message_type' => SupportMessage::USER,
            'message' => $message,
            'locale' => app()->getLocale(),
        ]);

        $support->load(['hackathon', 'messages']);

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
                $staff = $hackathon
                    ->users()
                    ->wherePivotIn('role_id', Role::STAFF)
                    ->get();

                foreach ($staff as $staffMember) {
                    $staffMember->notify(new NewSupportNotification($support));
                }

                if (!$staff->contains($hackathon->owner)) {
                    $hackathon->owner->notify(new NewSupportNotification($support));
                }

                break;
        }

        return back()->with('status', __('support_created'));
    }

    public function answer(AnswerSupportRequest $request, Support $support): RedirectResponse
    {
        if (!Gate::check('answer', $support)) {
            abort(403);
        }

        $user = auth()->user();

        $message = $request->input('message');

        $support->messages()->create([
            'user_id' => $user->id,
            'message_type' => SupportMessage::SUPPORT,
            'message' => $message,
            'locale' => app()->getLocale(),
        ]);

        $support->update([
            'is_completed' => true,
            'closed_by' => $user->id,
            'closed_at' => now(),
        ]);

        $support->load(['hackathon', 'closer', 'messages', 'creator']);

        $support->creator->notify(new SupportAnsweredNotification($support));

        return back()->with('status', __('ticket_closed'));
    }

    public function read(Support $support): void
    {
        if (!Gate::check('read', $support)) {
            abort(403);
        }

        $support->update([
            'is_read' => true,
        ]);
    }
}
