<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

use app\api\model\RoomList;

/**
 * 处理数据
 * @param  string  $data  数组
 * @param  integer $key 参数名
 * @param  integer $defalut 默认值
 * @param  integer $filter 过滤规则
 * @param  integer $canempty 是否必填
 * @return array
 * @author 宋占弟
 * @date 2018-11-7
 * @version 1.0
 */
if (!function_exists('my_input')) {
	function my_input ($data, $key, $defalut = null, $filter = '', $canempty = true){
		if (!isset($data[$key]) && $canempty) {
			exit("{\"status\": 0,\"msg\": \"参数" . $key . "不能为空\"}");
		} else if (!isset($data[$key]) && !$canempty) {
			return false;
		} else {
			switch ($filter) {
				case 'trim':
					$value = trim($data[$key]);
					break;
				case 'intval':
					$value = intval($data[$key]);
					break;
			}
			if (empty($value) &&  $canempty) {
				exit("{\"status\": 0,\"msg\": \"参数" . $key . "不能为空\"}");
			}
		}

		return $value;
	}
}

/**
 * @cc curl_post请求
 * @param $url
 * @param string $xml_data
 * @param bool $cert
 * @return mixed
 *
 * @author seaboyer@163.com
 * @date 2018-09-21
 * @version 1.0
 */
if (!function_exists('curl_post')) {
	function curl_post($url, $xml_data = '', $cert = false)
	{
		if (extension_loaded('curl')) {
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_TIMEOUT, 30);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_POST, true);
			if ($cert) {
				curl_setopt($curl, CURLOPT_SSLCERTTYPE, 'PEM');
				curl_setopt($curl, CURLOPT_SSLCERT, WX_API_CERT);
				curl_setopt($curl, CURLOPT_SSLKEYTYPE, 'PEM');
				curl_setopt($curl, CURLOPT_SSLKEY, WX_API_KEY);
			}
			if ($xml_data) {
				curl_setopt($curl, CURLOPT_POSTFIELDS, $xml_data);
			}
			$res = curl_exec($curl);
			curl_close($curl);
		} else {
			$res = file_get_contents($url);
		}
		return $res;
	}
}

/**
 * @cc curl_get请求
 * @param $url
 * @param string $xmldata
 * @param bool $cert
 * @return mixed
 *
 * @author seaboyer@163.com
 * @date 2018-09-21
 * @version 1.0
 */
if (!function_exists('curl_get')) {
	function curl_get($url)
	{
		if (extension_loaded('curl')) {
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_TIMEOUT, 30);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_POST, false);
			$res = curl_exec($curl);
			curl_close($curl);
		} else {
			$res = file_get_contents($url);
		}
		return $res;
	}
}

/**
 * @cc 实例化memcache
 *
 * @param int $uid
 * @return int
 *
 * @author seaboyer@163.com
 * @date 2018-09-07
 * @version 1.0
 */
if (!function_exists('get_memcache')) {
	function get_memcache($config = [])
	{
		$memcache = null;
		if (empty($config)) {
			$host = config("memcache_host");
			$port = config("memcache_port");
		} else {
			$host = isset($config["host"]) ? trim($config["host"]) : '';
			$port = isset($config["port"]) ? intval($config["port"]) : '';
		}

		//empty($host) ?  '172.17.30.30' : trim($host);
		empty($port) ?  '11211' : intval($port);
		if (!empty($host)) {
			$memcache = new Memcache();
			$memcache->connect($host, $port);
		}

		return $memcache;
	}
}

/**
 * Notes:添加房间人数
 * User: SongZhanDi
 * Date: 2019/1/15
 * Time: 15:54
 * @param $room_id
 * @return array|int|string
 */
if (!function_exists('add_memcache_num')) {
	function add_memcache_num ($room_id)
	{
		$config = [
			'host' => '127.0.0.1',
			'port' => 11211
		];

		$memcache = get_memcache($config);
		$type_list = "undercover_" . $room_id;
		$num = $memcache->get($type_list);

		if (!empty($num)) {
			$num++;
		} else {
			$num = 1;
		}
		$expireTime = 60 * 60 * 24 * 1;
		$memcache->set($type_list, $num, false, $expireTime);
	}
}

