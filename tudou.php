<?php
header("content-type:text/html;charset=utf-8");
//http://www.tudou.com/outplay/goto/getItemSegs.action?areaCode=&code=YDn_zTq_8gI
error_reporting(0);//���ô��󱨸�
function g_url($url) {//����һ���Զ���
$user = $_SERVER['HTTP_USER_AGENT'];//��ȡ�û������Ϣ
$ch = curl_init();//��ʼ��һ��cURL����
$timeout = 40;
curl_setopt($ch, CURLOPT_USERAGENT, $user);//��HTTP�����а���һ����user-agent��ͷ���ַ�����
curl_setopt($ch, CURLOPT_URL, $url);//������Ҫץȡ����վ$url
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//����cURL������Ҫ�������浽�ַ���0Ϊ��ʾ����Ļ��
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);//�ڷ�������ǰ�ȴ���ʱ��
@ $file = curl_exec($ch);//����cURL��������ҳ
curl_close($ch);//�ر�URL����
       return $file;//���
}
$mp4=g_url("http://www.tudou.com/outplay/goto/getItemSegs.action?areaCode=&code=$_GET[id]");//��ȡ
echo $mp4;
exit;
//$mp4=g_url("http://www.tudou.com/outplay/goto/getItemSegs.action?areaCode=&code=YDn_zTq_8gI");//��ȡ
//$mp4=g_url("http://vr.tudou.com/v2proxy/v?sid=95001&id=330192933&st=52");//��ȡ
preg_match("#pt(.*)\"k\":(.*),\"size#",$mp4,$m);
$a='http://vr.tudou.com/v2proxy/v?sid=95001&id='.$m[2].'&st=52';
$flv=get_headers($a);//��ȡ���ص�����
$a= str_replace("Location:","",$flv[4]);//�滻
echo  header("Location: $a");//������ص�ַ



// // ����һ��cURL��Դ
// $ch = curl_init();

// // ����URL����Ӧ��ѡ��
// curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);//��HTTP�����а���һ����user-agent��ͷ���ַ�����
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);//����cURL������Ҫ�������浽�ַ���0Ϊ��ʾ����Ļ��
// curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 40);//�ڷ�������ǰ�ȴ���ʱ��
// curl_setopt($ch, CURLOPT_URL, "http://tool.oschina.net/commons?type=3");
// curl_setopt($ch, CURLOPT_HEADER, 0);

// // ץȡURL���������ݸ������
// $file=curl_exec($ch);
// echo $file;
// // �ر�cURL��Դ�������ͷ�ϵͳ��Դ
// curl_close($ch);
?>
