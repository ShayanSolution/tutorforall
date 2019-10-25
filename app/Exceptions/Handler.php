<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,

        SessionExpired::class,
        SessionBookedStartedOrEnded::class,
        CouldNotMarkSessionAsBooked::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        if ($e instanceof AuthorizationException) {
            return response()->json((['status' => 403, 'message' => 'Insufficient privileges to perform this action']), 403);
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return response()->json((['status' => 405, 'message' => 'Method Not Allowed']), 405);
        }

        if ($e instanceof NotFoundHttpException) {
            return response()->json((['status' => 404, 'message' => 'The requested resource was not found']), 404);
        }

        if ($e instanceof SessionExpired) {
            return response()->json(['status' => 'error', 'message' => 'Session Expired']);
        }

        if ($e instanceof SessionBookedStartedOrEnded) {
            return response()->json(['status' => 'fail', 'message' => 'Session already booked!']);
        }

        if ($e instanceof CouldNotMarkSessionAsBooked) {
            return response()->json(['status' => 'fail', 'message' => 'Oops! Session booking failed!']);
        }

        return parent::render($request, $e);
    }
}
