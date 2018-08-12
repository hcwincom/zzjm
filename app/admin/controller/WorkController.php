<?php
 
namespace app\admin\controller;

 
use cmf\controller\AdminBaseController; 
use think\Db; 
 
/**
 * Class WorkController
 * @package app\admin\controller
 *
 * @adminMenuRoot(
 *     'name'   =>'产品作业',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 2,
 *     'icon'   =>'',
 *     'remark' =>'产品作业'
 * )
 *
 */
class WorkController extends AdminBaseController
{
    private $m;
    private $statuss;
    private $review_status;
    private $table;
    private $tables;
    private $fields;
    private $flag;
    private $file_type;
    private $goods_type;
    public function _initialize()
    {
        parent::_initialize();
        $this->m=Db::name('goods');
        $this->flag='产品'; 
        $this->table='goods'; 
        $this->assign('flag',$this->flag);
        $this->statuss=config('info_status');
        $this->review_status=config('review_status');
        $this->goods_type=config('goods_type');
        
        $this->assign('statuss',$this->statuss);
        $this->assign('review_status',$this->review_status);
        $this->assign('html',$this->request->action());
        $this->assign('goods_type',$this->goods_type);
        $this->assign('sn_type',config('sn_type'));
        $this->assign('is_box',config('is_box'));
        //计算小数位
        bcscale(2);
        
        $this->file_type=[
            1=>['pic_jm','极敏商城图片'],
            2=>['pic_pro','实物图片'],
            3=>['pic_logo','极敏logo图片'],
            4=>['pic_param','产品规格图'],
            5=>['pic_principle','产品原理图'],
            6=>['pic_other','其他图片'],
            7=>['file_instructions','产品说明书'], 
            8=>['file_other','其他文档'],
        ];
        $this->assign('file_type',$this->file_type);
        
        $this->tables=['goods','goods_file','goods_content','goods_type2','goods_type3','goods_type4'];
    }
     
    /**
     * 我的产品
     * @adminMenu(
     *     'name'   => '我的产品',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '我的产品',
     *     'param'  => ''
     * )
     */
    public function index()
    { 
        
        $admin=$this->admin;
         
        $m=$this->m;
        $data=$this->request->param();
        $admin=$this->admin;
        $where=['gc.uid'=>['eq',$admin['id']]];
        
        //状态
        if(empty($data['status'])){
            $data['status']=0;
        }else{
            $where['p.status']=['eq',$data['status']];
        }
        //类型
        if(empty($data['type'])){
            $data['type']=0;
        }else{
            $where['p.type']=['eq',$data['type']];
        }
        
        //一级分类
        if(empty($data['cid0'])){
            $data['cid0']=0;
        }else{
            $where['p.cid0']=['eq',$data['cid0']];
        }
        //二级分类
        if(empty($data['cid'])){
            $data['cid']=0;
        }else{
            $where['p.cid']=['eq',$data['cid']];
        }
        
       
        //查询字段
        $types=config('goods_search');
        //选择查询字段
        if(empty($data['type1'])){
            $data['type1']=key($types);
        }
        //搜索类型
        $search_types=config('search_types');
        if(empty($data['type2'])){
            $data['type2']=key($search_types);
        }
        if(!isset($data['name']) || $data['name']==''){
            $data['name']='';
        }else{
            $where['p.'.$data['type1']]=zz_search($data['type2'],$data['name']);
        }
        
        //时间类别
        $times=config('time1_search');
        if(empty($data['time'])){
            $data['time']=key($times);
            $data['datetime1']='';
            $data['datetime2']='';
        }else{
            //时间处理
            if(empty($data['datetime1'])){
                $data['datetime1']='';
                $time1=0;
                if(empty($data['datetime2'])){
                    $data['datetime2']='';
                    $time2=0;
                }else{
                    //只有结束时间
                    $time2=strtotime($data['datetime2']);
                    $where['p.'.$data['time']]=['elt',$time2];
                }
            }else{
                //有开始时间
                $time1=strtotime($data['datetime1']);
                if(empty($data['datetime2'])){
                    $data['datetime2']='';
                    $where['p.'.$data['time']]=['egt',$time1];
                }else{
                    //有结束时间有开始时间between
                    $time2=strtotime($data['datetime2']);
                    if($time2<=$time1){
                        $this->error('结束时间必须大于起始时间');
                    }
                    $where['p.'.$data['time']]=['between',[$time1,$time2]];
                }
            }
        }
        $list=db('goods_collect') 
        ->alias('gc')
        ->field('p.*,gc.ctime as gc_ctime,gc.time as gc_time,gc.type as gc_type') 
        ->join('cmf_goods p','p.id=gc.pid')
        ->where($where)
        ->order('gc.time desc')
        ->paginate();
        // 获取分页显示
        $page = $list->appends($data)->render();
       
        $this->assign('page',$page);
        $this->assign('list',$list);
       
        $this->assign('data',$data);
        $this->assign('types',$types);
        $this->assign('times',$times);
        $this->assign("search_types", $search_types);
        //分类
        $this->cates();
        $this->assign('cid0',$data['cid0']);
        $this->assign('cid',$data['cid']);
        $this->assign('select_class','form-control');
        
        $this->assign('collect_type',[1=>'创建',2=>'编辑',3=>'审核',4=>'收藏']);
         
        return $this->fetch();
    }
    /**
     * 删除收藏
     * @adminMenu(
     *     'name'   => '删除收藏',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '删除收藏',
     *     'param'  => ''
     * )
     */
    public function collect_del()
    { 
        if(empty($_POST['ids'])){
            $this->error('未选择产品');
        }
        $admin=$this->admin;
        $where=[
            'uid'=>$admin['id'],
            'pid'=>['in',$_POST['ids']], 
        ];
        $rows=db('goods_collect')->where($where)->delete();
        $this->error('已删除收藏'.$rows.'条');
    }
    
    //获取分类信息
    public function cates(){
        //分类
        $m_cate=db('cate');
        $where_cate=[
            'fid'=>0,
            'status'=>2,
        ];
        $cates0=$m_cate->where($where_cate)->order('sort asc,code_num asc')->column('id,name,code');
        $where_cate=[
            'fid'=>['neq',0],
            'status'=>['eq',2],
        ];
        $cates=$m_cate->where($where_cate)->order('sort asc,code_num asc')->column('id,name,fid,code');
        $this->assign('cates0',$cates0);
        $this->assign('cates',$cates);
    }
     
}
