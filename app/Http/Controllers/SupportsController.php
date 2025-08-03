<?php

namespace App\Http\Controllers;

use App\Models\Hackathon;
use App\Models\Support;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportsController extends Controller
{
    public function index(Hackathon $hackathon): JsonResponse
    {
        $user = auth()->user();

        if ($user->isHackathonStaff($hackathon)) {
            $receivedSupportIds = $hackathon->support()->select('supports.id');
            $supportIds = $user->support()->where('hackathon_id', $hackathon->id)->select('supports.id');

            $receivedSupportGoing = Support::query()
                ->whereIn('id', $receivedSupportIds)
                ->where('is_completed', false)
                ->with('messages')
                ->latest()
                ->paginate(6);

            $receivedSupportCompleted = Support::query()
                ->whereIn('id', $receivedSupportIds)
                ->where('is_completed', true)
                ->with('messages')
                ->latest()
                ->paginate(6);

            $going = Support::query()
                ->whereIn('id', $supportIds)
                ->where('is_completed', false)
                ->with('messages')
                ->latest()
                ->paginate(6);

            $completed = Support::query()
                ->whereIn('id', $supportIds)
                ->where('is_completed', true)
                ->with('messages')
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
            ->with('messages')
            ->latest()
            ->paginate(6);

        $completed = Support::query()
            ->whereIn('id', $supportIds)
            ->where('is_completed', true)
            ->with('messages')
            ->latest()
            ->paginate(6);

        return response()->json([
            'going' => $going,
            'completed' => $completed,
        ]);
    }

    public function store(Request $request)
    {

    }

    public function show(Support $support)
    {
    }

    public function update(Request $request, Support $support)
    {
    }

    public function destroy(Support $support)
    {
    }
}
