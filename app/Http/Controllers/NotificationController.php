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
        $perPage = min($request->get('per_page', 5), 10);

        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate($perPage)
            ->through(function ($notification) {
                $type = class_basename($notification->type);
                $data = $notification->data;

                $base = [
                    'id' => $notification->id,
                    'type' => $type,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                ];

                // === InviteNotification ===
                if ($type === 'InviteNotification') {
                    return array_merge($base, [
                        'title' => $data['title'] ?? '',
                        'description' => $data['description'] ?? '',
                        'url' => $data['url'] ?? '',
                        'send_at' => $data['send_at'] ?? null,
                        'is_active' => $data['is_active'] ?? false,
                    ]);
                }

                // === NewSupportNotification ===
                if ($type === 'NewSupportNotification' && isset($data['support']['id'])) {
                    $support = \App\Models\Support::find($data['support']['id']);

                    return array_merge($base, [
                        'support_id' => $data['support']['id'],
                        'title' => $data['support']['title'] ?? '',
                        'is_completed' => $support?->is_completed ?? false,
                    ]);
                }

                // === HackathonFinishedNotification ===
                if ($type === 'HackathonFinishedNotification') {
                    return array_merge($base, [
                        'title' => $data['title'] ?? '',
                        'description' => $data['description'] ?? '',
                        'url' => $data['url'] ?? '',
                        'send_at' => $data['send_at'] ?? null,
                    ]);
                }

                // === KickNotification ===
                if ($type === 'KickNotification') {
                    return array_merge($base, [
                        'title' => $data['title'] ?? '',
                        'description' => $data['description'] ?? '',
                        'send_at' => $data['send_at'] ?? null,
                    ]);
                }

                // === ModerateNotification ===
                if ($type === 'ModerateNotification') {
                    return array_merge($base, [
                        'title' => $data['message'] ?? '',
                        'status' => $data['status'] ?? null,
                        'comment' => $data['comment'] ?? null,
                        'send_at' => $data['send_at'] ?? null,
                    ]);
                }

                // === SupportAnsweredNotification ===
                if ($type === 'SupportAnsweredNotification' && isset($data['support_id'])) {
                    $support = \App\Models\Support::find($data['support_id']);

                    return array_merge($base, [
                        'title' => $data['title'] ?? '',
                        'description' => $data['description'] ?? '',
                        'support_id' => $data['support_id'],
                        'message' => $data['message'] ?? '',
                        'is_completed' => $support?->is_completed ?? false,
                        'send_at' => $data['send_at'] ?? null,
                    ]);
                }

                // fallback
                return array_merge($base, [
                    'title' => 'Неизвестное уведомление',
                ]);
            });

        return Inertia::render('Notification/Index', [
            'notifications' => $notifications,
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
