<?php

/**
 * SAE邮件服务
 *
 * @package sae
 * @version $Id$
 * @author lijun
 */

/**
 * SAE计数器服务
 *
 * <code>
 * <?php
 * $c = new SaeCounter();
 * $c->create('c1');  //创建计数器c1 创建成功返回true 如果该名字已被占用将返回false
 * $c->set('c1',100); // 返回true
 * $c->incr('c1'); // 返回101
 * $c->get('c1'); // 返回c1的值101
 * $c->decr('c1'); // 返回100
 * ?>
 * </code>
 *
 * @author  chenlei
 * @package sae
 */

class SaeCounter extends SaeObject
{
	const REDIS_HOST                                        = REDIS_HOST;
	const REDIS_PORT                                        = REDIS_PORT;
	const REDIS_CONNECT_TIMOUT              = 60; //s

	private $_accesskey 			= "";
	private $_secretkey 			= "";
	private $_errno					= SAE_Success;
	private $_errmsg           		= "OK";
	private $appName          		= NULL;
	private $modPrefix				= 'counter';
	private $redis	                = NULL;
	
	/**
	 * 构造函数
	 */
	public function __construct()
	{
		$this->redis = new Redis() ;
		while(!$this->redis->connect(REDIS_HOST,REDIS_PORT))
		{
			echo iconv('UTF-8','GBK','redis 连接错误，正在尝试从新连接。').PHP_EOL ;
			sleep(2) ;
		}

		
		$this->appName = trim($this->get_appname());
		$this->_accesskey = SAE_ACCESSKEY;
		$this->_secretkey = SAE_SECRETKEY;
		
		$this->redis->select(APP_NUMBER) ;
		$dbnum = $this->redis->hGet('app-num',$this->appName) ;
		$this->redis->select($dbnum) ;

		if($this->redis->hLen($this->modPrefix) >= 100 )
		{
			$this->appName = NULL ;
			$this->_errno = SAE_ErrParameter ;
			$this->_errmsg = "最多这能有100个计数器，现在已经有100个计数器了！" ;
			//$this->redis->close() ;
			$this->redis = NULL ;
		}
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
	 * 返回错误信息
	 *
	 * @return string
	 */
	public function errno()
	{
		return $this->_errno;
	}
	
	/**
	 * 取得错误信息
	 *
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
	 * 增加一个计数器
	 *
	 * @param string $name 计数器名称
	 * @param int $value 计数器初始值，默认值为0
	 * @return bool 成功返回true，失败返回false（计数器已存在返回false）
	 */
	public function create($name, $value = 0)
	{
		if(!$this->redis)
		{
			$this->_errno = SAE_ErrParameter ;
			$this->errmsg = "未初始化redis！" ;
			return false ;
		}
		if(!$name)
		{
			$this->_errno = SAE_ErrParameter ;
			$this->_errmsg ='请传入计数器名称！';
			return false;
		}
		if(!$this->redis->hSetNx($this->modPrefix,$name,$value))
		{
			if($this->redis->hExists($this->modPrefix,$name))
			{
				$this->_errno = SAE_ErrUnknown ;
				$this->_errmsg = '计数器已经存在！';
			}
			else
			{
				$this->_errno = SAE_ErrUnknown ;
				$this->_errmsg = '未知错误！';
			}
			return false ;
		}
		return true ;
	}
	
	/**
	 * 删除一个计数器
	 *
	 * @param string $name 计数器名称
	 * @return bool 成功返回true，失败返回false（计数器不存在返回false）
	 */
	public function remove($name)
	{
		if(!$this->redis)
		{
			$this->_errno = SAE_ErrParameter ;
			$this->errmsg = "未初始化redis！" ;
			return false ;
		}
		if(!$name)
		{
			$this->_errno = SAE_ErrParameter ;
			$this->_errmsg ='请传入计数器名称！';
			return false;
		}
		if($this->redis->hExists($this->modPrefix,$name))
		{
			return $this->redis->hDel($this->modPrefix,$name)===false? false : true ;
		}
		else
		{
			$this->_errno = SAE_ErrUnknown ;
			$this->_errmsg 	= '计数器不存在！';
			return false;
		}
	}
	
	/**
	 * 判断一个计数器是否存在
	 *
	 * @param string $name 计数器名称
	 * @return bool 存在返回true，不存在返回false
	 */
	public function exists($name)
	{
		if(!$this->redis)
		{
			$this->_errno = SAE_ErrParameter ;
			$this->errmsg = "未初始化redis！" ;
			return false ;
		}
		if(!$name)
		{
			$this->_errno = SAE_ErrParameter ;
			$this->_errmsg ='请传入计数器名称！';
			return false;
		}
		return $this->redis->hExists($this->modPrefix,$name) ;
	}
	
	/**
     * 获取当前应用的所有计数器数据
     * 
     * @return array|bool成功返回数组array，失败返回false
     */        
    public function listAll()
    {
		if(!$this->redis)
		{
			$this->_errno = SAE_ErrParameter ;
			$this->errmsg = "未初始化redis！" ;
			return false ;
		}
		return $this->redis->hGetAll($this->modPrefix) ;
    }

	
	/**
	 * 获取当前应用的计数器个数
	 *
	 * @return int|bool 成功返回计数器个数，失败返回false
	 */
	public function length()
	{
		if(!$this->redis)
		{
			$this->_errno = SAE_ErrParameter ;
			$this->errmsg = "未初始化redis！" ;
			return false ;
		}
		return $this->redis->hLen($this->modPrefix) ;
	}
	
	/**
	 * 获取指定计数器的值
	 *
	 * @param string $name 计数器名称
	 * @return int|bool 成功返回该计数器的值，失败返回false
	 */
	public function get($name)
	{
		if(!$this->redis)
		{
			$this->_errno = SAE_ErrParameter ;
			$this->errmsg = "未初始化redis！" ;
			return false ;
		}
		if(!$name)
		{
			$this->_errno = SAE_ErrParameter ;
			$this->_errmsg ='请传入计数器名称！';
			return false;
		}
		return $this->redis->hGet($this->modPrefix,$name) ;
	}
	
	/**
	 * 重新设置指定计数器的值
	 *
	 * @param string $name 计数器名称
	 * @param int $value 计数器的值
	 * @return bool 成功返回true，失败返回false
	 */
	public function set($name, $value)
	{
		if(!$this->redis)
		{
			$this->_errno = SAE_ErrParameter ;
			$this->errmsg = "未初始化redis！" ;
			return false ;
		}
		if(!$name)
		{
			$this->_errno = SAE_ErrParameter ;
			$this->_errmsg ='请传入计数器名称！';
			return false;
		}
		$this->redis->hSet($this->modPrefix,$name,$value) ;
		return true ;
	}
	
	/**
	 * 同时获取多个计数器值
	 *
	 * @param array $names 计数器名称数组，array($name1, $name2, ...)
	 * @return array|bool 成功返回关联数组，失败返回false
	 */
	public function mget($names)
	{
		if(!$this->redis)
		{
			$this->_errno = SAE_ErrParameter ;
			$this->errmsg = "未初始化redis！" ;
			return false ;
		}
		if(!is_array($names) || count($names) < 1) 
		{
			$this->_errmsg ='请传入计数器名称数组！';
			return false;
		}
		
		$rstArr        = array();
		foreach ($names as $name)
		{
			$ret        = $this->get($name);
			if($ret === false)return false;
			else
			{
				$rstArr[$name] = $ret;
			}
		}
		return $rstArr;
	}
	 
	/**
	 * 获取当前应用所有计数器的值
	 *
	 * @return array|bool 成功返回关联数组，失败返回false
	 */
	public function getall()
	{
		if(!$this->redis)
		{
			$this->_errno = SAE_ErrParameter ;
			$this->errmsg = "未初始化redis！" ;
			return false ;
		}
		$arr = $this->redis->hGetAll($this->modPrefix) ;
		if(!$arr)
		{
			if(!$this->redis->exists($this->modPrefix))
			{
				$this->_errno = SAE_ErrUnknown ;
				$this->_errmsg		= '当前应用没有计数器！';
			}
			else
			{
				$this->_errno = SAE_ErrUnknown ;
				$this->_errmsg = '未知错误！';
			}
			return false ;
		}
		return $arr ;
	}
	
	/**
	 * 对指定计数器做加法操作
	 *
	 * @param string $name 计数器名称
	 * @param int $value 计数器增加值
	 * @return int|bool 成功返回该计数器的当前值，失败返回false（计数器不存在返回false）
	 */
	public function incr($name, $value = 1)
	{
		if(!$this->redis)
		{
			$this->_errno = SAE_ErrParameter ;
			$this->errmsg = "未初始化redis！" ;
			return false ;
		}
		if(!$name)
		{
			$this->_errno = SAE_ErrParameter ;
			$this->_errmsg ='请传入计数器名称！';
			return false;
		}
		if(!$this->redis->hExists($this->modPrefix,$name))
		{
			$this->_errno = SAE_ErrUnknown ;
			$this->_errmsg    = '计数器不存在!';
			return false;
		}
		return $this->redis->hIncrBy($this->modPrefix,$name,$value) ;
	}
	
	/**
	 * 对指定计数器做减法操作
	 *
	 * @param string $name 计数器名称
	 * @param int $value 计数器减少值
	 * @return int|bool 成功返回该计数器的当前值，失败返回false（计数器不存在返回false）
	 */
	public function decr($name, $value = 1)
	{
		$value = 0 - $value ;
		return $this->incr($name, $value) ;
	}
	
	private function get_appname()
	{
		return $_SERVER['HTTP_APPNAME'] ;
	}
}
 ?>