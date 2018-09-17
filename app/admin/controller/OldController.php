<?php
 
namespace app\admin\controller;

 
use cmf\controller\AdminBaseController; 
use think\Db; 
 /* 旧数据处理 */
class OldController extends AdminBaseController
{
    private $dbconfig;
    private $db_old;
    private $corrects;
    private $where_corrects;
    public function _initialize()
    {
        //链接数据库
        $db=config('database'); 
        $this->dbconfig=[
            'host'=>$db['hostname'],
            'user'=>$db['username'],
            'psw'=>$db['password'],
            'dbname'=>'genele',
            'port'=>$db['hostport'],
        ];
        parent::_initialize();
        $aid=session('ADMIN_ID');
        if($aid!=1){
            $this->error('开发者功能，不要操作');
        }
        $this->corrects=['status'=>2,'aid'=>1,'rid'=>1,'atime'=>time(),'rtime'=>time(),'time'=>time()];
        $this->where_corrects=['rid'=>0];
        $this->db_old= config('db_old');
    }
     
    /**
     * 旧数据同步
     * @adminMenu(
     *     'name'   => '旧数据同步',
     *     'parent' => '',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 0,
     *     'icon'   => '',
     *     'remark' => '旧数据同步',
     *     'param'  => ''
     * )
     */
    public function index()
    { 
        $list=[
            '产品分类同步'=>url('cate'),
            '产品分类数据更正'=>url('cate_correct'),
            '产品数据(基本数据和技术详情，图片，文档)'=>url('goods'),
            '所属公司+付款银行+付款类型'=>url('sys'),
            '(客户/供货商)联系人+对应付款账号+物流公司对应联系人和账号'=>url('tel'),
            '客户(同步客户分类和客户主体，关联联系人和付款账号)'=>url('custom'), 
            '供货商'=>url('supplier'),
           
            
        ];
        $this->assign('list',$list);
        return $this->fetch();
    }
    /**
     * 产品分类同步
     * @adminMenu(
     *     'name'   => '产品分类同步',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 0,
     *     'icon'   => '',
     *     'remark' => '产品分类同步',
     *     'param'  => ''
     * )
     */
    public function cate()
    {
        $m= mysqli_init();
        $m->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2);//设置超时时间
        $dbconfig=$this->dbconfig;
        $m->real_connect($dbconfig['host'],$dbconfig['user'],$dbconfig['psw'],$dbconfig['dbname'],$dbconfig['port']);
        $sql='select id,cate_name as name,code_num,t_num as code,pid as fid,sortnum as sort '.
            ' from sp_category2 ';
       
        $res=$m->query($sql);
        if(empty($res)){
            $this->error('数据查询错误');
        }
        //一次读出所有
        $data=$res->fetch_all(MYSQLI_ASSOC);
       /*  while($tmp=($res->fetch_assoc())){ 
            $data[]=$tmp; 
        } */ 
      
