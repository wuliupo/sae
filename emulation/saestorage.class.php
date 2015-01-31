<?php
/**
 * SAE数据存储服务
 *
 * @author quanjun
 * @version $Id$
 * @package sae
 *
 */

/**
 * SaeStorage class
 * Storage服务适合用来存储用户上传的文件，比如头像、附件等。不适合存储代码类文件，比如页面内调用的JS、CSS等，尤其不适合存储追加写的日志。使用Storage服务来保存JS、CSS或者日志，会严重影响页面响应速度。建议JS、CSS直接保存到代码目录，日志使用sae_debug()方法记录。
 *
 * <code>
 * <?php
 * $s = new SaeStorage();
 * $s->write( 'example' , 'thebook' , 'bookcontent!' );
 *
 * echo $s->read( 'example' , 'thebook') ;
 * // will echo 'bookcontent!';
 *
 * echo $s->getUrl( 'example' , 'thebook' );
 * // will echo 'http://appname-example.stor.sinaapp.com/thebook';
 *
 * ?>
 * </code>
 *
 * 常见错误码参考：
 *  - errno: 0 		成功
 *  - errno: -2		配额统计错误
 *  - errno: -3		权限不足
 *  - errno: -7		Domain不存在
 *  - errno: -12	存储服务器返回错误
 *  - errno: -18 	文件不存在
 *  - errno: -101	参数错误
 *  - errno: -102	存储服务器连接失败
 *  - errno: -103   单个app的domain数量超出，带个应用的数量不应给超过5个
 *  - errno: -104   总的domain数量超出，所有应用的domain数量不应给超过10个
 * 注：可使用SaeStorage::errmsg()方法获得当前错误信息。
 *
 * @package sae
 * @author  quanjun
 *
 */

class SaeStorage extends SaeObject
{
	/**
	 * 用户accessKey
	 * @var string
	 */
	private $accessKey = '';
	/**
	 * 用户secretKey
	 * @var string
	 */
	private $secretKey = '';
	/**
	 * 运行过程中的错误信息
	 * @var string
	 */
	private $errMsg = 'success';
	/**
	 * 运行过程中的错误代码
	 * @var int
	 */
	private $errNum = 0;
	/**
	 * 应用名
	 * @var string
	 */
	private $appName = '';
	/**
	 * @var string
	 */
	private $restUrl = '';
	/**
	 * @var string
	 */
	private $filePath= '';

	/**
	 *  filepath rule:  url = $basedomain . '/' .  $this->appName. '/'. $domain .  '/' . $filename
	 * @var string
	 */
	 
	/**
	 *  filepath rule:  url = $basedomain . '/' .  $this->appName. '/'. $domain .  '/' . $filename
	 * @var string
	 */
	private $basedomain = STORAGE_HOST;
	
	/**
	 * 构造函数
	 * $_accessKey与$_secretKey可以为空，为空的情况下可以认为是公开读文件
	 * @param string $_accessKey
	 * @param string $_secretKey
	 * @return void
	 * @author Elmer Zhang
	 */
	public function __construct( $_accessKey='', $_secretKey='' )
	{
		if( $_accessKey== '' ) $_accessKey = SAE_ACCESSKEY;
		if( $_secretKey== '' ) $_secretKey = SAE_SECRETKEY;
		$app_domain_num = $this->getDomainNum(get_appname()) ;
		$all_app_domain_num = $this->getAllDomainNum() ;
		if($app_domain_num!==false && $app_domain_num<=5 && $all_app_domain_num!==false && $all_app_domain_num<=10)
			$this->setAuth( $_accessKey, $_secretKey );
		else
		{
			if($app_domain_num>5)
			{
				$this->errNum = 103 ;
				$this->errMsg = "单个app的domain数量超出，带个应用的数量不应给超过5个" ;
			}
			else
			{
				$this->errNum = 104 ;
				$this->errMsg = "总的domain数量超出，所有应用的domain数量不应给超过10个" ;
			}
		}
	}
	
	/**
	 * 设置key
	 *
	 * 当需要访问其他APP的数据时使用
	 *
	 * @param string $akey
	 * @param string $skey
	 * @return void
	 * @author Elmer Zhang
	 */
	public function setAuth( $akey , $skey )
	{
		//$this->initOptUrlList( $this->_optUrlList);
		$this->init( $akey, $skey );
	}

	/**
	 * 返回运行过程中的错误信息
	 *
	 * @return string
	 * @author Elmer Zhang
	 */
	public function errmsg()
	{
		$ret = $this->errMsg."url(".$this->filePath.")";
		$this->restUrl = '';
		$this->errMsg = 'success!';
		return $ret;
	}

	/**
	 * 返回运行过程中的错误代码
	 *
	 * @return int
	 * @author Elmer Zhang
	 */
	public function errno()
	{
		$ret = $this->errNum;
		$this->errNum = 0;
		return $ret;
	}

