<?php
/**
 * easy to use mipush
 * version 0.0.1
 * author: TIGERB <http://tigerb.cn>
 * descritp: 目前只实现了按alias(别名),user_account(用户账号),topic(标签), multi_topic(多标签),all(全体)推送
 */

// 使用示例
// require('./mipush.php');
// try {
// 	Push::init(
// 		['secret' => 'string,必传,ios密钥'], 
// 		['secret' => 'string,必传,android密钥', 'package_name' => 'string,必传,android包名']
// 		[	
// 		  'title'    	 => 'string,非必传,消息通知自定义title',
// 		  'pass_through' => 'int,非必传,0通知栏消息,1透传,默认0',
// 		  'notify_type'  => 'int,非必传,-1:默认所有,1:使用默认提示音提示,2:使用默认震动提示,4:使用默认led灯光提示',
// 		  'time_to_send' => 'int,非必传,定时推送,单位毫秒,默认0',
// 		],
// 		'string,develop开发环境，product生产环境, 默认develop'
// 		);	
// 	$res = Push::toUse('string,小米push方法名', 'array, 该方法对应的参数');
// 	echo json_encode($res, JSON_UNESCAPED_UNICODE);
// } catch (Exception $e) {
// 	echo $e->getMessage();
// }


/**
 * easy mipush sdk
 **/
class Mipush
{
	private $_environment = 'develop';	
	private $_osType      = '';
	private $_host        = '';
	private $_headers     = '';
	private $_url         = '';
	private $_function    = [];
	private $_data        = [];

	private $_options = [
		'title'                   => '消息通知自定义title',
		'restricted_package_name' => '',
		'pass_through'            => 0, // 0 通知栏消息 1 透传
		'notify_type'             => -1, // -1:默认所有,1:使用默认提示音提示,2:使用默认震动提示,4:使用默认led灯光提示
		'time_to_send'			  => 0, // 定时推送 单位毫秒 默认0
	];

	private static $_environmentConfig = [
		'domain' => [
			'product'  => 'https://api.xmpush.xiaomi.com/',
			'develop'  => 'https://sandbox.xmpush.xiaomi.com/'
		],
	];

	/* mipush uri 参考*/
	// const reg_url                          = '/v3/message/regid';
	// const alias_url                        = '/v3/message/alias';
	// const user_account_url                 = '/v2/message/user_account';
	// const topic_url                        = '/v3/message/topic';
	// const multi_topic_url                  = '/v3/message/multi_topic';
	// const all_url                          = '/v3/message/all';
	// const multi_messages_regids_url        = '/v2/multi_messages/regids';
	// const multi_messages_aliases_url       = '/v2/multi_messages/aliases';
	// const multi_messages_user_accounts_url = '/v2/multi_messages/user_accounts';
	// const stats_url                        = '/v1/stats/message/counters';
	// const message_trace_url                = '/v1/trace/message/status';
	// const messages_trace_url               = '/v1/trace/messages/status';
	// const validation_regids_url            = '/v1/validation/regids';
	// const subscribe_url                    = '/v2/topic/subscribe';
	// const unsubscribe_url                  = '/v2/topic/unsubscribe';
	// const subscribe_alias_url              = '/v2/topic/subscribe/alias';
	// const unsubscribe_alias_url            = '/v2/topic/unsubscribe/alias';
	// const fetch_invalid_regids_url         = 'https://feedback.xmpush.xiaomi.com/v1/feedback/fetch_invalid_regids';
	// const delete_schedule_job              = '/v2/schedule_job/delete';
	// const check_schedule_job_exist         = '/v2/schedule_job/exist';
	// const get_all_aliases                  = '/v1/alias/all';
	// const get_all_topics                   = '/v1/topic/all';

	private $_functionDefine = [
		'userAccount' => [
			'uri' => 'v2/message/user_account',
			'arguments' => [
				'accounts' => [
					'type' => 'array',
					'must' => 'y'
				],
				'description' => [
					'type' => 'string',
					'must' => 'y'
				],
				'params' => [//自定义参数
					'type' => 'array',
					'must' => 'n'
				],
			]
		],
		'alias' => [
			'uri' => 'v3/message/alias',
			'arguments' => [
				'aliases' => [
					'type' => 'array',
					'must' => 'y'
				],
				'description' => [
					'type' => 'string',
					'must' => 'y'
				],
				'params' => [//自定义参数
					'type' => 'array',
					'must' => 'n'
				],
			]
		],
		'topic' => [
			'uri' => 'v3/message/topic',
			'arguments' => [
				'accounts' => [
					'type' => 'array',
					'must' => 'y'
				],
				'description' => [
					'type' => 'string',
					'must' => 'y'
				],
				'params' => [//自定义参数
					'type' => 'array',
					'must' => 'n'
				],
			]
		],
		'multiTopic' => [
			'uri' => 'v3/message/multi_topic',
			'arguments' => [
				'topics' => [
					'type' => 'array',
					'must' => 'y'
				],
				'topics_op' => [
					'type' => 'string',
					'must' => 'y'
				],
				'description' => [
					'type' => 'string',
					'must' => 'y'
				],
				'params' => [//自定义参数
					'type' => 'array',
					'must' => 'n'
				],
			]
		],
		'all' => [
			'uri' => 'v3/message/all',
			'arguments' => [
				'description' => [
					'type' => 'string',
					'must' => 'y'
				],
				'params' => [//自定义参数
					'type' => 'array',
					'must' => 'n'
				],
			]
		],
	];

