<?php
// +----------------------------------------------------------------------
// | 乐视直播频道获取源码
// +----------------------------------------------------------------------
// | 开发者:护眼灯(QQ122475170)
// +----------------------------------------------------------------------
// | 本源码绿色免费，为保持分享精神，请勿修改上面信息！
// +----------------------------------------------------------------------
// | 更多更好的源码分享，请进群362782870
// +----------------------------------------------------------------------
@header("Content-Type: text/xml; charset=utf-8");
$xml=Live_api(2,'乐视卫视');
$xml.=Live_api(7,'乐视轮播');
if($xml){$xml='<list>'.PHP_EOL.$xml.'</list>';echo $xml;}
function Live_api($id,$label=null){if(!$label)$label='乐视轮播';
	$url='http://static.api.letv.com/live/proxy?belongArea=100&clientId=1001&signal='.$id;
	$data=@file_get_contents($url);$json=json_decode($data)->data;$xml=false;
	foreach($json as $k=>$v){$id=$v->channelId;$ti=$v->channelName;
		$xml.='<m type="deng" src="'.$id.'.liletv" label="'.$ti.'" />'.PHP_EOL;}
	if($xml){$xml='<m label="'.$label.'">'.PHP_EOL.$xml.'</m>'.PHP_EOL;return $xml;}
}
?>