	/**
	 * 取得访问存储文件的url
	 * url = $basedomain . '/' .  $this->appName. '/'. $domain .  '/' . $filename
	 * @param string $domain
	 * @param string $filename
	 * @return string
	 * @author Elmer Zhang
	 */
	public function getUrl( $domain, $filename )
	{
		if(!$this->keyValidate())
		{
			$this->setRightError();
			return false;
		}
		$domain = trim($domain) ;
		$filename = trim($filename) ;
		if(empty($domain) || empty($filename))
		{
			$this->errMsg = 'the value of parameter (domain,filename) can not be empty!';
			$this->errNum = -101;
			return false;
		}
		// make it full domain
		$filename = $this->formatFilename($filename);
		$domain   = $this->getDom($domain);

		//ccc
		//$this->filePath = urldecode($this->basedomain.'/'.$this->appName.'/'.$domain.'/'.$filename);
		$this->filePath = $this->basedomain.'/'.$this->appName.'/'.$domain.'/'.$filename;
		return $this->filePath;
	}
	
	private function setUrl( $domain , $filename )
	{
		$domain         =  $this->getDom($domain);
		$filename       =   $this->formatFilename($filename);

		//ccc
		//$this->filePath = urldecode($this->basedomain.'/'.$this->appName.'/'.$domain.'/'.$filename);
		$this->filePath = $this->basedomain.'/'.$this->appName.'/'.$domain.'/'.$filename;
	}
	
	/**
	 * 将数据写入存储
	 *
	 * 注意：文件名左侧所有的'/'都会被过滤掉。
	 *
	 * @param string $domain 存储域,在在线管理平台.storage页面可进行管理
	 * @param string $destFileName 文件名
	 * @param string $content 文件内容,支持二进制数据
	 * @param int $size 写入长度,默认为不限制
	 * @param array $attr 文件属性，可设置的属性请参考 SaeStorage::setFileAttr() 方法
	 * @param bool $compress 是否gzip压缩。如果设为true，则文件会经过gzip压缩后再存入Storage，常与$attr=array('encoding'=>'gzip')联合使用
	 * @return string 写入成功时返回该文件的下载地址，否则返回false
	 * @author Elmer Zhang
	 */
	public function write( $domain, $destFileName, $content, $size=-1, $attr=array(), $compress = false )
	{
		if(!$this->keyValidate())
		{
			$this->setRightError();
			return false;
		}
		
		if ( Empty( $domain ) || Empty( $destFileName ) || Empty( $content ) )
		{
			$this->errMsg = 'the value of parameter (domain,destFileName,content) can not be empty!';
			$this->errNum = -101;
			return false;
		}
		
		if ( $size > -1 )
		$content = substr( $content, 0, $size );
		$domain = $this->getDom($domain) ;
		$srcFileName = tempnam(SAE_TMP_PATH, 'SAE_STOR_UPLOAD');
		if ($compress) {
			$srcFileNew = tempnam(SAE_TMP_PATH, 'SAE_STOR_UPLOAD');
			file_put_contents($srcFileNew, $content);
			$srcFileName = $srcFileName.'.zip' ;
			$this->zipCompress($srcFileNew,$srcFileName) ;
			unlink($srcFileNew) ;
		}
		else
			file_put_contents($srcFileName, $content);
		$re = $this->upload($domain, $destFileName, $srcFileName, $attr);
		unlink($srcFileName);
		return $re;
	}
	
	
	/**
	 * 将文件上传入存储
	 *
	 * 注意：文件名左侧所有的'/'都会被过滤掉。
	 *
	 * @param string $domain 存储域,在在线管理平台.storage页面可进行管理
	 * @param string $destFileName 目标文件名
	 * @param string $srcFileName 源文件名
	 * @param array $attr 文件属性，可设置的属性请参考 SaeStorage::setFileAttr() 方法
	 * @param bool $compress 是否gzip压缩。如果设为true，则文件会经过gzip压缩后再存入Storage，常与$attr=array('encoding'=>'gzip')联合使用
	 * @return string 写入成功时返回该文件的下载地址，否则返回false
	 * @author Elmer Zhang
	 */
	public function upload( $domain, $destFileName, $srcFileName, $attr = array(), $compress = false )
	{
		if(!$this->keyValidate())
		{
			$this->setRightError();
			return false;
		}
		$domain = trim($domain);
		$destFileName = $this->formatFilename($destFileName);
		//$Dirnames = $this->getDirname($destFileName) ;
		if(!$this->creteDir($domain,$destFileName))
		{
			$this->errMsg = 'the value of parameter (filename) is error!';
			$this->errNum = -101;
			return false ;
		}
		$destFileName = str_replace("/","\\",$destFileName) ;
		
		if ( Empty( $domain ) || Empty( $destFileName ) || Empty( $srcFileName ) )
		{
			$this->errMsg = 'the value of parameter (domain,destFile,srcFileName) can not be empty!';
			$this->errNum = -101;
			return false;
		}
		
		// make it full domain
		$domain = $this->getDom($domain);
		$parseAttr = $this->parseFileAttr($attr);
		
		$this->setUrl( $domain, $destFileName );
		
		$dstStorDir    = $this->getDomainFullDir($domain);
		
		if(!file_exists($dstStorDir))
		{
			$this->setNoDomainError();
			return false;
		}
		
		if ($compress) {
			$srcFileNew = tempnam(SAE_TMP_PATH, 'SAE_STOR_UPLOAD');
			$srcFileNew = $srcFileNew.".zip" ;
			//$srcFileName = $srcFileName.'.zip' ;
			$this->zipCompress($srcFileName,$srcFileNew) ;
			$srcFileName = $srcFileNew ;
		}
		
		$fullFileNm    =  $dstStorDir.DIRECTORY_SEPARATOR.$destFileName;
		if(!copy($srcFileName, $fullFileNm))
		{
			if($compress)
			{
				ulink($srcFileName) ;
			}
			return false;
		}
		if($compress)
		{
			ulink($srcFileName) ;
		}
		$this->setFileAttr( $domain, $destFileName, $parseAttr) ;
		$ret 	= true;
		
		if ( $ret !== false )
		return $this->filePath;
		else
		{
			$this->setStorError();
			return false;
		}
	}
	
