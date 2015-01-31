<?php
/**
 * SAEKV 服务
 *
 * @package sae
 * @version $Id$
 * @author lijun
 */

/**
 * SAEKV 服务
 *
 * <code>
 *<?php
 *   $kv = new SaeKVClient();
 *  // 初始化KVClient对象
 *   $ret = $kv->init();
 *  var_dump($ret);
 *
 * // 更新key-value
 * $ret = $kv->set('abc', 'aaaaaa');
 * var_dump($ret);
 *
 * // 获得key-value
 * $ret = $kv->get('abc');
 *  var_dump($ret);
 *
 *  // 删除key-value
 *   $ret = $kv->delete('abc');
 *  var_dump($ret);
 *
 *  // 一次获取多个key-values
 *   $keys = array();
 *  array_push($keys, 'abc1');
 *   array_push($keys, 'abc2');
 *  array_push($keys, 'abc3');
 *   $ret = $kv->mget($keys);
 *  var_dump($ret);
 *
 *  // 前缀范围查找key-values
 *   $ret = $kv->pkrget('abc', 3);
 *   var_dump($ret);
 *
 *   // 循环获取所有key-values
 *   $ret = $kv->pkrget('', 100);
 *   while (true) {
 *      var_dump($ret);
 *       end($ret);
 *       $start_key = key($ret);
 *       $i = count($ret);
 *       if ($i < 100) break;
 *       $ret = $kv->pkrget('abc', 100, $start_key);
 *  }
 * ?>
 * </code>
 *
 * 0 "Success"

 * 10 "AccessKey Error"
 * 20 "Failed to connect to KV Router Server"
 * 21 "Get Info Error From KV Router Server"
 * 22 "Invalid Info From KV Router Server"

 * 30 "KV Router Server Internal Error"
 * 31 "KV DB Server UNINITED"
 * 32 "KV DB Server NOTOK"
 * 33 "App is BANNED"
 * 34 "KV DB Server CLOSED"
 * 35 "Unknown KV status"

 * 40 "Invalid Parameters"
 * 41 "Interaction Error (%d) With KV DB Server"
 * 42 "ResultSet Generation Error"
 * 43 "Out Of Memory"
 * 44 "KVClient constructor was not called"
 *
 * author: Chen Lei <simpcl2008@gmail.com>
 * version: $Id$
 */

class SaeKV
{
	const REDIS_HOST                                        = REDIS_HOST;
	const REDIS_PORT                                        = REDIS_PORT;
	const REDIS_CONNECT_TIMOUT              = 60; //s

	const EMPTY_PREFIXKEY 				= '';
	const MAX_PKRGET_SIZE 				= 100;
	const MAX_KEY_LENGTH                = 200;
	const MAX_VALUE_LENGTH 			    = 4194304; // expr 1024 \* 1024 \* 1024

	private $_accesskey 					= "";
	private $_secretkey 					= "";
	private $_errno						= SAE_Success;
	private $_errmsg                				= "OK";
	private $appName          				= NULL;

	private $modPrefix					= 'kvdb';
	private $arrmodPrefix               = 'arrkvdb' ;
	private $redis	                			= NULL;
	
	/**
	 * 构造函数
	 */
	public function __construct()
	{
		return true;
	}
	
	/**
	 * 析构函数
	 */
	public function __destruct()
	{
		if($this->redis)
		{
			$this->redis->close() ;
			$this->redis = NULL ;
		}
	}
	
	/**
	*  获取应用名称
	*  @return 应用的名字
	*/
	private function get_appname()
	{
		return $_SERVER['HTTP_APPNAME'] ;
	}
	
	/**
	 *  初始化Sae KV 服务
	 *
	 *   @return bool 成功返回true，失败返回false（计数器已存在返回false）e
	 */
	public function init()
	{
		$this->_errno		= SAE_Success;
		$this->_errmsg		= "OK";

		if(!$this->redis)
		{
			$this->redis = new Redis() ;
			if(!$this->redis->connect(REDIS_HOST,REDIS_PORT))
				return false ;
		}

		$this->appName 		= trim($this->get_appname());
		$this->_accesskey 	= SAE_ACCESSKEY;
		$this->_secretkey 	= SAE_SECRETKEY;
		
		$this->redis->select(APP_NUMBER) ;
		$dbnum = $this->redis->hGet('app-num',$this->appName) ;
		$this->redis->select($dbnum) ;
		return true ;
	}
	
	/**
	 * 返回错误信息
	 *
	 * @return string
	*/ 
	public function get_errno	()
	{
		return $this->_errno;
	}
	
