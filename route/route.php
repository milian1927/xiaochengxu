<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

/*------------------------------------------------------api------------------------------------------------*/
// 登陆
Route::get('/login', 'api/Login/login');

// 房间是否存在
Route::get('/room_here', 'api/GameRoom/room_here');

// 获取openid
Route::get('/get_openid', 'api/Login/get_openid');

// 获取房间id
Route::get('/exit_room', 'api/GameRoom/exit_room');

Route::group('/', function() {
	// 进入房间
	Route::post('/enter_room', 'api/UndercoverGame/enter_room');

	// 创建房间
	Route::get('/create_room', 'api/UndercoverGame/create_room');

	// 开始游戏
	Route::post('/start_game', 'api/UndercoverGame/start_game');

	// 再来一局
	Route::get('/again_game', 'api/UndercoverGame/again_game');
})->middleware('CheckLogin');
/*------------------------------------------------------api------------------------------------------------*/


/*------------------------------------------------------admin------------------------------------------------*/
// 登陆
Route::get('/login_view', 'admin/Login/login_view');

Route::group('/', function() {
	// 后台首页
	Route::get('/admin', 'admin/Index/index');
})->middleware('CheckAdminLogin');
/*------------------------------------------------------admin------------------------------------------------*/

return [

];
