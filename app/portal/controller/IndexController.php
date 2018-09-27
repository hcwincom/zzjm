<?php
 
namespace app\portal\controller;

use cmf\controller\HomeBaseController;
 
use taobao\Taobao;
class IndexController extends HomeBaseController
{

    
    public function index()
    {
         echo '<h1>index</h1>';
         exit;
         //测试淘宝接口
         //299509f2c17fd0287d3fd6148a6243d62eadd0d33e31f488ad7b10e42e343546
         //890f824c7a3fc8f3c8ae9f6618e2b6ddbb4fcb3432b7bf8f7364fd0419706775
         $ak='299509f2c17fd0287d3fd6148a6243d62eadd0d33e31f488ad7b10e42e343546';
         $as='890f824c7a3fc8f3c8ae9f6618e2b6ddbb4fcb3432b7bf8f7364fd0419706775';
         $taobao=new Taobao($ak,$as);
         dump($taobao->host);
         $fields = "tid,type,status,payment,orders,rx_audit_status,post_fee,status,modified,pay_time";
         
         $start_created = "2018-08-01%2000:00:00";
         
         $end_created = "2018-08-31%2023:59:59";
         
         $status = "";
         
         $taobao->get('/JSB/rest/trade/TradesSoldGetRequest?fields='.$fields.'&start_created='.$start_created.'&end_created='.$end_created.'&status='.$status);
         if($taobao->status!=200){
             exit('通信错误'.$taobao->status);
         }
         
         $order=json_decode($taobao->getContent(),true);
         dump($order);
         exit;
         $order = $taobao->status.$taobao->getContent();
         
         $State=intval($order);
         
         $order=str_replace($State,'',$order);
         
         //echo '状态：'.$state."<br>";
         
         //echo '订单：'.$order."<br><br>";
         
         $Json=json_decode($order,TRUE);
         
         $Num = count($Json['trades_sold_get_response']['trades']['trade']);
   }

}
