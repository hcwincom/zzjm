<?php
error_reporting(0);
set_time_limit(0);
header("Content-type: text/html; charset=utf-8");
require_once ("taobao.class.php");

if($_GET[kd]=="1"){$company_code="ZTO";}//中通快递-合肥青网
if($_GET[kd]=="2"){$company_code="ZTO";}//中通速递-上海爱博
if($_GET[kd]=="3"){$company_code="SF";}//顺丰速运-合肥青网
if($_GET[kd]=="4"){$company_code="SF";}//顺丰速运-上海爱博
if($_GET[kd]=="5"){$company_code="DBKD";}//德邦快递-合肥青网
if($_GET[kd]=="6"){$company_code="DBKD";}//德邦物流-合肥青网
if($_GET[kd]=="7"){$company_code="ZJS";}//宅急送-合肥青网
if($_GET[kd]=="8"){$company_code="OTHER";}//调货已发-单号没拿到
if($_GET[kd]=="9"){$company_code="OTHER";}//上海-上门取货
if($_GET[kd]=="10"){$company_code="OTHER";}//青网-上门取货
if($_GET[kd]=="11"){$company_code="SF";}//调货-全国顺丰

$client = new HttpClient();
$tid=$_GET[tid];
$out_sid=$_GET[out_sid];///*单号*/
$company_code=$company_code;/*快递*/
$client->put('/JSB/rest/logistics/LogisticsOfflineSendRequest?tid='.$tid.'&out_sid='.$out_sid.'&company_code='.$company_code);  
$fahuo=$client->status.$client->getContent();
$fahuo=json_decode($fahuo,TRUE);
?>