<?php
namespace Mipush;

use Mipush\Mipush;

/**
 * 推送小米实体
 */
class Push
{
	/**
	 * ios推送实体
	 * @var object
	 */
	private static $_ios;

	/**
	 * android推送实体
	 * @var object
	 */
	private static $_android;

	/**
	 * 初始化推送实体
	 * 
	 * @param  array  $ios_auth     ios鉴权信息
	 * @param  array  $android_auth android鉴权信息
	 * @param  array  $options      推送设置
	 * @param  string $environment  推送环境:develop开发环境，product生产环境
	 * @return void               
	 */
	public static function init($ios_auth=array(), $android_auth=array(), $options=array(), $environment='')
	{
		self::$_ios 	= new Mipush('ios', $ios_auth, $options, $environment);
		self::$_android = new Mipush('android', $android_auth, $options, $environment);
	}

	/**
	 * 调用小米推送接口
	 * 
	 * @param  string $function_name 接口名称
	 * @param  array  $arguments     请求参数
	 * @return array                 响应结果
	 */
	public static function toUse($function_name='', $arguments=array())
	{
		self::$_ios->$function_name($arguments);
		self::$_android->$function_name($arguments);
		return self::curlRequest(self::$_ios, self::$_android);
	}

	/**
	 * 并行推送
	 * 
	 * @param  Mipush $mipush_ios     ios端实体
	 * @param  Mipush $mipush_android android端实体
	 * @return array                  推送结果
	 */
	private static function curlRequest(Mipush $mipush_ios, Mipush $mipush_android) 
	{
		$ch_ios     = curl_init();
		$ch_android = curl_init();
		curl_setopt($ch_ios, CURLOPT_URL, $mipush_ios->_url);
		curl_setopt($ch_ios, CURLOPT_POST, 1);
		curl_setopt($ch_ios, CURLOPT_POSTFIELDS, $mipush_ios->_data);
		curl_setopt($ch_ios, CURLOPT_HTTPHEADER, $mipush_ios->_headers);
		curl_setopt($ch_ios, CURLOPT_RETURNTRANSFER, 1); 
		
		curl_setopt($ch_android, CURLOPT_URL, $mipush_android->_url);
		curl_setopt($ch_android, CURLOPT_POST, 1);
		curl_setopt($ch_android, CURLOPT_POSTFIELDS, $mipush_android->_data);
		curl_setopt($ch_android, CURLOPT_HTTPHEADER, $mipush_android->_headers);
		curl_setopt($ch_android, CURLOPT_RETURNTRANSFER, 1); 
		
		$mh = curl_multi_init();
		curl_multi_add_handle($mh, $ch_ios);
		curl_multi_add_handle($mh, $ch_android);

		$running=null;
		do {
		   curl_multi_exec($mh,$running);
		} while($running > 0);

		$result['ios'] 	   = json_decode(curl_multi_getcontent($ch_ios), true);
		$result['android'] = json_decode(curl_multi_getcontent($ch_android), true);

		curl_multi_remove_handle($mh, $ch_ios);
		curl_multi_remove_handle($mh, $ch_android);
		curl_multi_close($mh);
		return $result;
	}	
}