	/**
	 * 获取指定domain下的文件名列表
	 *
	 * <code>
	 * <?php
	 * //遍历Domain下所有文件
	 * $stor = new SaeStorage();
	 *
	 * $num = 0;
	 * while ( $ret = $stor->getList("test", "*", 100, $num ) ) {
	 * 		foreach($ret as $file) {
	 * 			echo "{$file}\n";
	 * 			$num ++;
	 * 		}
	 * }
	 *
	 * echo "\nTOTAL: {$num} files\n";
	 * ?>
	 * </code>
	 *
	 * @param string $domain	存储域,在在线管理平台.storage页面可进行管理
	 * @param string $prefix	如 *,abc*,*.txt
	 * @param int $limit		返回条数,最大100条,默认10条
	 * @param int $offset			起始条数。
	 * @return array 执行成功时返回文件列表数组，否则返回false
	 * @author Elmer Zhang
	 */
	 public function getList( $domain, $prefix='*', $limit=10, $offset = 0 )
	 {
		if(!$this->keyValidate())
		{
			$this->setRightError();
			return false;
		}
		$domain = trim($domain);
		if ( Empty( $domain ) )
		{
			//echo "f=".__FILE__.",l=".__LINE__."<br>";
			$this->errMsg = 'the value of parameter (domain) can not be empty!';
			$this->errNum = -101;
			return false;
		}
		
		// add prefix
		$domain = $this->getDom($domain);

		//ccc
		$dstStorDir    =$this->getDomainFullDir($domain);
		
		if(!file_exists($dstStorDir))
		{
			$this->setNoFileError();
			return false;
		}
		$fileArr    = NULL;
		//$fileArr    = glob($dstStorDir.DIRECTORY_SEPARATOR.$prefix);
		$fileArr = $this->getPrefixList($dstStorDir,$prefix) ;
		
		$rtnArr    = array();
		if(is_array($fileArr))
		{
			$end        = $limit + $offset;
			$domaindir = $dstStorDir.DIRECTORY_SEPARATOR ;
			for($i = $offset; $i<$end && $i<count($fileArr); $i++)
			{
				$rtnArr[] = substr($fileArr[$i],strlen($domaindir));
			}
		}
		return $rtnArr;
	 }
	 
	 /**
     * 获取指定Domain、指定目录下的文件列表
     *
     * @param string $domain    存储域
     * @param string $path        目录地址
     * @param int $limit        单次返回数量限制，默认100，最大1000
     * @param int $offset        起始条数
     * @param int $fold            是否折叠目录
     * @return array 执行成功时返回列表，否则返回false
     * @author Elmer Zhang
     */
    public function getListByPath( $domain, $path = NULL, $limit = 100, $offset = 0, $fold = true )
    {
		if(!$this->keyValidate())
		{
			$this->setRightError();
			return false;
		}
		$domain = trim($domain);
		if ( Empty( $domain ) )
		{
			//echo "f=".__FILE__.",l=".__LINE__."<br>";
			$this->errMsg = 'the value of parameter (domain) can not be empty!';
			$this->errNum = -101;
			return false;
		}
		$domain = $this->getDom($domain) ;
		$path = $this->formatFilename($path);
		$dstStorDir    =$this->getDomainFullDir($domain);
		$retarr = array() ;
		$retarr["dirNum"] = 0 ;
		$retarr["fileNum"] = 0 ;
		$retarr["dirs"] = array() ;
		$retarr["files"] = array() ;
		if(!file_exists($dstStorDir))
		{
			$this->setNoFileError();
			return false;
		}
		$path = trim($path) ;
		$path = trim($path,"/") ;
		$fileArr    = glob($dstStorDir.DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR.'*');
		//$fileArr = listFilename($dstStorDir.DIRECTORY_SEPARATOR.$path) ;
		if($limit > 1000)
		{
			$this->setQuotaError() ;
			return false ;
		}
		$filereutrn = array() ;
		$j = 0 ;
		for($i=0;$i<count($fileArr);$i++)
		{
			$filereturn[$j++] = $fileArr[$i] ;
			if(is_dir($fileArr[$i]))
			{
				$retarr["dirNum"] = $retarr["dirNum"] + 1 ;
				if($i >= $offset && $i<$offset+$limit)
				{
					$temp = array() ;
					$temp["name"] = basename($fileArr[$i]) ;
					if($path != "")
						$temp["fullName"] = $path."/".basename($fileArr[$i]) ;
					else
						$temp["fullName"] = basename($fileArr[$i]) ;
					array_push($retarr["dirs"],$temp) ;
				}
			}
			else
			{
				$retarr["fileNum"] = $retarr["fileNum"] + 1 ;
				if($i >= $offset && $i<$offset+$limit)
				{
					$temp = array() ;
					$temp["Name"] = basename($fileArr[$i]) ;
					if($path != "")
						$temp["fullName"] = $path."/".basename($fileArr[$i]) ;
					else
						$temp["fullName"] = basename($fileArr[$i]) ;
					$temp["length"] = filesize($fileArr[$i]) ;
					$temp["uploadTime"] = filectime($fileArr[$i]) ;
					array_push($retarr["files"],$temp) ;
				}
			}
		}
		return $retarr ;
	}
	 
