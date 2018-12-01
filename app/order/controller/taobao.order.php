<?php
error_reporting(0);
set_time_limit(0);
header("Content-type: text/html; charset=utf-8");
require_once ("taobao.class.php");

$client = new HttpClient();
$fields = "tid,type,status,payment,orders,rx_audit_status,post_fee,status,modified,pay_time";
$start_created = "2018-08-01%2000:00:00";
$end_created = "2018-08-31%2023:59:59";
$status = "";
$client->get('/JSB/rest/trade/TradesSoldGetRequest?fields='.$fields.'&start_created='.$start_created.'&end_created='.$end_created.'&status='.$status);
$order = $client->status.$client->getContent();
$State=intval($order);
$order=str_replace($State,'',$order);
//echo '状态：'.$state."<br>";
//echo '订单：'.$order."<br><br>";
$Json=json_decode($order,TRUE);
$Num = count($Json['trades_sold_get_response']['trades']['trade']);
//echo '订单数量：'.$Num."<br>";
for($I=0;$I<$Num;$I++){
	//echo '订单编号：'.$Json['trades_sold_get_response']['trades']['trade'][$I]['tid']."<br>";
	//echo '实付金额：'.$Json['trades_sold_get_response']['trades']['trade'][$I]['payment']."<br>";
	//echo '邮费：'.$Json['trades_sold_get_response']['trades']['trade'][$I]['post_fee']."<br>";
	$num = count($Json['trades_sold_get_response']['trades']['trade'][$I]['orders']['order']);
	for($i=0;$i<$num;$i++){
	//echo '产品编码：'.$Json['trades_sold_get_response']['trades']['trade'][$I]['orders']['order'][$i]['outer_sku_id']."<br>";
	//echo '产品名称：'.$Json['trades_sold_get_response']['trades']['trade'][$I]['orders']['order'][$i]['title']."<br>";
	//echo '产品单价：'.$Json['trades_sold_get_response']['trades']['trade'][$I]['orders']['order'][$i]['price']."<br>";
	//echo '产品数量：'.$Json['trades_sold_get_response']['trades']['trade'][$I]['orders']['order'][$i]['num']."<br>";
	//echo '优惠价格：'.$Json['trades_sold_get_response']['trades']['trade'][$I]['orders']['order'][$i]['discount_fee']."<br>";
	//echo '产品总价：'.$Json['trades_sold_get_response']['trades']['trade'][$I]['orders']['order'][$i]['total_fee']."<br>";
    $rows[codegoods] = $Json['trades_sold_get_response']['trades']['trade'][$I]['orders']['order'][$i]['outer_sku_id'];//产品编码
	$rows[outer_id] = $Json['trades_sold_get_response']['trades']['trade'][$I]['orders']['order'][$i]['outer_iid'];//产品编码
	$rows[title] = urlencode($Json['trades_sold_get_response']['trades']['trade'][$I]['orders']['order'][$i]['title']);//产品名称
	$rows[sell_price] = $Json['trades_sold_get_response']['trades']['trade'][$I]['orders']['order'][$i]['price'];//产品单价
	$rows[goods_nums] = $Json['trades_sold_get_response']['trades']['trade'][$I]['orders']['order'][$i]['num'];//产品数量
	$rows[prefer_price] = $Json['trades_sold_get_response']['trades']['trade'][$I]['orders']['order'][$i]['discount_fee'];//优惠价格
	$rows[total_fee] = $Json['trades_sold_get_response']['trades']['trade'][$I]['orders']['order'][$i]['total_fee'];//产品总价
	$orders[] = $rows;
	}
	$client = new HttpClient();	
	$fields = "tid,type,status,payment,orders,receiver_name,receiver_state,receiver_city,receiver_district,receiver_address,receiver_mobile,receiver_phone";
	$client->get('/JSB/rest/trade/TradeFullinfoGetRequest?fields='.$fields.'&tid='.$Json['trades_sold_get_response']['trades']['trade'][$I]['tid']);
	$address = $client->status.$client->getContent();
	$state=intval($address);
	$address=str_replace($state,'',$address);
	$json=json_decode($address,TRUE);
	//echo '收货人：'.$json['trade_fullinfo_get_response']['trade']['receiver_name']."<br>";
	//echo '手机：'.$json['trade_fullinfo_get_response']['trade']['receiver_mobile']."<br>";
	//echo '电话：'.$json['trade_fullinfo_get_response']['trade']['receiver_phone']."<br>";
	//echo '省：'.$json['trade_fullinfo_get_response']['trade']['receiver_state']."<br>";
	//echo '市：'.$json['trade_fullinfo_get_response']['trade']['receiver_city']."<br>";
	//echo '区：'.$json['trade_fullinfo_get_response']['trade']['receiver_district']."<br>";
	//echo '地址：'.$json['trade_fullinfo_get_response']['trade']['receiver_address']."<br>";
	$row[ordercompany]='1';//订单来源
	$row[order_no]=$Json['trades_sold_get_response']['trades']['trade'][$I]['tid'];//订单编号
	$row[suppaytype_id]='1';//付款类型
	$row[addressinfo]='';//地址信息
	$row[acceptname]=urlencode($json['trade_fullinfo_get_response']['trade']['receiver_name']);//收货人
	$row[accepttel]=$json['trade_fullinfo_get_response']['trade']['receiver_mobile'];//收货人手机
	$row[acceptmobile]=$json['trade_fullinfo_get_response']['trade']['receiver_phone'];//收货人电话
	$row[province]=urlencode($json['trade_fullinfo_get_response']['trade']['receiver_state']);//收货地址：
	$row[city]=urlencode($json['trade_fullinfo_get_response']['trade']['receiver_city']);//收货地址
	$row[area]=urlencode($json['trade_fullinfo_get_response']['trade']['receiver_district']);//收货地址
	$row[address]=urlencode($json['trade_fullinfo_get_response']['trade']['receiver_address']);//街道
	$row[is_msg]='0';//短信通知：是否
	$row[sfkp]='1'.$I;//是否开票：普通发票增值发票否
	$row[order_amount]=$Json['trades_sold_get_response']['trades']['trade'][$I]['payment'];//实付金额
	$row[real_freight]=$Json['trades_sold_get_response']['trades']['trade'][$I]['post_fee'];//邮费
	$row[status]=$Json['trades_sold_get_response']['trades']['trade'][$I]['status'];//订单状态
	$row[created]=$Json['trades_sold_get_response']['trades']['trade'][$I]['modified'];//订单时间
	$row[orders] = $orders;$orders='';
	$trade[] = $row;
}
$params[zt]=$state;
$params[sl]=$Num;
$params[trade]= $trade;
echo urldecode(json_encode($params));
?>