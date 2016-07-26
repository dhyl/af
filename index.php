<?php
header("content-type:text/html;charset=utf-8");
/*//$a= range("a", "z");
//var_dump(range("a", "z"));
$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n<list>\n";

for ($i=0;$i<30;$i++){
	$randKey ='';
for ($a = 0; $a < 4; $a++) {
	$randKey .= chr(mt_rand(97, 122));
	$randKey .=chr(mt_rand(65, 90));
}
//echo $randKey ;	
$v=base64_encode($randKey);
//echo "<br>".$v;
$a=base64_decode($v);


$xml .='<m type="img" src="' . $a . '" label="'.$v.'"/>'."\n";
}
$xml .= '</list>';
echo $xml;*/
/* header("content-type:text/html;charset=utf-8");
// $url="http://www.youku.com/show_episode/id_zd56886dc86fc11e3a705.html";
// $contents = file_get_contents($url);
// preg_match_all('#<a href="http://v.youku.com/v_show/id_(.*).html(.*)title="(.*)" charset=#',$contents,$a);	
// $a=$a[1];
// var_dump($a);
// foreach($a AS $v=>$k){
// 	echo $k[1];
// }
$str="删除：:01235删除你妹啊！！！！";
preg_match('/\:(.*)删除[\x{4e00}-\x{9fa5}]+/u',$str,$rz);
var_dump($rz) ; */
$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n<list>\n";
function m_v($url) {
	$user_agent = $_SERVER['HTTP_USER_AGENT'];
	$ch = curl_init();
	$timeout = 30;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	@ $file = curl_exec($ch);
	curl_close($ch);
	return $file;
}
$fname = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER["SCRIPT_NAME"];
if(isset ($_GET['w'])){
	$c ='http://www.99mc.com/zg/play/'.$_GET['w'].'.html';
	$d = m_v($c);
	preg_match('|http://mp3.99mc.com/(.*).mp3|', $d, $s);
	$j = 'http://mp3.99mc.com/'.$s[1].'.mp3';
	header("location:$j");
}
if(isset ($_GET['u'])){
	$u=$_GET['u'];
	$t = explode('-', $u);
	for ($i = 1; $i <= $t[1]; $i++) {
		$y = 'http://www.99mc.com/zg/'.$t[0].'_'.$i.'.html';
		$xml .= '<m list_src="'.$fname.'?p='.$y.'" label="第'.$i.'页" />'."\n";
	}}
	elseif(isset ($_GET['p'])){
		$a=$_GET['p'];
		$b = m_v($a);
		preg_match_all('#<a href="/zg/play/(?<grp0>[^\D]+)\.html"[\s]+target="_blank" title="(?<grp1>[^"]+)">[^<]*</a>#', $b, $e);
		var_dump($e);
		exit;
		$id=$e[1];
		$name=$e[3];
		foreach ($id as $k => $v){
			$xml .='<m type="1" src="'.$fname.'?w='.$v.'" label="'.$name[$k].'" />'."\n";
		}}
		else{
			$tttmv = array (
					'MC喊麦' => 'mchm/list_101-32',
					'MC伴奏' => 'mcbz/list_102-4',
					'聊吧搞笑' => 'lbgx/list_103-2',
					'现场串烧' => 'xccs/list_104-2',
					'慢摇舞曲' => 'mycs/list_105-4',
					'中文舞曲' => 'zwwq/list_106-13',
					'皇族战歌' => 'hzzg/list_63428-2'

			);
			foreach ($tttmv as $k => $v) {
				$xml .= '<m list_src="'.$fname.'?u='.$v.'" label="'.$k.'" />'."\n";
			}}
			$xml .= '</list>';
			echo $xml;
?>