	 /**
	 * 获取指定domain下的文件数量
	 *
	 *
	 * @param string $domain	存储域,在在线管理平台.storage页面可进行管理
	 * @return array 执行成功时返回文件数，否则返回false
	 * @author Elmer Zhang
	 */
	public function getFilesNum( $domain )
	{
		if(!$this->keyValidate())
		{
			$this->setRightError();
			return false;
		}

		$domain = trim($domain);

		//echo $prefix;
		if ( Empty( $domain ) )
		{
			//echo "f=".__FILE__.",l=".__LINE__."<br>";
			$this->errMsg = 'the value of parameter (domain) can not be empty!';
			$this->errNum = -101;
			return false;
		}
		$domain = $this->getDom($domain) ;
		return $this->getDirFileNum($this->getDomainFullDir($domain)) ;
		
		/*$fileArr    = glob($this->getDomainFullDir($domain).DIRECTORY_SEPARATOR.'*');

		if(is_array($fileArr))
		{
			return count($fileArr);
		}
		else
		{
			return 0;
		}*/
	}
	
	/**
	 * 获取文件属性
	 *
	 * @param string $domain
	 * @param string $filename
	 * @param array $attrKey 属性值,如 array("fileName", "length")，当attrKey为空时，以关联数组方式返回该文件的所有属性。
	 * @return array 执行成功以数组方式返回文件属性，否则返回false
	 * @author Elmer Zhang
	*/ 
	public function getAttr( $domain, $filename, $attrKey=array() )
	{
		if(!$this->keyValidate())
		{
			$this->setRightError();
			return false;
		}
		$domain = trim($domain);
		$filename = $this->formatFilename($filename);

		if ( Empty( $domain ) || Empty( $filename ) )
		{
			$this->errMsg = 'the value of parameter (domain,filename) can not be empty!';
			$this->errNum = -101;
			return false;
		}
		$domain = $this->getDom($domain) ;
		$distFile  = $this->getDistFilename($domain,$filename) ; 
		//   = $this->getDomainFullDir($domain).DIRECTORY_SEPARATOR.$filename;

		if(!file_exists($distFile))
		{
			$this->setNoFileError();
			return false;
		}
		$attr_file = $this->getAttrDir().DIRECTORY_SEPARATOR."file_sttr_".$domain ;
		if(!file_exists($attr_file))
		{
			return array() ;
		}
		$file_attr_str = file_get_contents($attr_file) ;
		$file_attr = json_decode($file_attr_str,true) ;
		$filename = urlencode($filename) ;
		if(!isset($file_attr[$filename]))
		{
			$file_attr[$filename] = array() ;
		}
		$file_attr[$filename]["fileName"] = $filename ;
		$file_attr[$filename]["length"] = filesize($distFile) ;
		$file_attr[$filename]["datetime"] = filemtime($distFile) ;
		return $file_attr[$filename] ;
	}
	
	/**
	 * 检查文件是否存在
	 *
	 * @param string $domain
	 * @param string $filename
	 * @return bool
	 * @author Elmer Zhang
	 */
	public function fileExists( $domain, $filename )
	{
		if(!$this->keyValidate())
		{
			$this->setRightError();
			return false;
		}

		$domain = trim($domain);
		$filename = $this->formatFilename($filename);

		if ( Empty( $domain ) || Empty( $filename ) )
		{
			$this->errMsg = 'the value of parameter (domain,filename) can not be empty!';
			$this->errNum = -101;
			return false;
		}
		$domain = $this->getDom($domain) ;
		//$distFile    = $this->getDomainFullDir($domain).DIRECTORY_SEPARATOR.$filename;
		$distFile  = $this->getDistFilename($domain,$filename) ; 

		if(!file_exists($distFile))
		{
			$this->setStorSavingError() ;
			return false;
		}
		else
		{
			return TRUE;
		}
	}
	
	/**
	 * 获取文件的内容
	 *
	 * @param string $domain
	 * @param string $filename
	 * @return string 成功时返回文件内容，否则返回false
	 * @author Elmer Zhang
	 */
	public function read( $domain, $filename )
	{
		if(!$this->keyValidate())
		{
			$this->setRightError();
			return false;
		}

		$domain = trim($domain);
		$filename = $this->formatFilename($filename);

		if ( Empty( $domain ) || Empty( $filename ) )
		{
			$this->errMsg = 'the value of parameter (domain,filename) can not be empty!';
			$this->errNum = -101;
			return false;
		}
		$domain = $this->getDom($domain) ;
		//$distFile    = $this->getDomainFullDir($domain).DIRECTORY_SEPARATOR.$filename;
		$distFile  = $this->getDistFilename($domain,$filename) ;
		if(!file_exists($distFile))
		{
			$this->setNoFileError();
			return false;
		}
		else
		{
			return file_get_contents($distFile);
		}
	}
	
