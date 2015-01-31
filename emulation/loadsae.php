<?php
/**
 * 加载所有实现类
*/

include_once __DIR__.DIRECTORY_SEPARATOR.'mysqlconf'.DIRECTORY_SEPARATOR.'mysql.php' ;
$SAEStorage = realpath(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'storage') ;

// storage
define( 'SAE_STOREHOST', 'http://stor.sae.sina.com.cn/storageApi.php' );
define('SAE_STORAGE_STORAGE_DIR',$SAEStorage.DIRECTORY_SEPARATOR.'storage'); //ccc
define('VCODE_HOST','127.0.0.1:'.HTTP_PORT.'/sae/vcode.php') ;
define('XHPROF_HOST','127.0.0.1:'.HTTP_PORT.'/sae/xhprof/xhprof_html/index.php') ;
define('STORAGE_HOST','127.0.0.1:'.HTTP_PORT.'/storage') ;

define('SAE_MYSQL_DB', 'app_'.$_SERVER['HTTP_APPNAME']);

define('SAE_TMP_PATH', $SAEStorage.DIRECTORY_SEPARATOR.'tempstorage'.DIRECTORY_SEPARATOR.$_SERVER['HTTP_APPNAME']);

define( 'SAE_APPNAME', $_SERVER['HTTP_APPNAME'] );
define( 'SAE_APPVERSION', $_SERVER['HTTP_APPVERSION']);
define('SAE_ACCESSKEY', 'sae');
define('SAE_SECRETKEY', 'sae');

//$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_APPNAME'].".sinaapp.com" ; 


// gravity define
define("SAE_NorthWest", 1);
define("SAE_North", 2);
define("SAE_NorthEast",3);
define("SAE_East",6);
define("SAE_SouthEast",9);
define("SAE_South",8);
define("SAE_SouthWest",7);
define("SAE_West",4);
define("SAE_Static",10);
define("SAE_Center",5);

// font stretch
define("SAE_Undefined",0);
define("SAE_Normal",1);
define("SAE_UltraCondensed",2);
define("SAE_ExtraCondensed",3);
define("SAE_Condensed",4);
define("SAE_SemiCondensed",5);
define("SAE_SemiExpanded",6);
define("SAE_Expanded",7);
define("SAE_ExtraExpanded",8);
define("SAE_UltraExpanded",9);

$_SERVER['DOCUMENT_ROOT'] = trim($_SERVER['DOCUMENT_ROOT']).DIRECTORY_SEPARATOR.$_SERVER['HTTP_APPNAME'].DIRECTORY_SEPARATOR.$_SERVER['HTTP_APPVERSION'] ;

// font style
define("SAE_Italic",2);
define("SAE_Oblique",3);


// font name
define("SAE_SimSun",__DIR__.DIRECTORY_SEPARATOR.'fonts'.DIRECTORY_SEPARATOR.'wqy-zenhei.ttc');
define("SAE_SimKai",__DIR__.DIRECTORY_SEPARATOR.'fonts'.DIRECTORY_SEPARATOR.'wqy-zenhei.ttc');
define("SAE_SimHei",__DIR__.DIRECTORY_SEPARATOR.'fonts'.DIRECTORY_SEPARATOR.'wqy-zenhei.ttc');
define("SAE_Arial",__DIR__.DIRECTORY_SEPARATOR.'fonts'.DIRECTORY_SEPARATOR.'wqy-zenhei.ttc');
define("SAE_MicroHei",__DIR__.DIRECTORY_SEPARATOR.'wqy-microhei.ttc');

// anchor postion
define("SAE_TOP_LEFT","tl");
define("SAE_TOP_CENTER","tc");
define("SAE_TOP_RIGHT","tr");
define("SAE_CENTER_LEFT","cl");
define("SAE_CENTER_CENTER","cc");
define("SAE_CENTER_RIGHT","cr");
define("SAE_BOTTOM_LEFT","bl");
define("SAE_BOTTOM_CENTER","bc");
define("SAE_BOTTOM_RIGHT","br");

// errno define
define("SAE_Success", 0); // OK
define("SAE_ErrKey", 1); // invalid accesskey or secretkey
define("SAE_ErrForbidden", 2); // access fibidden for quota limit
define("SAE_ErrParameter", 3); // parameter not exist or invalid
define("SAE_ErrInternal", 500); // internal Error
define("SAE_ErrUnknown", 999); // unknown error

//redis app number
define("APP_NUMBER",10) ;

