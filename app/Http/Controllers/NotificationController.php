<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function read(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);

        $notification->markAsRead();
        User::forgetUnreadNotificationsCount($request->user()->id);

        $url = $notification->data['url'] ?? null;

        $safe = is_string($url) && str_starts_with($url, '/') && ! str_starts_with($url, '//');

        return redirect($safe ? $url : route('notifications.index'));
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();
        User::forgetUnreadNotificationsCount($request->user()->id);

        return back()->with('status', __('Hammasi o‘qilgan deb belgilandi'));
    }
}
