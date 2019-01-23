<?php

/**
 * ============================================================================
 * 猿马科技年会小游戏
 * ----------------------------------------------------------------------------
 * 所有标准Controller类的基类，其他业务类继承本类后实现逻辑
 * ----------------------------------------------------------------------------
 * 版权所有: 	2018-2066 北京猿马网络技术有限公司，并保留所有权利。
 * 作者:		seaboyer
 * 邮箱:		seaboyer@163.com
 * Date: 	2018-09-06
 * Time: 	15:39
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！未经本公司授权您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 */

namespace app\api\controller;

use think\Controller;
use think\facade\Request;
use think\facade\Session;

use think\Db;

class BaseController extends Controller
{
    protected $requestParam;
    protected $requestAction;
    protected $requestType;
    protected $uid;
    protected $auth;
    protected $isDebug;
    protected $authCompanyid;
    protected $rowWithData;
    protected $nickname;
    protected $avatar_url;

	public function __construct()
    {
        parent::__construct();

		// 实例化用户信息
		$this->user_info_session = Session::get('user_info_session');
		// 用户id
		$this->uid      = $this->user_info_session['id'];
		// 用户昵称
		$this->nickname = $this->user_info_session['nickname'];
		// 头像链接
		$this->avatar_url = $this->user_info_session['avatar_url'];

		$xToken= empty($_SERVER['X-Token']) ? null : $_SERVER['X-Token'];

        $this->isDebug = 0;

        Request::instance()->filter(['stripslashes', 'htmlspecialchars', 'trim']);
        //Request::instance()->filter(['strip_tags','htmlspecialchars']);

        $requestValue = Request::instance()->param();      // 获取全部的request变量（经过过滤）
        //$request_data = Request::instance()->param(false);// 获取全部的request原始变量数据

        //$request_data = Request::instance()->post();      // 获取经过过滤的全部post变量
        //$request_data = Request::instance()->post(false); // 获取全部的post原始变量

        //$requestValue = request()->param();

		 $this->requestParam = $requestValue;
/*
        $request_post =  Request::instance()->isPost();//$_SERVER['REQUEST_METHOD'];
        if (!$this->isDebug) {
            if (!$request_post) {
                sdk_return('', 100, '非法请求数据(0)');
            }
        }

        $this->requestType = isset($requestValue['reqType']) ? intval($requestValue['reqType']) : 0;//APP主动传1，WEB主动传2，默认0

        $this->requestAction = null;
        $this->requestParam = null;
        $this->uid = 0;
        $this->auth = null;

        if (!empty($this->requestType)) {
            if (isset($requestValue['action']) && isset($requestValue['paramData']) && !empty($requestValue['action'])) {//
                if (!is_sdk_encrypt($requestValue['paramData'])) {
                    sdk_return('', 101, '参数数据异常');
                }
                $this->requestAction = $requestValue['action'];
                //var_dump($this->requestType);
                if ($this->requestType == 2) {
                    if (isset($requestValue['uid']) && !empty($requestValue['uid'])) {
                        //如果是测试的账户，允许直接PC端运行游戏，且auth=ab123456，方便开发人员测试//20181030
                        if($requestValue['uid'] >= 100001 && $requestValue['uid'] <= 100010){
                            $this->isDebug = 1;
                        }
                        $this->setParamWeb($requestValue);
                    } else {
                        sdk_return('', 102, '非法请求数据(2)');
                    }
                } else {
                    $this->setParamApp($requestValue);
                }
            } else {
                sdk_return('', 103, '非法请求数据(1)');
            }
        } else {
            $this->requestParam = $requestValue;
        }
*/
	}

    /**
     * @cc api错误信息统一处理，自动适应get和post输出对应格式信息
     * @param void
     * @return string
     *
     */
  private function getParamError()
  {
      if(Request::instance()->isPost()){
          $r['time'] = get_time();
          $r['status'] = 0;
          $r['msg'] = 'error param !';
          $r['res_data'] = '';
          $str_err = json_encode($r);
      }else{
          $str_err = "<div align='center'><img src='/static/images/not_foundx6.jpg'></div>";
      }
      return $str_err;
  }

    /**
     * @cc api调用不存在方法统一处理
     * @param void
     * @return string
     *
     * @author seaboyer@163.com
     * @date 2018-09-11
     * @version 1.0
     */
    public function _empty()
    {
        return $this->getParamError();
    }


