<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\NotificationStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function getNotifications(Request $request){
        $userId = Auth::user()->id;
        if ($userId){
            $notifications = NotificationStatus::with('notification')->where('receiver_id', $userId)->get();
            $UnReadNotification = NotificationStatus::where('receiver_id', $userId)->where('read_status', 0)->get();
            $unReadNotificationCount = count($UnReadNotification);
            return response()->json(
                [
                    'notifications' => $notifications,
                    'unread_notification_count' => $unReadNotificationCount
                ]
            );
        } else {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to find User'
                ], 422
            );
        }
    }

    public function notificationReadStatus(Request $request){
        $userId = Auth::user()->id;
        if ($userId){
            $readStatusUpdate = NotificationStatus::where('id', $request->notification_status_id)->update(['read_status' => $request->read_status]);
            if ($readStatusUpdate) {
                return response()->json(
                    [
                        'status' => 'success',
                        'message' => 'Notification read successfully'
                    ], 200
                );
            } else {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Unable to find Notification'
                    ], 422
                );
            }
        } else {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to find User'
                ], 422
            );
        }
    }
}