define('SAE_Font_Sun',__DIR__.DIRECTORY_SEPARATOR.'fonts'.DIRECTORY_SEPARATOR.'wqy-zenhei.ttc');
define('SAE_Font_Kai',__DIR__.DIRECTORY_SEPARATOR.'fonts'.DIRECTORY_SEPARATOR.'wqy-zenhei.ttc');
define('SAE_Font_Hei', __DIR__.DIRECTORY_SEPARATOR.'fonts'.DIRECTORY_SEPARATOR.'wqy-zenhei.ttc');
define('SAE_Font_MicroHei',__DIR__.DIRECTORY_SEPARATOR.'fonts'.DIRECTORY_SEPARATOR.'wqy-zenhei.ttc');


/**
 * Sae基类
 * 
 * STDLib的所有class都应该继承本class,并实现SaeInterface接口  
 *
 * @author Easychen <easychen@gmail.com>
 * @version $Id$
 * @package sae
 * @ignore
 */
/**
 * SaeObject
 *
 * @package sae
 * @ignore
 */


abstract class SaeObject implements SaeInterface
{
	function __construct()
	{
		// 
	}
}
/**
 * SaeInterface , public interface of all sae client apis
 *
 * all sae client classes must implement these method for setting accesskey and secretkey , getting error infomation.
 * @package sae
 * @ignore
 **/

interface SaeInterface
{
	public function errmsg();
	public function errno();
	public function setAuth( $akey , $skey );
}

function get_appname()
{
	return $_SERVER['HTTP_APPNAME'] ;
}

function get_app_version()
{
	return $_SERVER['HTTP_APPVERSION'] ;
}

foreach(glob(__dir__.DIRECTORY_SEPARATOR.'*.class.php') as $filename){
	if($filename != __dir__.DIRECTORY_SEPARATOR.'mYaml.class.php')
		include_once($filename);
}

function saeAutoLoader( $class_name )
{
    $file = dirname( __FILE__ ) . '/' . strtolower($class_name) . '.class.php';
    if( file_exists($file) )
        include_once( $file );
    if(!class_exists($class_name) && function_exists('__autoload'))
        __autoload($class_name);
    if(!class_exists($class_name) && function_exists('__sae_autoload'))
        __sae_autoload($class_name);
}


spl_autoload_register('saeAutoLoader');	

//ccc this just simulate the online sae_debug() function
function sae_debug($str = NULL)
{
	if(!$str)return false;
	else
	{
		echo $str.'<br>';
	}
}

function is_https() {
    return ( ( isset($_SERVER['HTTP_APPMASK']) && $_SERVER['HTTP_APPMASK'] & 0x1 ) || ( isset($_SERVER['HTTP_X_PROTO']) && $_SERVER['HTTP_X_PROTO'] == 'SSL' ) );
}
if ( is_https() ) {
    $_SERVER['HTTPS'] = 'on';
}

function sae_xhprof_start()
{
	xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
}

function sae_xhprof_end()
{
	$xhprof_data = xhprof_disable();
	$appname = get_appname() ;
	$XHPROF_ROOT = realpath(dirname(__FILE__) .'/xhprof');
	include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_lib.php";
	include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_runs.php";
	$xhprof_runs = new XHProfRuns_Default();
	
	// save raw data for this profiler run using default
	// implementation of iXHProfRuns.
	$xhprof_runs = new XHProfRuns_Default();

	// save the run under a namespace "appname"
	$run_id = $xhprof_runs->save_run($xhprof_data, $appname);
	echo "---------------\n".
     "Assuming you have set up the http based UI for \n".
     "XHProf at some address, you can view run at \n".
     "<a href=\"http://".XHPROF_HOST."?run=$run_id&source=$appname\">http://".XHPROF_HOST."?run=$run_id&source=$appname</a> \n".
     "---------------\n";
}


if ( ! in_array("saemc", stream_get_wrappers()) )
stream_register_wrapper("saemc", "SaeMemcacheWrapper");

class SaeMemcacheWrapper // implements WrapperInterface
{
	public $dir_mode = 16895 ; //040000 + 0222;
	public $file_mode = 33279 ; //0100000 + 0777;


	public function __construct()
	{
		$this->mc = memcache_init(); //>>>
	}

	public function mc() {
		if ( !isset( $this->mc ) ) $this->mc = memcache_init();
		return $this->mc;
	}

