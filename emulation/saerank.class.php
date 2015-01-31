<?php
/**
 * SAE Rank服务
 *
 * @package
 * @version
 * @author Seeker Zhang
 */

/**
 * SAE Rank<br />
 * //mimiced with redis and mysql
 * <code>
 * <?php
 * $bill = new SaeRank();
 *
 * //添加排行榜
 * $ret=bill->create("zhang_money", 100);
 * if($ret==false)
 *		var_dump($bill->errno(), $bill->errmsg());
 * //添加或设置key的value
 *
 * $ret = $bill->set("zhang_money", "friendA", 30, true);
 * if($ret==false)
 *		var_dump($bill->errno(), $bill->errmsg());
 *
 * //获得排行榜
 * $ret = $bill->getList("zhang_money");
 * if($ret==false)
 *		var_dump($bill->errno(), $bill->errmsg());
 *
 * ?>
 * </code>
 *
 * 错误码参考：
 *  - errno: 0 	    成功
 *  - errno: -1     参数错误
 *  - errno: -2     元素的排名超出排行榜的范围
 *  - errno: -3     查找元素没有在排行榜中
 *  - errno: -4     排行榜不存在
 *  - errno: -5     服务器返回值错误
 *  - errno: -6	   SAE内部错误
 *  - errno: -7	   排行榜php扩展内部错误
 * @package sae
 * @author Seeker Zhang
 */
 
class SaeRank extends SaeObject
{
	const REDIS_HOST                                        = REDIS_HOST;
	const REDIS_PORT                                        = REDIS_PORT;
	const REDIS_CONNECT_TIMOUT              = 60; //s

	private $_accesskey = "";
	private $_secretkey = "";
	private $_errno=SAE_Success;
	private $_errmsg="OK";
	private $_post="";
	private $modPrefix			= 'rank';
	private $appName          	= NULL;
	private $redis	            = NULL;
	private $number  ;
	private $expire = 0 ;
	
