<?php
header("content-type:text/html;charset=utf-8");
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
$fname = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER["SCRIPT_NAME"];
if(isset ($_GET['u'])){
       $u=$_GET['u'];
       $t = explode('-', $u);
       for ($i = 1; $i <= $t[1]; $i++) {
       $y = 'http://www.youku.com/v_olist/c_97_g_'.$t[0].'_a__sg__mt__lg__q__s_1_r_0_u_0_pt_1_av_0_ag_0_sg__pr__h__d_1_p_'.$i.'.html';
       $xml .= '<m list_src="' . $fname . '?p=' . $y . '" label="优酷'.$t[0].'剧 第' . $i . '页"/>'."\n";
}}
elseif(isset ($_GET['p'])){
       $a=$_GET['p'];
       $b = m_v($a);
       preg_match_all('# <div class="p-meta-title"><a href="http://www.youku.com/show_page/id_(.*?).html" target="_blank" title="(.*?)">#ims', $b, $v);
       $f = count($v[1]);
       for($m=0;$m<$f;$m++){
       $xml .='<m list_src="' . $fname . '?r=' . $v[1][$m] . '" label="' . $v[2][$m] . '"/>'."\n";
}}
elseif(isset ($_GET['r'])){
       $l ='http://www.youku.com/show_episode/id_'.$_GET['r'].'.html';
       $r = m_v($l);
       preg_match_all('#<a href="http://v.youku.com/v_show/id_(.*).html(.*)title="(.*)" charset=#',$r,$a);
       $y = count($a[1]);
       for($m=0;$m<$y;$m++){
       $xml .='<m type="youku" src="' . $a[1][$m] . '" label="'.$a[3][$m].'"/>'."\n";
}}
else{
$class = array (
       '古装剧' => '古装-30',
       '武侠剧' => '武侠-30',
       '警匪剧' => '警匪-30',
       '军事剧' => '军事-30',
       '神话剧' => '神话-30',
       '科幻剧' => '科幻-30',
       '悬疑剧' => '悬疑-30',
       '历史剧' => '历史-30',
       '儿童剧' => '儿童-30',
       '农村剧' => '农村-30',
       '都市剧' => '都市-30',
       '家庭剧' => '家庭-30',
       '搞笑剧' => '搞笑-30',
       '偶像剧' => '偶像-30',
       '言情剧' => '言情-30',
       '时装剧' => '时装-30',
       '优酷出品' => '优酷出品-30',
       '全部' => '-30',
);
foreach ($class as $k => $v) {
       $xml .= '<m list_src="' . $fname . '?u=' . $v  . '" label="' . $k . '"/>'."\n";
}}
$xml .= '</list>';
echo $xml;
?>