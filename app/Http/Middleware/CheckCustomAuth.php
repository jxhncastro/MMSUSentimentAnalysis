<?php



namespace App\Http\Middleware;



use Closure;

use Illuminate\Http\Request;

use Symfony\Component\HttpFoundation\Response;



class CheckCustomAuth

{

    /**

     * Handle an incoming request.

     */

    public function handle(Request $request, Closure $next): Response

    {

        // Check if the 'authenticated' session variable exists and is true

        if (!$request->session()->get('authenticated')) {

            // If not logged in, redirect them back to the login page

            return redirect()->route('login');

        }



        // If they are logged in, let them proceed to the requested page

        return $next($request);

    }

}