	public function stream_open( $path , $mode , $options , &$opened_path)
	{
		$this->position = 0;
		$this->mckey = trim(substr($path, 8));
		$this->mode = $mode;
		$this->options = $options;

		if ( in_array( $this->mode, array( 'r', 'r+', 'rb' ) ) ) {
			if ( $this->mccontent = memcache_get( $this->mc, $this->mckey ) ) {  //ccc APPNAME as prefix when get and set
				$this->get_file_info( $this->mckey ); //ccc
				$this->stat['mode'] = $this->stat[2] = $this->file_mode;
			} else {
				trigger_error("fopen({$path}): failed to read from Memcached: No such key.", E_USER_WARNING);
				return false;
			}
		} elseif ( in_array( $this->mode, array( 'a', 'a+', 'ab' ) ) ) {
			if ( $this->mccontent = memcache_get( $this->mc , $this->mckey ) ) {
				$this->get_file_info( $this->mckey );
				$this->stat['mode'] = $this->stat[2] = $this->file_mode;
				$this->position = strlen($this->mccontent);
			} else {
				$this->mccontent = '';
				$this->stat['ctime'] = $this->stat[10] = time();
			}
		} elseif ( in_array( $this->mode, array( 'x', 'x+', 'xb' ) ) ) {
			if ( !memcache_get( $this->mc , $this->mckey ) ) {
				$this->mccontent = '';
				$this->statinfo_init();
				$this->stat['ctime'] = $this->stat[10] = time();
			} else {
				trigger_error("fopen({$path}): failed to create at Memcached: Key exists.", E_USER_WARNING);
				return false;
			}
		} elseif ( in_array( $this->mode, array( 'w', 'w+', 'wb' ) ) ) {
			$this->mccontent = '';
			$this->statinfo_init();
			$this->stat['ctime'] = $this->stat[10] = time();
		} else {
			$this->mccontent = memcache_get( $this->mc , $this->mckey );
		}

		return true;
	}

	public function stream_read($count)
	{
		if (in_array($this->mode, array('w', 'x', 'a', 'wb', 'xb', 'ab') ) ) {
			return false;
		}

		$ret = substr( $this->mccontent , $this->position, $count);
		$this->position += strlen($ret);

		$this->stat['atime'] = $this->stat[8] = time();
		$this->stat['uid'] = $this->stat[4] = 0;
		$this->stat['gid'] = $this->stat[5] = 0;

		return $ret;
	}

	public function stream_write($data)
	{
		if ( in_array( $this->mode, array( 'r', 'rb' ) ) ) {
			return false;
		}

		$left = substr($this->mccontent, 0, $this->position);
		$right = substr($this->mccontent, $this->position + strlen($data));
		$this->mccontent = $left . $data . $right;

		if ( memcache_set( $this->mc , $this->mckey , $this->mccontent ) ) {
			$this->stat['mtime'] = $this->stat[9] = time();
			$this->position += strlen($data);
			return $this->stat['size'] = $this->stat[7] = strlen( $data );
		}
		else return false;
	}

	public function stream_close()
	{

		memcache_set( $this->mc , $this->mckey.'.meta' ,  serialize($this->stat)  );
		//memcache_close( $this->mc );
	}


	public function stream_eof()
	{

		return $this->position >= strlen( $this->mccontent  );
	}

	public function stream_tell()
	{

		return $this->position;
	}

	public function stream_seek($offset , $whence = SEEK_SET)
	{

		switch ($whence) {
			case SEEK_SET:

				if ($offset < strlen( $this->mccontent ) && $offset >= 0) {
					$this->position = $offset;
					return true;
				}
				else
				return false;

				break;

			case SEEK_CUR:

				if ($offset >= 0) {
					$this->position += $offset;
					return true;
				}
				else
				return false;

				break;

			case SEEK_END:

				if (strlen( $this->mccontent ) + $offset >= 0) {
					$this->position = strlen( $this->mccontent ) + $offset;
					return true;
				}
				else
				return false;

				break;

			default:

				return false;
		}
	}

	public function stream_stat()
	{
		return $this->stat;
	}

	// ============================================
	public function mkdir($path , $mode , $options)
	{
		$path = trim(substr($path, 8));


		//echo "回调mkdir\n";
		$path  = rtrim( $path  , '/' );

		$this->stat = $this->get_file_info( $path );
		$this->stat['ctime'] = $this->stat[10] = time();
		$this->stat['mode'] = $this->stat[2] = $this->dir_mode;

		//echo "生成新的stat数据" . print_r( $this->stat , 1 );

		memcache_set( $this->mc() , $path.'.meta' ,  serialize($this->stat)  );

		//echo "写入MC. key= " . $path.'.meta ' .  memcache_get( $this->mc , $path.'.meta'  );
		memcache_close( $this->mc );


		return true;
	}