	/**
     * 删除目录
     *
     * @param string $domain    存储域
     * @param string $path        目录地址
     * @return bool
     * @author Elmer Zhang
     */
    public function deleteFolder( $domain, $path )
	{
		if(!$this->keyValidate())
		{
			$this->setRightError();
			return false;
		}
		$domain = trim($domain);
		if ( Empty( $domain ) )
		{
			//echo "f=".__FILE__.",l=".__LINE__."<br>";
			$this->errMsg = 'the value of parameter (domain) can not be empty!';
			$this->errNum = -101;
			return false;
		}
		$domain = $this->getDom($domain) ;
		$path = $this->formatFilename($path);
		$dstStorDir    =$this->getDomainFullDir($domain);
		$delDir = $dstStorDir.DIRECTORY_SEPARATOR.$path ;
		if(!file_exists($dstStorDir) || !is_dir($path))
		{
			$this->setNoFileError();
			return false;
		}
		return $this->deleteDir($delDir) ;
		
		/*
		$fileArr    = glob($dstStorDir.DIRECTORY_SEPARATOR.$path.'%2F');
		
		foreach($fileArr as $filename)
		{
			$filename = urlencode($filename) ;
			if(ulink($filename) === false)
				return false ;
		}
		return true ;*/
	}
	
	/**
	 * 删除文件
	 *
	 * @param string $domain
	 * @param string $filename
	 * @return bool
	 * @author Elmer Zhang
	 */
	public function delete( $domain, $filename )
	{
		if(!$this->keyValidate())
		{
			$this->setRightError();
			return false;
		}

		$domain = trim($domain);
		$filename = $this->formatFilename($filename);

		if ( Empty( $domain ) || Empty( $filename ) )
		{
			$this->errMsg = 'the value of parameter (domain,filename) can not be empty!';
			$this->errNum = -101;
			return false;
		}
		$domain = $this->getDom($domain) ;
		$file_attr = array() ;
		$file_attr_str = NULL ;
		//$distFile    = $this->getDomainFullDir($domain).'\\'.$filename;
		$distFile  = $this->getDistFilename($domain,$filename) ; 
		$attrFile = $this->getAttrDir().DIRECTORY_SEPARATOR."file_sttr_".$domain ;
		if(file_exists($attrFile))
		{
			$file_attr_str = file_get_contents($attrFile) ;
			if($file_attr_str===NULL || $file_attr_str===false)
				$file_attr = array() ;
			else
				$file_attr = json_decode($file_attr_str,true ) ;
		}
		if(!file_exists($distFile))
		{
			return true;
		}
		else
		{
			if(isset($file_attr[$filename]))
			{
				$filename = urlencode($filename) ;
				unset($file_attr[$filename]) ;
				$file_attr_str = json_encode($file_attr) ;
				file_put_contents($attr_file,$file_attr_str) ;
			}
			return unlink($distFile);
		}
	}
	
