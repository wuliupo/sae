<?php

class Memcache
{
	const REDIS_HOST = REDIS_HOST;
	const REDIS_PORT = REDIS_PORT;
	const REDIS_CONNECT_TIMOUT = 60; //s
	private $appName = NULL ;
	private $modPrefix = 'memcache';
	private $arrayModPrefix = 'arrmemchche' ;
	private $redis = NULL ;
	
	public function __construct()
	{
		$this->appName = $this->get_appname() ;
		$this->_errno		= SAE_Success;
		$this->_errmsg		= "OK";
		if(!$this->redis)
		{
			$this->redis = new Redis() ;
			while(!$this->redis->connect(REDIS_HOST,REDIS_PORT))
			{
				echo iconv('UTF-8','GBK','redis 连接错误，正在尝试从新连接。').PHP_EOL ;
				sleep(2) ;
			}
		}
		try{$this->redis->select(APP_NUMBER) ;}
		
		catch(Exception $ttt)
		{
			var_dump($ttt) ;
		}
		$dbnum = $this->redis->hGet('app-num',$this->appName) ;
		$this->redis->select($dbnum) ;
	}
	
	private function get_appname()
	{
		return $_SERVER['HTTP_APPNAME'] ;
	}
	
	public function add($key,$value,$flag=NULL,$expire=0)
	{
		
		if(!$this->redis)
			return false ;
		if($this->keyExists($key))
			return false ;
		if(is_string($value))
		{
			return $this->redis->hSet($this->modPrefix,$key,$value) ;
		}
		else
		{
			$str = serialize($value) ;
			return $this->redis->hSet($this->arrayModPrefix,$key,$str) ;
		}
	}
	
	public function close()
	{
		if($this->redis)
		{
			$this->redis->close() ;
			$this->redis = NULL ;
		}
		return true ;
	}
	
	public function decrement($key,$value = 1)
	{
		if(!$this->redis)
			return false ;
		$value = 0 - $value ;
		return $this->redis->hIncrBy($this->modPrefix,$key,$value) ;
	}
	
	public function delete($key, $timeout=0)
	{
		if(!$this->redis)
			return false ;
		if($this->redis->hExists($this->modPrefix,$key))
			return $this->redis->hDel($this->modPrefix,$key)===false ? false : true ;
		else
			return $this->redis->hDel($this->arrayModPrefix,$key)===false ? false : true ;
	}
	
	public function flush()
	{
		if(!$this->redis)
			return false ;
		if($this->redis->del($this->modPrefix) === false)
		{
			return false ;
		}
		if($this->redis->del($this->arrayModPrefix)  === false)
			return false ;
		return true ;
	}
	
	public function get($key, &$flags = 0)
	{
		if(!$this->redis)
			return false ;
		if($this->redis->hExists($this->modPrefix,$key))
			return $this->redis->hGet($this->modPrefix,$key) ;
		else
		{
			$str = $this->redis->hGet($this->arrayModPrefix,$key) ;
			return unserialize($str) ;
		}
	}
	
	public function getStats()
	{
		$arr = array('pid' => 25855, 'uptime' => 413082, 'time' => 1321958757, 'version' => '1.4.5', 'pointer_size' => 64, 'rusage_user' => 6549.280000, 'rusage_system' => 5356.970000, 'curr_connections' => 9, 'total_connections' => 60235206, 'connection_structures' => 338, 'cmd_get' => 0, 'cmd_set' => 0, 'cmd_flush' => 0, 'get_hits' => 0, 'get_misses' => 0, 'delete_misses' => 0, 'delete_hits' => 0, 'incr_misses' => 0, 'incr_hits' => 0, 'decr_misses' => 0, 'decr_hits' => 0, 'cas_misses' => 0, 'cas_hits' => 0, 'cas_badval' => 0, 'bytes_read' => 47286, 'bytes_written' => 284584, 'limit_maxbytes' => 1048576, 'conn_yields' => 0, 'bytes' => 0, 'curr_items' => 0, 'total_items' => 0, 'evictions' => 0, 'reclaimed' => 0 ) ;
		return $arr ;
	}
	
	public function getVersion()
	{
		return "1.4.5" ;
	}
	
	public function increment($key, $value = 1)
	{
		if(!$this->redis)
			return false ;
		return $this->redis->hIncrBy($this->modPrefix,$key,$value) ;
	}
	
	public function replace($key, $var, $flag=0, $expire=0)
	{
		if(!$this->redis)
			return false ;
		if(!$this->keyExists($key))
			return false ;
		
		if($this->redis->hExists($this->modPrefix,$key))
		{
			$this->redis->hDel($this->modPrefix,$key) ;
		}
		else if($this->redis->hExists($this->arrayModPrefix,$key))
		{
			$this->redis->hDel($this->arrayModPrefix,$key) ;
		}
		else
			return false ;
		
		if(is_string($var))
		{
			return $this->redis->hSet($this->modPrefix,$key,$var) ;
		}
		else
		{
			return $this->redis->hSet($this->arrayModPrefix,$key,serialize($var)) ;
		}
	}
	
	public function set($key, $var, $flag=0, $expire=0)
	{
		if(!$this->redis)
			return false ;
		
		if($this->redis->hExists($this->modPrefix,$key))
		{
			$this->redis->hDel($this->modPrefix,$key) ;
		}
		else
		{
			$this->redis->hDel($this->arrayModPrefix,$key) ;
		}
		
		if(is_string($var))
		{
			return $this->redis->hSet($this->modPrefix,$key,$var) ;
		}
		else
		{
			return $this->redis->hSet($this->arrayModPrefix,$key,serialize($var)) ;
		}
	}
	
	public function setCompressThreshold($threshold, $min_savings)
	{
		return true ;
	}
	
	private function keyExists($key)
	{
		if($this->redis->hExists($this->modPrefix,$key))
			return true ;
		else
			return $this->redis->hExists($this->arrayModPrefix,$key) ;
	}
}

function memcache_init() 
{
	return new Memcache() ;
}

function memcache_add($obj,$key, $var, $flag=null, $expire=null)
{
	if(!($obj instanceof Memcache))
		return false;
	return $obj->add($key, $var, $flag, $expire);
}

function memcache_close($obj){
    if(!($obj instanceof Memcache))
		return false;
	$obj->close() ;
}

function memcache_delete($obj,$key, $timeout=0)
{
	if(!($obj instanceof Memcache))
		return false;
	return $obj->delete($key, $timeout);
}

function memcache_flush($obj)
{
	if(!($obj instanceof Memcache))
		return false;
	return $obj->flush();
}

function memcache_get($obj,$key, &$flags=0)
{
	if(!($obj instanceof Memcache))
		return false;
	return $obj->get($key, $flags);
}

function memcache_get_stats($obj)
{
	if(!($obj instanceof Memcache))
		return false;
	return $obj->getStats() ;
}

function memcache_get_version($obj)
{
	if(!($obj instanceof Memcache))
		return false;
	return $obj->getVersion() ;
}

function memcache_increment($obj,$key, $value=1)
{
	if(!($obj instanceof Memcache))
		return false;
	return $obj->increment($key, $value);
}

function memcache_replace($obj,$var, $flag=0, $expire=0)
{
	if(!($obj instanceof Memcache))
		return false;
	return $obj->replace($key, $var, $flag, $expire);
}

function memcache_set($obj,$key, $var, $flag=0, $expire=0)
{
	if(!($obj instanceof Memcache))
		return false;
	return $obj->set($key, $var, $flag, $expire);
}

function memcache_set_compress_threshold($obj, $threshold, $min_savings)
{
	return true ;
}

?>