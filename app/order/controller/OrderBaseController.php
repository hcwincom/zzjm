<?php
 
namespace app\order\controller;

 
use app\common\controller\AdminInfo0Controller; 
use think\Db; 
  
class OrderBaseController extends AdminInfo0Controller
{
    
    public function _initialize()
    {
        parent::_initialize();
        
        //没有店铺区分
        $this->isshop=1;
        $this->edit=['name','company','cid','city_code','code_num','postcode','paytype',
            'email','mobile','level','url','shopurl','wechat','qq','fax',
            'province','city','area','street','other','announcement','invoice_type',
            'tax_point','freight','payer','dsc','sort',
        ];
        $this->search=[
            'p.name'=>'客户名称', 
            'p.code'=>'客户编码',
            'p.email'=>'客户邮箱',
            'p.mobile'=>'客户电话', 
            'tels.name'=>'联系人姓名',
            'tels.mobile|tels.mobile1|tels.mobile2|tels.phone|tels.phone1'=>'联系人手机',
            'tels.qq|p.qq'=>'联系人qq',
            'tels.wechat|p.wechat'=>'微信',
            'tels.taobaoid|tels.aliid'=>'淘宝阿里id'
        ];
      
        
    }
    
}
