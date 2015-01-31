<?php
/**
 * SAE邮件服务
 *
 * @package sae
 * @version $Id$
 * @author lijun
 */

/**
 * SAE邮件class , 目前只支持SMTP
 *
 * 最大可发送1MB大小的邮件（含附件）
 *
 * <code>
 * <?php
 * $mail = new SaeMail();
 * $mail->setAttach( array( 'my_photo' => '照片的二进制数据' ) );
 * $ret = $mail->quickSend( 'to@sina.cn' , '邮件标题' , '邮件内容' , 'smtpaccount@gmail.com' , 'password' );
 *
 * //发送失败时输出错误码和错误信息
 * if ($ret === false)
 * 		var_dump($mail->errno(), $mail->errmsg());
 *
 * $mail->clean(); // 重用此对象
 * $ret = $mail->quickSend( 'to@sina.cn' , '邮件标题' , '邮件内容' , 'smtpaccount@unknown.com' , 'password' , 'smtp.unknown.com' , 25 ); // 指定smtp和端口
 *
 * //发送失败时输出错误码和错误信息
 * if ($ret === false)
 * 		var_dump($mail->errno(), $mail->errmsg());
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
 * @author lijun
 *
 */
require_once __DIR__.DIRECTORY_SEPARATOR.'PHPMailer'.DIRECTORY_SEPARATOR.'class.phpmailer.php' ;
require_once __DIR__.DIRECTORY_SEPARATOR.'Archive'.DIRECTORY_SEPARATOR.'saezip.class.php' ;

class SaeMail// extends SaeObject
{
	private $_accesskey = "";
	private $_secretkey = "";
	private $_errno=SAE_Success;
	private $_errmsg="OK";
	private $_count = 0;
	private $_attachSize = 0;
	private $_post = array();
	private $_allowedAttachType = array("bmp","css","csv","gif","htm","html","jpeg","jpg","jpe","pdf","png","rss","text","txt","asc","diff","pot","tiff","tif","wbmp","ics","vcf");
	/**
	 * @ignore
	 */
	const DISPOSITION_ATTACH = 'A';
	/**
	 * @ignore
	 */
	const DISPOSITION_INLINE = 'I';
	private $_disposition = array(  "bmp"=>self::DISPOSITION_INLINE, "css"=>self::DISPOSITION_ATTACH,
		"csv"=>self::DISPOSITION_ATTACH, "gif"=>self::DISPOSITION_INLINE,
		"htm"=>self::DISPOSITION_INLINE,"html"=>self::DISPOSITION_INLINE,
		"jpeg"=>self::DISPOSITION_INLINE,"jpg"=>self::DISPOSITION_INLINE,
		"jpe"=>self::DISPOSITION_INLINE, "pdf"=>self::DISPOSITION_ATTACH,
		"png"=>self::DISPOSITION_INLINE, "rss"=>self::DISPOSITION_INLINE,
		"text"=>self::DISPOSITION_ATTACH,"txt"=>self::DISPOSITION_ATTACH,
		"asc"=>self::DISPOSITION_ATTACH,"diff"=>self::DISPOSITION_ATTACH,
		"pot"=>self::DISPOSITION_ATTACH,"tiff"=>self::DISPOSITION_ATTACH,
		"tif"=>self::DISPOSITION_ATTACH,"wbmp"=>self::DISPOSITION_INLINE,
		"ics"=>self::DISPOSITION_INLINE,"vcf"=>self::DISPOSITION_INLINE,);
	private $msp = array("sina.com"	=> array("smtp.sina.com",25,0),
		"sina.cn"		=> array("smtp.sina.cn",25,0),
		"163.com"		=> array("smtp.163.com",25,0),
		"263.com"		=> array("smtp.263.com",25,0),
		"gmail.com"	=> array("smtp.gmail.com",587,1),
		"sohu.com"	=> array("smtp.sohu.com",25,0),
		"qq.com"		=> array("smtp.qq.com",25,0),
		"vip.qq.com"	=> array("smtp.qq.com",25,0),
		"126.com"		=> array("smtp.126.com",25,0),
	);

	/**
	 * @ignore
	 */
	const baseurl = "http://mail.sae.sina.com.cn/index.php";
	/**
	 * @ignore
	 */
	const mail_limitsize = 1048576;
	/**
	 * @ignore
	 */
	const subject_limitsize = 256;

	/**
	 * 构造对象，此处options选项的设置和函数setOpt相同
	 *
	 * @param array $options 邮件发送参数，详细参数请参考SaeMail::setOpt($options)
	 */
	function __construct($options = array()) {
		$this->_accesskey = SAE_ACCESSKEY;
		$this->_secretkey = SAE_SECRETKEY;

		$this->setOpt($options);
	}

