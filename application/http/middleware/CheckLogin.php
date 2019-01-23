<?php

namespace app\http\middleware;

use think\facade\Session;

class CheckLogin
{
    public function handle($request, \Closure $next)
    {
		if (empty(Session::get('user_info_session'))) {
			return json(['status'=>0,'msg'=>'没有登陆']);
		}

		return $next($request);
    }
}