	public function rename($path_from , $path_to)
	{
		$path_from = trim(substr($path_from, 8));
		$path_to = trim(substr($path_to, 8));


		memcache_set( $this->mc() , $path_to , memcache_get( $this->mc() , $path_from ) );
		memcache_set( $this->mc() , $path_to . '.meta' , memcache_get( $this->mc() , $path_from . '.meta' ) );
		memcache_delete( $this->mc() , $path_from );
		memcache_delete( $this->mc() , $path_from.'.meta' );
		clearstatcache( true );
		return true;
	}

	public function rmdir($path , $options)
	{
		$path = trim(substr($path, 8));


		$path  = rtrim( $path  , '/' );

		memcache_delete( $this->mc() , $path .'.meta'  );
		clearstatcache( true );
		return true;
	}

	public function unlink($path)
	{
		$path = trim(substr($path, 8));
		$path  = rtrim( $path  , '/' );

		memcache_delete( $this->mc() , $path );
		memcache_delete( $this->mc() , $path . '.meta' );
		clearstatcache( true );
		return true;
	}

	public function url_stat($path , $flags)
	{
		$path = trim(substr($path, 8));
		$path  = rtrim( $path  , '/' );

		if ( !$this->is_file_info_exists( $path ) ) {
			return false;
		} else {
			$this->get_file_info( $path );
			return $this->stat;
		}
	}






	// ============================================

	public function is_file_info_exists( $path )
	{
		//echo "获取MC数据 key= " .  $path.'.meta' ;
		$d = memcache_get( $this->mc() , $path . '.meta' );
		//echo "\n返回数据为" . $d . "\n";
		return $d;
	}

	public function get_file_info( $path )
	{
		if ( $stat = memcache_get( $this->mc() , $path . '.meta' ) )
		return $this->stat =  unserialize($stat);
		else $this->statinfo_init();
	}

	public function statinfo_init( $is_file = true )
	{
		$this->stat['dev'] = $this->stat[0] = 0;
		$this->stat['ino'] = $this->stat[1] = mt_rand(10000, PHP_INT_MAX);

		if( $is_file )
		$this->stat['mode'] = $this->stat[2] = $this->file_mode;
		else
		$this->stat['mode'] = $this->stat[2] = $this->dir_mode;

		$this->stat['nlink'] = $this->stat[3] = 0;
		$this->stat['uid'] = $this->stat[4] = 0;
		$this->stat['gid'] = $this->stat[5] = 0;
		$this->stat['rdev'] = $this->stat[6] = 0;
		$this->stat['size'] = $this->stat[7] = 0;
		$this->stat['atime'] = $this->stat[8] = 0;
		$this->stat['mtime'] = $this->stat[9] = 0;
		$this->stat['ctime'] = $this->stat[10] = 0;
		$this->stat['blksize'] = $this->stat[11] = 0;
		$this->stat['blocks'] = $this->stat[12] = 0;

	}

	public function dir_closedir() {
		return false;
	}

	public function dir_opendir($path, $options) {
		return false;
	}

	public function dir_readdir() {
		return false;
	}

	public function dir_rewinddir() {
		return false;
	}

	public function stream_cast($cast_as) {
		return false;
	}

	public function stream_flush() {
		return false;
	}

	public function stream_lock($operation) {
		return false;
	}

	public function stream_set_option($option, $arg1, $arg2) {
		return false;
	}
}

/* BEGIN *******************  HTTP Wrapper By Elmer Zhang At 16/Mar/2010 14:47 ****************/

class SaeHttpWrapper // implements WrapperInterface
{
	private $content;

	public function stream_open( $path , $mode , $options , &$opened_path)
	{
		$this->position = 0;
		$this->options = $options;

		$fu = new SaeFetchUrl();
		$this->content = $fu->fetch($path);

		if ($this->content === false) {
			return false;
		} else {
			$GLOBALS['http_response_header'] = $fu->responseHeaders(false);
			return true;
		}
	}

	public function stream_read($count)
	{

		$ret = substr( $this->content , $this->position, $count);
		$this->position += strlen($ret);

		return $ret;
	}

	public function stream_close()
	{
	}


