<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // mark one as read, then open its page
    public function read(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        // reorder alerts link to a page, low stock alerts link to a product
        if (isset($notification->data['url'])) {
            return redirect($notification->data['url']);
        }

        return redirect()->route('products.show', $notification->data['product_id']);
    }

    // mark every unread notification as read
    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