	/**
	 * 设置文件属性
	 *
	 * 目前支持的文件属性
	 *  - expires: 浏览器缓存超时，功能与Apache的Expires配置相同
	 *  - encoding: 设置通过Web直接访问文件时，Header中的Content-Encoding。
	 *  - type: 设置通过Web直接访问文件时，Header中的Content-Type。
	 *
	 * <code>
	 * <?php
	 * $stor = new SaeStorage();
	 *
	 * $attr = array('expires' => 'access plus 1 year');
	 * $ret = $stor->setFileAttr("test", "test.txt", $attr);
	 * if ($ret === false) {
	 * 		var_dump($stor->errno(), $stor->errmsg());
	 * }
	 *
	 * $attr = array('expires' => 'A3600');
	 * $ret = $stor->setFileAttr("test", "expire/*.txt", $attr);
	 * if ($ret === false) {
	 * 		var_dump($stor->errno(), $stor->errmsg());
	 * }
	 * ?>
	 * </code>
	 *
	 * @param string $domain
	 * @param string $filename 	文件名，可以使用通配符"*"和"?"
	 * @param array $attr 		文件属性。格式：array('attr0'=>'value0', 'attr1'=>'value1', ......);
	 * @return bool
	 * @author Elmer Zhang
	 */
	public function setFileAttr( $domain, $filename, $attr = array() )
	{
		$file_attr_str = NULL ;
		if(!$this->keyValidate())
		{
			$this->setRightError();
			return false;
		}

		$domain = trim($domain);
		$domain = $this->getDom($domain) ;
		$filename = $this->formatFilename($filename);
		$filename = urlencode($filename) ;

		if ( Empty( $domain ) || Empty( $filename ) )
		{
			$this->errMsg = 'the value of parameter (domain,filename) can not be empty!';
			$this->errNum = -101;
			return false;
		}
		$attr_dir = $this->getAttrDir() ;
		if(!is_dir($attr_dir))
			mkdir($attr_dir) ;
		$attr_file = $attr_dir.DIRECTORY_SEPARATOR."file_sttr_".$domain ;
		if(file_exists($attr_file))
			$file_attr_str = file_get_contents($attr_file) ;
			
		if($file_attr_str === false || $file_attr_str=== NULL)
			$file_attr = array() ;
		else
			$file_attr = json_decode($file_attr_str,true ) ;
			
		$parseAttr = $this->parseFileAttr($attr);
		if(is_array($parseAttr))
		{
			foreach($parseAttr as $key => $value)
			{
				$file_attr[$filename][$key] = $value ;
			}
		}
		$file_attr_str = json_encode($file_attr) ;
		file_put_contents($attr_file,$file_attr_str) ;
	}
	
	
	/**
	 * 设置Domain属性
	 *
	 * 目前支持的Domain属性
	 *  - expires: 浏览器缓存超时，功能与Apache的Expires配置相同
	 *
	 * <code>
	 * <?php
	 * $expires = 'ExpiresActive On
	 * ExpiresDefault "access plus 30 days"
	 * ExpiresByType text/html "access plus 1 month 15 days 2 hours"
	 * ExpiresByType image/gif "modification plus 5 hours 3 minutes"
	 * ExpiresByType image/jpg A2592000
	 * ExpiresByType text/plain M604800
	 * ';
	 *
	 * $stor = new SaeStorage();
	 *
	 * $attr = array('expires'=>$expires);
	 * $ret = $stor->setDomainAttr("test", $attr);
	 * if ($ret === false) {
	 * 		var_dump($stor->errno(), $stor->errmsg());
	 * }
	 *
	 * ?>
	 * </code>
	 *
	 * @param string $domain
	 * @param array $attr 		Domain属性。格式：array('attr0'=>'value0', 'attr1'=>'value1', ......);
	 * @return bool
	 * @author Elmer Zhang
	 */
	public function setDomainAttr( $domain, $attr = array() )
	{
		if(!$this->keyValidate())
		{
			$this->setRightError();
			return false;
		}

		$domain = trim($domain);
		
		if ( Empty( $domain ) || Empty( $filename ) )
		{
			$this->errMsg = 'the value of parameter (domain,filename) can not be empty!';
			$this->errNum = -101;
			return false;
		}
		$domain = $this->getDom($domain) ;
		$file_attr_str = NULL ;
		
		$attr_dir = $this->getAttrDir() ;
		if(!is_dir($attr_dir))
			mkdir($attr_sir) ;
		$attr_file = $attr_dir.DIRECTORY_SEPARATOR.$domain ;
		if(file_exists($attr_file))
			$file_attr_str = file_get_contents($attr_file) ;
			
		if($file_attr_str === false || $file_attr_str===NULL)
			$file_attr = array() ;
		else
			$file_attr = json_decode($file_attr_str,true ) ;
		foreach($attr as $key => $value)
		{
			$file_attr[$key] = $value ;
		}
		$file_attr_str = json_encode($file_attr) ;
		file_put_contents($attr_file) ;
	}
	
	/**
     * 获取domain所占存储的大小
     *
     * @param string $domain 
     * @return int
     * @author Elmer Zhang
     */
    public function getDomainCapacity( $domain )
	{
		if(!$this->keyValidate())
		{
			$this->setRightError();
			return false;
		}

		$domain = trim($domain);
		if ( Empty( $domain ))
		{
			$this->errMsg = 'the value of parameter (domain) can not be empty!';
			$this->errNum = -101;
			return false;
		}
		$domain = $this->getDom($domain) ;
		$domaindir = $this->getDomainFullDir($domain) ;
		return getDirCapacity($domaindir) ;
		/*$domainsize = 0 ;
		if(is_dir($domaindir))
		{
			if($dh = opendir($domaindir))
			{
				while($file=readdir($dh) !== false)
				{
					$domainsize += filesize($file) ;
				}
				closedir($dh) ;
			}
		}
		else
		{
			$this->setNoDomainError() ;
			return false ;
		}
		return $domainsize ;*/
	}
	
	
	
	/**
	 * @ignore
	 */
	protected function parseDomainAttr($attr)
	{
		$parseAttr = array();

		if ( !is_array( $attr ) || empty( $attr ) )
		{
			return false;
		}

		foreach ( $attr as $k => $a )
		{
			switch ( strtolower( $k ) )
			{
				case 'expires':
					$parseAttr['expires'] = $this->parseExpires($a);
					break;
					//simulate the private domain; hjc
				case 'expires':
					$parseAttr['private'] = $a;
					break;
				default;
				break;
			}
		}

		return $parseAttr;
	}
	
	/**
	 * @ignore
	 */
	protected function parseFileAttr($attr) {
		$parseAttr = array();

		if ( !is_array( $attr ) || empty( $attr ) ) {
			return false;
		}

		foreach ( $attr as $k => $a )
		{
			switch ( strtolower( $k ) )
			{
				case 'expires':
					$parseAttr['expires'] = $a;
					break;
				case 'encoding':
					$parseAttr['encoding'] = $a;
					break;
				case 'type':
					$parseAttr['type'] = $a;
					break;
				default;
				break;
			}
		}

		return $parseAttr;
	}
	
