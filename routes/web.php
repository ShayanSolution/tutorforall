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
/** @var \Laravel\Lumen\Routing\Router $router */
$router->get('/', function () {
    return app()->version();
});

// Generate random string
$router->get('appKey', function () {
    return str_random('32');
});


$router->post('login', 'AccessTokenController@createAccessToken');
$router->get('get-phone-code', 'AuthenticationController@getPhoneVerificationCode');
$router->post('verify-phone-code', 'AuthenticationController@postPhoneVerificationCode');
$router->post('register-student', 'AuthenticationController@postRegisterStudent');

$router->get('get-class-name', 'ProgrammeSubjectController@getProgramme');
$router->get('get-subjectby-id', 'ProgrammeSubjectController@getSubjectById');$router->post('save-programme', 'ProgrammeSubjectController@postSaveProgramme');
$router->post('save-programme-subject', 'ProgrammeSubjectController@postSaveProgrammeSubject');
$router->post('/register-tutor', 'AuthenticationController@postRegisterTutor');
$router->get('/request-categories', 'PackageController@getPackageCategories');
$router->get('/register/verify/{confirmationCode}', 'AuthenticationController@confirm');
$router->get('/user/session/{userid}', 'SessionController@getUserSession');
$router->get('/user/deserve/{id}', 'SessionController@updateDeserveStudentStatus');
$router->get('/user/active/{id}', 'UserController@updateUserActiveStatus');
$router->get('/user/remove/{id}', 'UserController@removeUser');
$router->get('/user/profile/{id}', 'UserController@userProfile');
$router->post('/update-user', 'AuthenticationController@updateUser');

Route::post('/booked','SessionController@bookedTutor');
$router->group(['middleware' => ['auth:api', 'throttle:60']], function () use ($router) {
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
    $router->post('/update-student-profile','UserController@updateStudentProfile');
    $router->post('/tutor-notification','UserController@tutorSessionInfo');
    $router->post('/package-cost', 'PackageController@packageCost');
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

    Route::post('/update-device-token','UserController@updateUserDeviceToken');

    Route::post('/get-user','UserController@getUser');

    Route::post('/update-session-status','SessionController@updateSessionStatus');
        
});



