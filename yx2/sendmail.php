<?php

	/**
	 * ע�����ʼ��඼�Ǿ����Ҳ��Գɹ��˵ģ������ҷ����ʼ���ʱ��������ʧ�ܵ����⣬������¼����Ų飺
	 * 1. �û����������Ƿ���ȷ��
	 * 2. ������������Ƿ�������smtp����
	 * 3. �Ƿ���php���������⵼�£�
	 * 4. ��26�е�$smtp->debug = false��Ϊtrue��������ʾ������Ϣ��Ȼ����Ը��Ʊ�����Ϣ��������һ�´����ԭ��
	 * 5. ������ǲ��ܽ�������Է��ʣ�http://www.daixiaorui.com/read/16.html#viewpl 
	 *    ����������У���������Ҫ�ҵĴ𰸡�
	 */
/*
	require_once "email.class.php";
	//******************** ������Ϣ ********************************
	$smtpserver = "smtp.exmail.qq.com";//SMTP������
	$smtpserverport =25;//SMTP�������˿�
	$smtpusermail = "guoxiaofeng@rongyitech.com";//SMTP���������û�����
	$smtpemailto = $_POST['toemail'];//���͸�˭
	$smtpuser = "guoxiaofeng";//SMTP���������û��ʺ�
	$smtppass = "gxf-+123";//SMTP���������û�����
	$mailtitle = $_POST['title'];//�ʼ�����
	$mailcontent = "<h1>".$_POST['content']."</h1>";//�ʼ�����
	$mailtype = "TXT";//�ʼ���ʽ��HTML/TXT��,TXTΪ�ı��ʼ�
	//************************ ������Ϣ ****************************
	$smtp = new Smtp($smtpserver,$smtpserverport,true,$smtpuser,$smtppass);//�������һ��true�Ǳ�ʾʹ�������֤,����ʹ�������֤.
	$smtp->debug = true;//�Ƿ���ʾ���͵ĵ�����Ϣ
	$state = $smtp->sendmail($smtpemailto, $smtpusermail, $mailtitle, $mailcontent, $mailtype);

	echo "<div style='width:300px; margin:36px auto;'>";
	if($state==""){
		echo "�Բ����ʼ�����ʧ�ܣ�����������д�Ƿ�����";
		echo "<a href='index.html'>��˷���</a>";
		exit();
	}
	echo "��ϲ���ʼ����ͳɹ�����";
	echo "<a href='index.html'>��˷���</a>";
	echo "</div>";*/





require_once('class.phpmailer.php'); 
$address = $_POST['toemail']; //�ռ���email 
$mail = new PHPMailer(); //ʵ���� 
$mail->IsSMTP(); // ����SMTP 
$mail->Host = "smtp.rongyitech.com"; //SMTP������ ��163����Ϊ���� 
$mail->Port = 25;  //�ʼ����Ͷ˿� 
$mail->SMTPAuth = true;  //����SMTP��֤ 
  
$mail->CharSet = "UTF-8"; //�ַ��� 
$mail->Encoding = "base64"; //���뷽ʽ 
$email_system = "guoxiaofeng@rongyitech.com"; 
$mail->Username = "guoxiaofeng@rongyitech.com";  //������� 
$mail->Password = "gxf-+123";  //������� 
$mail->Subject = "����Email"; //�ʼ����� 
  
$mail->From = $email_system;  //�����˵�ַ��Ҳ����������䣩 
$mail->FromName = "����";  //���������� 
$mail->AddAddress($address, "��"); //����ռ��ˣ���ַ���ǳƣ� 
  
$mail->AddAttachment('gxf2016217.doc', '�ҵĸ���'); // ��Ӹ���,��ָ������ 
$mail->IsHTML(true); //֧��html��ʽ���� 
$mail->AddEmbeddedImage("logo.jpg", "my-attach", "logo.jpg"); //�����ʼ��е�ͼƬ 
$mail->Body = '���, <b>����</b>! <br/>����һ������<a href="" target="_blank">s*ucaihuo.com</a>���ʼ���<br/><img alt="" src="cid:my-attach">'; //�ʼ��������� 
//���� 
if (!$mail->Send()) { 
    echo "����ʧ��: " . $mail->ErrorInfo; 
} else { 
    echo "���ͳɹ�������"; 
}









?>