    /**
     * @cc 子类参数错误调用返回
     * @param void
     * @return string
     */
    public function echoErrorInfo()
    {
        echo $this->getParamError();
        return;
    }

    /**
     * @cc 初始化WEB接口参数
     * @param $req mixed 传过来的数据集
     * @return void
     *
     * @author seaboyer@163.com
     * @date 2018-09-11
     * @version 1.0
     */
    private function setParamApp($req)
    {
        //cyi2iFi+HaMSizFXX6sduFbGDC8N/hs/b3wNOBy6mQ9F/1WbI0puWPiguJ4nd80v+FPFA6p5ernBiVAa3klFuw==
        $expire_time = get_global_data('expire_time');

        $req_decrypt = sdk_decrypt($req['paramData']);
        if (!$req_decrypt) {
            sdk_return('',105, '参数解析异常');
        } else {
            $user_id = isset($req_decrypt['uid']) ? intval($req_decrypt['uid']) : 0;
            if (!empty($user_id)) {
                $this->uid = $user_id;
            } else {
                sdk_return('', 106, '参数不存在(1)');
            }

            //校验auth,获取服务器memcache中$auth值与解密后的值比较
            if (isset($req_decrypt['auth']) && !empty($req_decrypt['auth'])) {
                $this->requestParam = $req_decrypt;
                $req_auth           = $req_decrypt['auth'];
                $this->auth         = $req_auth;

                if (!$this->isDebug) {
                    $memcache = get_memcache();
                    $mem_auth = $memcache->get('user_auth_' . $user_id);
                    if (empty($mem_auth)) {
                        $memcache_user_data = $memcache->get('user_data_' . $user_id);// 获取用户信息
                        if (!empty($memcache_user_data)) {
                            $mem_auth = md5($memcache_user_data['uid'] . $memcache_user_data['unionid'] . config('auth_key') . $memcache_user_data['login_times']);
                            $memcache->set('user_auth_' . $user_id, $req_auth, false, $expire_time['m10']);
                        } else {
                            sdk_return('', 107, '登录校验异常');
                        }
                    }
                    if ($mem_auth == $req_auth) {
                        //完全通过校验，此次接口为正常请求
                        $this->auth = $mem_auth;
                    } else {
                        sdk_return('', 108, '身份校验异常');
                    }
                }
            } else {
                sdk_return('', 109, '参数不存在(2)');
            }
        }
    }

    /**
     * @cc 初始化APP接口参数
     * @param $req mixed 传过来的数据集
     * @return void
     *
     * @author seaboyer@163.com
     * @date 2018-09-11
     * @version 1.0
     */
    private function setParamWeb($req)
    {
        //$expire_time = get_global_data('expire_time');
        //iBP4IN7d0l1Rz4OD7HRIENSAtwFLrYK3Hey6v0vBdHN5UhBmWjJOwXaSvD4TOLmvoVflQpLAjavrlEygLVkVdg==
        $web_user_id = $req['uid'];
        $this->uid = $web_user_id;

        $memcache = get_memcache();
        $mem_user_game_data = $memcache->get('user_game_data_' . $web_user_id);
        if ($this->isDebug){
            $cache_auth = 'ab123456ab123456';
            $this->auth = $cache_auth;
            $mem_user_game_data['auth'] = $cache_auth;
        }
        //由app在创建browser时分发两份：一份通过接口(reqType=1的接口)给php，另一份存在localStorage通知web，此处php直接校验web的参数
        if (!empty($mem_user_game_data) && isset($mem_user_game_data['auth']) && !empty($mem_user_game_data['auth'])) {
            $cache_auth = $mem_user_game_data['auth'];
            $this->auth = $cache_auth;
        } else {
            web_return('', '', 104, 'WEB参数不存在(1)');
        }

        // 标注：搞什么 2018.09.22
        // $cache_auth = 'ab123456ab123456';
        // $this->auth = $cache_auth;

        $req_decrypt = web_decrypt($req['paramData'], $this->auth);

        if (!$req_decrypt) {
            web_return('','',105, '参数解析异常');
        } else {
            $this->requestParam = $req_decrypt;
            if (isset($req_decrypt['auth']) && !empty($req_decrypt['auth'])) {
                $req_auth = $req_decrypt['auth'];
                $this->auth = substr($req_auth, 0, 16);
                if (!$this->isDebug) {
                    if (substr($req_auth, 0, 16) == substr($cache_auth, 0, 16)) {

                        //$this->auth = $cache_auth;
                        if ($req_decrypt['device'] == $mem_user_game_data['device']) {
                            //完全通过校验，此次接口为正常请求
                        } else {
                            web_return('', '', 107, 'WEB身份校验异常11');
                        }
                    } else {
                        web_return('', '', 108, 'WEB身份校验异常22');
                    }
                }
            } else {
                web_return('', '', 109, 'WEB参数不存在(2)');
            }
        }
    }

