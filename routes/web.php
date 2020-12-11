<?php
use Laravel\Lumen\Routing\Router;
use Illuminate\Http\Request;
use Davibennun\LaravelPushNotification\Facades\PushNotification;
use Symfony\Component\HttpFoundation\File\File;
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

Route::get('/test-push', function () {
    $message = PushNotification::Message(
        'Arslan Ali wants a session with you',
        array(
            'badge' => 1,
            'sound' => 'example.aiff',
            'actionLocKey' => 'Action button title!',
            'locKey' => 'localized key',
            'locArgs' => array(
                'localized args',
                'localized args',
            ),
            'launchImage' => 'image.jpg',
            'custom' => array('custom_data' => array(
                'notification_type' => 'session_request',
                'session_id' => 1,
                'Student_Name' => 'Arslan Ali',
                'Student_id' => 1,
                'Class_Name' => 'Matric',
                'Subject_Name' => 'Maths',
                'Class_id' => 1,
                'Subject_id' => 1,
                'IS_Group' => 1,
                'Group_Members' => 'My Group',
                'IS_Home' => 1,
                'Hourly_Rate' => '10',
                'Longitude' =>  '0.0000',
                'Latitude' => '0.0000',
                'Session_Location' => 'My home',
            ))
        ));





    $optionBuilder = new \LaravelFCM\Message\OptionsBuilder();
    $optionBuilder->setTimeToLive(60*20);

    $notificationBuilder = new \LaravelFCM\Message\PayloadNotificationBuilder('my title');
    $notificationBuilder->setBody('Hello world')
        ->setSound('default');

    $dataBuilder = new \LaravelFCM\Message\PayloadDataBuilder();
    $dataBuilder->addData(['a_data' => 'my_data']);

    $option = $optionBuilder->build();
    $notification = $notificationBuilder->build();
    $data = $dataBuilder->build();

    //prod
    $token = "cq87UNVJ-Fw:APA91bE-55wajvGoRlQ0M-vXVA_MS3B20fSjhIA5v9T_MGh4TmTA3EmWbUwiYQ99iaQKijkr64J5fsFa-Bx5GA82JJj6y99MIJnljHaSaFvmvTpktoGn4eu28P0rhMDoClps415p_skN";

    //dev
//    $token = 'cIGAbkWEY8Q:APA91bGHJiqV82AGW7fQtOKhfGPBfjSPzeAuFWtzwtfVWjPLer1uCF_aenIviCyC_OEpPVwCR69-neCbnWZJjrHWeOrHWypXzY7Z49KMt1cKraXb_a2KsfiOc6PdYFxVpROYXOPA_chl';


    //android device token
    $token = 'cycBJTc3T7s:APA91bHZWsDPFM5kYHtKXn11qa6uc4D7kZtfoeTdK9Ch1DBoAFpk7t3eqj4cCYVY14L_HAS2CvDPkEB5VGG3UeWvD9iLJMy3tOOh62PMB8seDGruLU5naXTqzsYI4L8JdM4XTmDAFH86';
    //PushNotification::app('appNameAndroid')->to($token)->send($message);
    $downstreamResponse = \LaravelFCM\Facades\FCM::sendTo($token, $option, $notification, $data);




    //ios device token
    $token = "cq87UNVJ-Fw:APA91bE-55wajvGoRlQ0M-vXVA_MS3B20fSjhIA5v9T_MGh4TmTA3EmWbUwiYQ99iaQKijkr64J5fsFa-Bx5GA82JJj6y99MIJnljHaSaFvmvTpktoGn4eu28P0rhMDoClps415p_skN";

    //PushNotification::app('appNameAndroid')->to($token)->send($message);
    $downstreamResponse = \LaravelFCM\Facades\FCM::sendTo($token, $option, $notification, $data);

    dd("tst");








    $url = "https://fcm.googleapis.com/fcm/send";
    $serverKey = env('FCM_SERVER_KEY');
    $title = "Title";
    $body = "Body of the message";
    $notification = array('title' =>$title , 'text' => $body, 'sound' => 'default', 'badge' => '1');
    $arrayToSend = array('to' => $token, 'notification' => $notification, 'priority'=>'high');
    $json = json_encode($arrayToSend);
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Authorization: key='. $serverKey;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
//Send the request
    $response = curl_exec($ch);
//Close request
    if ($response === FALSE) {
        die('FCM Send Error: ' . curl_error($ch));
    }
    curl_close($ch);
});


/** @var \Laravel\Lumen\Routing\Router $router */
$router->get('/', function () {
    dd('Welcome to Tootar and Tootar Teacher applications API project');
    return app()->version();
});

