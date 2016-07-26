<?php

	/**
	 * 注：本邮件类都是经过我测试成功了的，如果大家发送邮件的时候遇到了失败的问题，请从以下几点排查：
	 * 1. 用户名和密码是否正确；
	 * 2. 检查邮箱设置是否启用了smtp服务；
	 * 3. 是否是php环境的问题导致；
	 * 4. 将26行的$smtp->debug = false改为true，可以显示错误信息，然后可以复制报错信息到网上搜一下错误的原因；
	 * 5. 如果还是不能解决，可以访问：http://www.daixiaorui.com/read/16.html#viewpl 
	 *    下面的评论中，可能有你要找的答案。
	 */
/*
	require_once "email.class.php";
	//******************** 配置信息 ********************************
	$smtpserver = "smtp.exmail.qq.com";//SMTP服务器
	$smtpserverport =25;//SMTP服务器端口
	$smtpusermail = "guoxiaofeng@rongyitech.com";//SMTP服务器的用户邮箱
	$smtpemailto = $_POST['toemail'];//发送给谁
	$smtpuser = "guoxiaofeng";//SMTP服务器的用户帐号
	$smtppass = "gxf-+123";//SMTP服务器的用户密码
	$mailtitle = $_POST['title'];//邮件主题
	$mailcontent = "<h1>".$_POST['content']."</h1>";//邮件内容
	$mailtype = "TXT";//邮件格式（HTML/TXT）,TXT为文本邮件
	//************************ 配置信息 ****************************
	$smtp = new Smtp($smtpserver,$smtpserverport,true,$smtpuser,$smtppass);//这里面的一个true是表示使用身份验证,否则不使用身份验证.
	$smtp->debug = true;//是否显示发送的调试信息
	$state = $smtp->sendmail($smtpemailto, $smtpusermail, $mailtitle, $mailcontent, $mailtype);

	echo "<div style='width:300px; margin:36px auto;'>";
	if($state==""){
		echo "对不起，邮件发送失败！请检查邮箱填写是否有误。";
		echo "<a href='index.html'>点此返回</a>";
		exit();
	}
	echo "恭喜！邮件发送成功！！";
	echo "<a href='index.html'>点此返回</a>";
	echo "</div>";*/





require_once('class.phpmailer.php'); 
$address = $_POST['toemail']; //收件人email 
$mail = new PHPMailer(); //实例化 
$mail->IsSMTP(); // 启用SMTP 
$mail->Host = "smtp.rongyitech.com"; //SMTP服务器 以163邮箱为例子 
$mail->Port = 25;  //邮件发送端口 
$mail->SMTPAuth = true;  //启用SMTP认证 
  
$mail->CharSet = "UTF-8"; //字符集 
$mail->Encoding = "base64"; //编码方式 
$email_system = "guoxiaofeng@rongyitech.com"; 
$mail->Username = "guoxiaofeng@rongyitech.com";  //你的邮箱 
$mail->Password = "gxf-+123";  //你的密码 
$mail->Subject = "测试Email"; //邮件标题 
  
$mail->From = $email_system;  //发件人地址（也就是你的邮箱） 
$mail->FromName = "阿峰";  //发件人姓名 
$mail->AddAddress($address, "亲"); //添加收件人（地址，昵称） 
  
$mail->AddAttachment('gxf2016217.doc', '我的附件'); // 添加附件,并指定名称 
$mail->IsHTML(true); //支持html格式内容 
$mail->AddEmbeddedImage("logo.jpg", "my-attach", "logo.jpg"); //设置邮件中的图片 
$mail->Body = '你好, <b>朋友</b>! <br/>这是一封来自<a href="" target="_blank">s*ucaihuo.com</a>的邮件！<br/><img alt="" src="cid:my-attach">'; //邮件主体内容 
//发送 
if (!$mail->Send()) { 
    echo "发送失败: " . $mail->ErrorInfo; 
} else { 
    echo "发送成功！！！"; 
}









?>