	/**
	 * 取得错误信息
	 *
	 * @return string
	*/
	public function get_errmsg()
	{
		return $this->_errmsg;
	}
	 
	/**
	 * 获得key对应的value
	 *
	 * @param: string $key: 长度小于MAX_KEY_LENGTH字节
	 * @return: 成功返回value值，失败返回false
	*/ 
	public function get($key)
	{
		if(!$this->hasInited())return false;
		if(strlen($key) == 0 || strlen($key) > SaeKV::MAX_KEY_LENGTH)
		{
			$this->_errno    	=  40;
			$this->_errmsg	= "Invalid Parameters";
		}
		$val = false ;
		if($this->redis->exists($this->getNomalKey($key)))
			$val = $this->redis->get($this->getNomalKey($key));
		else
		{
			do{
				$val 	= $this->redis->get($this->getArrNomalKey($key));
				if($val === false)
					break ;
				$val = unserialize($val) ;
			}while(0) ;
		}

		if($val === false)
		{
			$this->checkError();
			return false;
		}

		return $val;
	}
	
	/**
     * 增加key-value对，如果key存在则返回失败
     *
     * @param string $key 长度小于MAX_KEY_LENGTH字节，当不设置encodekey选项时，key中不允许出现非可见字符
     * @param string $value 长度小于MAX_VALUE_LENGTH
     * @return bool 成功返回true，失败返回false
     */
	function add($key, $value) 
	{
		if($this->get($key) !== false)
			return false ;
		return $this->set($key, $value) ;
    }
	
	/**
     * 替换key对应的value，如果key不存在则返回失败
     *
     * @param string $key 长度小于MAX_KEY_LENGTH字节，当不设置encodekey选项时，key中不允许出现非可见字符
     * @param string $value 长度小于MAX_VALUE_LENGTH
     * @return bool 成功返回true，失败返回false
     */
	function replace($key, $value)
	{
		if($this->get($key) === false)
			return false ;
		return $this->set($key, $value) ;			
	}
	
	/**
	 * 批量获得key-values
	 * @paramarray $ary: 一个包含多个key的数组，数组长度小于等于32
	 * @return: 成功返回key-value数组，失败返回false
	*/
	public function mget ($ary)
	{
		if(!$this->hasInited())return false;

		if(!is_array($ary) || count($ary) < 1 || count($ary) > 32)
		{
			$this->_errno    	=  40;
			$this->_errmsg	= "Invalid Parameters";
		}

		return $this->_mget($ary);
	}
	
	/**
	 * 前缀范围查找key-values
	 *
	 * @param: string $prefix_key: 前缀，长度小于MAX_KEY_LENGTH字节
	 * @param: int $count: 前缀查找最大返回的key-values个数，小于等于MAX_PKRGET_SIZE
	 * @param: string $start_key: 最小key，在执行前缀查找时，应该返回大于等于该key的key-values，默认值为空字符串（即忽略该参数）
	 * @return: 成功返回key-value数组，失败返回false
	 */
	public function pkrget ($prefix_key, $count, $start_key = '')
	{
		if(!$this->hasInited())return false;

		if($count > SaeKV::MAX_PKRGET_SIZE || !$prefix_key || strlen($prefix_key) > SaeKV::MAX_KEY_LENGTH)
		{
			$this->_errno    	=  40;
			$this->_errmsg	= "Invalid Parameters";
		}

		$count = $count -1;
		
		$prefix           = $this->getNomalKey($prefix_key).'*';
		$nKeyArr      	= $this->redis->Keys($prefix);

		if(!$nKeyArr)
		{
			$this->checkError(false);

			if($this->_errno	!= 34)
			return array();
			else
			return false;
		}

		$kArr	= array();
		foreach ($nKeyArr as $v)
		{
			$kArr[]		= $this->getUserKey($v);
		}

		$arr		= $this->_mget($kArr);

		if(!$arr)return false;
		asort($arr) ;

		if($start_key)
		{
			ksort($arr);
			$rstArr 	= array();

			$i=0;
			foreach ($arr as $k => $v)
			{
				if(strcmp($k, $start_key) >= 0)
				{
					$rstArr[$k] = $v;
					$i++;
					if($i > $count)break;
				}
			}
			return $rstArr;
		}
		else
		{
			if(count($arr) < $count)
			{
				return $arr;
			}
			else
			{
				$rstArr 	= array();
				$i = 0;
				foreach ($arr as $k => $v)
				{
					$rstArr[$k] = $v;
					$i++;
					if($i > $count)break;
				}
				return $rstArr;
			}
		}

	}
	
