<?php

/**
 * ============================================================================
 * 源码科技聚会小游戏
 * 版权所有: 	2018-2066 北京猿马网络技术有限公司，并保留所有权利。
 * 作者:		宋占弟
 * 联系QQ:	1029134683
 * Date: 	2019/1/16
 * Time: 	15:39
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！未经本公司授权您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * 登陆控制器
 */

namespace app\admin\controller;

use think\Controller;
use app\admin\model\AdminList;
use app\admin\validate\DoLogin;
use think\facade\Session;

class Login extends Controller
{
	private $m_AdminList;

	public function __construct(\think\App $app = null)
	{
		parent::__construct($app);
		// 实例化用户模型
		$this->m_AdminList 		= new AdminList();
	}

	/**
	 * Notes:登陆页面
	 * User: SongZhanDi
	 * Date: 2019/1/18
	 * Time: 9:22
	 * @return mixed|void
	 */
	public function login_view ()
	{
		// 没登陆跳到登陆页面
		if (Session::get('user.id')) return $this->redirect('/admin');
		return $this->fetch('login/login');
	}

	/**
	 * Notes:	登陆网站
	 * User: 	宋占弟
	 * Date: 	2019/1/18
	 * Time: 	19:26
	 * @return  \think\response\Json
	 */
	public function do_login ()
	{
		// 接收参数
		// 用户名
		$username 	= input('post.username','','trim');
		// 密码
		$password 	= input('post.password','123456','trim');

		$data = [
			'username'  => $username,
			'password'  => $password,
		];

		// 验证参数
		$validate = new DoLogin();
		if (!$validate->check($data)) {
			return json(['status'=>0, 'msg'=>$validate->getError()]);
		}

		// 判断添加的用户是否存在
		if (!user_exists($username)) {
			return json(['status'=>0, 'msg'=>'用户名不存在']);
		}

		// 获取用户信息
		$user = $this->m_AdminList->getInfo(['username'=>$username]);

		// 加密密码
		$password = registration_encryption($password);

		// 验证密码
		if ($user['password'] != $password) return json(['status'=>0, 'msg'=>'密码不正确']);

		// 登陆
		Session::set('user', $user);

		return json(['status'=>1, 'msg'=>'登陆成功']);
	}

	/**
	 * Notes:注销登录
	 * User: SongZhanDi
	 * Date: 2018/8/28
	 * Time: 18:17
	 */
	public function login_out()
	{
		// 清除session信息
		Session::delete('user');
		Session::delete('companyArr');
		return $this->redirect('/loginView');
	}

}
