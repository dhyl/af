<?php
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
$fname='http://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
if(isset ($_GET['p'])){
       global $fname;
       $a = 'http://www.soku.com/v/q_%E7%BE%8E%E5%A5%B3%E7%83%AD%E8%88%9E_orderby_1_limitdate_0?site=14&page='.$_GET['p'];
       $b = m_v($a);
       preg_match_all('#<img alt="(.*)" src="(.*)".*_log_vid="(.*)"#Us',$b,$c);
       foreach($c[3] as $L=>$LU){
       $xml .='<m type="merge" src="youku.php?id=' . $LU . '" image="'.$c[2][$L].'" label="'.$c[1][$L].'" />'."\n";
}}else{
       global $fname;
       for($i=1;$i<=20;$i++){
       $xml .= '<m list_src="'.$fname.'?p='. $i . '" label="第' . $i . '页" />'."\n";
}}
$xml .= '</list>';
echo $xml;
?>