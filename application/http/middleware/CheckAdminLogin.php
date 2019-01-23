<?php

namespace app\http\middleware;

use think\facade\Session;

class CheckAdminLogin
{
    public function handle($request, \Closure $next)
    {
		if (empty(Session::get('user'))) {
			return redirect('/login_view');
		}

		return $next($request);
    }
}
