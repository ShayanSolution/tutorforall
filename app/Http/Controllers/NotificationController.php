<?php

namespace App\Http\Controllers;

use App\Helpers\Push;
use App\Jobs\ReachedNotification;
use App\Models\Notification;
use App\Models\NotificationStatus;
use App\Models\Session;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class NotificationController extends Controller
{
    public function getNotifications(Request $request){
        $userId = Auth::user()->id;
        if ($userId){
            $notifications = NotificationStatus::with('notification')->where('receiver_id', $userId)->orderBy('id', 'desc')->get();
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

    public function reachedNotification(Request $request){
        $this->validate($request,[
            'device_token' => 'required',
            'to' => 'required',
            'session_id' => 'required'
        ]);
        $session = Session::where('id', $request->session_id)->first();
        if ($session) {
            $session->update([
                'tracking_on' => 0,
                'start_session_enable' => 1
            ]);
        }
        $device_token = $request->device_token;
        $to = $request->to;
        $user = User::where('device_token', $device_token)->first();
        if ($user){
            $customData = array(
                'notification_type' => 'reached_notification',
            );
            $title = Config::get('');
            $body = 'Your '. $to .' has arrived.';
            Push::handle($title, $body, $customData, $user);
            return [
                'status' => 'success',
                'messages' => 'Notification sent successfully',
            ];
        } else {
            return [
                'status' => 'error',
                'messages' => 'Device token not exist.',
                ];
        }
    }
}