	/**
	 * 设置发送参数,此处设置的参数只有使用send()方法发送才有效;quickSend()时将忽略此设置.
	 *
	 *
	 * @param array $options 支持的Key如下:
	 * <pre>
	 *	-----------------------------------------
	 *	<b>KEY		VALUE</b>
	 *	-----------------------------------------
	 *    from		string (only one)
	 *    -----------------------------------------
	 *    to		string (多个用,分开)
	 *    -----------------------------------------
	 *    cc		string (多个用,分开)
	 *    -----------------------------------------
	 *    smtp_host	string
	 *    -----------------------------------------
	 *    smtp_port	port,default 25
	 *    -----------------------------------------
	 *    smtp_username	string
	 *    -----------------------------------------
	 *    smtp_password	string
	 *    -----------------------------------------
	 *    subject		string,最大长度256字节
	 *    -----------------------------------------
	 *    content		text
	 *    -----------------------------------------
	 *    content_type	"TEXT"|"HTML",default TEXT
	 *    -----------------------------------------
	 *    charset		default utf8
	 *    -----------------------------------------
	 *    tls		default false(ssl)
	 *    -----------------------------------------
	 *    compress		string 设置此参数后，SaeMail会将所有附件压缩成一个zip文件，此参数用来指定压缩后的文件名。
	 *    -----------------------------------------
	 * </pre>
	 *
	 * @return bool
	 * @author Lijun
	 */
	public function setOpt( $options ) {
		$nece_array=array('to'=>'',
			'smtp_username'=>'', 'smtp_password'=>'',
			'smtp_host'=>'', 'smtp_port'=>'', 'tls'=>'');
		$res_array = (array_diff_key($nece_array, $options));
		if(!empty($res_array))
		{
			$errno = SAE_ErrParameter;
			$parame="";
			foreach($res_array as $k => $v)
			{
				$parame .= " '$k'";
			}
			$errmsg = "$parame need a value in function setOpt() parameter";
			return false;
		}

		if (isset($options['subject']) && strlen($options['subject']) > self::subject_limitsize) {
			$this->_errno = SAE_ErrParameter;
			$this->_errmsg = "subject cannot larger than ".self::subject_limitsize." bytes";
			return false;
		}
		if(isset($options['content']))
		$this->_count = $this->_attachSize + strlen($options['content']);
		if($this->_count > self::mail_limitsize) {
			$this->_errno = SAE_ErrParameter;
			$this->_errmsg = "mail size cannot larger than ".self::subject_limitsize." bytes";
			return false;
		}

		$this->_post = array_merge($this->_post, $options);

		return true;
	}

	/**
	 * 用于重用实例化对象时，将上一次的相关数据清零
	 *
	 * @return bool
	 * @author Lijun
	 */
	public function clean() {
		$this->_post = array();
		$this->_count = 0;
		$this->_attachSize = 0;
		return true;
	}