    /**
     * @cc 接口统计信息
     * @param $api_action string 传字符串，函数内部转换成101...
     * @param $uid int 1
     * @param $req_data string json格式字符串
     * @param $res_data string json格式字符串
     * @param $success = 1 1：成功，其他：失败
     *
     * @author seaboyer@163.com
     * @date 2018-09-06
     * @version 1.0
     */
    public function writeApiLog($api_action, $uid, $req_data, $res_data, $success = 1)
    {
        $m_CMApiSucLog = new CMApiSucLog();
        $m_CMApiCount = new CMApiCount();
        $m_CMApiFailLog = new CMApiFailLog();

        $count_sec = 60*5;
        $now_time = get_time();
        $write_log = true;          //false;//是否开启写日志
        $filter_repeat = false;     //true; //是否开启去重复
        $filter_auto = true;        //过滤app5秒自动请求的接口，n分钟统计写入db一次

        $expire_time = get_global_data('expire_time');
        $action_index = 0;
        $api_list = get_global_data('api_list');
        if(is_array($api_list)) {
            foreach ($api_list as $one) {
                if ($one['action'] == $api_action) {
                    $action_index = $one['index'];
                    break;
                }
            }
        }

        $memcache = get_memcache();
        //--- 1.memcache写api调用统计表 ---//
        if($write_log && $action_index > 100){
            $mem_key = 'api_count_data';
            $api_count_data = $memcache->get($mem_key);
            //每5分钟生成一个数组，然后里边是101...200，每次调用判断如果过时就保存到db。
            //a.如果没缓存，生成time和插入data的第一个值
            if (empty($api_count_data)) {
                $time_i =date('i',$now_time);
                $time_s =date('s',$now_time);
                $time_previous_5 =  $now_time - ($time_i % 5 * 60) - $time_s; //前一个5分钟整对应秒数
                $time_next_5 = $time_previous_5 + 5 * 60;                     //下一个5分钟整对应秒数
                $api_count_data['count_time'] = $time_next_5;
                $api_count_data['count_data'][$action_index] = 1;
            } else {
                $count_time = $api_count_data['count_time'];
                //b.有缓存数据，没过期，data累加值
                if ($count_time > $now_time) {
                    if (empty($api_count_data['count_data'][$action_index])) {
                        $api_count_data['count_data'][$action_index] = 1;
                    } else {
                        $api_count_data['count_data'][$action_index] = $api_count_data['count_data'][$action_index] + 1;
                    }
                } else {
                    //c.有缓存，过期，老时间段的数据写入db,清理data数据，并将刚刚调用的值初始化time和第一个值
                    if (!is_numeric($count_time)) {
                        $count_time = 4;//这里是做什么的(时间整了个4，可能编码阶段测试用，忘记了。。。)
                    }
                    if (!empty($api_count_data['count_data'])) {
                        $arr_k = array();
                        $arr_v = array();
                        foreach ($api_count_data['count_data'] as $k=>$v){
                            $arr_k[] = 'i_'.$k;
                            $arr_v[] = $v;
                        }
                        $str_k = implode(',',$arr_k);
                        $str_v = implode(',',$arr_v);
                        $t_api_count = get_db_table_name('1_api_count');
                        $str_sql = "INSERT INTO {$t_api_count} (i_time, addtime, $str_k) VALUES ($count_time, $now_time, $str_v)";
                        $m_CMApiCount->executeSql($str_sql);

                        unset($arr_k);//清理data数据
                        unset($arr_v);
                        $api_count_data = null;
                    }
                    //值初始化time和第一个值
                    $time_i =date('i',$now_time);
                    $time_s =date('s',$now_time);
                    $time_previous_5 =  $now_time - ($time_i % 5 * 60) - $time_s; //前一个5分钟整对应秒数
                    $time_next_5 = $time_previous_5 + 5 * 60;                     //下一个5分钟整对应秒数
                    $api_count_data['count_time'] = $time_next_5;
                    $api_count_data['count_data'][$action_index] = 1;
                }
            }
            $memcache->set($mem_key, $api_count_data, false, $expire_time['d1']);
        }

        //--- 2.db写api调用成功流水表 ---//
        $one_data = array();
        if ($write_log && $success == 1) {
            $mem_key = 'api_success_data';
            $api_success_data = $memcache->get($mem_key);
            $one_data = null;
            $one_data['uid'] = $uid;
            $one_data['api_action'] = $action_index;
            $one_data['req_data'] = $req_data;
            $one_data['res_data'] = $res_data;
            $one_data['status'] = $success;
            $req_arr = json_decode($req_data,true);
            if (!empty($req_arr) && is_array($req_arr)) {
                $city_code = end($req_arr);
                if (!is_numeric($city_code) || strlen($city_code) <> 6) {
                    $city_code = 0;
                }
            } else {
                $city_code = 0;
            }
            $one_data['city_code'] = $city_code;
            $one_data['addtime'] = $now_time;
            $no_repeat = true;
            if ($filter_repeat) {
                foreach ($api_success_data as $one) {
                    if($one['uid'] == $one_data['uid'] && $one['api_action'] == $one_data['api_action'] && $one['req_data'] == $one_data['req_data'] && $one['res_data'] == $one_data['res_data'] && $one['status'] == $one_data['status']){
                        $no_repeat = false;
                        break;
                    }
                }
            }

            $no_auto = true;
            if ($filter_auto) {
                $auto_action_list = array(126, 112);
//            if(in_array($api_action,$auto_action_list)){
                if (in_array($action_index, $auto_action_list)) {
                    //取缓存中 某用户的126接口失效时间，如果没失效本次不统计，如果失效增加到数组中一次
                    $mem_key = 'api_user_data_'.$uid;
                    $api_user_data = $memcache->get($mem_key);
                    if (!empty($api_user_data[$action_index])) {
                        $no_auto = false;
                    } else {
                        $api_user_data[$action_index] = $now_time + 60 * 5;
                        $memcache->set($mem_key,$api_user_data, false, $expire_time['m5']);
                    }
                }
            }
            if ($no_repeat && $no_auto) {
                $api_success_data[] = $one_data;
            }

            if (count($api_success_data) >= 1000) {//数量不能太大(>1000)//后期取消req_data和res_data的保存，数量可以1000或者10000//线下50 线上500
                //方法1，逐条插入，效率慢
                //foreach($api_success_data as $one){//one['']
                //    $str_sql = "INSERT INTO {$db->pre}1_api_success_log (uid,api_action,req_data,res_data,status,addtime) VALUES ('$one['uid']','$one['action_index']','$one['req_data']','$one['res_data']','$one['status']','$one['addtime']')";
                //    $db->query($str_sql);
                //}
                //方法2，一次批量插入，最优化
                $arr_sql = array();
                foreach ($api_success_data as $one) {
                    $arr_sql[] =  "('{$one['uid']}','{$one['api_action']}','{$one['status']}','{$one['addtime']}','{$one['city_code']}')";
                }
                $str_sql = implode(',', $arr_sql);
                $u_id = $uid;
                if (empty($u_id)) {
                    $u_id = 1;//get_db_table_name函数如果uid为空，逻辑有异常，这里默认用1
                }
                $table_w_api = get_db_table_name('w_api', $u_id, date('Y-m-d', $now_time));
                $m_CMApiSucLog->setTableName($table_w_api);
                $str_sql = "INSERT INTO {$table_w_api} (uid,api_action,status,addtime,city_code) VALUES " . $str_sql;
                //$db->query($str_sql);
                $m_CMApiSucLog->executeSql($str_sql);

                $api_success_data = null;
            }
            $memcache->set($mem_key, $api_success_data, false, $expire_time['d1']);

        }
//        if(sys_ver() == 1){
//            $str_sql = "INSERT INTO b_1_log_temp (l_type,l_text,l_time) VALUES ($uid, '{$api_action}@{$req_data}@{$res_data}@{$success}',$now_time)";
//            $m_CMApiSucLog->executeSql($str_sql);
//        }

        //--- 3.db写api调用失败流水表 ---//
        if ($write_log && $success != 1) {
            $mem_key = 'api_fail_data';
            $api_success_data = $memcache->get($mem_key);
            $one_data = null;
            $one_data['uid'] = $uid;
            $one_data['api_action'] = $action_index;
            $one_data['req_data'] = $req_data;
            $one_data['res_data'] = $res_data;
            $one_data['status'] = $success;
            $one_data['addtime'] = $now_time;
            $have_data = false;
            if ($filter_repeat) {
                foreach ($api_success_data as $one) {
                    if ($one['uid'] == $one_data['uid'] && $one['api_action'] == $one_data['api_action'] && $one['req_data'] == $one_data['req_data'] && $one['res_data'] == $one_data['res_data'] && $one['status'] == $one_data['status']) {
                        $have_data = true;
                        break;
                    }
                }
            }
            if ($have_data == false) {
                $api_success_data[] = $one_data;
            }
            if (count($api_success_data) >= 100) {//数量不能太大(>1000)//线下10 线上100
                //方法1，逐条插入，效率慢
                //foreach($api_success_data as $one){//one['']
                //    $str_sql = "INSERT INTO {$db->pre}1_api_fail_log (uid,api_action,req_data,res_data,status,addtime) VALUES ('$one['uid']','$one['action_index']','$one['req_data']','$one['res_data']','$one['status']','$one['addtime']')";
                //    $db->query($str_sql);
                //}
                //方法2，一次批量插入，最优化
                $arr_sql = array();
                foreach ($api_success_data as $one) {
                    $arr_sql[] =  "('{$one['uid']}','{$one['api_action']}','{$one['req_data']}','{$one['res_data']}','{$one['status']}','{$one['addtime']}')";
                }
                $str_sql = implode(',', $arr_sql);
                $t_api_fail_log = get_db_table_name('1_api_fail_log');
                $m_CMApiFailLog->setTableName($t_api_fail_log);
                $str_sql = "INSERT INTO {$t_api_fail_log} (uid,api_action,req_data,res_data,status,addtime) VALUES " . $str_sql;
                $m_CMApiFailLog->executeSql($str_sql);

                $api_success_data = null;
            }
            $memcache->set($mem_key, $api_success_data, false, $expire_time['d1']);
        }
        unset($one_data);
    }