	public function stream_eof()
	{

		return $this->position >= strlen( $this->content  );
	}

	public function stream_tell()
	{


		return $this->position;
	}

	public function stream_seek($offset , $whence = SEEK_SET)
	{


		switch ($whence) {
			case SEEK_SET:

				if ($offset < strlen( $this->content ) && $offset >= 0) {
					$this->position = $offset;
					return true;
				}
				else
				return false;

				break;

			case SEEK_CUR:

				if ($offset >= 0) {
					$this->position += $offset;
					return true;
				}
				else
				return false;

				break;

			case SEEK_END:

				if (strlen( $this->content ) + $offset >= 0) {
					$this->position = strlen( $this->content ) + $offset;
					return true;
				}
				else
				return false;

				break;

			default:

				return false;
		}
	}

	public function stream_stat() {
		return false;
	}

	public function url_stat() {
		return false;
	}

	public function dir_closedir() {
		return false;
	}

	public function dir_opendir($path, $options) {
		return false;
	}

	public function dir_readdir() {
		return false;
	}

	public function dir_rewinddir() {
		return false;
	}

	public function mkdir($path, $mode, $options) {
		return false;
	}

	public function rename($path_from, $path_to) {
		return false;
	}

	public function rmdir($path, $options) {
		return false;
	}

	public function stream_cast($cast_as) {
		return false;
	}

	public function stream_flush() {
		return false;
	}

	public function stream_lock($operation) {
		return false;
	}

	public function stream_set_option($option, $arg1, $arg2) {
		return false;
	}

	public function unlink() {
		return false;
	}

}

if ( in_array( "http", stream_get_wrappers() ) ) {
	stream_wrapper_unregister("http");
}
stream_wrapper_register( "http", "SaeHttpWrapper" )
or die( "Failed to register protocol" );


/* END *********************  HTTP Wrapper By Elmer Zhang At 16/Mar/2010 14:47 ****************/



/* BEGIN *******************  Storage Wrapper By Elmer Zhang At 16/Mar/2010 14:47 ****************/

class SaeStorageWrapper // implements WrapperInterface
{
	private $writen = true;

	public function __construct()
	{
		$this->stor = new SaeStorage();
	}

	public function stor() {
		if ( !isset( $this->stor ) ) $this->stor = new SaeStorage();
	}

	public function stream_open( $path , $mode , $options , &$opened_path)
	{
		$pathinfo = parse_url($path);
		$this->domain = $pathinfo['host'];
		$this->file = ltrim(strstr($path, $pathinfo['path']), '/\\');
		$this->position = 0;
		$this->mode = $mode;
		$this->options = $options;

		// print_r("OPEN\tpath:{$path}\tmode:{$mode}\toption:{$option}\topened_path:{$opened_path}\n");

		if ( in_array( $this->mode, array( 'r', 'r+', 'rb' ) ) ) {
			if ( $this->fcontent = $this->stor->read($this->domain, $this->file) ) {
			} else {
				trigger_error("fopen({$path}): failed to read from Storage: No such domain or file.", E_USER_WARNING);
				return false;
			}
		} elseif ( in_array( $this->mode, array( 'a', 'a+', 'ab' ) ) ) {
			trigger_error("fopen({$path}): Sorry, saestor does not support appending", E_USER_WARNING);
			if ( $this->fcontent = $this->stor->read($this->domain, $this->file) ) {
			} else {
				trigger_error("fopen({$path}): failed to read from Storage: No such domain or file.", E_USER_WARNING);
				return false;
			}
		} elseif ( in_array( $this->mode, array( 'x', 'x+', 'xb' ) ) ) {
			if ( !$this->stor->getAttr($this->domain, $this->file) ) {
				$this->fcontent = '';
			} else {
				trigger_error("fopen({$path}): failed to create at Storage: File exists.", E_USER_WARNING);
				return false;
			}
		} elseif ( in_array( $this->mode, array( 'w', 'w+', 'wb' ) ) ) {
			$this->fcontent = '';
		} else {
			$this->fcontent = $this->stor->read($this->domain, $this->file);
		}

		return true;
	}

	public function stream_read($count)
	{
		if (in_array($this->mode, array('w', 'x', 'a', 'wb', 'xb', 'ab') ) ) {
			return false;
		}

		$ret = substr( $this->fcontent , $this->position, $count);
		$this->position += strlen($ret);

		return $ret;
	}

