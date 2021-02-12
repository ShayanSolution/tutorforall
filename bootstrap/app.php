<?php

use Dusterio\LumenPassport\LumenPassport;

require_once __DIR__.'/../vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/../');
    $dotenv->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    //
}

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    realpath(__DIR__.'/../')
);
$app->withFacades();
$app->withEloquent();

class_alias(\LaravelFCM\Facades\FCM::class, 'FCM');

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/
$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);
$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);
// load cors configurations
$app->configure('cors');
// load mail configurations
$app->configure('mail');
// load mail services
$app->configure('services');
// load constatns configurations
$app->configure('user-constants');
// load database configurations
$app->configure('database');
$app->configure('twilio');

// load alfalah configurations
$app->configure('alfalah');

$app->configure('broadcasting');
/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

 $app->middleware([
     Fruitcake\Cors\HandleCors::class,
 ]);

 $app->routeMiddleware([
     'auth' => App\Http\Middleware\Authenticate::class,
     'throttle' => App\Http\Middleware\ThrottleRequests::class,
     'scopes'   => \Laravel\Passport\Http\Middleware\CheckScopes::class,
     'scope'    => \Laravel\Passport\Http\Middleware\CheckForAnyScope::class,
     'student' => App\Http\Middleware\CheckStudent::class,
     'tutor' => App\Http\Middleware\CheckTutor::class,
     'admin' => App\Http\Middleware\CheckAdmin::class
 ]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

$app->register(App\Providers\AppServiceProvider::class);
$app->register(App\Providers\AuthServiceProvider::class);
$app->register(App\Providers\EventServiceProvider::class);
$app->register(App\Providers\RepositoriesServiceProvider::class);
$app->register(Laravel\Passport\PassportServiceProvider::class);
$app->register(Dusterio\LumenPassport\PassportServiceProvider::class);
$app->register(Fruitcake\Cors\CorsServiceProvider::class);
$app->register(\Illuminate\Mail\MailServiceProvider::class);
$app->register(Flipbox\LumenGenerator\LumenGeneratorServiceProvider::class);
$app->register(\LaravelFCM\FCMServiceProvider::class);
$app->register(Jcf\Geocode\GeocodeServiceProvider::class);
$app->register('Sentry\Laravel\ServiceProvider');
$app->register(\Illuminate\Broadcasting\BroadcastServiceProvider::class);

LumenPassport::routes($app);

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->router->group([
    'namespace' => 'App\Http\Controllers'
], function ($router) {
    require __DIR__.'/../routes/web.php';
});

require __DIR__.'/../routes/channels.php';

return $app;
