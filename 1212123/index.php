<!DOCTYPE HTML>
<html>
<head>
	<title>灯云库 - 最方便好用的在线视频库</title>
	<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="renderer" content="webkit|ie-comp|ie-stand">
	<meta charset="utf-8" />
	<meta name="keywords" content="灯云库,最新视频,古装,武侠,在线漫画,原创漫画,日本动漫,动画片大全,好看的动漫,好看的漫画,漫画网,动漫网,动漫之家">
	<meta name="description" content="做最做方便好用的在线视频库,致力于做方便好用的在线视频库。">
	<link rel="stylesheet" href="./styles/font.css">
	<link rel="stylesheet" href="./styles/scrollbar.css">
	<link rel="stylesheet" href="./styles/main.css">
</head>
<body>
<!--[if lt IE 9]>
<div style="position:fixed;top:0%;width:100%;font-size:50px;line-height:50px;height:100%; background:#111;padding-top:25%;z-index:999999999;text-align:center;color:#fff">提示：本站只支持现代浏览器！</div>
<![endif]-->
<!--顶部-->
<div id="loading" class="px">
	<!-- <i class="pa fa fa-paper-plane"></i> -->
	<div id="loading-bar" class="pa"><span></span></div>
	<div id="loading-percent" class="pa"></div>
</div>
<div class="px md-modal" id="about">
	<div class="pr md-content">
		<h3><i class="fa fa-times-circle pull-right cp"></i><i class="fa fa-info-circle cp"></i>&nbsp;关于灯云库</h3>
		<div class="md-body">
		<p class="lead"><strong>灯云库，致力于做方便好用的在线视频库。</strong></p>

		<p>上线日期：2016年02月28日  <br>当前版本：V1.0 </p>
		<p><strong>免责声明：</strong><br>本站资源均来自正规视频站点提供的引用资源如果您对本站所载视频作品版权的归属存有异议，请立即通知我，我们将在第一时间予以删除，同时向你表示歉意！不论何种情形，本站都不对任何由于使用或无法使用本站提供的视频资料所造成的直接的和间接的损失负任何责任。</p>
		
		<p class="text-right" style="color:#fff">
		<script type="text/javascript">var cnzz_protocol = (("https:" == document.location.protocol) ? " https://" : " http://");document.write(unescape("%3Cspan id='cnzz_stat_icon_1254874986'%3E%3C/span%3E%3Cscript src='" + cnzz_protocol + "s13.cnzz.com/stat.php%3Fid%3D1254874986' type='text/javascript'%3E%3C/script%3E"));</script></p>
		</div>
	</div>
</div>
 
<div class="px md-modal" id="play">
	<div class="pr md-content">
		<h3><i class="fa fa-times-circle pull-right cp"></i><i class="fa fa-youtube-play"></i> <span></span>&nbsp;-&nbsp;<small></small></h3>
		<div id="player"></div>
		<div id="player-share" class="pa">
			<i class="fa fa-share-alt"></i> 分享到：<i class="fa fa-qq cp"></i><i class="fa fa-weibo cp"></i>
		</div>
		<div id="play-list" class="pa"></div>
	</div>
</div>

<div class="px md-mask"></div>


<div id="header" class="px">
	<div id="header-inner" class="pr">
	<input class="pa" id="video-search" lang="zh-CN" type="search" x-webkit-grammar="builtin:search" placeholder="女医明" title="输入完按回车">
	<i class="fa fa-search pa"></i>
	<img src="./images/logo.png" id="logo" class="pa cp pull-left"> 
		<div id="type" class="pa pull-left">
		<span data-id="0" id="video-tab-0" class="active">全部</span>
		<span data-id="1" id="video-tab-1">古装</span>
		<span data-id="2" id="video-tab-2">武侠</span>
		<span data-id="3" id="video-tab-3">警匪</span>
		<span data-id="4" id="video-tab-4">军事</span>
		<span data-id="5" id="video-tab-5">神话</span>
		<span data-id="6" id="video-tab-6">科幻</span>
		<span data-id="7" id="video-tab-7">悬疑</span>
		<span data-id="8" id="video-tab-8">历史</span>
		<span data-id="9" id="video-tab-9">儿童</span>
		<span data-id="10" id="video-tab-10">农村</span>
		<span data-id="11" id="video-tab-11">都市</span>
		<span data-id="12" id="video-tab-12">家庭</span>
		<span data-id="13" id="video-tab-13">搞笑</span>
		<span data-id="14" id="video-tab-14">偶像</span>
		</div>
	</div>
</div>

<div id="video"></div>
<script>var xtit='灯云库';</script>
<script src="./scripts/jquery.js"></script>
<script src="./scripts/jquery.cookie.js"></script>
<script src="./scripts/jquery.mousewheel.js"></script>
<script src="./scripts/jquery.scrollbar.js"></script>
<script src="./scripts/main.js"></script>
</body>
</html>