	public function stream_write($data)
	{	
		if ( in_array( $this->mode, array( 'r', 'rb' ) ) ) {
			return false;
		}

		// print_r("WRITE\tcontent:".strlen($this->fcontent)."\tposition:".$this->position."\tdata:".strlen($data)."\n");

		$left = substr($this->fcontent, 0, $this->position);
		$right = substr($this->fcontent, $this->position + strlen($data));
		$this->fcontent = $left . $data . $right;

		//if ( $this->stor->write( $this->domain, $this->file, $this->fcontent ) ) {
		$this->position += strlen($data);
		if ( strlen( $data ) > 0 )
		$this->writen = false;

		return strlen( $data );
		//}
		//else return false;
	}

	public function stream_close()
	{
		if (!$this->writen) {
			$this->stor->write( $this->domain, $this->file, $this->fcontent );
			$this->writen = true;
		}
	}


	public function stream_eof()
	{

		return $this->position >= strlen( $this->fcontent  );
	}

	public function stream_tell()
	{

		return $this->position;
	}

	public function stream_seek($offset , $whence = SEEK_SET)
	{


		switch ($whence) {
			case SEEK_SET:

				if ($offset < strlen( $this->fcontent ) && $offset >= 0) {
					$this->position = $offset;
					return true;
				}
				else
				return false;

				break;

			case SEEK_CUR:

				if ($offset >= 0) {
					$this->position += $offset;
					return true;
				}
				else
				return false;

				break;

			case SEEK_END:

				if (strlen( $this->fcontent ) + $offset >= 0) {
					$this->position = strlen( $this->fcontent ) + $offset;
					return true;
				}
				else
				return false;

				break;

			default:

				return false;
		}
	}

	public function unlink($path)
	{
		self::stor();
		$pathinfo = parse_url($path);
		$this->domain = $pathinfo['host'];
		$this->file = ltrim(strstr($path, $pathinfo['path']), '/\\');

		clearstatcache( true );
		return $this->stor->delete( $this->domain , $this->file );
	}

	public function stream_flush() {
		if (!$this->writen) {
			$this->stor->write( $this->domain, $this->file, $this->fcontent );
			$this->writen = true;
		}

		return $this->writen;
	}

	public function stream_stat() {
		return array();
	}

	public function url_stat($path, $flags) {
		self::stor();
		$pathinfo = parse_url($path);
		$this->domain = $pathinfo['host'];
		$this->file = ltrim(strstr($path, $pathinfo['path']), '/\\');

		if ( $attr = $this->stor->getAttr( $this->domain , $this->file ) ) {
			$stat = array();
			$stat['dev'] = $stat[0] = 0;
			$stat['ino'] = $stat[1] = 0;;
			$stat['mode'] = $stat[2] = 33279; //0100000 + 0777;
			$stat['nlink'] = $stat[3] = 0;
			$stat['uid'] = $stat[4] = 0;
			$stat['gid'] = $stat[5] = 0;
			$stat['rdev'] = $stat[6] = 0;
			$stat['size'] = $stat[7] = $attr['length'];
			$stat['atime'] = $stat[8] = 0;
			$stat['mtime'] = $stat[9] = $attr['datetime'];
			$stat['ctime'] = $stat[10] = $attr['datetime'];
			$stat['blksize'] = $stat[11] = 0;
			$stat['blocks'] = $stat[12] = 0;
			return $stat;
		} else {
			return false;
		}
	}

	public function dir_closedir() {
		return false;
	}

	public function dir_opendir($path, $options) {
		return false;
	}

	public function dir_readdir() {
		return false;
	}

	public function dir_rewinddir() {
		return false;
	}

	public function mkdir($path, $mode, $options) {
		return false;
	}

	public function rename($path_from, $path_to) {
		return false;
	}

	public function rmdir($path, $options) {
		return false;
	}

	public function stream_cast($cast_as) {
		return false;
	}

	public function stream_lock($operation) {
		return false;
	}

	public function stream_set_option($option, $arg1, $arg2) {
		return false;
	}

}


if ( in_array( "saestor", stream_get_wrappers() ) ) {
	stream_wrapper_unregister("saestor");
}
stream_register_wrapper( "saestor", "SaeStorageWrapper" )
or die( "Failed to register protocol" );

/* END *********************  Storage Wrapper By Elmer Zhang At 16/Mar/2010 14:47 ****************/


/* BEGIN *******************  KVDB Wrapper By Elmer Zhang At 12/Dec/2011 12:37 ****************/

