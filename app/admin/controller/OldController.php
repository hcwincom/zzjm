<?php
 
namespace app\admin\controller;

 
use cmf\controller\AdminBaseController; 
use think\Db; 
use app\order\model\OrderModel;
 /* 旧数据处理 */
class OldController extends AdminBaseController
{
    private $dbconfig;
    private $db_old;
    private $corrects;
    private $where_corrects;
    public function _initialize()
    {
        parent::_initialize();
        //链接数据库
        $db=config('database'); 
        $this->dbconfig=[
            'host'=>$db['hostname'],
            'user'=>$db['username'],
            'psw'=>$db['password'],
            'dbname'=>$db['database'],
            'port'=>$db['hostport'],
        ];
       
        
        $this->corrects=['status'=>2,'aid'=>1,'rid'=>1,'atime'=>time(),'rtime'=>time(),'time'=>time()];
        $this->where_corrects=['rid'=>0];
        $this->db_old= [
            'type' => 'mysql',
            'hostname' => $db['hostname'],
            'database' => 'genele',
            'username' =>$db['username'],
            'password' =>$db['password'],
            'charset' => 'utf8',
            'prefix' => 'sp_',
        ];
       
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
            '付款银行+付款类型'=>url('sys'),
            '地区信息'=>url('area'), 
            
            '(客户/供货商)联系人+对应付款账号+物流公司对应联系人和账号'=>url('tel'),
            '客户(同步分类和主体，关联联系人和付款账号)'=>url('custom'), 
            '供货商(同步分类和主体，关联联系人和付款账号)'=>url('supplier'),   
            '订单'=>url('order'), 
            '采购单'=>url('ordersup'),  
            '仓库还原,清空库存，出入库记录，料位，编辑记录,售后单,考勤和事件'=>url('store_clear'),
            '清空菜单和权限'=>url('menu_clear'),
           
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
        debug('begin');
        $m_old=Db::connect($this->db_old);
        
        $sql='select id,cate_name as name,code_num,t_num as code,pid as fid,sortnum as sort '.
            ' from sp_category2 ';
        
        $data=$m_old->query($sql); 
        if(empty($data)){
            $this->error('数据查询错误');
        }
        
        $m_new=Db::name('cate');
        //开启事务
        $m_new->startTrans();
        //先截取旧数据
        $m_new->execute('truncate table cmf_cate');
        $row_mew=$m_new->insertAll($data);
      
        $m_new->where($this->where_corrects)->update($this->corrects);
       
        echo '<h2>已添加产品分类'.$row_mew.'</h2>';
        
        //根据分类编码修正
         
        $m_new->commit();
       
        // ...其他代码段
        debug('end');
        // ...也许这里还有其他代码
        // 进行统计区间
        echo debug('begin','end').'s'; 
        echo debug('begin','end','m').'kb';
        exit;
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
        debug('begin');
        $m=Db::name('cate');
        
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
        echo '<h2>已更新产品一级分类编码记录值</h2>';
        //更新二级分类的max_num
        $list=Db::name('goods')->group('cid')->column('cid,max(code_num)');
        if(isset($list[0])){
            unset($list[0]);
        }
        foreach($list as $k=>$v){
            $m->where('id',$k)->update(['max_num'=>$v]);
        }
        echo '<h2>已更新产品二级分类的编码记录值</h2>';
        // ...其他代码段
        debug('end');
        // ...也许这里还有其他代码
        // 进行统计区间
        echo debug('begin','end').'s';
        echo debug('begin','end','m').'kb';
        exit;
         //根据分类编码修正
       
    }
    /* 产品基本数据 */
    public function goods()
    {
        debug('begin');
        $m=Db::connect($this->db_old);
        
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
        $time=time();
        foreach ($res as $k=>$tmp){ 
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
                'aid'=>1,
                'rid'=>1,
                'atime'=>$time,
                'rtime'=>$time,
                'time'=>$time,
                'status'=>2,
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
        //删除标签和组合
        $m_goods->execute('truncate table cmf_goods');
        $m_goods->execute('truncate table cmf_goods_collect');
        $m_goods->execute('truncate table cmf_goods_compare');
        $m_goods->execute('truncate table cmf_goods_link');
        $m_goods->execute('truncate table cmf_goods_label');
        $m_goods->execute('truncate table cmf_goods_info');
        $m_goods->execute('truncate table cmf_goods_sn');
        $m_goods->execute('truncate table cmf_goods_tech');
        $row_mew=$m_goods->insertAll($data_goods);
        echo '<h2>已添加产品数据数'.$row_mew.'</h2>';
        //详情
        $m_info=Db::name('goods_info'); 
        //先截取旧数据
        $m_info->execute('truncate table cmf_goods_info');
        $row_mew=$m_info->insertAll($data_info);
        echo '<h2>已添加产品详情'.$row_mew.'</h2>';
        //技术资料
        $m_tech=Db::name('goods_tech');
        //先截取旧数据
        $m_tech->execute('truncate table cmf_goods_tech');
        $row_mew=$m_tech->insertAll($data_tech);
        echo '<h2>已添加产品资料'.$row_mew.'</h2>';
        //说明书
        $m_file=Db::name('goods_file');
        //先截取旧数据
        $m_file->execute('truncate table cmf_goods_file');
        $row_mew=$m_file->insertAll($data_file);
        echo '<h2>已添加产品说明书'.$row_mew.'</h2>';
        //产品图片
        $sql='select id,goods_id as pid,img as file from sp_goods_photo where goods_id>0';
       
        $res=$m->query($sql);
        if(empty($res)){
            $this->error('数据查询错误');
        }
        $data_file=[];
        $path='upload/';
      
        $pic_size=config('pic_size');
        $pid=0;
        //转化图片
        set_time_limit(0);
        foreach ($res as $k=>$tmp){ 
            $pathid='seller2/goods'.$tmp['pid'].'/';
            if(!is_dir($path.$pathid)){
                mkdir($path.$pathid);
            }
            
            if (!is_file($path.$tmp['file']))
            {
               continue;
            }
            //获取后缀名,复制文件
            $ext=substr($tmp['file'], strrpos($tmp['file'],'.'));
            $new_file=$pathid.'jmold'.$tmp['id'].$ext;
            $data_file[]=[
                'pid'=>$tmp['pid'],
                'file'=>$new_file,
                'name'=>'极敏商城图片'.$tmp['id'],
                'type'=>1,
            ];
            if(!is_file($path. $new_file) ){
                $result =copy($path.$tmp['file'], $path.$new_file);
                if(!$result){
                    echo '复制文件错误';
                    $m_goods->rollback();
                    exit;
                }
            } 
            //判断是否需要编制图片 
            $tmp_file=['file'=>$new_file];
            $tmp_file['file1']= $tmp_file['file'].'1.jpg';
            $tmp_file['file2']= $tmp_file['file'].'2.jpg';
            $tmp_file['file3']= $tmp_file['file'].'3.jpg';
            //设置封面图片
            if($pid!=$tmp['pid']){
                $pid=$tmp['pid'];
                $m_goods->where('id',$pid)->setField('pic',$tmp_file['file1']);
            }
            if(!is_file($path. $tmp_file['file1']) ){
                $dd=zz_set_image($tmp_file['file'], $tmp_file['file1'], $pic_size[1][0], $pic_size[1][1]); 
            }
            if(!is_file($path. $tmp_file['file2'])){
                zz_set_image($tmp_file['file'], $tmp_file['file2'], $pic_size[2][0], $pic_size[2][1]);
            }
            if(!is_file($path. $tmp_file['file3'])){
                zz_set_image($tmp_file['file'], $tmp_file['file3'], $pic_size[3][0], $pic_size[3][1]);
            } 
        }
        $row_mew=$m_file->insertAll($data_file);
        echo '<h2>已添加产品图片'.$row_mew.'</h2>';
        $m_goods->commit();
        debug('end');
        // ...也许这里还有其他代码
        // 进行统计区间
        echo debug('begin','end').'s';
        echo debug('begin','end','m');
        exit;
        $this->success('已同步数据数'.$row_mew);
    }
     
    // '所属公司+付款银行+付款类型
    public function sys()
    {
        //  '所属公司+付款银行+付款类型 
        $m_old=Db::connect($this->db_old);
        //2018-11-22添加了淘宝关联，数据结构已变化
//         $sql='select id,company as name,code,allname,account_name,account_bank,account_num,feenum,contact,address'.
//             ' from sp_company ';
//         $data=$m_old->query($sql); 
//         $m_new=Db::name('company');
//         //开启事务
//         $m_new->startTrans();
//         //先截取旧数据
//         $m_new->execute('truncate table cmf_company');
//         $row_mew=$m_new->insertAll($data);  
//         $m_new->where($this->where_corrects)->update($this->corrects);
        
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
        //mobile2,phone1无用
        //联系人,只取姓名不为空的数据
        $sql='select id,user_id as uid,name,position,other,'.
            'cast(sex as signed) as sex,mobile,mobile1,phone,province,city,area,street,postcode,fax,qq,'.
            'wechat,wechatphone,wechatname,email,taobaoid,aliid,ctype '.
            ' from sp_new_contacts where name is not null and name!=""'; 
        $data=$m_old->query($sql); 
       
        //先截取旧数据
        $m_tel->execute('truncate table cmf_tel');
        $m_tel->insertAll($data);
        
        
        //对应付款账号
//         $sql='select id,user_id as uid,bank_id as bank1,account_name as name1,account_num as num1,account_location as location1,'.
//                 'income_id as bank2,income_name as name2,income_num as num2,income_location as location2 from sp_new_accounts ';
        $sql='select id,user_id as uid,bank_id as bank1,account_name as name1,account_num as num1,account_location as location1 '.
            ' from sp_new_accounts ';
        
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
        $correct=$this->corrects;
        unset($correct['atime']);
        $m_new->where($this->where_corrects)->update($correct);
         
        //获取主体数据
        $sql='select * from sp_user ';
        $data=$m_old->query($sql);
        $m_tel=Db::name('tel');
        $m_account=Db::name('account');
        $data_user=[];
        $time=time();
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
                'city_code'=>intval($v['khqh']),
                'code_num'=>intval($v['khbh']), 
                'paytype'=>intval($v['suppaytype_id']),
                'email'=>empty($v['email'])?'':$v['email'],
                'mobile'=>empty($v['phone'])?'':$v['phone'],
                'level'=>intval($v['level']),
                'url'=>empty($v['url'])?'':$v['url'],
                'shopurl'=>empty($v['shopurl'])?'':$v['shopurl'],
                'wechat'=>empty($v['wechat'])?'':$v['wechat'],
                'qq'=>empty($v['qq'])?'':$v['qq'],
                'fax'=>empty($v['fax'])?'':$v['fax'],
                'province'=>intval($v['province']),
                'city'=>intval($v['city']),
                'area'=>intval($v['area']),
                'street'=>empty($v['street'])?'':$v['street'],
                'other'=>empty($v['other'])?'':$v['other'],
                'announcement'=>empty($v['announcement'])?'':$v['announcement'],
                'invoice_type'=>intval($v['invoice_type']),
                'tax_point'=>round($v['tax_point'],2),
                'dsc'=>empty($v['remark'])?'':$v['remark'],
                'atime'=>intval($v['addtime']),
                'status'=>2,
                'aid'=>1,
                'rid'=>1,
                'rtime'=>$time,
                'time'=>$time,
            ];
           switch ($tmp['paytype']){ 
               case 2:
                   $tmp['pay_type']=3;
                   break;
               case 10:
               case 11:
                   $tmp['pay_type']=2;
                   break;
               case 12:
                   $tmp['pay_type']=4;
                   break;
               default:
                   $tmp['pay_type']=1;
                   break;
           }
            //客户编号
            $tmp['code']='KH-'.
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
                    'paytype2'=>$tmp['paytype'],
                ];
                $m_account->update($account);
            }
            if(!empty($v['account2'])){
                $account=[
                    'id'=>$v['account2'],
                    'site'=>2,
                    'uid'=>$v['id'],
                    'type'=>1,
                    'paytype2'=>$tmp['paytype'],
                ];
                $m_account->update($account);
            }
            if(!empty($v['account3'])){
                $account=[
                    'id'=>$v['account3'],
                    'site'=>3,
                    'uid'=>$v['id'],
                    'type'=>1,
                    'paytype2'=>$tmp['paytype'],
                ];
                $m_account->update($account);
            }
            
            $data_user[]=$tmp;
            
        }
      
        //客户关联产品sp_usergoods
        $sql='select user_id as uid,goods_id as goods,u_goods_name as name,u_price as price,u_goods_model as dsc from sp_usergoods ';
        $data=$m_old->query($sql); 
        foreach($data as $k=>$v){
            if(empty($v['name'])){
                $data[$k]['name']='';
            }
            if(empty($v['dsc'])){
                $data[$k]['dsc']='';
            }
            if(empty($v['price'])){
                $data[$k]['price']=0;
            }
        }
        $m_ugoods=Db::name('custom_goods');
        $m_ugoods->execute('truncate table cmf_custom_goods');
        $m_ugoods->insertAll($data);
        //更新客户，供应商关联产品数
        $goods_nums=$m_ugoods->group('uid')->column('uid,count(id)');
        //客户主体数据
        //先截取旧数据
        $m_new=Db::name('custom');
        $m_new->execute('truncate table cmf_custom');
        $m_new->insertAll($data_user);
        //更新客户，供应商关联产品数
        foreach($goods_nums as $k=>$v){
            $m_new->where('id',$k)->update(['goods_num'=>$v]);
        }
       
        $m_new->commit();
        $this->success('已同步数据数'.$row_mew);
        exit;
    }
    //供货商
    public function supplier(){;
        set_time_limit(300);
        $m_old=Db::connect($this->db_old);
        
        //分类
        $sql='select id,supcate_name as name,sort,addtime as atime,note as dsc'.
            ' from sp_supcate ';
        $data=$m_old->query($sql);
        
        $m_new=Db::name('supplier_cate');
        //开启事务
        $m_new->startTrans();
        //先截取旧数据
        $m_new->execute('truncate table cmf_supplier_cate');
        $row_mew=$m_new->insertAll($data);
        $correct=$this->corrects;
        unset($correct['atime']);
        $m_new->where($this->where_corrects)->update($correct);
       
        //获取主体数据
        $sql='select * from sp_supplier ';
        $data=$m_old->query($sql);
        $m_tel=Db::name('tel');
        $m_account=Db::name('account');
        $data_user=[];
        $time=time();
        foreach($data as $k=>$v){
            if(empty($v['name'])){
                continue;
            }
            //组装数据
            $tmp=[
                'id'=>$v['id'],
                'name'=>$v['name'],
                'company'=>intval($v['gysly']),
                'cid'=>intval($v['supcate_id']), 
                'code_num'=>intval($v['nummm']),
                'code'=>$v['supcode'],
                'paytype'=>intval($v['suppaytype_id']),
                'email'=>empty($v['email'])?'':$v['email'],
                'mobile'=>empty($v['phone'])?'':$v['phone'],
                'level'=>intval($v['level']),
                'url'=>empty($v['url'])?'':$v['url'],
                'shopurl'=>empty($v['shopurl'])?'':$v['shopurl'],
                'wechat'=>empty($v['wechat'])?'':$v['wechat'],
                'qq'=>empty($v['qq'])?'':$v['qq'],
                'fax'=>empty($v['fax'])?'':$v['fax'],
                'province'=>intval($v['province_id']),
                'city'=>intval($v['city_id']),
                'area'=>intval($v['area_id']),
                'street'=>empty($v['street'])?'':$v['street'],
                'other'=>empty($v['other'])?'':$v['other'],
                'announcement'=>empty($v['announcement'])?'':$v['announcement'],
                'invoice_type'=>intval($v['invoice_type']),
                'tax_point'=>round($v['tax_point'],2),  
                'dsc'=>$v['remark'].'退货地址'.$v['backdz'], 
                'atime'=>intval($v['addtime']),
                'status'=>2,
                'aid'=>1,
                'rid'=>1,
                'rtime'=>$time,
                'time'=>$time,
            ];
            switch ($tmp['paytype']){
                case 2:
                    $tmp['pay_type']=3;
                    break;
                case 10:
                case 11:
                    $tmp['pay_type']=2;
                    break;
                case 12:
                    $tmp['pay_type']=4;
                    break;
                default:
                    $tmp['pay_type']=1;
                    break;
            }
            //编号,供货商编号不重编
            
            
            $i=1;
            $receiver=1;
            //联系人信息更新
            if(!empty($v['contact_person'])){
                $tel=[
                    'id'=>$v['contact_person'],
                    'site'=>$i++,
                    'uid'=>$v['id'],
                    'type'=>2,
                ];
                $m_tel->update($tel);
            }
            if(!empty($v['contact_person1'])){
                $tel=[
                    'id'=>$v['contact_person1'],
                    'site'=>$i++,
                    'uid'=>$v['id'],
                    'type'=>2,
                ];
                $m_tel->update($tel);
            }
            if(!empty($v['contact_person2'])){
                $tel=[
                    'id'=>$v['contact_person2'],
                    'site'=>$i++,
                    'uid'=>$v['id'],
                    'type'=>2,
                ];
                $m_tel->update($tel);
            }
            if(!empty($v['receiver'])){ 
                $tel=[
                    'id'=>$v['receiver'],
                    'site'=>$i++,
                    'uid'=>$v['id'],
                    'type'=>2,
                ];
                $m_tel->update($tel);
            }
            
            //付款账号更新
            if(!empty($v['account1'])){
                $account=[
                    'id'=>$v['account1'],
                    'site'=>1,
                    'uid'=>$v['id'],
                    'type'=>2,
                    'paytype2'=>$tmp['paytype'],
                ];
                $m_account->update($account);
            }
            if(!empty($v['account2'])){
                $account=[
                    'id'=>$v['account2'],
                    'site'=>2,
                    'uid'=>$v['id'],
                    'type'=>2,
                    'paytype2'=>$tmp['paytype'],
                ];
                $m_account->update($account);
            }
            if(!empty($v['account3'])){
                $account=[
                    'id'=>$v['account3'],
                    'site'=>3,
                    'uid'=>$v['id'],
                    'type'=>2,
                    'paytype2'=>$tmp['paytype'],
                ];
                $m_account->update($account);
            }
            
            $data_user[]=$tmp;
                
        }
        
        //客户主体数据
        //先截取旧数据
        $m_new=Db::name('supplier');
        $m_new->execute('truncate table cmf_supplier');
        $row_mew=$m_new->insertAll($data_user);
      
        $m_new->where($this->where_corrects)->update($correct);
        //客户关联产品sp_supgoods
        $sql='select sup_id as uid,codegoods_id as goods,factory_no as name,factory_price as price'.
        ',qdl as num,note as dsc from sp_supgoods ';
        $data=$m_old->query($sql);
        foreach($data as $k=>$v){
            if(empty($v['name'])){
                $data[$k]['name']='';
            }
            if(empty($v['dsc'])){
                $data[$k]['dsc']='';
            }
            if(empty($v['price'])){
                $data[$k]['price']=0;
            }
            if(empty($v['num'])){
                $data[$k]['num']=1;
            }
        }
        $m_ugoods=Db::name('supplier_goods');
        $m_ugoods->execute('truncate table cmf_supplier_goods');
        $m_ugoods->insertAll($data);
        //更新客户，供应商关联产品数
        $goods_nums=$m_ugoods->group('uid')->column('uid,count(id)');
        
        //更新客户，供应商关联产品数
        foreach($goods_nums as $k=>$v){
            $m_new->where('id',$k)->update(['goods_num'=>$v]);
        }
        
        $m_new->commit();
        $this->success('已同步数据数'.$row_mew);
        exit;
    }
    
    //订单
    public function order(){
//         $val['ordertype']==1){echo "商城订单";}elseif ($val['ordertype']==2){echo "淘宝订单";}else{echo "线下订单";}
        set_time_limit(300);
        $m_old=Db::connect($this->db_old);
        $count=1000; 
        
        //订单产品主体
        $m_new=Db::name('order_goods');
        //开启事务
        $m_new->startTrans();
        //先截取旧数据
        $m_new->execute('truncate table cmf_order_goods');
        //获取最大的id来分页查询
        $sql='select max(id) as count from sp_order_goods';
        $data=$m_old->query($sql);
        $page=ceil($data[0]['count']/$count);
        $field='og.id,og.order_id as oid,og.codeid as goods,og.factory_price as price_in,'.
            'og.sell_price as price_sale,og.prefer_price as price_real,og.goods_nums as num,'.
            'og.goods_weight as weight,g.name as goods_name,g.goods_no as goods_code,g.img as goods_pic';
        for($i=0;$i<$page;$i++){
            $sql='select '.$field.' from sp_order_goods as og '.
                'join sp_codegoods g on g.id=og.codeid '.
                'where og.id >'.($i*$count).' and og.id<='.(($i+1)*$count);
            $data=$m_old->query($sql);
            $row_mew=$m_new->insertAll($data);
        }
        zz_log('order_goods订单产品同步完成');
        //统计产品数量
        $nums=$m_new->group('oid')->column('oid,sum(num)');
        //检查订单号重复
        $sql='select order_sn as name,count(id) as num from sp_order group by order_sn';
        $list=$m_old->query($sql);
        $order_sns=[];
        //没重复的去掉
        foreach($list as $k=>$v){
            if($v['num']>1){
                $order_sns[$v['name']]=$v['num'];
            }
        }
        //订单主体
        $m_order=new OrderModel();
        //开启事务
        $m_order->startTrans();
        //先截取旧数据
        $m_order->execute('truncate table cmf_order');   
        //获取最大的id来分页查询
        $sql='select max(id) as count from sp_order'; 
        $data=$m_old->query($sql);
        $page=ceil($data[0]['count']/$count);
        $field='p.id,p.order_sn as name,p.order_no as express_no,p.user_id as uid,'.
                'p.pay_type as paytype,p.pay_status,p.paystate,p.distribution_status,p.status,'.
                'p.create_time,p.pay_time,p.send_time,p.accept_time,p.completion_time,'.
                'p.accept_name,p.telphone as mobile,p.province,p.city,p.area,p.address,p.mobile as phone,'.
                'p.payable_amount as goods_money,p.order_amount,p.payable_freight as pay_freight,p.real_freight,'.
                'p.postscript as udsc,p.note as dsc,p.if_del as is_del,'.
                'p.ordertype as order_type,p.ordercompany as company,p.admin_id as aid,'.
                'p.sfkp as invoice_type,'.
                'concat(province.area_name,"-",city.area_name,"-",area.area_name) as addressinfo,area.area_postcode as postcode';
        // sort排序，线下订单待发货10，已准备发货9，待确认货款5，待付款4，淘宝已付款 待发货3，其他按时间顺序排 
        //先检查pay_status
        for($i=0;$i<$page;$i++){
            $sql='select '.$field.
                ' from sp_order as p '.  
                ' left join sp_areas province on province.area_type=1 and p.province>0 and province.id=p.province '.
                ' left join sp_areas city on city.area_type=2 and p.city>0 and city.id=p.city '.
                ' left join sp_areas area on area.area_type=3 and p.area>0 and area.id=p.area '.
                'where p.id >'.($i*$count).' and p.id<='.(($i+1)*$count);
            $data=$m_old->query($sql);
            foreach($data as $k=>$v){
                //补足重复的订单号
                if(isset($order_sns[$v['name']])){
                    $order_sns[$v['name']]--;
                    $v['name']=$v['name'].'_'.$order_sns[$v['name']];
                   
                } 
                $v['time']=max($v['create_time'],$v['send_time'],$v['pay_time'],$v['completion_time']);
                $v['goods_num']=isset($nums[$v['id']])?$nums[$v['id']]:0;
                
                //如果company为空就是上海极敏
                if(empty($v['company'])){
                    $v['company']=5;
                }
                if(empty($v['invoice_type'])){
                    $v['invoice_type']=0;
                }
                //pay_type
                switch ($v['paytype']){
                    case 2:
                        $v['pay_type']=3;
                        break;
                    case 10: 
                        $v['pay_type']=2;
                        break;
                    case 11:
                        $v['pay_type']=2;
                        break;
                    case 12:
                        $v['pay_type']=4;
                        break;
                    default:  
                        $v['pay_type']=1;
                        break;
                }
                //order_type
               /*  1 => '线下订单',
                2 => '商城订单',
                3 => '淘宝订单', */
                switch ($v['order_type']){
                    case 1:
                        $v['order_type']=2;
                        break;
                    case 2:
                        $v['order_type']=3;
                        break;
                    case 3:
                        $v['order_type']=1;
                        break;
                }
                // sort排序，线下订单待发货10，已准备发货9，待确认货款5，待付款4，淘宝已付款 待发货3，其他按时间顺序排 
                $v['sort']=0;
                switch ($v['status']){
                    case 3:
                        $v['status']=80;
                        break;
                    case 4:
                        $v['status']=81;
                        break;
                    case 5:
                        $v['status']=30;
                        break;
                    default:  
                        //status1,2 
                        if($v['pay_type']==1 && $v['paystate']==0 && $v['order_type']==1){
                            //待确认货款 
                            $v['status']=10;
                        }elseif($v['distribution_status']==0){
                            if($v['pay_status']==1 || ($v['pay_type']==2 || $v['pay_type']==10)){
                                //待发货 
                                $v['status']=20; 
                            }else{
                                //待付款 
                                $v['status']=10;
                            } 
                        }  
                        break;
                }
                //已发货,就是收获完成
                if($v['distribution_status']>0 &&  $v['status']<30){
                    
                    $v['status']=26; 
                }
                if($v['pay_status']==0){
                    $v['pay_status']=1;
                }else{ 
                    $v['pay_status']=($v['status']==10)?2:3;
                }
                //订单完成
                if($v['pay_status']==3 && $v['status']==26){
                    $v['status']=30;
                }
                //排序
                $v['sort']=$m_order->get_sort($v);
                 
                unset($v['paystate']);
                unset($v['distribution_status']);
                
                $data[$k]=$v; 
            }
           
            $row_mew=$m_order->insertAll($data);
        } 
        
        $m_order->commit();
        zz_log('order订单主体同步完成');
       
        echo ('end');
    }
    //采购单
    public function ordersup(){
        //         $val['ordertype']==1){echo "商城订单";}elseif ($val['ordertype']==2){echo "淘宝订单";}else{echo "线下订单";}
        set_time_limit(300);
        $m_old=Db::connect($this->db_old);
        $count=1000;
        
        //订单产品主体
        $m_new=Db::name('ordersup_goods');
        //开启事务
        $m_new->startTrans();
        //先截取旧数据
        $m_new->execute('truncate table cmf_ordersup_goods');
        //purchase purchase_codegoods
        //获取最大的id来分页查询
        $sql='select max(id) as count from sp_purchase_codegoods';
        $data=$m_old->query($sql);
        $page=ceil($data[0]['count']/$count);
        $field='og.id,og.purchase_id as oid,og.codegoods_id as goods,og.codegoods_name as goods_uname,'.
            'og.num,og.cai_price as price_real,og.cai_note as dsc,cast(og.cai_price * og.num  as  decimal(8,2)) as pay,'.
            'g.name as goods_name,g.goods_no as goods_code,g.img as goods_pic';
        for($i=0;$i<$page;$i++){
            $sql='select '.$field.' from sp_purchase_codegoods as og '.
                'join sp_codegoods g on g.id=og.codegoods_id '.
                'where og.id >'.($i*$count).' and og.id<='.(($i+1)*$count);
            $data=$m_old->query($sql);
            $row_mew=$m_new->insertAll($data);
        }
        zz_log('order_goods订单产品同步完成');
        //统计产品数量
        $nums=$m_new->group('oid')->column('oid,sum(num) as goods_num,sum(pay) as goods_money');
        
        //订单主体
        $m_new=Db::name('ordersup');
        //开启事务
        $m_new->startTrans();
        //先截取旧数据
        $m_new->execute('truncate table cmf_ordersup');
        //获取最大的id来分页查询
        $sql='select max(id) as count from sp_purchase';
        $data=$m_old->query($sql);
        $page=ceil($data[0]['count']/$count);
        //kc_type1上海库存2为合肥库存,3,仓库三库存
        //sta0为未结算1为已结算
        //putin_admin入库操作管理员-暂时不管
        //state当前状态1为未审核，2为已审核待财务付款，3财务已付款待发货4供应商以发货,5确认收货入库，完成采购
     
        //examin_time审核时间
        $field='p.id,p.purchase_no as name,concat(p.wuname,p.order_no) as express_no,p.supplier_id as uid,'.
            'p.kc_type as store,p.sta as pay_status, p.state as status,'. 
            'p.addtime as create_time,p.pay_time,p.finish_time as completion_time';
        
        //先检查pay_status
        for($i=0;$i<$page;$i++){
            $sql='select '.$field.
            ' from sp_purchase as p '. 
            'where p.id >'.($i*$count).' and p.id<='.(($i+1)*$count);
            $data=$m_old->query($sql);
            foreach($data as $k=>$v){
                if(empty($v['completion_time'])){
                    $v['completion_time']=0;
                }
                if(empty($v['pay_time'])){
                    $v['pay_time']=0;
                }
                if(empty($v['create_time'])){
                    $v['create_time']=0;
                }
                $v['goods_num']=isset($nums[$v['id']])?$nums[$v['id']]['goods_num']:0;
                $v['goods_money']=isset($nums[$v['id']])?$nums[$v['id']]['goods_money']:0;
                $v['order_amount']= $v['goods_money'];
                //kc_type1上海库存2为合肥库存要改
                if($v['store']==1){
                    $v['store']=2;
                }else{
                    $v['store']=1;
                }
                if(empty($v['pay_status'])){
                    $v['pay_status']=1;
                }else{
                    $v['pay_status']=3;
                }
                
                
               
                $v['order_type']=1;
             
                //state当前状态1为未审核，2为已审核待财务付款，3财务已付款待发货4供应商以发货,5确认收货入库，完成采购
                //pay_status好像没用
                switch ($v['status']){
                    case 1:
                        $v['status']=2; 
                        $v['pay_status']=1;
                        break;
                    case 2:
                        $v['status']=10; 
                        $v['pay_status']=1;
                        break;
                    case 3:
                        $v['status']=20; 
                        $v['pay_status']=3;
                        break;
                    case 4:
                        $v['status']=22;
                        $v['pay_status']=3;
                        break;
                    case 5:
                        $v['pay_status']=3;
                        $v['status']=30;
                        break;
                    default:
                        $v['status']=2; 
                        $v['pay_status']=1;
                        break;
                }
                //排序
                switch ($v['status']){
                    case 24:
                        $sort=9;
                        break;
                    case 22:
                        $sort=8;
                        break;
                    case 20:
                        $sort=7;
                        break;
                    case 2:
                    case 1:
                        $sort=6;
                        break;
                    case 10:
                        switch ($v['pay_status']){
                            case 1:
                                $sort=3;
                                break;
                            case 2:
                                $sort=2;
                                break;
                            default:
                                break;
                        }
                        break;
                    default:
                        $sort=0;
                        break;
                }
                $v['sort']=$sort;
                $data[$k]=$v;
            }
            
            $row_mew=$m_new->insertAll($data);
        }
        
        $m_new->commit();
        zz_log('ordersup订单主体同步完成');
        
        
        echo ('end');
    }
    //发货记录
    public function freight_doc(){
          set_time_limit(300);
        $m_old=Db::connect($this->db_old);
        $count=1000;
        //订单主体
        $m_new=Db::name('order_freight');
        //开启事务
        $m_new->startTrans();
        //先截取旧数据
        $m_new->execute('truncate table cmf_order_freight');
        //获取最大的id来分页查询
        $sql='select max(id) as count from sp_delivery_doc';
        $data=$m_old->query($sql);
        $page=ceil($data[0]['count']/$count);
        $field='id,order_id as oid,user_id as uid,admin_id as aid,k_id as store'.
            ',addtime as atime,sjweight as weight,delivery_code as express_no,note as dsc,freight_id as freight';
         
        for($i=0;$i<$page;$i++){
            $sql='select '.$field.' from sp_delivery_doc '.
                'where id >'.($i*$count).' and id<='.(($i+1)*$count);
            $data=$m_old->query($sql);
            
            $row_mew=$m_new->insertAll($data);
        } 
        $m_new->commit();
        
        echo ('end');
    }
    /**
     * 仓库还原
     * @adminMenu(
     *     'name'   => '仓库还原',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 0,
     *     'icon'   => '',
     *     'remark' => '仓库还原',
     *     'param'  => ''
     * )
     */
    public function store()
    {
        debug('begin');
        $m_old=Db::connect($this->db_old);
        
        $sql='select id,cate_name as name,code_num,t_num as code,pid as fid,sortnum as sort '.
            ' from sp_category2 ';
        
        $data=$m_old->query($sql);
        if(empty($data)){
            $this->error('数据查询错误');
        }
        
        $m_new=Db::name('cate');
        //开启事务
        $m_new->startTrans();
        //先截取旧数据
        $m_new->execute('truncate table cmf_cate');
        $row_mew=$m_new->insertAll($data);
        
        $m_new->where($this->where_corrects)->update($this->corrects);
        
        echo '<h2>已添加产品分类'.$row_mew.'</h2>';
        
        //根据分类编码修正
        
        $m_new->commit();
        
        // ...其他代码段
        debug('end');
        // ...也许这里还有其他代码
        // 进行统计区间
        echo debug('begin','end').'s';
        echo debug('begin','end','m').'kb';
        exit;
    }
    //清空库存
    public function store_clear(){
        set_time_limit(300);
        $m_old=Db::connect($this->db_old);
        
        $sql='select id,sto_name as name'.
            ' from sp_new_warehouse ';
        
        $data=$m_old->query($sql);
        if(empty($data)){
            $this->error('数据查询错误');
        }
        
        $m_new=Db::name('store');
        
        //先截取旧数据
        $m_new->execute('truncate table cmf_store');
        $row_mew=$m_new->insertAll($data);
        
        $m_new->where($this->where_corrects)->update($this->corrects);
        
        echo '<h2>已添加仓库'.$row_mew.'</h2>';
         
        //先截取旧数据
        $m_new->execute('truncate table cmf_store_goods');
        $m_new->execute('truncate table cmf_store_in');
        $m_new->execute('truncate table cmf_store_box'); 
        $m_new->execute('truncate table cmf_edit'); 
        $m_new->execute('truncate table cmf_edit_info'); 
        $m_new->execute('truncate table cmf_msg'); 
        $m_new->execute('truncate table cmf_msg_txt'); 
       
        $m_new->execute('truncate table cmf_orderback'); 
        $m_new->execute('truncate table cmf_orderback_goods'); 
        $m_new->execute('truncate table cmf_action'); 
        $m_new->execute('truncate table cmf_attendance_day'); 
        $m_new->execute('truncate table cmf_attendance_apply'); 
        $m_new->execute('truncate table cmf_event'); 
        $m_new->execute('truncate table cmf_event_uid'); 
         
        //产品关联数据
        
        echo ('end');
    }
    /**
     * 清空菜单和权限
     */
    public function menu_clear(){
        set_time_limit(300);
        
        //订单主体
        $m_new=Db::name('admin_menu');
        
        //先截取旧数据
        $m_new->execute('truncate table cmf_admin_menu');
        $m_new->execute('truncate table cmf_auth_access');
        $m_new->execute('truncate table cmf_auth_rule');
        
        echo ('end');
    }
    /**
     * 地区信息同步
     * @adminMenu(
     *     'name'   => '地区信息同步',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 0,
     *     'icon'   => '',
     *     'remark' => '地区信息同步',
     *     'param'  => ''
     * )
     */
    public function area()
    {
        debug('begin');
        $m_old=Db::connect($this->db_old);
        
        $sql='select * from sp_areas';
        
        $data=$m_old->query($sql);
        if(empty($data)){
            $this->error('数据查询错误');
        }
        $time=time();
        foreach($data as $k=>$v){
            $tmp=[
                'aid'=>1,
                'rid'=>1,
                'status'=>2,
                'atime'=>$time,
                'rtime'=>$time,
                'time'=>$time,
                'type'=>intval($v['area_type']),
                'fid'=>intval($v['pid']),
                'sort'=>intval($v['sort']),
                'code'=>(empty($v['area_code']))?'':$v['area_code'],
                'postcode'=>(empty($v['area_postcode']))?'':$v['area_postcode'],
                'name'=>(empty($v['area_name']))?'':$v['area_name'],
                'type'=>intval($v['area_type']),
            ];
             
            $data[$k]=$tmp;
        }
        $m_new=Db::name('area');
        //开启事务
        $m_new->startTrans();
        //先截取旧数据
        $m_new->execute('truncate table cmf_area');
        $row_mew=$m_new->insertAll($data);
        
        $m_new->where($this->where_corrects)->update($this->corrects);
        
        echo '<h2>已添加地区'.$row_mew.'</h2>';
        
        //根据分类编码修正
        
        $m_new->commit();
        
        // ...其他代码段
        debug('end');
        // ...也许这里还有其他代码
        // 进行统计区间
        echo debug('begin','end').'s';
        echo debug('begin','end','m').'kb';
        exit;
    }
    
}