$router->get('/twilio-test', function(){
    $toNumber = '+923093512171'; //923004412255
    $code = '2255';
    $response = \App\Helpers\TwilioHelper::sendCodeSms($toNumber, $code);
    return $response ? 'Message Sent' : 'Message Not Sent';
});

// Generate random string
$router->get('appKey', function () {
    return str_random('32');
});

$router->get('number', 'UserController@scriptNumber');
$router->get('number/list', 'UserController@scriptNumberSearch');
$router->get('number/delete', 'UserController@scriptNumberDelete');

$router->post('login', 'AccessTokenController@createAccessToken');
$router->get('get-phone-code', 'AuthenticationController@getPhoneVerificationCode');
$router->post('verify-phone-code', 'AuthenticationController@postPhoneVerificationCode');
$router->post('register', 'AuthenticationController@postRegister');

$router->get('get-class-name', 'ProgrammeSubjectController@getProgramme');
$router->get('get-subjectby-id', 'ProgrammeSubjectController@getSubjectById');$router->post('save-programme', 'ProgrammeSubjectController@postSaveProgramme');
$router->post('save-programme-subject', 'ProgrammeSubjectController@postSaveProgrammeSubject');
$router->post('/register-tutor', 'AuthenticationController@postRegisterTutor');
$router->get('/request-categories', 'PackageController@getPackageCategories');
$router->get('/register/verify/{confirmationCode}', 'AuthenticationController@confirm');
$router->get('/user/session/{userid}', 'SessionController@getUserSession');
$router->get('/user/deserve/{id}', 'SessionController@updateDeserveStudentStatus');
$router->get('/user/active/{id}', 'UserController@updateUserActiveStatus');

$router->get('/user/profile/{id}', 'UserController@userProfile');
$router->post('/update-user', 'AuthenticationController@updateUser');
$router->post('admin-upload-documents', 'DocumentsController@uploadDocs');
$router->post('admin-update-tutors-document', 'DocumentsController@updateTutorsDoc');

$router->post('add_card', 'UserController@addCard');
$router->get('get_card', 'UserController@getCard');
$router->post('delete_card', 'UserController@deleteCard');




