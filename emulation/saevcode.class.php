<?php
/**
 * SAE 验证码服务 
 *
 * @package sae 
 * @version $Id$
 * @author Elmer Zhang
 */

/**
 * SAE 验证码服务
 *
 * <code>
 * <?php
 * session_start();
 * $vcode = new SaeVCode();
 * if ($vcode === false)
 * 		var_dump($vcode->errno(), $vcode->errmsg());
 *
 * $_SESSION['vcode'] = $vcode->answer();
 * $question=$vcode->question();
 * echo $question['img_html'];
 *
 * ?>
 * </code>
 *
 * 错误码参考：
 *  - errno: 0 		成功
 *  - errno: 3 		参数错误
 *  - errno: 500 	服务内部错误
 *  - errno: 999 	未知错误
 *  - errno: 403 	权限不足或超出配额
 * 
 * @package sae
 * @author Elmer Zhang
 *
 */
class SaeVCode extends SaeObject
{
	const REDIS_HOST    = REDIS_HOST ;
	const REDIS_PORT    = REDIS_PORT ;
	const REDIS_CONNECT_TIMOUT  = 60; //s
	
	private $_accesskey = "";	
	private $_secretkey = "";
	private $_errno=SAE_Success;
	private $_errmsg="OK";
	private $vcode;
	private $key ;
	
	/**
	 * @ignore
	 */
	const baseurl = VCODE_HOST;
	/**
	 * @ignore
	 */
	 
	function __construct($options = array()) 
	{
		$this->_accesskey = SAE_ACCESSKEY;
		$this->_secretkey = SAE_SECRETKEY;
		
		$img_height = 80;
		$img_width = 20;

		$allno = 'ABCDEFGHJKMNPQRSWXZ234689';
		$ckno = '';

		$ret = array("errno"=>SAE_Success, "errmsg"=>"OK");

		for( $i = 0 ; $i < 4 ; $i++ )
		{
			$t = rand(1,strlen($allno));
			$t--;
			$ckno .= $allno{$t};
		}
		$this->vcode = $ckno ;
		$aimg = imageCreate($img_height,$img_width);    //生成图片		
		
		ImageColorAllocate($aimg, 255,255,255);          //图片底色，ImageColorAllocate第1次定义颜色PHP就认为是底色了
		$black = ImageColorAllocate($aimg, 200,200,200);        //定义需要的黑色

		
		ImageRectangle($aimg,0,0,$img_height-1,$img_width-1,$black);//先成一黑色的矩形把图片包围

		//下面该生成雪花背景了，其实就是在图片上生成一些符号
		for ($i=1; $i<=100; $i++) 
		{
			imageString($aimg,1,mt_rand(1,$img_height),mt_rand(1,$img_width),"*",imageColorAllocate($aimg,mt_rand(200,255),mt_rand(200,255),mt_rand(200,255)));
		} 
		for ($i=0;$i<strlen($ckno);$i++){ 
			imageString($aimg, mt_rand(3,5),$i*$img_height/4+mt_rand(1,10),mt_rand(1,$img_width/2), $ckno{$i},imageColorAllocate($aimg,mt_rand(0,100),mt_rand(0,150),mt_rand(0,200)));
		}
		ob_start();
		ImagePng($aimg);  
		ImageDestroy($aimg);
		$img_bin = ob_get_contents();
		ob_end_clean();

		$this->key = 'k-'.time().rand(1,10000);
		
		
		$redis = new Redis() ;
		while(!$redis->connect(REDIS_HOST,REDIS_PORT))
		{
			echo iconv('UTF-8','GBK','redis 连接错误，正在尝试从新连接。').PHP_EOL ;
			sleep(1) ;
		}
		
		$redis->select(APP_NUMBER) ;
		$redis->set($this->key,$img_bin) ;
		$redis->close() ;
	}
	
	/**
	 * 取得验证码问题
	 *
	 * 图片验证码返回格式: array("img_url"=>"验证码图片URL", "img_html"=>"用于显示验证码图片的HTML代码")
	 *
	 * @return array
	 * @author Elmer Zhang
	 */
	public function question() {
		return array(
            'img_url'=> 'http://'.self::baseurl.'?key='.$this->key ,
			'img_html'=>'<img src="http://'.self::baseurl.'?key='.$this->key.'"/>');
	}
	
	/**
	 * 取得验证码答案
	 *
	 * @return string
	 * @author Elmer Zhang
	 */
	public function answer() {
		return $this->vcode;
	}
	
	/**
	 * 取得错误码
	 *
	 * @return int
	 * @author Elmer Zhang
	 */
	public function errno() {
		return $this->_errno;
	}
	
	/**
	 * 取得错误信息
	 *
	 * @return string
	 * @author Elmer Zhang
	 */
	public function errmsg() {
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
	 * @author Elmer Zhang
	 * @ignore
	 */
	public function setAuth( $accesskey, $secretkey) {
		$accesskey = trim($accesskey);
		$secretkey = trim($secretkey);
		$this->_accesskey = $accesskey;
		$this->_secretkey = $secretkey;
		return true;
	}
	
	private function postData($post) {
	}
}
?>