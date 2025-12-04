<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * For API routes we return a JSON 401 instead of performing an HTTP redirect,
     * which avoids sending the Laravel homepage when the client didn't send
     * an `Accept: application/json` header.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        // If the request is for the API (prefix `api/*`) or explicitly expects JSON,
        // throw an HttpResponseException with a JSON 401 payload.
        if ($request->is('api/*') || $request->expectsJson()) {
            throw new HttpResponseException(response()->json([
                'message' => 'Non authentifié.',
            ], 401));
        }

        // Otherwise keep the default behaviour (redirect to login page) if the
        // named route exists. If no `login` route is defined we return null so
        // the framework won't throw a RouteNotFoundException.
        if (Route::has('login')) {
            return route('login');
        }

        return null;
    }
}