/**
 * Notes:获取房间人数
 * User: SongZhanDi
 * Date: 2019/1/15
 * Time: 15:54
 * @param $room_id
 * @return array|int|string
 */
if (!function_exists('get_memcache_num')) {
	function get_memcache_num ($room_id)
	{
		$config = [
			'host' => '127.0.0.1',
			'port' => 11211
		];

		$memcache = get_memcache($config);
		$type_list = "undercover_" . $room_id;
		$num = $memcache->get($type_list);

		if (empty($num)) {
			$num = 0;
		}

		return $num;
	}

}

/**
 * Notes:删除房间人数
 * User: SongZhanDi
 * Date: 2019/1/15
 * Time: 15:54
 * @param $room_id
 * @return array|int|string
 */
if (!function_exists('del_room_list')) {
	function del_room_list ($room_id, $client_id)
	{
		$config = [
			'host' => '127.0.0.1',
			'port' => 11211
		];

		$memcache = get_memcache($config);
		$type_list = "room_list_" . $room_id;
		$list = $memcache->get($type_list);

		$user = [];
		if ($list == '') {
			// 关闭房间
			$m_RoomList = new RoomList();
			$m_RoomList->updateInfo($room_id, ['status'=>2]);
		} else {
			$user = $list[$client_id];
			unset($list[$client_id]);
			if (empty($list)) {
				// 关闭房间
				$m_RoomList = new RoomList();
				$m_RoomList->updateInfo($room_id, ['status'=>2]);
			}
			$expireTime = 60 * 60 * 24 * 1;
			$memcache->set($type_list, $list, false, $expireTime);
		}
		return $user;
	}
}

/**
 * Notes:添加房间人数
 * User: SongZhanDi
 * Date: 2019/1/15
 * Time: 15:54
 * @param $room_id
 * @return array|int|string
 */
if (!function_exists('add_room_list')) {
	function add_room_list ($room_id, $client_id, $data)
	{
		$config = [
			'host' => '127.0.0.1',
			'port' => 11211
		];

		$memcache = get_memcache($config);
		$type_list = "room_list_" . $room_id;
		$list = $memcache->get($type_list);

		if ($list == '') {
			$list = [];
		}
		$list[$client_id] = $data;

		$expireTime = 60 * 60 * 24 * 1;
		$memcache->set($type_list, $list, false, $expireTime);
		return $list;
	}
}

/**
 * Notes:获取房间人数
 * User: SongZhanDi
 * Date: 2019/1/15
 * Time: 15:54
 * @param $room_id
 * @return array|int|string
 */
if (!function_exists('get_room_list')) {
	function get_room_list ($room_id)
	{
		$config = [
			'host' => '127.0.0.1',
			'port' => 11211
		];

		$memcache = get_memcache($config);
		$type_list = "room_list_" . $room_id;
		$list = $memcache->get($type_list);

		return $list;
	}
}

/**
 * Notes:添加房间id
 * User: SongZhanDi
 * Date: 2019/1/15
 * Time: 15:54
 * @param $room_id
 * @return array|int|string
 */
if (!function_exists('add_memcache_room')) {
	function add_memcache_room ($client_id, $room_id)
	{
		$config = [
			'host' => '127.0.0.1',
			'port' => 11211
		];

		$memcache = get_memcache($config);
		$type_list = "room_id_" . $client_id;
		$expireTime = 60 * 60 * 24 * 1;

		$memcache->set($type_list, $room_id, false, $expireTime);
	}
}

/**
 * Notes:获取房间id
 * User: SongZhanDi
 * Date: 2019/1/15
 * Time: 15:54
 * @param $room_id
 * @return array|int|string
 */
if (!function_exists('get_memcache_room')) {
	function get_memcache_room ($client_id)
	{
		$config = [
			'host' => '127.0.0.1',
			'port' => 11211
		];

		$memcache = get_memcache($config);
		$type_list = "room_id_" . $client_id;
		$room_id = $memcache->get($type_list);

		return $room_id;
	}
}

/**
 * @cc curl_get请求
 * @param $url
 * @param string $xmldata
 * @param bool $cert
 * @return mixed
 *
 * @author seaboyer@163.com
 * @date 2018-09-21
 * @version 1.0
 */
