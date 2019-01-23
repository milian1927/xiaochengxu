<?php

namespace app\api\controller;

use think\facade\Session;
use app\api\model\UserList;

class Login extends BaseController
{
	protected $m_UserList;

	public function __construct()
	{
		parent::__construct();

		// 实例化用户列表类
		$this->m_UserList = new UserList();
	}

	/**
	 * Notes:将微信用户信息存到session数组
	 * User: SongZhanDi
	 * Date: 2019/1/10
	 * Time: 16:03
	 */
	public function login()
	{
		//接收参数
		$req 	= $this->requestParam;

		// 获取openid的code
		$openid 	= my_input($req, 'openid', '', 'trim');
		// 昵称
		$nickname 	= my_input($req, 'nickname', '', 'trim');
		// 头像连接
		$avatar_url = my_input($req, 'avatar_url', '', 'trim');

		$user = $this->m_UserList->get(['openid'=>"$openid"]);
		// 没有绑定，添加绑定
		if (empty($user)) {
			$data = [
				'nickname' => $nickname,
				'openid' => $openid,
				'avatar_url' => $avatar_url,
			];
			$this->m_UserList->save($data);
		}

		// 获取绑定用户信息
		$user = $this->m_UserList->get(['openid'=>$openid]);

		//需要存session的数据
		$user_info = [
			'id' 			=> $user['id'],
			'nickname'     	=> $nickname,
			'avatar_url'  	=> $avatar_url,
		];

		Session::set('user_info_session',$user_info);
		$session_id = Session::sid();
		$user_info = [
			'uid' 			=> $user['id'],
			'nickname'     	=> $nickname,
			'avatar_url'  	=> $avatar_url,
			'session_id'  	=> $session_id,
		];
		return json(['status'=>1,'msg'=>'登陆成功','data'=>$user_info]);
	}

	public function get_openid ()
	{
		//接收参数
		$req   = $this->requestParam;

		// 获取openid的code
		$code  = my_input($req, 'code', '', 'trim');
		// appid
		$appid = config('wx_appid');
		// secret
		$secret = config('wx_secret');
		// 获取oppenid的接口地址
		$url = 'https://api.weixin.qq.com/sns/jscode2session?appid=' . $appid . '&secret=' . $secret . '&js_code=' . $code . '&grant_type=authorization_code';
		// 使用curl调用接口
		$openid = json_decode(curl_get($url),true);

		return json(['status'=>1,'msg'=>'请求成功','openid'=>$openid['openid']]);

	}
}
