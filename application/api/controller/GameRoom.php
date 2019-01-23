<?php

namespace app\api\controller;

use app\api\model\RoomList;
use think\facade\Session;

use \GatewayWorker\Lib\Gateway;

class GameRoom extends BaseController
{
	protected $RoomList;

	public function __construct()
	{
		parent::__construct();

		// 实例化房间列表类
		$this->m_RoomList = new RoomList();

		Gateway::$registerAddress = '192.168.1.189:1238';
	}

	/**
	 * Notes:游戏房间是否存在
	 * User: SongZhanDi
	 * Date: 2019/1/10
	 * Time: 16:27
	 * @return \think\response\Json
	 */
	public function room_here()
	{
		//接收参数
		$req = $this->requestParam;

		// 获取房间id
		$room_id = my_input($req, 'room_id', '', 'intval');

		// 获取当前房间
		$room = $this->m_RoomList->get(['id' => $room_id]);

		// 判断房间是否存在
		return json(room_exist($room_id));
	}

	/**
	 * Notes:退出游戏房间
	 * User: SongZhanDi
	 * Date: 2019/1/16
	 * Time: 14:01
	 */
	public function exit_room()
	{
		//接收参数
		$req = $this->requestParam;

		// 链接id
		$client_id = my_input($req, 'client_id', '', 'trim');
		// 获取房间id
		$room_id = get_memcache_room($client_id);

		// 从房间的客户端列表中删除
		$user        = del_room_list($room_id, $client_id);
		$new_message = ['type' => 'exit_room', 'client_id' => $client_id, 'nickname' => htmlspecialchars($user['nickname']), 'avatar_url' => $user['avatar_url'], 'time' => date('Y-m-d H:i:s')];

		// 转播给当前房间的所有客户端，xx退出游戏房间 message {type:login, client_id:xx, name:xx}
		Gateway::sendToGroup($room_id, json_encode($new_message));

		// 获取当前房间
		$room = $this->m_RoomList->get(['id' => $room_id]);

		// 房主退出房间
		if ($user['type'] == 1 && $room['status'] == 1) {
			// 房主给其他人
			if (change_homeowner($room_id)) {
				// 获取房间内所有用户列表
				$data = [];
				$clients = get_room_list($room_id);
				$clients_list = [];
				foreach ($clients as $k => $v) {
					$v['client_id'] = $k;
					$clients_list[] = $v;
				}
				// 给当前用户发送用户列表
				$data['type'] = 'change_homeowner';
				$data['client_list'] = $clients_list;
				Gateway::sendToGroup($room_id, json_encode($data));
			}
		}
	}
}
