<?php

namespace App\Http\Controllers\Divers;

use App\Http\Controllers\Controller;
use App\Models\Ticket\Ticket;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;

class NotificationsController extends Controller
{
    function allNotifications(): View|Factory|Application
    {
        return view('divers.notifications-liste',[
            'notifications' => \Auth::user()->notifications()->get(),
        ]);
    }

     function showNotification($notificationId): RedirectResponse
    {
        $notification = \Auth::user()->notifications()->whereId($notificationId)->get()->last();
        $notification->markAsRead();
        $ticket = Ticket::findOrFail($notification->data['ticket_id']);
        return to_route('ticket.show-ticket', $ticket);
    }
}
