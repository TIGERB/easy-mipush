<?php
namespace Mipush;

/**
 * easy to use mipush parallelly
 * version 0.1.0
 * author: TIGERB <https://github.com/TIGERB>
 * description: 目前只实现了按regid,alias(别名),user_account(用户账号),topic(标签), multi_topic(多标签),all(全体)推送
 * date: 2016-09-28
 */

// -----------------------------------------------------------------------------------------------------------------------------
// 使用示例：
// try {
// 	Push::init(
// 		['secret' => 'string,必传,ios密钥'], 
// 		['secret' => 'string,必传,android密钥', 'package_name' => 'string,必传,android包名'],
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
// 
// this is a example:
// try {
// 	Push::init(
// 		['secret' => ''], 
// 		['secret' => '', 'package_name' => 'com.test'],
// 		[	
// 		  'title'    	 => 'test',
// 		  'pass_through' => 0,
// 		  'notify_type'  => -1,
// 		  'time_to_send' => 0,
// 		],
// 		'develop'
// 		);	
// 	$res = Push::toUse('userAccount', [
// 			'user_account' => [1],
// 			'description'  => 'test'
// 		]);
// 	echo json_encode($res, JSON_UNESCAPED_UNICODE);
// } catch (Exception $e) {
// 	echo $e->getMessage();
// }
// -----------------------------------------------------------------------------------------------------------------------------


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

	/**
	 * 小米推送接口信息定义
	 * 
	 * url/请求参数
	 * @var array
	 */
	private $_functionDefine = [
		'regid' => [
			'uri' => 'v3/message/regid',
			'arguments' => [
				'registration_id' => [
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
		'userAccount' => [
			'uri' => 'v2/message/user_account',
			'arguments' => [
				'user_account' => [
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
				'alias' => [
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
				'topics' => [
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
				'topics_op' => [// UNION并集，INTERSECTION交集，EXCEPT差集
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

	/**
	 * 请求参数校验
	 * 
	 * @param  array  $arguments 请求参数
	 * @return mixed  void||object
	 */
	private function dataCheck($arguments=array())
	{
		foreach ($this->_function['arguments'] as $k => $v) {
			if ($v['must'] === 'y' && !$arguments[$k]) {
				throw new \Exception("$k is must argument", 400);
			}
			if ($arguments[$k] && gettype($arguments[$k]) !== $v['type']) {
				throw new \Exception("$k type is $v", 400);
			}
		}
		
	}

	/**
	 * 魔法方法
	 * 获取类属性
	 * 
	 * @param  string $name 类属型名称
	 * @return mixed       
	 */
	public function __get($name='')
	{
		return $this->$name;
	}

	/**
	 * 魔术方法
	 * 
	 * 重载对象方法
	 * @param  string $name      小米推送方法名称
	 * @param  array  $arguments 请求参数
	 * @return mixed             void||object
	 */
	public function __call($name,$arguments)
	{
		$arguments = $arguments[0];
		$this->_function = $this->_functionDefine[$name];
		$this->_url = $this->_host . $this->_function['uri'];
		$this->dataCheck($arguments);

		switch ($name) {
			case 'regid':
				$this->_data['registration_id'] = $arguments['registration_id'];
				break;
			case 'userAccount':
				$this->_data['user_account'] = implode(',', $arguments['user_account']);
				break;
			case 'alias':
				$this->_data['alias']        = implode(',', $arguments['alias']);
				break;
			case 'topic':
				$this->_data['topic']        = $arguments['topic'];
				break;
			case 'multiTopic':
				$this->_data['topics']       = implode(";$;", $arguments['topics']);
				$this->_data['topic_op']     = $arguments['topic_op'];
				break;
			case 'all':
				$this->_data['topics']       = implode(";$;", $topics);
				$this->_data['topic_op']     = 'UNION';
				break;
				
				default:
				throw new \Exception('Sorry, This function is useless in this version', 404);
				break;
		}

		$this->_data['title']  = $arguments['title'];
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

}