    /**
     * @cc 初始化缓存用户数据
     * @param $uid
     * @param array $update_data
     * @return array
     */
    public function initUserData($uid)
    {
        $expire_time = get_global_data('expire_time');

        $memcache = get_memcache();

        $m_UserList = new UserList();
        $m_UserInfo = new UserInfo();
        $where = null;
        $where['uid']= $uid;
        $one_user_list = $m_UserList->getInfo($where);
        $one_user_info = $m_UserInfo->getInfo($where);

        $user_data = array();// 定义用户的缓存信息，每次登陆或者更新数据的时候需要修改这个东西
        if(!empty($one_user_list)) {
            $user_data['uid'] = $one_user_list['uid'];
            $user_data['range'] = $one_user_list['range'];
            $user_data['to_uid'] = $one_user_list['to_uid'];
            $user_data['nickname'] = $one_user_list['nickname'];
            $user_data['stock'] = $one_user_list['stock'];
            $user_data['unionid'] = $one_user_info['unionid'];
            $user_data['login_times'] = $one_user_list['login_times'];// 登陆次数
            $user_data['type'] = $one_user_list['type'];              // 登陆类型
            $user_data['city_code'] = $one_user_list['city_code'];    // 登陆城市
            $user_data['login_type'] = $one_user_list['login_type'];  // 系统类型
        }


        $memcache->set('user_data_' . $uid, $user_data, false, $expire_time['d1']);

        return $user_data;

    }