	/**
	 * 快速发送邮件
	 *
	 * 由于采用邮件队列发送,本函数返回成功时,只意味着邮件成功送到发送队列,并不等效于邮件已经成功发送.
	 * 邮件编码默认为UTF-8,如需发送其他编码的邮件,请使用setOpt()方法设置charset,否则收到的邮件标题和内容都将是空的.
	 *
	 * @param string $to 要发送到的邮件地址,多个邮件地址之间用英文逗号","隔开
	 * @param string $subject 邮件标题
	 * @param string $msgbody 邮件内容
	 * @param string $smtp_user smtp用户名，必须为邮箱地址。注：和setOpt()中的smtp_user不同。
	 * @param string $smtp_pass smtp用户密码
	 * @param string $smtp_host smtp服务host,使用sina,gmail,163,265,netease,qq,sohu,yahoo的smtp时可不填
	 * @param string $smtp_port smtp服务端口,使用sina,gmail,163,265,netease,qq,sohu,yahoo的smtp时可不填
	 * @param string $smtp_tls smtp服务是否开启tls(如gmail),使用sina,gmail,163,265,netease,qq,sohu,yahoo的smtp时可不填
	 * @return bool
	 * @author Lijun
	 */
	function quickSend($to, $subject, $msgbody, $smtp_user, $smtp_pass, $smtp_host='', $smtp_port=25, $smtp_tls=false)
	{
		$to = trim($to);
		$subject = trim($subject);
		$msgbody = trim($msgbody);
		$smtp_user = trim($smtp_user);
		$smtp_host = trim($smtp_host);
		$smtp_port = intval($smtp_port);

		$this->_count = strlen($msgbody) + $this->_attachSize;
		if(strlen($subject) > self::subject_limitsize) {
			$_errno = SAE_ErrParameter;
			$this->_errmsg = "subject cannot larger than ".self::subject_limitsize." bytes";
			return false;
		}
		if($this->_count > self::mail_limitsize) {
			$this->_errno = SAE_ErrParameter;
			$this->_errmsg = "mail size cannot larger than ".self::subject_limitsize." bytes";
			return false;
		}

		//if(preg_match('/([a-zA-Z0-9_-]+)@([a-zA-Z0-9-]+\.[a-zA-Z0-9-]+\.?[a-zA-Z0-9-]*)/', $smtp_user, $match)) {
		if (filter_var($smtp_user, FILTER_VALIDATE_EMAIL)) {
			preg_match('/([^@]+)@(.*)/', $smtp_user, $match);
			$user = $match[1]; $host = $match[2];
			if(empty($smtp_host)) {
				//print_r($match);
				if(isset($this->msp[$host])) { $smtp_host = $this->msp[$host][0]; }
				else {
					$this->_errno = SAE_ErrParameter;
					$this->_errmsg = "you can set smtp_host explicitly or choose msp from sina,gmail,163,265,netease,qq,sohu,yahoo";
					return false;
				}
			}
			if($smtp_port == 25 and isset($this->msp[$host])) {
				$smtp_port = $this->msp[$host][1];
			}
			if(!$smtp_tls and isset($this->msp[$host])) {
				$smtp_tls = $this->msp[$host][2];
			}
			$smtp_tls = ($smtp_tls == true);
			$username = $user;
		} else {
			$this->_errno = SAE_ErrParameter;
			$this->_errmsg = "invalid email address";
			return false;
		}
		$this->_post = array_merge($this->_post, array("from"=>$smtp_user, "smtp_username"=>$username, "smtp_password"=>$smtp_pass, "smtp_host"=>$smtp_host, "smtp_port"=>$smtp_port, 'to'=>$to,'subject'=>$subject,'content'=>$msgbody, 'tls'=>$smtp_tls));

		return $this->send();
	}

	/**
	 * 取得错误码
	 *
	 * @return int
	 * @author Lijun
	 */
	public function errno() {
		return $this->_errno;
	}

	/**
	 * 取得错误信息
	 *
	 * @return string
	 * @author Lijun
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
	 * @return bool
	 * @author Lijun
	 */
	public function setAuth( $accesskey, $secretkey) {
		$accesskey = trim($accesskey);
		$secretkey = trim($secretkey);
		$this->_accesskey = $accesskey;
		$this->_secretkey = $secretkey;
		return true;
	}

	/**
	 * 添加附件
	 *
	 * 附件和邮件正文的总大小不可超过1MB。
	 *
	 * @param array $attach , key为文件名称,附件类型由文件名后缀决定,value为文件内容;文件内容支持二进制<br>
	 * 支持的文件后缀:bmp,css,csv,gif,htm,html,jpeg,jpg,jpe,pdf,png,rss,text,txt,asc,diff,pot,tiff,tif,wbmp,ics,vcf
	 * @return bool
	 * @author Lijun
	 */
	public function setAttach($attach) {
		if(!is_array($attach)) {
			$this->_errmsg = "attach parameter must be an array!";
			$this->_errno = SAE_ErrParameter;
			return false;
		}
		$this->_attachSize = 0;
		foreach($attach as $fn=>$blob) {
			$temp = explode(".", $fn) ;
			$suffix = end($temp);
			if(!in_array($suffix, $this->_allowedAttachType)) {
				$this->_errno = SAE_ErrParameter;
				$this->_errmsg = "Invalid attachment type";
				return false;
			}
			$this->_attachSize += strlen($blob);
			$this->_count = $this->_attachSize + array_key_exists('content',$this->_post) ? strlen($this->_post['content']) : 0 ;
			if($this->_count > self::mail_limitsize) {
				$this->_errno = SAE_ErrForbidden;
				$this->_errmsg = "mail size cannot larger than ".self::mail_limitsize." bytes";
				return false;
			}
			$this->_post = array_merge($this->_post, array("attach:$fn:B:".$this->_disposition[$suffix] => $blob));
			//print_r(strlen(base64_encode($blob)));
		}
		return true;
	}

