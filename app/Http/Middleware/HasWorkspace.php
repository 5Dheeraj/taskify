<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class HasWorkspace
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = getAuthenticatedUser();

        // Check if the user is either a manager or a superadmin
        if ($user->hasRole('manager') || $user->hasRole('superadmin')) {
            // Proceed to the next request
            return $next($request);
        }

        // Check if the user is not a participant in any workspace
        if (session()->get('workspace_id') == 0) {
            if (!$request->ajax()) {
                return redirect(route('home.index'))->with('error', get_label('must_workspace_participant', 'You must be a participant in at least one workspace'));
            }
            return response()->json(['error' => true, 'message' => get_label('must_workspace_participant', 'You must be a participant in at least one workspace')]);
        }

        // Proceed to the next request if none of the conditions matched
        return $next($request);
    }

}