	/**
	 * 初始化配置
	 * 
	 * @param $string $os_type 系统类型
	 * @param $string $config  配置
	 * @param array   $options 设置 [
	 *                        'title'        => 'string,标题', 
	 *                        'pass_through' => 'tinyint,0通知栏消息,1透传,默认0'
	 *                        'notify_type'  => 'tinyint,-1,1,2,3,4',
	 *                        'time_to_send' => 'int, 定时推送, 毫秒'
	 *                        ]
	 * @param string  $environment 环境
	 */
	public function __construct($os_type='', $config=array(), $options=array(), $environment='')
	{
		/* init environment */
		if ($environment) {
			$this->_environment = $environment;
		}
		if ($os_type === 'ios') {
			$this->_host     = self::$_environmentConfig['domain'][$this->_environment];// ios
		}else{
			$this->_host     = self::$_environmentConfig['domain']['product'];// android
		}
		
		/* init option */
		$this->_headers   = [];
		$this->_headers[] = 'Authorization: key=' . $config['secret'];
		if ($os_type === 'android') {
			$this->_options['restricted_package_name'] = $config['package_name'];
		}
		foreach ($this->_options as $k => &$v) {
			if (in_array($k, $options)) {
				$v = $options[$k];
			}
		}
	}

	private function dataCheck($arguments=array())
	{
		foreach ($this->_function['arguments'] as $k => $v) {
			if ($v['must'] === 'y' && !$arguments[$k]) {
				throw new \Exception("$k is must argument", 500);
			}
			if ($arguments[$k] && gettype($arguments[$k]) !== $v['type']) {
				throw new \Exception("$k type is $v", 500);
			}
		}
		
	}

	public function __get($name='')
	{
		return $this->$name;
	}

	public function userAccount($arguments=array())
	{
		$this->_function = $this->_functionDefine[__FUNCTION__];
		$this->_url = $this->_host . $this->_function['uri'];
		$this->dataCheck($arguments);
		$this->_data['user_account'] = implode(',', $arguments['accounts']);
		$this->_data['description']  = $arguments['description'];
		if($arguments['params']) {
			foreach ($arguments['params'] as $k => $v) {
				$this->_data['extra.'.$k] = $v;// 自定义参数
			}
		}
		if ($this->_osType === 'android') {
			$this->_data = array_merge($this->_data, $this->_options);
		}
	}

	public function alias($arguments=array()) 
	{
		$this->_function = $this->_functionDefine[__FUNCTION__];
		$this->_url = $this->_host . $this->_function['uri'];
		$this->dataCheck($arguments);
		$this->_data['alias'] = implode(',', $arguments['aliases']);
		$this->_data['description']  = $arguments['description'];
		if($arguments['params']) {
			foreach ($arguments['params'] as $k => $v) {
				$this->_data['extra.'.$k] = $v;// 自定义参数
			}
		}
		if ($this->_osType === 'android') {
			$this->_data = array_merge($this->_data, $this->_options);
		}
	}
	
	public function topic($arguments=array())
	{
		$this->_function = $this->_functionDefine[__FUNCTION__];
		$this->_url = $this->_host . $this->_function['uri'];
		$this->dataCheck($arguments);
		$this->_data['topic'] = $arguments['topic'];
		$this->_data['description']  = $arguments['description'];
		if($arguments['params']) {
			foreach ($arguments['params'] as $k => $v) {
				$this->_data['extra.'.$k] = $v;// 自定义参数
			}
		}
		if ($this->_osType === 'android') {
			$this->_data = array_merge($this->_data, $this->_options);
		}
	}
	
	public function multiTopic($arguments=array())
	{
		$this->_function = $this->_functionDefine[__FUNCTION__];
		$this->_url = $this->_host . $this->_function['uri'];
		$this->dataCheck($arguments);
		$this->_data['topics']      = implode(";$;", $arguments['topics']);
		$this->_data['topic_op']    = $arguments['topic_op'];
		$this->_data['description'] = $arguments['description'];
		if($arguments['params']) {
			foreach ($arguments['params'] as $k => $v) {
				$this->_data['extra.'.$k] = $v;// 自定义参数
			}
		}
		if ($this->_osType === 'android') {
			$this->_data = array_merge($this->_data, $this->_options);
		}
	}
	
	public function all($description='', $params=array())
	{
		$this->_function = $this->_functionDefine[__FUNCTION__];
		$this->_url = $this->_host . $this->_function['uri'];
		$this->dataCheck($arguments);
		$this->_data['topics']      = implode(";$;", $topics);
		$this->_data['topic_op']    = 'UNION';
		$this->_data['description'] = $arguments['description'];
		if($arguments['params']) {
			foreach ($arguments['params'] as $k => $v) {
				$this->_data['extra.'.$k] = $v;// 自定义参数
			}
		}
		if ($this->_osType === 'android') {
			$this->_data = array_merge($this->_data, $this->_options);
		}
	}

}

/**
 * 推送小米实体 
 */
class Push
{
	private static $_ios;
	private static $_android;

	public static function init($ios_auth=array(), $android_auth=array(), $options=array(), $environment='')
	{
		self::$_ios 	= new Mipush('ios', $ios_auth, $options, $environment);
		self::$_android = new Mipush('android', $android_auth, $options, $environment);
	}

	public function toUse($function_name='', $arguments=array())
	{
		self::$_ios->$function_name($arguments);
		self::$_android->$function_name($arguments);
		return self::curlRequest(self::$_ios, self::$_android);
	}

	private static function curlRequest(Mipush $mipush_ios, Mipush $mipush_android) {
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