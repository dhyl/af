<?php
header("content-type:text/html;charset=utf-8");
//http://www.tudou.com/outplay/goto/getItemSegs.action?areaCode=&code=YDn_zTq_8gI
error_reporting(0);//禁用错误报告
function g_url($url) {//设置一个自定义
$user = $_SERVER['HTTP_USER_AGENT'];//获取用户相关信息
$ch = curl_init();//初始化一个cURL对象
$timeout = 40;
curl_setopt($ch, CURLOPT_USERAGENT, $user);//在HTTP请求中包含一个”user-agent”头的字符串。
curl_setopt($ch, CURLOPT_URL, $url);//设置你要抓取的网站$url
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//设置cURL参数，要求结果保存到字符串0为显示到屏幕中
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);//在发起连接前等待的时间
@ $file = curl_exec($ch);//运行cURL，请求网页
curl_close($ch);//关闭URL请求
       return $file;//输出
}
$mp4=g_url("http://www.tudou.com/outplay/goto/getItemSegs.action?areaCode=&code=$_GET[id]");//获取
echo $mp4;
exit;
//$mp4=g_url("http://www.tudou.com/outplay/goto/getItemSegs.action?areaCode=&code=YDn_zTq_8gI");//获取
//$mp4=g_url("http://vr.tudou.com/v2proxy/v?sid=95001&id=330192933&st=52");//获取
preg_match("#pt(.*)\"k\":(.*),\"size#",$mp4,$m);
$a='http://vr.tudou.com/v2proxy/v?sid=95001&id='.$m[2].'&st=52';
$flv=get_headers($a);//获取返回的数组
$a= str_replace("Location:","",$flv[4]);//替换
echo  header("Location: $a");//输出返回地址



// // 创建一个cURL资源
// $ch = curl_init();

// // 设置URL和相应的选项
// curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);//在HTTP请求中包含一个”user-agent”头的字符串。
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);//设置cURL参数，要求结果保存到字符串0为显示到屏幕中
// curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 40);//在发起连接前等待的时间
// curl_setopt($ch, CURLOPT_URL, "http://tool.oschina.net/commons?type=3");
// curl_setopt($ch, CURLOPT_HEADER, 0);

// // 抓取URL并把它传递给浏览器
// $file=curl_exec($ch);
// echo $file;
// // 关闭cURL资源，并且释放系统资源
// curl_close($ch);
?>