	/**
	 * 更新key对应的value
	 *
	 *@param: string $key: 长度小于MAX_KEY_LENGTH字节
	 *@param: string $value: 长度小于MAX_VALUE_LENGTH
	 *@return: 成功返回true，失败返回false
	*/ 
	public function set($key, $value)
	{
		if(!$this->hasInited())return false;
		if(strlen($key) == 0 || strlen($key) > SaeKV::MAX_KEY_LENGTH || strlen($value) > SaeKV::MAX_VALUE_LENGTH)
		{
			$this->_errno    	=  40;
			$this->_errmsg	= "Invalid Parameters";
		}
		$rst = false ;
		if(is_array($value) == false)
		{
			$key	= $this->getNomalKey($key);
			$rst 	= $this->redis->set($key, $value);
		}
		else
		{
			$key = $this->getArrNomalKey($key) ;
			$rst 	= $this->redis->set($key, serialize($value));
		}

		if(!$rst)
		{
			$this->checkError();
			return false;
		}

		return true;
	}
	
	/**
	 * 删除key-value
	 *
	 * @param: string $key: 长度小于MAX_KEY_LENGTH字节
	 * @return: 成功返回true，失败返回false
	*/ 
	public function delete ($key)
	{
		if(!$this->hasInited())return false;
		if(strlen($key) == 0 || strlen($key) > SaeKV::MAX_KEY_LENGTH)
		{
			$this->_errno    	=  40;
			$this->_errmsg	= "Invalid Parameters";
		}
		if($this->redis->exists($this->getNomalKey($key)))
			$rst 	= $this->redis->del($this->getNomalKey($key));
		else
			$rst 	= $this->redis->del($this->getArrNomalKey($key));

		if(!$rst)
		{
			$this->checkError();
			return false;
		}

		return true;
	}
	
	/**
     * 获得kv信息
     *
     * @return array 返回kv信息数组
     *  array(2) {
     *    ["total_size"]=>
     *    int(49)
     *    ["total_count"]=>
     *    int(1)
     *  }
     */
    function get_info() 
	{
		$KeyArr      	= $this->redis->Keys($this->modPrefix);
		$arrKeyArr      	= $this->redis->Keys($this->arrmodPrefix);
		$total_count = count($KeyArr) + count($arrKeyArr) ;
		$temp = array("total_size"=>0,"total_count"=>$total_count) ;
		return $temp ;
    }
	
	/**
     * 获取选项值
     *
     * @return array 成功返回选项数组，失败返回false
     *  array(1) {
     *    "encodekey" => 1 // 默认为1
     *                     // 1: 使用urlencode编码key；0：不使用urlencode编码key
     *  }
     */
    function get_options() 
	{
		$arr = array("encodekey" => 1) ;
		return $arr ;
    }
	
	/**
     * 设置选项值
     *
     * @param array $options array (1) {
     *    "encodekey" => 1 // 默认为1
     *                     // 1: 使用urlencode编码key；0：不使用urlencode编码key
     *  }
     * @return bool 成功返回true，失败返回false
     */
    function set_options($options) 
	{
		return true ;
    }
	
	
	private function getNomalKey($uKey)
	{
		return $this->modPrefix.$uKey ;
	}
	
	private function getArrNomalKey($uKey)
	{
		return $this->arrmodPrefix.$uKey ;
	}	
	
	private function getUserKey($nKey)
	{
		return substr($nKey,strlen($this->modPrefix)) ;
	}
	
	private function getArrUserKey($nKey)
	{
		return substr($nKey,strlen($this->arrmodPrefix)) ;
	}
	
	private function   hasInited()
	{
		if(!$this->redis)
		{
			$this->_errno	= 31;
			$this->_errmsg	= "KV DB Server UNINITED";
			return false;
		}

		return true;
	}
	
	private function checkError($last = true)
	{
		$this->_errno		= SAE_Success;
		$this->_errmsg		= "OK";

		try
		{
			$this->redis->ping();
		}
		catch(Exception $e )
		{
			$this->_errno	= 34;
			$this->_errmsg	= "KV DB Server CLOSED";
			return false;
		}

		if($last)
		{
			$this->_errno	= 41;
			$this->_errmsg	= 'Interaction Error  With KV DB Server';
		}

		return false;
	}
	
	private function _mget ($ary)
	{
		$rArr 	= array();
		foreach ($ary as $k)
		{
			$v 	= $this->get($k);
			if($v === false)return false;
			$rArr[$k]	=  $v ;
		}

		return $rArr;
	}
}

class SaeKVClient extends SaeKV 
{}
?>