	/**
	 * 发送邮件
	 *
	 * @return bool
	 * @author Lijun
	 */
	//ccc
	public function send()
	{
		if($this->_count > self::mail_limitsize)
		{
			$this->_errno = SAE_ErrForbidden;
			$this->_errmsg = "mail size cannot larger than ".self::mail_limitsize." bytes";
			return false;
		}
		//print_r($this->_post);
		if ( isset($this->_post['charset']) ) {
			$charset = strtolower($this->_post['charset']);
			$content_conved = @iconv($charset, 'utf-8', $this->_post['content']);
			if ($content_conved)
			$this->_post['content'] = $content_conved;
			$subject_conved = @iconv($charset, 'utf-8', $this->_post['subject']);
			if ($subject_conved)
			$this->_post['subject'] = $subject_conved;
			$this->_post['charset'] = 'utf-8';
		}

		//from/to/cc/smtp_host/smtp_port/smtp_username/smtp_password/subject/content/content_type/compress////
		$mailer             = new PHPMailer();

		if($this->_post['content'])
		{
			$body   		= $this->_post['content'];
			$body			= stripslashes($body);
		}
		else
		{
			$body				= '';
		}

		$mailer->IsSMTP();
		$mailer->SMTPAuth   = true;                  // assume all smtp needs authentication

		if($this->_post['tls'])
		{
			$mailer->SMTPSecure = "ssl";                 // sets the prefix to the servier
		}

		$mailer->Host       		= $this->_post['smtp_host'];      // sets GMAIL as the SMTP server
		$mailer->Port       		= $this->_post['smtp_port'];       // set the SMTP port

		$mailer->Username   = $this->_post['from'];      // GMAIL username
		$mailer->Password    =  $this->_post['smtp_password'];               // GMAIL password

		$mailer->CharSet  = 'utf-8';
		$mailer->Encoding = 'base64';

		//$mailer->From       		= "raineelee@gmail.com";
		$mailer->From       		 	= $this->_post['from'];
		$mailer->FromName  	= $this->_post['smtp_username'];
		$mailer->Subject    			= $this->_post['subject'];
		$mailer->WordWrap   	= 50    ; // set word wrap

		if(isset($this->_post['content_type']) && strtolower($this->_post['content_type']) == 'text')
		{
			$mailer->IsHTML(false); // send as HTML
			$mailer->Body	= $body;
		}
		else
		{
			$mailer->IsHTML(true); // send as HTML
			$mailer->MsgHTML($body);
		}

		$toArr	= explode(',', $this->_post['to']);
		
		foreach ($toArr as $ma)
		{
			$mailer->AddAddress($ma);
		}

		if(isset($this->_post['cc']) && $this->_post['cc'])
		{
			$ccArr	= explode(',', $this->_post['cc']);
			foreach ($ccArr as $ma)
			{
				$mailer->AddCC($ma);
			}
		}

		$attachArr 		= array();
		$i		= 0;

		foreach ($this->_post as $pk => $pd)
		{
			$pkArr	= NULL;
			$pkArr	= explode(':', $pk);
			
			if(trim($pkArr[0] == 'attach'))
			{
				$tfName = tempnam(SAE_TMP_PATH, 'SAE_MAIL_SRV');
				file_put_contents($tfName, $pd);
				$attachArr[$i]['tfn']				= $tfName;
				$attachArr[$i]['ofn']			= $pkArr[1];
				$attachArr[$i]['amode']		= $pkArr[3];
				$i++;
			}
		}

		if(count($attachArr) > 0)
		{
			if($this->_post['compress'])
			{
				$zipFName = tempnam(SAE_TMP_PATH, 'SAE_MAIL_SRV');
				$obj = new SaeZip();

				$files	 = array();

				foreach($attachArr as $atch)
				{
					$files[]	= $atch['tfn'];
				}

				if (!$obj ->CompileZipFile($files, $zipFName, 'array'))
				{
					$this->_errmsg   = 'compress file error!';
				}

				$mailer->AddAttachment($zipFName, $this->_post['compress']); // attachment
			}
			else
			{
				foreach($attachArr as $atch)
				{
					if($atch['amode'] == 'A')
					{
						$mailer->AddAttachment($atch['tfn'], $atch['ofn']); // attachment
					}
					elseif($atch['amode'] == 'I')
					{
						$mailer->AddEmbeddedImage($atch['tfn'], $atch['ofn'] ,$atch['ofn']);
					}
				}
			}
		}

		$ret		= true;
		if(!$mailer->Send()) {
			$this->_errmsg  = "Mailer Error: " . $mailer->ErrorInfo;
			$ret = false;
		} else {
			//echo "Message has been sent";
		}
		
		$mailer->ClearAddresses();
		$mailer->ClearCCs();
		$mailer->ClearAttachments();
		$mailer->ClearAllRecipients();
		$mailer->ClearBCCs();
		$mailer->ClearCustomHeaders();
		$mailer->ClearReplyTos();

		return $ret;
		/* ccc
		 $tobepost = json_encode($this->_post);
		 return $this->postData(array("saemail"=>$tobepost));
		 */
	}
}