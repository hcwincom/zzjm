<?php
 
namespace app\admin\controller;

 
use cmf\controller\AdminBaseController; 
use think\Db; 
 /* 旧数据处理 */
class OldController extends AdminBaseController
{
    private $dbconfig;
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
            '产品基本数据'=>url('goods'),
            '产品图片同步'=>url('goods_pic'),
            '客户主体'=>url('customer'),
            '客户联系人'=>url('customer_tel'),
            '供货商'=>url('supplier'),
            '供货商主体'=>url('supplier_tel'),
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
      //取得数量后期比对
        $row=count($data);
      
        $m_new=db('cate');
        //开启事务
        $m_new->startTrans();
        //先截取旧数据
        $m_new->execute('truncate table cmf_cate');
        $row_mew=$m_new->insertAll($data);
        if($row_mew==$row){
            $m_new->commit();
        }else{
            $m_new->rollback();
            $this->error('同步错误');
        }
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
         //根据分类编码修正
        $this->success('已更正数据数'.$row);
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
                'pid'=>$tmp['pid'],
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
//          echo '<h1>goods</h1>';
//          dump($data_goods);
//          echo '<h1>$$data_info</h1>';
//          dump($data_info);
//          echo '<h1>$data_tech</h1>';
//          dump($data_tech);
//          echo '<h1>$$$data_file</h1>';
//          dump($data_file);
//         exit; 
        //取得数量后期比对
         $row=count($data_goods);
        
        $m_goods=db('goods');
        //开启事务
        $m_goods->startTrans();
        //先截取旧数据
        $m_goods->execute('truncate table cmf_goods');
        $row_mew=$m_goods->insertAll($data_goods);
        if($row_mew!=$row){
            $m_goods->rollback();
            $this->error('同步错误');
        } 
        //详情
        $m_info=db('goods_info'); 
        //先截取旧数据
        $m_info->execute('truncate table cmf_goods_info');
        $m_info->insertAll($data_info);
        
        //技术资料
        $m_tech=db('goods_tech');
        //先截取旧数据
        $m_tech->execute('truncate table cmf_goods_tech');
        $m_tech->insertAll($data_tech);
        
        //说明书
        $m_file=db('goods_file');
        //先截取旧数据
        $m_file->execute('truncate table cmf_goods_file');
        $m_file->insertAll($data_file);
        
        //产品图片
        $m_file=db('goods_file');
        //先截取旧数据
        $m_file->execute('truncate table cmf_goods_file');
        $m_file->insertAll($data_file);
        
        $m_goods->commit();
        $this->success('已同步数据数'.$row_mew);
    }
    /* 产品图片 */
    public function goods_pic()
    {
        
        $m= mysqli_init();
        $m->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2);//设置超时时间
        $dbconfig=$this->dbconfig;
        $m->real_connect($dbconfig['host'],$dbconfig['user'],$dbconfig['psw'],$dbconfig['dbname'],$dbconfig['port']);
        $sql='select id,goods_id as pid,img as file from sp_goods_photo where goods_id>0';
       
        $res=$m->query($sql);
        if(empty($res)){
            $this->error('数据查询错误');
        } 
        
        $data_file=[]; 
        while($tmp=($res->fetch_assoc())){
            if(is_file()){
                
            }
            $data_file[]=[
                'id'=>$tmp['id'],
                'pid'=>$tmp['pid'],
                'file'=>$tmp['file'],
                'name'=>'极敏商城图片'.$tmp['id'],
                'type'=>1,
            ];
             
        }
        
        //产品图片
        $m_file=db('goods_file');
        //先截取旧数据
        $m_file->execute('truncate table cmf_goods_file');
        $row_mew=$m_file->insertAll($data_file);
        
        $this->success('已同步数据数'.$row_mew);
    }
}