    /**
     * @cc 更新缓存用户数据
     * @param $uid
     * @param array $update_data
     * @return void
     */
    public function updateUserData($uid, $update_data = [])
    {
        $expire_time = get_global_data('expire_time');

        $memcache = get_memcache();
        $mem_user_data = $memcache->get('user_data_' . $uid);// 获取用户信息
        if (empty($mem_user_data)) {
            $this->initUserData($uid);
        }
        if (!empty($update_data)) {
            foreach ($update_data as $key => $value) {
                $mem_user_data[$key] = $value;
            }
            $memcache->set('user_data_' . $uid, $mem_user_data, false, $expire_time['d1']);
        }
    }


    /**
     * @Author   Hulkzero
     * @DateTime 2018-10-10T15:51:20+0800
     * @Email    hulkzero@163.com
     * @param 	[int] 				$c_type 		[要进行统计的类别，例如：答题为1，刮奖2]
     * @param 	[int] 				$uid 			[用户id]
     * @param 	[int] 				$c_id 			[统计表中要统计的编号，例如：101,102]
     * @param 	[string] 			$table_name 	[表名]
     * @param 	[int/decimal] 		$increment 		[统计次数为1，钱数为开的红包金额*10000]
     * @return  [type]                   			[description]
     */
    public function writeCountLog($c_type, $c_id, $increment = 1, $uid = 1)//, $callback_function = null
    {
        if(!is_numeric($c_type) || !is_numeric($c_id) || !is_numeric($increment)){
            return;
        }

        $m_CountData 	= 	new CMApiCount();       //随便new一个common模型
        $now_time 		= 	get_time();
        $expire_time 	= 	get_global_data('expire_time');		//缓存有效时间

        if ($c_type >= 1 && $c_type <= 4) {
            //if(!empty($callback_function)){
             //   $params = array($c_type, $c_id, $increment, $uid, $now_time);
            //    call_user_func_array( $callback_function, $params);
            //}
            $t_all_count = get_db_table_name('game_count_g'.$c_type);
        } elseif ($c_type == '5') {
            $t_all_count = get_db_table_name('game_count_g5');
        } elseif ($c_type == '6') {
            $t_all_count = get_db_table_name('game_count_g6');
        } elseif ($c_type == '7') {
            $t_all_count = get_db_table_name('millions_count');
        } else {
            return;
        }

        $memcache = get_memcache();
        //--- 1.memcache写count统计表 ---//

        $mem_key = 'count_all_data_' . $c_type;
        $count_all_data = $memcache->get($mem_key);

        if (empty($count_all_data)) {						//初始缓存
            $time_str = date('Y-m-d H',$now_time);
            $time_previous 	=  	strtotime($time_str.":00:00"); 			    //前一个小时整对应秒数
            $time_next 		= 	$time_previous + 60 * 60;                     		//下一个小时整对应秒数

            $count_all_data['count_time'] = $time_next;
            $count_all_data['count_data'][$c_id] = $increment;
        } else {
            $count_time = $count_all_data['count_time'];
            //b.有缓存数据，没过期，data累加值
            if ($count_time > $now_time) {
                if (empty($count_all_data['count_data'][$c_id])) {
                    $count_all_data['count_data'][$c_id] = $increment;
                } else {
                    $count_all_data['count_data'][$c_id] = $count_all_data['count_data'][$c_id] + $increment;
                }
            } else {
                if (!empty($count_all_data['count_data'])) {
                    $arr_k = array();
                    $arr_v = array();
                    foreach ($count_all_data['count_data'] as $k=>$v){
                        $arr_k[] = 'c_'.$k;
                        $arr_v[] = $v;
                    }
                    $str_k = implode(',',$arr_k);
                    $str_v = implode(',',$arr_v);

                    $str_sql = "INSERT INTO {$t_all_count} (c_time, addtime, $str_k) VALUES ($count_time, $now_time, $str_v)";
                    $m_CountData->executeSql($str_sql);

                    unset($arr_k);//清理data数据
                    unset($arr_v);
                    $count_all_data = null;
                }
                //值初始化time和第一个值
                $time_str = date('Y-m-d H',$now_time);
                $time_previous 	=  	strtotime($time_str.":00:00");  					    //前一个小时整对应秒数
                $time_next 		= 	$time_previous + 60 * 60;                     				//下一个小时整对应秒数

                $count_all_data['count_time'] 			= $time_next;
                $count_all_data['count_data'][$c_id] 	= $increment;
            }
        }
        $memcache->set($mem_key, $count_all_data, false, $expire_time['d1']);

    }


}
