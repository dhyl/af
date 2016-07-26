<?php
if ($_REQUEST['act'] == 'download')
{
	admin_priv('area_supplier_cars_view');
	//$filename = !empty($_REQUEST['filename']) ? trim($_REQUEST['filename']) : '';

	$filename='车辆信息';
	header("Content-type: application/vnd.ms-excel; charset=utf-8");
	header("Content-Disposition: attachment; filename=$filename.xls");

	/*
	租赁公司	车牌号码	车辆品牌	车辆型号	
	名称	颜色	车架号	发动机号	保险到期	年检到期	
	备案证证号	发证日期	行驶证初领日期	交强险单号	
	交强险保单日期	交强险投保公司	商业险单号	商业险保单日期	
	商业险投保公司
	suppliers_id ,card,car_brand,car_model,car_name,color,".
				"frame_number,engine_number,insurance_maturity,annual_inspection,".
				"filing_number,filing_start_time,driving_licence_date,insurance_number,".
				"policy_date,insurance_company,insurance_number2,policy_date2,insurance_company2
		*/		
	/* 订单概况 */
	$result = cars_list_down();
	$data = '序号' . "\t";
	$data .= "公司名称\t";
	$data .= "车牌号码\t";
	$data .= "车辆品牌\t";
	$data .= "车辆型号\t";
	$data .= "颜色\t";
	$data .= "车辆识别代号\t";
	$data .= "发动机号\t";
	$data .= "保险到期\t";
	$data .= "年检到期\t";
	$data .= "备案证证号\t";
	$data .= "发证日期\t";
	$data .= "行驶证初领日期\t";
	$data .= "交强险单号\t";
	$data .= "交强险保单日期\t";
	$data .= "交强险投保公司\t";
	$data .= "商业险单号\t";
	$data .= "商业险保单日期\t";
	$data .= "商业险投保公司\t\n";
	
	$i=1;
	if(!empty($result)){
		foreach($result as $k=>$v){
			$data .= $i++ . "\t";
			$data .= $v['suppliers_name']."\t";
			$data .= $v['card']."\t";
			
			$data .= $v['car_brand']."\t";
			$data .= $v['car_model']."\t";
			$data .= $v['color']."\t";
			$data .= $v['frame_number']."\t";
			$data .= $v['engine_number']."\t";
			
			$data .= $v['insurance_maturity']."\t";
			$data .= $v['annual_inspection']."\t";
			$data .= $v['filing_number']."\t";
			$data .= $v['filing_start_time']."\t";
			$data .= $v['driving_licence_date']."\t";
			$data .= $v['insurance_number']."\t";
			$data .= $v['policy_date']."\t";
			$data .= $v['insurance_company']."\t";
			
			$data .= $v['insurance_number2']."\t";
			$data .= $v['policy_date2']."\t";
			$data .= $v['insurance_company2']."\t\n";	
		}
	}
	
	echo hhs_iconv(EC_CHARSET, 'GB2312', $data) . "\t";
	exit;

}



function cars_list_down()
{
	$result = get_filter();
		$ex_where = " WHERE 1 and  c.a_type=1 ";
		if ($_SESSION['area_suppliers_ids'])
		{
			$ex_where .= " AND c.`suppliers_id` in (".$_SESSION['area_suppliers_ids'].")";
		}

		$sql = "SELECT c.*,s.suppliers_name FROM " . $GLOBALS['hhs']->table('supplier_cars') ." as c left join "
				. $GLOBALS['hhs']->table('suppliers') ." as s on c.suppliers_id=s.suppliers_id "
						. $ex_where .
						" ORDER by c.suppliers_id desc , c.create_at desc" ;
	$cars_list = $GLOBALS['db']->getAll($sql);
	foreach($cars_list as $k=>$v){
		$cars_list[$k]['insurance_maturity']=local_date('Y-m-d', $v['insurance_maturity']);
		$cars_list[$k]['annual_inspection']=local_date('Y-m-d', $v['annual_inspection']);
		$cars_list[$k]['filing_start_time']=local_date('Y-m-d', $v['filing_start_time']);
		$cars_list[$k]['driving_licence_date']=local_date('Y-m-d', $v['driving_licence_date']);
		$cars_list[$k]['policy_date']=local_date('Y-m-d', $v['policy_date']);
		$cars_list[$k]['policy_date2']=local_date('Y-m-d', $v['policy_date2']);

	}
	return $cars_list;
}

?>