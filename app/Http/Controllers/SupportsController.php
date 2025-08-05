<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnswerSupportRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\StoreSupportRequest;
use App\Models\Hackathon;
use App\Models\Support;
use App\Models\SupportMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SupportsController extends Controller
{
    public function index(Hackathon $hackathon): JsonResponse
    {
        if (!Gate::check('viewAny', [Support::class, $hackathon])) {
            abort(404);
        }

        $user = auth()->user();

        if ($user->isHackathonStaff($hackathon)) {
            $receivedSupportIds = $hackathon->support()->select('supports.id');
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

        return back()->with('status', 'Обращение создано');
    }

    public function answer(AnswerSupportRequest $request): RedirectResponse
    {
        $support = Support::findOrFail($request->input('support_id'));
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

        return back()->with('status', 'Ответ отправлен');
    }

    public function close(Request $request, Hackathon $hackathon): RedirectResponse
    {
        $support = Support::findOrFail($request->input('support_id'));

        $user = auth()->user();

        if (!Gate::check('answer', $support)) {
            abort(404);
        }

        $support->update([
            'is_completed' => true,
            'closed_by' => $user->id,
            'closed_at' => now(),
        ]);

        return back()->with('status', 'Обращение закрыто');
    }

    public function update(Request $request, Support $support)
    {
    }

    public function destroy(Support $support)
    {
    }
}