        $m_new=Db::name('cate');
        //开启事务
        $m_new->startTrans();
        //先截取旧数据
        $m_new->execute('truncate table cmf_cate');
        $row_mew=$m_new->insertAll($data); 
        $m_new->where($this->where_corrects)->update($this->corrects);
        $m_new->commit();
        $this->success('已同步数据数'.$row_mew);
    }
    /**
     * 产品分类数据更正
     * @adminMenu(
     *     'name'   => '产品分类数据更正',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 0,
     *     'icon'   => '',
     *     'remark' => '产品分类数据更正',
     *     'param'  => ''
     * )
     */
    public function cate_correct()
    {
        $row=0;
        $m=Db::name('cate');
        //先审核
        $where=['status'=>1];
        $time=time();
        $data=[
            'status'=>2,
            'rid'=>1,
            'rtime'=>$time,
            'time'=>$time,
        ];
        $m->where($where)->update($data);
        //得到最大一级分类
//         $where=['fid'=>0];
//         $info=$m->where($where)->order('code_num desc')->find();
//         cmf_set_dynamic_config(['cate_max'=>($info['code_num']+1)]);
        //得到二级分类最大
        $where=[];
        $list=$m->where($where)->group('fid')->column('fid,max(code_num)');
        cmf_set_dynamic_config(['cate_max'=>$list[0]]);
        unset($list[0]);
        //update和字段更新的效率？
        foreach($list as $k=>$v){
            $m->where('id',$k)->update(['max_num'=>$v]);
        }
        //更新二级分类的max_num
        $list=Db::name('goods')->group('cid')->column('cid,max(code_num)');
        if(isset($list[0])){
            unset($list[0]);
        }
        foreach($list as $k=>$v){
            $m->where('id',$k)->update(['max_num'=>$v]);
        }
         //根据分类编码修正
        $this->success('已更正数据数');
    }
    /* 产品基本数据 */
    public function goods()
    {
         
        $m= mysqli_init();
        $m->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2);//设置超时时间
        $dbconfig=$this->dbconfig;
        $m->real_connect($dbconfig['host'],$dbconfig['user'],$dbconfig['psw'],$dbconfig['dbname'],$dbconfig['port']);
        $sql='select * from sp_codegoods';
        //$fields='id,name,name2,goods_no,tiao as sn,codename as code_name,codenum as code_num';
        $res=$m->query($sql);
        if(empty($res)){
            $this->error('数据查询错误');
        }
       
        $data_goods=[];
        $data_file=[];
        $data_info=[];
        $data_tech=[];
        while($tmp=($res->fetch_assoc())){
            $data_goods[]=[
                'id'=>$tmp['id'],
                'name'=>$tmp['name'],
                'name2'=>$tmp['name2'],
                'code'=>$tmp['goods_no'],
                'sn'=>$tmp['tiao'],
                'code_name'=>$tmp['codename'],
                'code_num'=>$tmp['codenum'], 
                'price_sale'=>$tmp['sell_price'], 
                'pic'=>$tmp['img'],
                'weight0'=>$tmp['weight'],
                'weight1'=>$tmp['weight'],
                'cid'=>$tmp['code_id2'],
                'cid0'=>$tmp['code_id'],
                
            ];
           
            if(!empty($tmp['content'])){
                $data_info[]=[
                    'pid'=>$tmp['id'],
                    'content'=>$tmp['content']
                ];
            }
            if(!empty($tmp['content2'])){
                $data_tech[]=[
                    'pid'=>$tmp['id'],
                    'content'=>$tmp['content2']
                ];
            }
            if(!empty($tmp['wd'])){
                $data_file[]=[
                    'pid'=>$tmp['id'],
                    'file'=>$tmp['wd'],
                    'name'=>'技术文档'.$tmp['id'],
                    'type'=>7,
                ];
            }
         
         }  
        
        $m_goods=Db::name('goods');
        //开启事务
        $m_goods->startTrans();
        //先截取旧数据
        $m_goods->execute('truncate table cmf_goods');
        $row_mew=$m_goods->insertAll($data_goods);
        $m_goods->where($this->where_corrects)->update($this->corrects);
        //详情
        $m_info=Db::name('goods_info'); 
        //先截取旧数据
        $m_info->execute('truncate table cmf_goods_info');
        $m_info->insertAll($data_info);
        
        //技术资料
        $m_tech=Db::name('goods_tech');
        //先截取旧数据
        $m_tech->execute('truncate table cmf_goods_tech');
        $m_tech->insertAll($data_tech);
        
        //说明书
        $m_file=Db::name('goods_file');
        //先截取旧数据
        $m_file->execute('truncate table cmf_goods_file');
        $m_file->insertAll($data_file);
        
        //产品图片
        $sql='select id,goods_id as pid,img as file from sp_goods_photo where goods_id>0';
       
        $res=$m->query($sql);
        if(empty($res)){
            $this->error('数据查询错误');
        }
        $data_file=[];
        
        while($tmp=($res->fetch_assoc())){
            
            $data_file[]=[ 
                'pid'=>$tmp['pid'],
                'file'=>$tmp['file'],
                'name'=>'极敏商城图片'.$tmp['id'],
                'type'=>1,
            ];
            
        }
        $m_file->insertAll($data_file);
        
        $m_goods->commit();
        $this->success('已同步数据数'.$row_mew);
    }
    
    // '所属公司+付款银行+付款类型
    public function sys()
    {
        //  '所属公司+付款银行+付款类型 
        $m_old=Db::connect($this->db_old);
        $sql='select id,company as name,code,allname,account_name,account_bank,account_num,feenum,contact,address'.
            ' from sp_company ';
        $data=$m_old->query($sql); 
        $m_new=Db::name('company');
        //开启事务
        $m_new->startTrans();
        //先截取旧数据
        $m_new->execute('truncate table cmf_company');
        $row_mew=$m_new->insertAll($data); 
        $m_new->where($this->where_corrects)->update($this->corrects);
        
        //转账银行
        $sql='select id,bank_name as name'.
            ' from sp_new_banks ';
        $data=$m_old->query($sql); 
        $m_new=Db::name('bank'); 
        //先截取旧数据
        $m_new->execute('truncate table cmf_bank');
        $row_mew=$m_new->insertAll($data); 
        $m_new->where($this->where_corrects)->update($this->corrects);
        
        //付款类型
        $sql='select id,suppaytype_name as name,sort,note as dsc,addtime as atime '.
            ' from sp_suppaytype ';
        $data=$m_old->query($sql);  
        $m_new=Db::name('paytype'); 
        //先截取旧数据
        $m_new->execute('truncate table cmf_paytype');
        $row_mew=$m_new->insertAll($data);
        $m_new->where($this->where_corrects)->update($this->corrects);
        
        $m_new->commit();
        $this->success('已同步数据数'.$row_mew);
    }
    //联系人
    public function tel(){
        set_time_limit(300);
        $m_old=Db::connect($this->db_old);
        $m_tel=Db::name('tel');
        $m_account=Db::name('account');
        $m_new=Db::name('freight');
        
        //开启事务
        $m_tel->startTrans();
        
        //联系人
        $sql='select id,user_id as uid,name,position,other,'.
            'sex,mobile,mobile1,mobile2,phone,phone1,province,city,area,street,postcode,fax,qq,'.
            'wechat,wechatphone,wechatname,email,taobaoid,aliid,ctype '.
            ' from sp_new_contacts '; 
        $data=$m_old->query($sql); 
       
        //先截取旧数据
        $m_tel->execute('truncate table cmf_tel');
        $m_tel->insertAll($data);
        
        
        //对应付款账号
        $sql='select id,user_id as uid,bank_id as bank1,account_name as name1,account_num as num1,account_location as location1,'.
                'income_id as bank2,income_name as name2,income_num as num2,income_location as location2 from sp_new_accounts ';
        $data=$m_old->query($sql); 
        
        //先截取旧数据
        $m_account->execute('truncate table cmf_account');
        $m_account->insertAll($data);
        
        //物流公司
        $sql='select * from sp_freight ';
        $data=$m_old->query($sql);
       
        $data_freight=[];  
        foreach($data as $v){
            $tmp=[
                'name'=>$v['name'],
                'province'=>intval($v['province_id']),
                'city'=>intval($v['city_id']),
                'area'=>intval($v['area_id']),
                'code'=>$v['code'],
                'paytype'=>intval($v['suppaytype_id']),
                'dg'=>0,
                'ds'=>0,
                'zfb'=>0,
                'tel1'=>0,
                'tel2'=>0,
            ];
            //对公支付账号信息
            if(!empty($v['dg'])){
                $dgs=explode(',', $v['dg']);
                if(!empty($dgs[2])){
                    $data_account=[
                        'num1'=>$dgs[0],
                        'name1'=>$dgs[1],
                        'location1'=>$dgs[2],
                        'type'=>3,
                        'site'=>1,
                        'uid'=>$v['id'],
                    ];
                    $tmp['dg']=$m_account->insertGetId($data_account);
                }
                
            }
            //对私支付账号信息
            if(!empty($v['ds'])){
                $dss=explode(',', $v['ds']);
                if(!empty($dgs[2])){
                    $data_account=[
                        'num1'=>$dss[0],
                        'name1'=>$dss[1],
                        'location1'=>$dss[2],
                        'type'=>3,
                        'site'=>1,
                        'uid'=>$v['id'],
                    ];
                    $tmp['ds']=$m_account->insertGetId($data_account);
                }
                
            }
            
            //支付宝支付账号信息
            if(!empty($v['zfb'])){
                $data_account=[
                    'num1'=>$v['zfb'],
                    'name1'=>$v['zfbzhm'],
                    'location1'=>'支付宝',
                    'type'=>3,
                    'site'=>3,
                    'uid'=>$v['id'],
                ];
                $tmp['zfb']=$m_account->insertGetId($data_account);
            }
            //联系人1信息
            if(!empty($v['fzr1info'])){
                $tel=explode(',', $v['fzr1info']);
                if(!empty($tel['0'])){
                    $data_tel=[
                        'name'=>$tel[0],
                        'sex'=>$tel[1],
                        'position'=>$tel[2],
                        'mobile'=>$tel[4],
                        'mobile1'=>$tel[5],
                        'type'=>3,
                        'site'=>1,
                        'uid'=>$v['id'],
                    ];
                    $tmp['tel1']=$m_tel->insertGetId($data_tel);
                }
            }
            //联系人2信息
            if(!empty($v['fzr2info'])){
                $tel=explode(',', $v['fzr2info']);
                if(!empty($tel['0'])){
                    $data_tel=[
                        'name'=>$tel[0],
                        'sex'=>$tel[1],
                        'position'=>$tel[2],
                        'mobile'=>$tel[4],
                        'mobile1'=>$tel[5],
                        'type'=>3,
                        'site'=>1,
                        'uid'=>$v['id'],
                    ];
                    $tmp['tel2']=$m_tel->insertGetId($data_tel);
                }
            }
            $data_freight[]=$tmp;
        }
        
        //先截取旧数据
        $m_new->execute('truncate table cmf_freight');
        $row_mew=$m_new->insertAll($data_freight);
        $m_new->where($this->where_corrects)->update($this->corrects);
        
        $m_tel->commit();
        $this->success('已同步数据数'.$row_mew);
        exit;
    }
    //客户 
    public function custom(){
        set_time_limit(300);
        $m_old=Db::connect($this->db_old);
       
        //客户分类
        $sql='select id,customcate_name as name,sort,addtime as atime,note as dsc'.
            ' from sp_customcate ';
        $data=$m_old->query($sql); 
        
        $m_new=Db::name('custom_cate');
        //开启事务
        $m_new->startTrans();
        //先截取旧数据
        $m_new->execute('truncate table cmf_custom_cate');
        $row_mew=$m_new->insertAll($data);
        $m_new->where($this->where_corrects)->update($this->corrects);
        
        //获取主体数据
        $sql='select * from sp_user ';
        $data=$m_old->query($sql);
        $m_tel=Db::name('tel');
        $m_account=Db::name('account');
        $data_user=[];
        
        foreach($data as $k=>$v){
            if(empty($v['name'])){
                continue;
            }
            //组装数据
            $tmp=[
                'id'=>$v['id'],
                'name'=>$v['name'],
                'company'=>intval($v['khly']),
                'cid'=>intval($v['customcate_id']),
                'city_code'=>$v['khqh'],
                'code_num'=>intval($v['khbh']), 
                'paytype'=>intval($v['suppaytype_id']),
                'email'=>$v['email'],
                'mobile'=>$v['mobile'], 
                'level'=>intval($v['level']),
                'url'=>$v['url'],
                'shopurl'=>$v['shopurl'],
                'wechat'=>$v['wechat'],
                'qq'=>$v['qq'],
                'fax'=>$v['fax'],
                'province'=>intval($v['province']),
                'city'=>intval($v['city']),
                'area'=>intval($v['area']),
                'street'=>$v['street'],
                'other'=>$v['other'],
                'announcement'=>$v['announcement'],
                'invoice_type'=>intval($v['invoice_type']),
                'tax_point'=>$v['tax_point'],
                'freight'=>intval($v['freight']),
                'status'=>intval($v['state']),
                
            ];
           
            //客户编号
            $tmp['code']='KF-'.
                str_pad($tmp['city_code'], 4,'0',STR_PAD_LEFT).'-'.
                str_pad($tmp['code_num'], 3,'0',STR_PAD_LEFT);
            
            $i=1;
            $receiver=1;
            //联系人信息更新
            if(!empty($v['contact_person'])){
                $tel=[
                    'id'=>$v['contact_person'],
                    'site'=>$i++,
                    'uid'=>$v['id'],
                    'type'=>1,
                ];
                $m_tel->update($tel);
            }
            if(!empty($v['contact_person1'])){
                $tel=[
                    'id'=>$v['contact_person1'],
                    'site'=>$i++,
                    'uid'=>$v['id'],
                    'type'=>1,
                ]; 
                $m_tel->update($tel);
            }
            if(!empty($v['contact_person2'])){
                $tel=[
                    'id'=>$v['contact_person2'],
                    'site'=>$i++,
                    'uid'=>$v['id'],
                    'type'=>1,
                ];
                $m_tel->update($tel);
            }
            if(!empty($v['receiver'])){
                $receiver=$i++;
                $tel=[
                    'id'=>$v['receiver'],
                    'site'=>$receiver,
                    'uid'=>$v['id'],
                    'type'=>1,
                ];
                $m_tel->update($tel);
            }
            if(!empty($v['receiver1'])){
                $tel=[
                    'id'=>$v['receiver1'],
                    'site'=>$i++,
                    'uid'=>$v['id'],
                    'type'=>1,
                ];
                $m_tel->update($tel);
            }
            if(!empty($v['receiver2'])){
                $tel=[
                    'id'=>$v['receiver2'],
                    'site'=>$i++,
                    'uid'=>$v['id'],
                    'type'=>1,
                ];
                $m_tel->update($tel);
            }
            //默认收货人
            if(empty($v['default_receiver'])){
                $tmp['receiver']=$receiver; 
            }else{
                $tmp['receiver']=$receiver+$v['default_receiver'];
            }
            
            //付款账号更新
            if(!empty($v['account1'])){
                $account=[
                    'id'=>$v['account1'],
                    'site'=>1,
                    'uid'=>$v['id'],
                    'type'=>1,
                ];
                $m_account->update($account);
            }
            if(!empty($v['account2'])){
                $account=[
                    'id'=>$v['account2'],
                    'site'=>2,
                    'uid'=>$v['id'],
                    'type'=>1,
                ];
                $m_account->update($account);
            }
            if(!empty($v['account3'])){
                $account=[
                    'id'=>$v['account3'],
                    'site'=>3,
                    'uid'=>$v['id'],
                    'type'=>1,
                ];
                $m_account->update($account);
            }
            
            $data_user[]=$tmp;
            
        }
       //客户主体数据
        //先截取旧数据
        $m_new=Db::name('custom');
        $m_new->execute('truncate table cmf_custom');  
        $row_mew=$m_new->insertAll($data_user); 
        $m_new->where($this->where_corrects)->update($this->corrects);
        
        $m_new->commit();
        $this->success('已同步数据数'.$row_mew);
        exit;
    }
    
    
}
