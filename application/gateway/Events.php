<?php

use \GatewayWorker\Lib\Gateway;
use think\facade\Session;

class Events
{
	// 当有客户端连接时，将client_id返回，让mvc框架判断当前uid并执行绑定
	public static function onConnect($client_id)
	{
		Gateway::sendToClient($client_id, json_encode(array(
			'type'      => 'init',
			'client_id' => $client_id
		)));
	}

   /**
    * 有消息时
    * @param int $client_id
    * @param mixed $message
    */
   public static function onMessage($client_id, $message)
   {
	   // $file  = 'log.txt';//要写入文件的文件名（可以是任意文件名），如果文件不存在，将会创建一个
	   //
	   // $message_data = json_decode($message, true);
	   //
	   // if($f  = file_put_contents($file, $message_data,FILE_APPEND)){// 这个函数支持版本(PHP 5)
	   //    echo "写入成功<br />";
	   // }

	   switch ($message_data['type']) {
		   case 'heart':
		   		echo $message_data['message'];
		   		break;
	   }
   }
   
   /**
    * 当客户端断开连接时
    * @param integer $client_id 客户端id
    */
   public static function onClose($client_id)
   {
	   $url = '192.168.1.189:8079/exit_room?client_id=' . $client_id;

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

	   // $file  = 'log.txt';//要写入文件的文件名（可以是任意文件名），如果文件不存在，将会创建一个
	   // $content = $res;
	   //
	   // if($f  = file_put_contents($file, $content,FILE_APPEND)){// 这个函数支持版本(PHP 5)
		//    echo "写入成功<br />";
	   // }
   }

}
