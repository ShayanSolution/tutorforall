<?php
use Laravel\Lumen\Routing\Router;
use Illuminate\Http\Request;
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
$router->get('get-subjectby-id', 'ProgrammeSubjectController@getSubjectById');
$router->post('save-programme', 'ProgrammeSubjectController@postSaveProgramme');
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
    $router->post('/wallet', 'WalletController@wallet');
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
    // Tutor Payment against session
    Route::post('/tutor_session_payments_detail', 'SessionController@tutorSessionPaymentsDetail');
    //Tutor get Invoice
    Route::get('/get_tutor_invoices', 'TutorInvoiceController@getTutorInvoice');
    //Tutor pay invoice
    Route::post('/tutor_pay_invoice', 'TutorInvoiceController@tutorPayInvoice');
    //Tutor Wallet debit/credit detail
    Route::post('/tutor_wallet_detail', 'WalletController@tutorWalletDetail');

	Route::post('/teacher-card-invoice-payment', 'TutorInvoiceController@teacherCardInvoicePayment');
	//Start demo session
    Route::post('/demo-start', 'SessionController@demoStart');
    //End demo session
    Route::post('/demo-end', 'SessionController@demoEnd');
    //Demo review from student
    Route::post('/demo-review', 'SessionController@demoReview');
    //Use wallet first
    Route::post('/use-wallet-first', 'WalletController@useWalletFirst');
    //accept term and condition
    Route::post('/accept-term-and-condition', 'UserController@acceptTermAndCondition');
    //get term and condition
    Route::get('/get-term-and-condition', 'UserController@getTermAndCondition');
    //read banner
    Route::post('/read-banner', 'BannerController@readBanner');
    //get term and condition
    Route::get('/get-banner', 'BannerController@getBanner');
    //Cancel session during find tutor by student
    Route::post('/cancel-finding-session', 'SessionController@cancelFindingSession');
});
Route::post('/session-payment-jazz-cash', 'SessionController@sessionPayment');
Route::post('/tutor-pay-invoice-jazz-cash', 'TutorInvoiceController@tutorPayInvoice');
Route::get('get-password-reset-code', 'AuthenticationController@getPasswordResetCode');
Route::post('reset-password', 'AuthenticationController@resetPassword');



