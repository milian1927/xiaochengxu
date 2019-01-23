<?php

namespace app\api\controller;

use app\api\model\RoomList;
use think\facade\Session;

use \GatewayWorker\Lib\Gateway;

class UndercoverGame extends BaseController
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
	 * Notes:进入游戏房间
	 * User: SongZhanDi
	 * Date: 2019/1/16
	 * Time: 14:00
	 */
	public function enter_room()
	{
		//接收参数
		$req = $this->requestParam;

		// 用户id
		$uid = $this->uid;
		// 用户昵称
		$nickname = $this->nickname;
		// 头像链接
		$avatar_url = $this->avatar_url;
		// 房间id
		$room_id = my_input($req, 'room_id', '', 'intval');
		// 房主
		$type = my_input($req, 'type', '', 'intval');
		// 链接id
		$client_id = my_input($req, 'client_id', '', 'trim');

		// 判断房间是否存在
		$res = room_exist($room_id);
		if ($res['status'] == 0) return json($res);

		// 连接id和房间id绑定
		add_memcache_room($client_id, $room_id);

		// client_id与uid绑定
		Gateway::bindUid($client_id, $uid);

		// 获取开始游戏的房间人数
		$old_user = get_start_game_list($room_id, Session::get('user_info_session.id'));

		// 获取当前房间
		$room = $this->m_RoomList->get(['id' => $room_id]);

		// 转播给当前房间的所有客户端，xx进入游戏房间 message {type:login, client_id:xx, name:xx}
		if (!empty($old_user) && $room['status'] == 3) {
			$type = $old_user['type'];
			$new_message = ['type' => 'reconnect_room', 'client_id' => $client_id, 'nickname' => htmlspecialchars($nickname), 'avatar_url' => $avatar_url, 'room_status' => $room['status'],'old_user' => $old_user, 'time' => date('Y-m-d H:i:s')];
			// 获取掉线是的游戏进度
			Gateway::sendToClient($client_id, json_encode($old_user));
		} else {
			$new_message = ['type' => 'enter_room', 'client_id' => $client_id, 'nickname' => htmlspecialchars($nickname), 'avatar_url' => $avatar_url, 'room_status' => $room['status'], 'time' => date('Y-m-d H:i:s')];
		}

		// 获取用户信息
		$user = [
			'uid' => $uid,
			'client_id' => $client_id,
			'type' => $type,
			'nickname' => $nickname,
			'avatar_url' => $avatar_url,
			'avatar_url' => $avatar_url,
		];

		// 将登陆用户加入到房间用户列表
		add_room_list($room_id, $client_id, $user);

		Gateway::sendToGroup($room_id, json_encode($new_message));
		// 加入某个群组（可调用多次加入多个群组）
		Gateway::joinGroup($client_id, $room_id);

		// 获取房间内所有用户列表
		$clients = get_room_list($room_id);
		$clients_list = [];
		foreach ($clients as $k => $v) {
			$v['client_id'] = $k;
			$clients_list[] = $v;
		}
		// 给当前用户发送用户列表
		$new_message['client_list'] = $clients_list;
		Gateway::sendToClient($client_id, json_encode($new_message));

		return;
	}

	/**
	 * Notes:创建游戏房间
	 * User: SongZhanDi
	 * Date: 2019/1/16
	 * Time: 14:01
	 * @return \think\response\Json
	 */
	public function create_room()
	{
		//接收参数
		$req = $this->requestParam;

		// 房间名称
		$room_name = Session::get('user_info_session.nickname') . '创建的游戏房间';
		// 创建人
		$creater = Session::get('user_info_session.id');

		// 创建游戏房间
		$param = ['room_name'=>$room_name,'creater'=>$creater];
		$room = $this->m_RoomList->saveAllInfo([$param]);

		// 获取游戏房间的房间id
		$room_id = $room[0];
		return json(['status' => 1, 'msg' => '创建房间成功', 'room_id' => $room_id]);
	}

	/**
	 * Notes:开始游戏
	 * User: SongZhanDi
	 * Date: 2019/1/22
	 * Time: 17:50
	 */
	public function start_game()
	{
		//接收参数
		$req = $this->requestParam;

		// 房间id
		$room_id = my_input($req, 'room_id', '', 'intval');
		// 是否自定义词组
		$customize = my_input($req, 'customize', '', 'intval');

		// 获取房间用户
		$list = get_room_list($room_id);

		// 卧底数组
		$undercover = [];
		// 房主数组
		$homeowner_arr = [];

		// 获取房间人数
		$num = count($list);

		// 获取房主
		$homeowner_arr = [];
		$homeowner_arr['type'] = 'homeowner';

		// 始游戏用户数组
		$start_arr = [];
		foreach ($list as $k => $v) {
			// 获取开始游戏用户
			$start_arr[$v['uid']]['uid'] = $v['uid'];
			$start_arr[$v['uid']]['type'] = $v['type'];
			$start_arr[$v['uid']]['client_id'] = $v['type'];
			$start_arr[$v['uid']]['undercover_word'] = '';
			$start_arr[$v['uid']]['civilian_word'] = '';
			$start_arr[$v['uid']]['undercover_arr'] = [];

			// 获取房主
			if ($v['type'] == 1) {
				$homeowner_arr['client_id'] = $v['client_id'];
				$homeowner_arr['uid'] = $v['uid'];
				// 删除房主
				unset($list[$k]);
			}

		}

		// 房间有3-7个人一个卧底， 大于7个人时两个卧底
		if ($num > 3 && $num < 7) {
			$undercover[] = array_rand($list, 1);
		} else if ($num > 7 ) {
			$undercover = array_rand($list, 2);
		} else if ($num < 4) {
			return json(['status' => 0, 'msg' => '房间人数少于4人不可开始游戏']);
		}

		// 从数组中删除卧底
		foreach ($undercover as $k => $v) {
			$homeowner_undercover[] = $list[$v];
			unset($list[$v]);
		}

		// 获取词组
		$word_arr = ['黄瓜','窝瓜'];

		// 自定义词组
		if ($customize == 1) {
			// 卧底词
			$undercover_word = my_input($req, 'undercover_word', '', 'trim');
			// 平民词
			$civilian_word = my_input($req, 'civilian_word', '', 'trim');
		} else {
			// 卧底词
			$key_undercover = array_rand($word_arr);
			$undercover_word = $word_arr[$key_undercover];
			// 平民词
			unset($word_arr[$key_undercover]);
			$key_civilian = array_rand($word_arr);
			$civilian_word = $word_arr[$key_civilian];
		}

		// 添加房主开始游戏数组
		$start_arr[$homeowner_arr['uid']]['undercover_word'] = $undercover_word;
		$start_arr[$homeowner_arr['uid']]['civilian_word'] = $civilian_word;
		$start_arr[$homeowner_arr['uid']]['undercover_arr'] = $homeowner_undercover;

		// 将卧底词，贫民词，谁是卧底返给房主
		$homeowner_arr['undercover_word'] = $undercover_word;
		$homeowner_arr['civilian_word'] = $civilian_word;
		$homeowner_arr['undercover_arr'] = $homeowner_undercover;
		Gateway::sendToClient($homeowner_arr['client_id'], json_encode($homeowner_arr));

		// 添加卧底开始游戏数组
		foreach ($homeowner_undercover as $j => $l) {
			foreach ($start_arr as $k => $v) {
				if ($v['uid'] == $l['uid']) {
					$start_arr[$v['uid']]['undercover_word'] = $undercover_word;
				}
			}
		}

		// 将卧底词返给卧底
		$undercover_arr = [
			'type' => 'undercover',
			'word' => $undercover_word,
		];
		foreach ($undercover as $k => $v) {
			Gateway::sendToClient($v, json_encode($undercover_arr));
		}

		// 添加平民开始游戏数组
		foreach ($list as $j => $l) {
			foreach ($start_arr as $k => $v) {
				if ($v['uid'] == $l['uid']) {
					$start_arr[$v['uid']]['civilian_word'] = $civilian_word;
				}
			}
		}

		// 将平民词返给平民
		$civilian_arr = [
			'type' => 'civilian',
			'word' => $civilian_word,
		];
		foreach ($list as $k => $v) {
			Gateway::sendToClient($v['client_id'], json_encode($civilian_arr));
		}

		// 开始游戏
		$this->m_RoomList->updateInfo($room_id, ['status'=>3]);

		// 添加开始游戏用户
		add_start_game_list($room_id, $start_arr);
	}

	/**
	 * Notes:再来一局
	 * User: SongZhanDi
	 * Date: 2019/1/22
	 * Time: 17:50
	 * @return \think\response\Json
	 */
	public function again_game ()
	{
		//接收参数
		$req = $this->requestParam;

		// 房间id
		$room_id = my_input($req, 'room_id', '', 'intval');

		// 判断房间是否存在
		$res = room_exist($room_id, true);
		if ($res['status'] == 0) return json($res);

		// 清空游戏用户数组
		add_start_game_list($room_id, []);

		// 关闭游戏房间
		$this->m_RoomList->updateInfo($room_id, ['status'=>1]);
		$message = [
			'type' => 'again_game',
		];

		// 推送到游戏房间
		Gateway::sendToGroup($room_id, json_encode($message));
	}
}