	/**
	 * @ignore
	 */
	protected function parseExpires($expires) {
		$expires = trim($expires);
		$expires_arr = array();
		$expires_arr['active'] = 1;

		$expires = preg_split("/(\n|\r\n)/", $expires);
		if (is_array($expires) && !empty($expires)) {
			foreach ($expires as $e) {
				$e = trim($e);
				if ( preg_match("/^ExpiresActive\s+(on|off)$/i", strtolower($e), $matches) ) {
					if ($matches[1] == "on") {
						$expires_arr['active'] = 1;
					} else {
						$expires_arr['active'] = 0;
					}
				} elseif ( preg_match("/^ExpiresDefault\s+(A\d+|M\d+|\"(.+)\")$/i", $e, $matches) ) {
					if (isset($matches[2])) {
						$expires_arr['default'] = $matches[2];
					} else {
						$expires_arr['default'] = $matches[1];
					}
				} elseif ( preg_match("/^ExpiresByType\s+(?P<type>.+)\s+(?P<expires>A\d+|M\d+|\"(.+)\")$/i", $e, $matches) ) {
					if (isset($matches[3])) {
						$expires_arr['byType'][strtolower($matches['type'])] = $matches[3];
					} else {
						$expires_arr['byType'][strtolower($matches['type'])] = $matches[2];
					}
				}
			}
		}

		return $expires_arr;
	}
	
	/**
	 * 构造函数运行时替换所有$this->optUrlList值里的accessKey与secretKey
	 * @param string $_accessKey
	 * @param string $_secretKey
	 * @return void
	 * @ignore
	 */
	protected function init( $_accessKey, $_secretKey )
	{
		$_accessKey = trim($_accessKey);
		$_secretKey = trim($_secretKey);
	
		$this->appName = trim($this->get_appname());
		$this->accessKey = $_accessKey;
		$this->secretKey = $_secretKey;

	}
	
	private function get_appname()
	{
		return $_SERVER['HTTP_APPNAME'] ;
	}
	
	protected function getDom($domain, $concat = true)
	{
		return strtolower(trim($domain));
	}
	
	protected  function  getDomainFullDir($domain)
	{
		return SAE_STORAGE_STORAGE_DIR.DIRECTORY_SEPARATOR.$this->appName.DIRECTORY_SEPARATOR.$this->getDom($domain);
	}
	
	private function getAttrDir()
	{
		return realpath(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'attrstorage'.DIRECTORY_SEPARATOR.$this->appName) ;
	}
	
	protected function  keyValidate( )
	{
		return true;
	}
	
	private function formatFilename($filename) {
		$filename = trim($filename);
		$filename = trim($filename,"/") ;
		$encodings = array( 'UTF-8', 'GBK', 'BIG5' );
		$charset = mb_detect_encoding( $filename , $encodings);
		if ( $charset !='UTF-8' ) {
			$filename = mb_convert_encoding( $filename, "UTF-8", $charset);
		}

		// ccc suport chinese
		//$filename = urlencode($filename);

		//ccc
		//$filename = str_replace('/', '', $filename);
		//$filename = urlencode($filename);

		return $filename;
	}
	
	private function getDirname($filename)
	{
		return explode("/",$filename) ;
	}
	
	private function creteDir($domain,$filename)
	{
		$domaindir = $this->getDomainFullDir($domain) ;
		$dirnames = $this->getDirname($filename) ;
		for($i=0;count($dirnames)>1&&$i<count($dirnames)-1;++$i)
		{
			$domaindir = $domaindir.DIRECTORY_SEPARATOR.$dirnames[$i] ;
			if(!is_dir($domaindir))
			{
				if(!mkdir($domaindir))
					return false ;
			}
		}
		return true ;
	}
	
	private function getDistFilename($domain,$filename)
	{
		$filename = str_replace("/","\\",$filename) ;
		return $this->getDomainFullDir($domain).DIRECTORY_SEPARATOR.$filename ;
	}
	
	private function getDirFileNum( $dir )
	{
		$filenum = 0 ;
		if(!is_dir($dir))
			return false ;
		$files = glob($dir.DIRECTORY_SEPARATOR.'*');
		if(is_array($files))
		{
			for($i=0;$i<count($files);++$i)
			{
				if(is_dir($files[$i]))
				{
					$temp = $this->getDirFileNum($files[$i]) ;
					if($temp === false)
						return false ;
					$filenum += $temp ;
				}
				else
					$filenum++ ;
			}
			return $filenum ;
		}
		else
			return 0 ;
	}
	
	private function getDirCapacity($dir)
	{
		$fsize = 0 ;
		if(!is_dir($dir))
			return false ;
		$files = glob($dir.DIRECTORY_SEPARATOR.'*');
		if(is_array($files))
		{
			for($i=0;$i<count($files);++$i)
			{
				if(is_dir($files[$i]))
				{
					$temp = getDirCapacity($files[$i]) ;
					if($temp === false)
						return false ;
					$fsize += temp ;
				}
				else
					$fsize += filesize($files[$i]) ;
			}
			return $fsize ;
		}
		else
			return 0 ;
	}
	
	private function deleteDir($dir)
	{
		if(!is_dir($dir))
			return false ;
		$files = glob($dir.DIRECTORY_SEPARATOR.'*');
		if(is_array($files))
		{
			for($i=0;$i<count($files);++$i)
			{
				if(is_dir($files[$i]))
				{
					deleteDir($files[$i]) ;
					rmdir($files[$i]) ;
				}
				else
					ulink($files[$i]) ;
			}
			return true ;
		}
		else
			return true ;
	}
	