class SaeKVWrapper // implements WrapperInterface
{
    private $dir_mode = 16895 ; //040000 + 0222;
    private $file_mode = 33279 ; //0100000 + 0777;


    public function __construct() { }

    private function kv() {
        if ( !isset( $this->kv ) ) $this->kv = new SaeKV();
        $this->kv->init();
        return $this->kv;
    }

    private function open( $key ) {
        $value = $this->kv()->get( $key );
        if ( $value !== false && $this->unpack_stat(substr($value, 0, 20)) === true ) {
            $this->kvcontent = substr($value, 20);
            return true;
        } else {
            return false;
        }
    }

    private function save( $key ) {
        $this->stat['mtime'] = $this->stat[9] = time();
        if ( isset($this->kvcontent) ) {
            $this->stat['size'] = $this->stat[7] = strlen($this->kvcontent);
            $value = $this->pack_stat() . $this->kvcontent;
        } else {
            $this->stat['size'] = $this->stat[7] = 0;
            $value = $this->pack_stat();
        }
        return $this->kv()->set($key, $value);
    }

    private function unpack_stat( $str ) {
        $arr = unpack("L5", $str);

        // check if valid
        if ( $arr[1] < 10000 ) return false;
        if ( !in_array($arr[2], array( $this->dir_mode, $this->file_mode ) ) ) return false;
        if ( $arr[4] > time() ) return false;
        if ( $arr[5] > time() ) return false;

        $this->stat['dev'] = $this->stat[0] = 0x8003;
        $this->stat['ino'] = $this->stat[1] = $arr[1];
        $this->stat['mode'] = $this->stat[2] = $arr[2];
        $this->stat['nlink'] = $this->stat[3] = 0;
        $this->stat['uid'] = $this->stat[4] = 0;
        $this->stat['gid'] = $this->stat[5] = 0;
        $this->stat['rdev'] = $this->stat[6] = 0;
        $this->stat['size'] = $this->stat[7] = $arr[3];
        $this->stat['atime'] = $this->stat[8] = 0;
        $this->stat['mtime'] = $this->stat[9] = $arr[4];
        $this->stat['ctime'] = $this->stat[10] = $arr[5];
        $this->stat['blksize'] = $this->stat[11] = 0;
        $this->stat['blocks'] = $this->stat[12] = 0;

        return true;
    }

    private function pack_stat( ) {
        $str = pack("LLLLL", $this->stat['ino'], $this->stat['mode'], $this->stat['size'], $this->stat['ctime'], $this->stat['mtime']);
        return $str;
    }

    public function stream_open( $path , $mode , $options , &$opened_path)
    {
        $this->position = 0;
        $this->kvkey = rtrim(trim(substr(trim($path), 8)), '/');
        $this->mode = $mode;
        $this->options = $options;

        if ( in_array( $this->mode, array( 'r', 'r+', 'rb' ) ) ) {
            if ( $this->open( $this->kvkey ) === false ) {
                trigger_error("fopen({$path}): No such key in KVDB.", E_USER_WARNING);
                return false;
            }
        } elseif ( in_array( $this->mode, array( 'a', 'a+', 'ab' ) ) ) {
            if ( $this->open( $this->kvkey ) === true ) {
                $this->position = strlen($this->kvcontent);
            } else {
                $this->kvcontent = '';
                $this->statinfo_init();
            }
        } elseif ( in_array( $this->mode, array( 'x', 'x+', 'xb' ) ) ) {
            if ( $this->open( $this->kvkey ) === false ) {
                $this->kvcontent = '';
                $this->statinfo_init();
            } else {
                trigger_error("fopen({$path}): Key exists in KVDB.", E_USER_WARNING);
                return false;
            }
        } elseif ( in_array( $this->mode, array( 'w', 'w+', 'wb' ) ) ) {
            $this->kvcontent = '';
            $this->statinfo_init();
        } else {
            $this->open( $this->kvkey );
        }

        return true;
    }

    public function stream_read($count)
    {
        if (in_array($this->mode, array('w', 'x', 'a', 'wb', 'xb', 'ab') ) ) {
            return false;
        }

        $ret = substr( $this->kvcontent , $this->position, $count);
        $this->position += strlen($ret);

        return $ret;
    }

    public function stream_write($data)
    {
        if ( in_array( $this->mode, array( 'r', 'rb' ) ) ) {
            return false;
        }

        $left = substr($this->kvcontent, 0, $this->position);
        $right = substr($this->kvcontent, $this->position + strlen($data));
        $this->kvcontent = $left . $data . $right;

        if ( $this->save( $this->kvkey ) === true ) {
            $this->position += strlen($data);
            return strlen( $data );
        } else return false;
    }