if (!function_exists('curl_get')) {
	function curl_get($url)
	{
		if (extension_loaded('curl')) {
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_TIMEOUT, 30);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_POST, false);
			$res = curl_exec($curl);
			curl_close($curl);
		} else {
			$res = file_get_contents($url);
		}
		return $res;
	}
}

/**
 * Notes:添加开始游戏用户id
 * User: SongZhanDi
 * Date: 2019/1/15
 * Time: 15:54
 * @param $room_id
 * @return array|int|string
 */
if (!function_exists('add_start_game_list')) {
	function add_start_game_list ($room_id, $list)
	{
		$config = [
			'host' => '127.0.0.1',
			'port' => 11211
		];

		$memcache = get_memcache($config);
		$type_list = "start_game_" . $room_id;
		$expireTime = 60 * 60 * 24 * 1;

		$memcache->set($type_list, $list, false, $expireTime);
	}
}

/**
 * Notes:添加开始游戏用户id
 * User: SongZhanDi
 * Date: 2019/1/15
 * Time: 15:54
 * @param $room_id
 * @return array|int|string
 */
if (!function_exists('get_start_game_list')) {
	function get_start_game_list ($room_id, $uid)
	{
		$config = [
			'host' => '127.0.0.1',
			'port' => 11211
		];

		$memcache = get_memcache($config);
		$type_list = "start_game_" . $room_id;
		$list = $memcache->get($type_list);

		$data = [];
		if (!empty($list)) {
			foreach ($list as $k => $v) {
				if ($v['uid'] == $uid) {
					$data = $v;
				}
			}
		}
		return $data;
	}
}

/**
 * Notes:房主退出的时候房主给其他人
 * User: SongZhanDi
 * Date: 2019/1/15
 * Time: 15:54
 * @param $room_id
 * @return array|int|string
 */
if (!function_exists('change_homeowner')) {
	function change_homeowner ($room_id)
	{
		$config = [
			'host' => '127.0.0.1',
			'port' => 11211
		];

		$memcache = get_memcache($config);
		$type_list = "room_list_" . $room_id;
		$list = $memcache->get($type_list);

		// 房间没人
		if ($list == '') {
			return false;
		} else {// 房间有人
			foreach ($list as $k => $v) {
				$list[$k]['type'] = 1;
				break;
			}
			$expireTime = 60 * 60 * 24 * 1;
			$memcache->set($type_list, $list, false, $expireTime);
			return true;
		}
	}
}

/**
 * Notes:房主退出的时候房主给其他人
 * User: SongZhanDi
 * Date: 2019/1/15
 * Time: 15:54
 * @param $room_id
 * @return array|int|string
 */
if (!function_exists('room_exist')) {
	function room_exist ($room_id, $again =  false)
	{
		// 获取当前房间
		$m_RoomList = new RoomList();
		$room = $m_RoomList->get(['id' => $room_id]);

		// 判断是否有房间号
		if (!empty($room)) {
			if ($room['status'] == 3) {
				if (get_start_game_list($room_id, Session::get('user_info_session.id'))) {
					return ['status' => 1, 'msg' => '房间存在1','room_id'=>$room_id];
				} else {
					if ($again == false) {
						return ['status' => 0, 'msg' => '正在游戏中，不可加入'];
					} else {
						return ['status' => 1, 'msg' => '房间存在'];
					}
				}
			} else if ($room['status'] == 2) {
				return ['status' => 0, 'msg' => '房间已关闭，不可加入'];
			} else {
				// 获取当前房间人数
				$list = get_room_list($room_id);
				if (!empty($list)) {
					$num  = count($list);
				} else {
					$num = 0;
				}
				if ($num > config('undercover_num')) {
					return ['status' => 0, 'msg' => '房间人数已满'];
				} else {
					return ['status' => 1, 'msg' => '房间存在','room_id'=>$room_id];
				}
			}
		} else {
			return ['status' => 0, 'msg' => '房间不存在'];
		}
	}
}

/**
 * 添加用户密码加密
 * User: 	宋占弟
 * Date: 	2018/9/19
 * Time: 	19:35
 * @access 	public
 * @param  	string    $password 加密的密码
 * @return 	string
 */
if (!function_exists('registration_encryption')) {
	function registration_encryption ($password)
	{
		// 加密密码
		$password = md5(md5($password));
		return $password;
	}
}