	/**
	 * 构造对象
	 *
	 */
	function __construct()
	{
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
	 * 取得错误码
	 *
	 * @return int
	 * @author Seeker Zhang
	 */
	public function errno()
	{
		return $this->_errno;
	}
	
	/**
	 * 取得错误信息
	 *
	 * @return string
	 * @author Seeker Zhang
	 */
	public function errmsg()
	{
		return $this->_errmsg;
	}
	
	/**
	 * 设置key
	 *
	 * 只有使用其他应用的key时才需要调用
	 *
	 * @param string $accesskey
	 * @param string $secretkey
	 * @return void
	 * @author Seeker Zhang
	 */
	public function setAuth( $accesskey, $secretkey)
	{
		$accesskey = trim($accesskey);
		$secretkey = trim($secretkey);
		$this->_accesskey = $accesskey;
		$this->_secretkey = $secretkey;
		return true;
	}
	
	/**
	 * 创建排行榜
	 *
	 * @param string $namespace 排行榜的命名空间
	 * @param int $number 排行榜的大小
	 * @param int $expire 排行榜失效时间，单位（分min）
	 * @return bool 成功返回true；失败返回false。
	 * @author Seeker Zhang
	 * @when: 	1. 过期后gelist返回成功，但无数据;
	 * 			2. ns存在时（包括过期的），无动作;
	 */
	function create($namespace, $number, $expire=0)
	{
		$this->redis = new Redis() ;
		if(!$this->redis->connect(REDIS_HOST,REDIS_PORT))
		{
			return false ;
		}
		
		$this->appName 		= trim($this->get_appname());
		$this->_accesskey 	= SAE_ACCESSKEY;
		$this->_secretkey 	= SAE_SECRETKEY;
		
		$this->redis->select(APP_NUMBER) ;
		$dbnum = $this->redis->hGet('app-num',$this->appName) ;
		$this->redis->select($dbnum) ;
		
		if($this->getRankNum() >= 64)
		{
			$this->appName = NULL ;
			$this->_errno = SAE_ErrParameter ;
			$this->_errmsg = "最多这能有64个rank，现在已经有64个rank了！" ;
			//$this->redis->close() ;
			$this->redis = NULL ;
		}
		
		$prefix = $this->getNomalKey($namespace) ;
		if($this->redis->exists($prefix))
		{
			$this->_errno = -10 ;
			$this->_errmsg = "排行榜已经存在" ;
			$this->appName = NULL ;
			//$this->redis->close() ;
			$this->redis = NULL ;
			return false ;
		}
		$this->number = $number ;
		$this->expire = $expire ;
		return true ;
	}
	
	/**
	 * 设置或添加key，value
	 *
	 * @param string $namespace 排行榜的命名空间
	 * @param string $key 排行榜中的元素的名称
	 * @param int $value 要设置的值
	 * @param bool $rankReturn 是否返回排名的标识
	 * @return int   当rankReturn=true时，返回更新后元素的排名。失败返回false。<br />
	 *               当rankReturn=fasle时，成功返回true，失败返回false。
	 * @author Seeker Zhang
	 * @when: 	1. 存在时:  覆盖，
	 */
	function set($namespace, $key, $value, $rankReturn=false)
	{
		if(!$this->redis)
		{
			$this->_errno = SAE_ErrParameter ;
			$this->errmsg = "未初始化redis！" ;
			return false ;
		}
		
		$prefix = $this->getNomalKey($namespace) ;
		if(!$this->redis->exists($prefix))
		{
			$var = $this->redis->zAdd($prefix,$value,$key) ;
			if(!$var)
			{
				$this->_errno = SAE_ErrParameter ;
				$this->errmsg = "插入元素失败！" ;
				return false ;
			}
			if($this->expire != 0)
				$this->redis->setTimeOut($prefix,$this->expire*60) ;
			if($rankReturn == false)
				return true ;
			else
				return $this->redis->zRank($prefix,$key) ;
		}
		if($this->redis->zCount($prefix,0,PHP_INT_MAX) > $this->number)
		{
			echo "heooasbahivb" ;
			$this->_errno = -2 ;
			$this->errmsg = "元素的排名超出排行榜的范围" ;
			return false ;
		}
		if(!$this->redis->zAdd($prefix,$value,$key))
		{
			$this->redis->zRem($prefix,$key) ;
			$this->redis->zAdd($prefix,$value,$key) ;
		}
		if($rankReturn == false)
			return true ;
		else
			return $this->redis->zRank($prefix,$key) ;
	}
	
	/**
	 * 在排行榜元素$key的值的基础上加$value
	 *
	 * @param string $namespace 排行榜的命名空间
	 * @param string $key 排行榜中的元素的名称
	 * @param int $value 值的增量。
	 * @param bool $rankReturn 是否返回排名的标识
	 * @return int 	当rankReturn=true时，返回更新后元素的排名。失败返回false。<br />
	 *				当rankReturn=fasle时，成功返回true，失败返回false。
	 * @author Seeker Zhang
	 * @when:  不存在时:
	 */
	function increase($namespace, $key, $value, $rankReturn=false)
	{
		if(!$this->redis)
		{
			$this->_errno = SAE_ErrParameter ;
			$this->errmsg = "未初始化redis！" ;
			return false ;
		}
		
		$prefix = $this->getNomalKey($namespace) ;
		$var = $this->redis->zIncrBy($prefix,$value,$key) ;
		if($var == false)
		{
			$this->_errno = SAE_ErrParameter ;
			$this->errmsg = "给value值加增量是错误！" ;
			return false ;
		}
		if($rankReturn)
			return $this->redis->zRank($prefix,$key) ;
		else
			return true ;
	}
	
	/** 
     * 在排行榜元素$key的值的基础上减$value 
     *  
     * @param string $namespace 排行榜的命名空间 
     * @param string $key 排行榜中的元素的名称，key的长度最大为256个字节 
     * @param int $value 值的减量。 
     * @param bool $rankReturn 是否返回排名的标识 
     * @return int     当rankReturn=true时，返回更新后元素的排名，失败返回false。<br /> 
     *                当rankReturn=false时，成功返回true，失败返回false。<br /> 
     *                当元素$key的值改变后，排名不在排行榜的存储范围内，返回false。 
     * @author Seeker Zhang 
     */ 
    function decrease($namespace, $key, $value, $rankReturn=false) 
	{
		$value = 0 - $value ;
		return $this->increase($namespace, $key, $value, $rankReturn) ;
	}
	
	/**
	 * 获得元素的排名
	 *
	 * @param string $namespace 排行榜的命名空间
	 * @param string $key 排行榜中的元素的名称
	 * @return int 	成功时返回$key在排行榜中的排名，失败返回false。
	 * @author Seeker Zhang
	 */
	function getRank($namespace, $key)
	{
		if(!$this->redis)
		{
			$this->_errno = SAE_ErrParameter ;
			$this->errmsg = "未初始化redis！" ;
			return false ;
		}
		
		$prefix = $this->getNomalKey($namespace) ;
		return $this->redis->zRank($prefix,$key) ;
	}
	
	
	/**
     * 获得元素的值
     * 
     * @param string $namespace 排行榜的命名空间
     * @param string $key 排行榜中的元素的名称，key的长度最大为256个字节
     * @return int     成功时返回$key在排行榜中的值，失败返回false。
     * @author Seeker Zhang
     */
	 function getValue($namespace, $key)
	 {
		if(!$this->redis)
		{
			$this->_errno = SAE_ErrParameter ;
			$this->errmsg = "未初始化redis！" ;
			return false ;
		}
		$prefix = $this->getNomalKey($namespace) ;
		return $this->redis->zScore($prefix,$key) ;
	 }
	
	/**
	 * 删除排行榜中的元素$key
	 *
	 * @param string $namespace 排行榜的命名空间
	 * @param string $key 排行榜中的元素的名称
	 * @param bool $rankReturn 是否返回排名的标识
	 * @return int 	当rankReturn=true时，返回删除前元素的排名，失败返回false。<br />
	 *				当rankReturn=false时，成功返回true，失败返回false。
	 * @author Seeker Zhang
	 */
	function delete($namespace, $key, $rankReturn=false)
	{
		if(!$this->redis)
		{
			$this->_errno = SAE_ErrParameter ;
			$this->errmsg = "未初始化redis！" ;
			return false ;
		}
		$prefix = $this->getNomalKey($namespace) ;
		$var = $this->redis->zRank($prefix,$key) ;
		if(!$this->redis->zRem($prefix,$key))
			return false ;
		if($rankReturn)
			return $var ;
		else
			return true ;
	}
	
	/**
	 * 获得实时排行榜数据
	 *
	 * @param string $namespace 排行榜的命名空间
	 * @param bool $order 返回结果是否有序的标识
	 * @param int $offsetFrom 希望获得的起始排名，可选，仅当order为true时有效
	 * @param int $offsetTo 希望获得的终止排名，可选，仅当order为true时有效
	 * @return int 	返回值的形式：array（array（key，value) ....)
	 *	当order=true时，返回排名在offsetFrom和offsetTo之间的有序的结果，offsetFrom<offsetTo ，不设置时返回所有结果。<br />
	 *	当order=false时，返回的结果不是有序的。<br />
	 *	失败时返回false。
	 * @author Seeker Zhang
	 */
	function getList($namespace, $order=false, $offsetFrom=0, $offsetTo=PHP_INT_MAX)
	{
		if(!$this->redis)
		{
			$this->_errno = SAE_ErrParameter ;
			$this->errmsg = "未初始化redis！" ;
			return false ;
		}
		$prefix = $this->getNomalKey($namespace) ;
		$list = array() ;
		$temp = $this->redis->zRange($prefix,$offsetFrom,$offsetTo) ;
		for($i=0;$i<count($temp);$i++)
		{
			array_push($list,array($temp[$i] => $this->redis->zScore($prefix,$temp[$i]))) ;
		}
		return $list ;
	}
	
	/** 
     * 获得应用中现有排行榜名称 
     *  
     * @return array 返回值的形式：array（rankNum，rank1，rank2，....) <br /> 
     * 成功返回包括排行榜的数量和名称，失败返回false。 
     * @author Seeker Zhang 
    */ 
	public function getAllName ()
	{
		if(!$this->redis)
		{
			$this->_errno = SAE_ErrParameter ;
			$this->errmsg = "未初始化redis！" ;
			return false ;
		}
		$prefix = $this->modPrefix.'*' ;
		return $this->redis->Keys($prefix);
	}
	
	/** 
     * 获得排行榜的具体信息 
     *  
     * @param string $namespace 排行榜的命名空间 
     * @return array 返回值的形式：array（info1，info2，....) <br /> 
     * 成功返回包括排行榜的具体信息，失败返回false。 
     * @author Seeker Zhang 
     */ 
    function getInfo($namespace) 
	{
		if(!$this->redis)
		{
			$this->_errno = SAE_ErrParameter ;
			$this->errmsg = "未初始化redis！" ;
			return false ;
		}
		$info = array() ;
		$prefix = $this->getNomalKey($namespace) ;
		$info["data count"] = $this->redis->zCount($prefix,0,PHP_INT_MAX) ;
		if($this->expire == 0 )
			$info["expire time"] = "last modify time" ;
		else
			$info["expire time"] = $this->expire ;
		$info["last modify time"] = date("Y/m/d H:i:s") ;
		$info["rank name"] = $this->appName."_".$namespace ;
		$info["rank size"] = $this->number ;
		return $info ;
	}
	
	/**
	 * 清除数据
	 *
	 * @param string $namespace 排行榜的命名空间
	 * @return int  成功返回true，失败返回false。
	 * @author Seeker Zhang
	 */
	function clear($namespace)
	{
		if(!$this->redis)
		{
			$this->_errno = SAE_ErrParameter ;
			$this->errmsg = "未初始化redis！" ;
			return false ;
		}
		$prefix = $this->getNomalKey($namespace) ;
		return $this->redis->del($prefix)===false?false:true ;
	}
	
	private function getRankNum() 
	{
		if(!$this->redis)
		{
			$this->_errno = SAE_ErrParameter ;
			$this->errmsg = "未初始化count！" ;
			return false ;
		}
		return count($this->getAllName()) ;
	}
	
	private function get_appname()
	{
		/*$path = $_SERVER['PHP_SELF'] ;
		$posend = strpos($path,"/",1) ;
		if($posend == false)
			return NULL ;
		return substr($path,1,$posend-1) ;*/
		return $_SERVER['HTTP_APPNAME'] ;
	}
	
	private function getNomalKey($uKey)
	{
		return $this->modPrefix.$uKey ;
	}
}
?>