    public function stream_close()
    {
        $this->save( $this->kvkey );
    }


    public function stream_eof()
    {

        return $this->position >= strlen( $this->kvcontent  );
    }

    public function stream_tell()
    {

        return $this->position;
    }

    public function stream_seek($offset , $whence = SEEK_SET)
    {

        switch ($whence) {
        case SEEK_SET:

            if ($offset < strlen( $this->kvcontent ) && $offset >= 0) {
                $this->position = $offset;
                return true;
            }
            else
                return false;

            break;

        case SEEK_CUR:

            if ($offset >= 0) {
                $this->position += $offset;
                return true;
            }
            else
                return false;

            break;

        case SEEK_END:

            if (strlen( $this->kvcontent ) + $offset >= 0) {
                $this->position = strlen( $this->kvcontent ) + $offset;
                return true;
            }
            else
                return false;

            break;

        default:

            return false;
        }
    }

    public function stream_stat()
    {
        return $this->stat;
    }

    // ============================================
    public function mkdir($path , $mode , $options)
    {
        $path = rtrim(trim(substr(trim($path), 8)), '/');

        if ( $this->open( $path ) === false ) {
            $this->statinfo_init( false );
            return $this->save( $path );
        } else {
            trigger_error("mkdir({$path}): Key exists in KVDB.", E_USER_WARNING);
            return false;
        }
    }

    public function rename($path_from , $path_to)
    {
        $path_from = rtrim(trim(substr(trim($path_from), 8)), '/');
        $path_to = rtrim(trim(substr(trim($path_to), 8)), '/');

        if ( $this->open( $path_from ) === true ) {
            clearstatcache( true );
            return $this->save( $path_to );
        } else {
            trigger_error("rename({$path_from}, {$path_to}): No such key in KVDB.", E_USER_WARNING);
            return false;
        }
    }

    public function rmdir($path , $options)
    {
        $path = rtrim(trim(substr(trim($path), 8)), '/');

        clearstatcache( true );
        return $this->kv()->delete($path);
    }

    public function unlink($path)
    {
        $path = rtrim(trim(substr(trim($path), 8)), '/');

        clearstatcache( true );
        return $this->kv()->delete($path);
    }

    public function url_stat($path , $flags)
    {
        $path = rtrim(trim(substr(trim($path), 8)), '/');

        if ( $this->open( $path ) !== false ) {
            return $this->stat;
        } else {
            return false;
        }
    }






    // ============================================

    private function statinfo_init( $is_file = true )
    {
        $this->stat['dev'] = $this->stat[0] = 0x8003;
        $this->stat['ino'] = $this->stat[1] = crc32(SAE_APPNAME . '/' . $this->kvkey);

        if( $is_file )
            $this->stat['mode'] = $this->stat[2] = $this->file_mode;
        else
            $this->stat['mode'] = $this->stat[2] = $this->dir_mode;

        $this->stat['nlink'] = $this->stat[3] = 0;
        $this->stat['uid'] = $this->stat[4] = 0;
        $this->stat['gid'] = $this->stat[5] = 0;
        $this->stat['rdev'] = $this->stat[6] = 0;
        $this->stat['size'] = $this->stat[7] = 0;
        $this->stat['atime'] = $this->stat[8] = 0;
        $this->stat['mtime'] = $this->stat[9] = time();
        $this->stat['ctime'] = $this->stat[10] = 0;
        $this->stat['blksize'] = $this->stat[11] = 0;
        $this->stat['blocks'] = $this->stat[12] = 0;

    }

    public function dir_closedir() {
        return false;
    }

    public function dir_opendir($path, $options) {
        return false;
    }

    public function dir_readdir() {
        return false;
    }

    public function dir_rewinddir() {
        return false;
    }

    public function stream_cast($cast_as) {
        return false;
    }

    public function stream_flush() {
        return false;
    }

    public function stream_lock($operation) {
        return false;
    }

    public function stream_set_option($option, $arg1, $arg2) {
        return false;
    }

}

if ( ! in_array("saekv", stream_get_wrappers()) )
    stream_wrapper_register("saekv", "SaeKVWrapper");

/* END *********************  KVDB Wrapper By Elmer Zhang At 12/Dec/2011 12:37 ****************/

?>