	private function listFilename($dir)
	{
		if(!is_dir($dir))
			return false ;
		$files = glob($dir.DIRECTORY_SEPARATOR.'*');
		if(is_array($files))
		{
			$filelist = array() ;
			foreach($files as $key => $value)
			{
				if(is_dir($value))
				{
					$childfile = $this->listFilename($value) ;
					foreach($childfile as $childkey => $childvalue)
					{
						array_push($filelist,$childvalue) ;
					}
				}
				else
				{
					array_push($filelist,$value) ;
				}
			}
			return $filelist ;
		}
		else
		{
			return array() ;
		}
	}
	
	private function getPrefixList($dstStorDir,$prefix)
	{
		$domaindir = $dstStorDir ;
		$filelist = array() ;
		if(!is_dir($domaindir))
		{
			$this->setStorSavingError() ;
			return false ;
		}
		
		if($prefix[0] != '*')
		{
			$files = glob($domaindir.DIRECTORY_SEPARATOR."$prefix") ;
			
			if(is_array($files))
			{
				foreach($files as $file)
				{
					if(is_dir($file))
					{	
						$temp = $this->getPrefixList($file,"*") ;
						for($i=0;$i<count($temp);$i++)
						{
							array_push($filelist,$temp[$i]) ;
							
						}
					}
					else
					{
						array_push($filelist,$file) ;
					}
				}
			}
			return $filelist ;
		}
		
		$allfiles = glob($domaindir.DIRECTORY_SEPARATOR."*") ;

		if(is_array($allfiles))
		{
			for($i=0;$i<count($allfiles);++$i)
			{
				if(is_dir($allfiles[$i]))
				{
					$temp = $this->getPrefixList($allfiles[$i],$prefix) ;
					foreach($temp as $key => $value)
					{
						array_push($filelist,$value) ;
					}
				}
			}
		}
		else
			return array() ;
			
		$files = glob($domaindir.DIRECTORY_SEPARATOR.$prefix);
		if(is_array($files))
		{
			for($i=0;$i<count($files);++$i)
			{
				if(!is_dir($files[$i]))
				{
					array_push($filelist,$files[$i]) ;
				}
			}
		}
		else
		{
			return array() ;
		}
		return $filelist ;
	}
	
	/*
	 *  * 常见错误码参考：
	 *  - errno: 0 		成功
	 *  - errno: -2		配额统计错误
	 *  - errno: -3		权限不足
	 *  - errno: -7		Domain不存在
	 *  - errno: -12	存储服务器返回错误
	 *  - errno: -18 	文件不存在
	 *  - errno: -101	参数错误
	 *  - errno: -102	存储服务器连接失败
	 * 注：可使用SaeStorage::errmsg()方法获得当前错误信息。
	 *
	 * @package sae
	 * @author  quanjun
	 */
	private function setQuotaError( )
	{
		$this->errMsg 		= 'you do not have enough quota for saving files now! error points '.__FILE__.'::'.__LINE__.'  ';
		$this->errNum		= -2;
	}

	private function setRightError( )
	{
		$this->errMsg 		= 'you do not have correct key to carry out the operation! error points '.__FILE__.'::'.__LINE__.'  ';
		$this->errNum		= -3;
	}

	private function setNoDomainError( )
	{
		$this->errMsg 		= 'domain does not exists! error points '.__FILE__.': :'.__LINE__.'	';
		$this->errNum		= -7;
	}

	private function setNoFileError( )
	{
		$this->errMsg 		= 'sorry, the file does not exist! error points '.__FILE__.'::'.__LINE__.'	';
		$this->errNum		= -12;
	}

	private function setStorSavingError( )
	{
		$this->errMsg 		= 'sorry, the stor can not saving the file now! error points '.__FILE__.'::'.__LINE__.'  ';
		$this->errNum		= -18;
	}

	private function setParamsError( )
	{
		$this->errMsg 		= 'you should provide the correct params! error points '.__FILE__.'::'.__LINE__.'  ';
		$this->errNum		= -101;
	}

	private function setStorError( )
	{
		$this->errMsg 		= 'Stor conncecton error! error points '.__FILE__.'::'.__LINE__.'  ';
		$this->errNum		= -102;
	}
	
	private function zipCompress($srcFileNew,$srcFileName) 
	{
		$archive = new PclZip($srcFileName);
		$v_list = $archive->create($srcFileNew);
		if($v_list == 0)
			return false ;
		else
			return true ;
	}
	
	private function getDomainNum($appname)
	{
		$domainfile = SAE_STORAGE_STORAGE_DIR.DIRECTORY_SEPARATOR.$appname ;
		if(!is_dir($domainfile))
			return false ;
		$domainnum = 0 ;
		if($dir = opendir($domainfile))
		{
			while(false !== ($file=readdir($dir)))
			{
				if($file=='.' || $file=='..' || is_dir($domainfile.DIRECTORY_SEPARATOR.$file)==false)
					continue ;	
				$domainnum++ ;
			}
		}
		return $domainnum ;
	}
	
	private function getAllDomainNum()
	{
		$domainfile = SAE_STORAGE_STORAGE_DIR ;
		if(!is_dir($domainfile))
			return false ;
		$domainnum = 0 ;
		if($dir = opendir($domainfile))
		{
			while(($file=readdir($dir)) !== false)
			{
				if($file!='.'  && $file!='..' && is_dir($domainfile.DIRECTORY_SEPARATOR.$file))
					$domainnum += $this->getDomainNum($file) ;
			}
		}
		return $domainnum ;
	}
}
?>