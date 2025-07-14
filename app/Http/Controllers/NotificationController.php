<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $per_page = $request->get('per_page') ?? 2;
        if ($per_page > 10) {
            $per_page = 10;
        }
        return Inertia::render('Notification/Index', [
            'notifications' => $request->user()->notifications()->latest()->paginate($per_page),
        ]);
    }

    public function markAsRead(Request $request): RedirectResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array'],
        ]);

        foreach ($ids as $id) {
            $notification = auth()->user()->notifications()->findorFail($id);
            $notification->markAsRead();
        }

        return redirect()->back();
    }
}
