<?php
header("content-type:text/html;charset=utf-8");
		$wd = $_POST['keyword'];
        $keywords = $wd;    
		$db_host   = "172.16.100.51:3306";
		// database name
		$db_name   = "gov_zucheqq";
		// database username
		$db_user   = "root";
		// database password
		$db_pass   = "123.com";
		//链接数据库
		$db_count=mysql_connect($db_host,$db_user,$db_pass);
		//指定字符集
		mysql_query("set names 'utf8'");
		//选择数据库
		mysql_select_db($db_name,$db_count);

		$sql="select car_brand from hhs_car_type where car_brand like "."'%$keywords%'";
		//echo $sql;
		//执行SQL语句
		$res=mysql_query($sql);
		while($rows = mysql_fetch_row($res)){
			for($i = 0; $i < count($rows); $i++){
				$suggestions[]=array('title'=>$rows[$i]);
			}
		}
        echo json_encode(array('data' => $suggestions));     
?>