$router->group(['middleware' => ['auth:api', 'throttle:60']], function () use ($router) {

    $router->post('upload-documents', 'DocumentsController@uploadDocs');
    $router->get('tutors-documents-list', 'DocumentsController@tutorsDocsList');
    $router->post('delete-tutors-document', 'DocumentsController@deleteTutorsDoc');
    $router->post('update-tutors-document', 'DocumentsController@updateTutorsDoc');
    $router->post('all-documents-submitted', 'DocumentsController@allDocumentsSubmitted');

    $router->get('get-final-verification-code', 'AuthenticationController@getFinalVerificationCode');
    $router->post('verify-final-verification-code', 'AuthenticationController@postVerifyFinalVerificationCode');

    $router->post('add-tutors-classes-and-subjects', 'ProgrammeSubjectController@addTutorsClassesAndSubjects');

    //Dashboard Routes
    $router->get('dashboard-pie-chart-totals',  [
        'uses'       => 'UserController@getDashboardTotalOfPieCharts',
        //'middleware' => "scope:admin"
    ]);
    $router->get('my-sessions', 'SessionController@mySessions');
    $router->get('get-profile', 'UserController@getUserProfile');

    $router->post('update-location', 'AuthenticationController@postUpdateLocation');
    $router->get('get-classes', 'ProgrammeSubjectController@getAllProgrammes');
    $router->get('get-all-subjects', 'ProgrammeSubjectController@getAllSubjects');
    $router->post('get-tutors-profile', 'UserController@postTutorProfile');
    $router->get('get-class-subjects', 'ProgrammeSubjectController@getProgrammeSubjects');
    $router->get('request-sessions', 'SessionController@requestSessions');



    $router->post('/update-tutor-profile','UserController@updateTutorProfile');
    /**
     * $router->post('/update-tutor-profile','UserController@updateTutorProfile');
     * This route updates tutor's own profile with his own gender.
     **/



    $router->post('/update-tutor-profile-setting','UserController@updateTutorProfileSetting');
    /**
     * $router->post('/update-tutor-profile-setting','UserController@updateTutorProfileSetting');
     * This route updates Tutors type settings with the gender which he/she wants to teach.
     */



    $router->post('/update-student-profile','UserController@updateStudentProfile');
    $router->post('/tutor-notification','UserController@tutorSessionInfo');
    $router->post('/package-cost', 'PackageController@packageCost');
    $router->post('/session-start', 'SessionController@sessionStart');
    $router->post('/session-calculation-cost', 'SessionController@sessionCalculationCost');
    $router->post('/session-payment', 'SessionController@sessionPayment');

	$router->post('/student-card-session-payment', 'SessionController@studentCardSessionPayment');

    $router->post('/get-latest-session', 'SessionController@getLatestSession');
    $router->post('/receive_payment', 'WalletController@receivePayment');
    $router->post('/wallet_student', 'WalletController@walletStudent');
    Route::post('/session-rejected','SessionController@sessionRejected');
    $router->get('get-students', [
        'uses'       => 'UserController@getStudents',
        'middleware' => "scope:users,users:list"
    ]);

    $router->get('get-tutors', [
        'uses'       => 'UserController@getTutors',
        //'middleware' => "scope:users,users:create"
    ]);

    $router->post('users', [
        'uses'       => 'UserController@store',
        'middleware' => "scope:users,users:create"
    ]);
    $router->get('users',  [
        'uses'       => 'UserController@index',
        'middleware' => "scope:users,users:list"
    ]);
    $router->get('users/{id}', [
        'uses'       => 'UserController@show',
        'middleware' => "scope:users,users:read"
    ]);
    $router->put('users/{id}', [
        'uses'       => 'UserController@update',
        'middleware' => "scope:users,users:write"
    ]);
    $router->delete('users/{id}', [
        'uses'       => 'UserController@destroy',
        'middleware' => "scope:users,users:delete"
    ]);

    Route::post('/booked','SessionController@bookedTutor');
    
    // Find Tutor APi
    $router->post('/find-tutor', 'FindTutorController@findTutor');
    // Update User device token
    Route::post('/update-device-token','UserController@updateUserDeviceToken');
    // Get User Detail By Authentication token
    Route::post('/get-user','UserController@getUser');
    //Update User Session Status
    Route::post('/update-session-status','SessionController@updateSessionStatus');

    $router->group(['middleware' => ['tutor']], function () use ($router) {
    });

    Route::post('logout','UserController@logout');

    Route::post('add-rating','RatingController@save');
    
    //Delete user account
    Route::get('/user/remove', 'UserController@removeUser');

    //Online offline tutor
    Route::post('/online', 'UserController@online');
    // get notification when offline
    Route::post('/offline_notification', 'UserController@offlineNotification');
    // get notifications
    Route::get('/get-notifications', 'NotificationController@getNotifications');
    //read notification
    Route::post('/notification_read_status', 'NotificationController@notificationReadStatus');
    //tutor reached notification
    Route::post('/reached_notification', 'NotificationController@reachedNotification');
    // Search location
    Route::post('/search_location', 'SearchLocationController@createSearchLocation');
    Route::get('/get_search_location/{id}', 'SearchLocationController@getSearchLocation');
    // Cancel Session
    Route::post('/cancel_session', 'SessionController@cancelSession');
    // Student Payment against session
    Route::post('/student_session_payments_detail', 'SessionController@studentSessionPaymentsDetail');
    //Tutor get Invoice
    Route::get('/get_tutor_invoices', 'TutorInvoiceController@getTutorInvoice');
    //Tutor pay invoice
    Route::post('/tutor_pay_invoice', 'TutorInvoiceController@tutorPayInvoice');

	Route::post('/teacher-card-invoice-payment', 'TutorInvoiceController@teacherCardInvoicePayment');
	//Start demo session
    Route::post('/demo-start', 'SessionController@demoStart');
    //End demo session
    Route::post('/demo-end', 'SessionController@demoEnd');
});

Route::get('get-password-reset-code', 'AuthenticationController@getPasswordResetCode');
Route::post('reset-password', 'AuthenticationController@resetPassword');

Route::get("notify", function(){
    $userToken = "fI0b50hYRS4:APA91bFPdsH42J7ZRUJtFxU9vyIVwaEbg8jRDRXetI8ESWMegHvGUMWTFibxmejS0X9Ui9opKL_cBXvHws2B5-i81V_3Yk-AtmQaQH-qF0eln_4yVaWhPyHcl6dHAIgHevl-Co-up7ScCz3oVuSDZT45VvL7EnHfLQ";
    $message = PushNotification::Message( 'Cero Team wants a session with you',
    array(
        'badge' => 1,
        'sound' => 'example.aiff',
        'actionLocKey' => 'Action button title!',
        'locKey' => 'localized key',
        'locArgs' => array(
            'localized args',
            'localized args',
        ),
        'launchImage' => 'image.jpg',
        'custom' => array('custom_data' => array(

        ))
    ));
    PushNotification::app('appNameAndroid')->to($userToken)->send($message);
});



