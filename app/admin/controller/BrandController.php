<?php
 
namespace app\admin\controller;

 
use app\common\controller\AdminInfoController; 
use think\Db; 
 
class BrandController extends AdminInfoController
{
    
    public function _initialize()
    {
        parent::_initialize();
       
        $this->flag='产品品牌';
        $this->table='brand';
        $this->m=Db::name('brand');
          
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
         
    }
    /**
     * 产品品牌列表
     * @adminMenu(
     *     'name'   => '产品品牌列表',
     *     'parent' => 'admin/Goods/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品品牌列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $table=$this->table;
        $m=$this->m;
        $data=$this->request->param();
        $where=[];
        if(empty($data['status'])){
            $data['status']=0;
        }else{
            $where['p.status']=['eq',$data['status']];
        }
        if(empty($data['aid'])){
            $data['aid']=0;
        }else{
            $where['p.aid']=['eq',$data['aid']];
        }
        if(empty($data['rid'])){
            $data['rid']=0;
        }else{
            $where['p.rid']=['eq',$data['rid']];
        }
        //查询字段
        $types=config($table.'_search');
        if(empty($types)){
            $types=config('base_search');
        }
        //选择查询字段
        if(empty($data['type1'])){
            $data['type1']=key($types);
        }
        //搜索类型
        $search_types=config('search_types');
        if(empty($data['type2'])){
            $data['type2']=key($search_types);
        }
        if(empty($data['name'])){
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
        $list=$m
        ->alias('p')
        ->field('p.*')
        ->where($where)
        ->order('p.status asc,p.time desc')
        ->paginate();
        // 获取分页显示
        $page = $list->appends($data)->render();
        $m_user=db('user');
        //创建人
        $where_aid=[
            'user_type'=>1,
            'shop'=>1,
        ];
        $aids=$m_user->where($where_aid)->column('id,user_nickname');
        //审核人
        $where_rid=[
            'user_type'=>1,
            'shop'=>1,
        ];
        $rids=$m_user->where($where_rid)->column('id,user_nickname');
        
        $this->assign('page',$page);
        $this->assign('list',$list);
        $this->assign('aids',$aids);
        $this->assign('rids',$rids);
        $this->assign('data',$data);
        $this->assign('types',$types);
        $this->assign('times',$times);
        $this->assign("search_types", $search_types);
        return $this->fetch();
    }
     
   
    /**
     * 产品品牌添加
     * @adminMenu(
     *     'name'   => '产品品牌添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品品牌添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        
        $m=$this->m;
       
        return $this->fetch();
    }
    /**
     * 产品品牌添加do
     * @adminMenu(
     *     'name'   => '产品品牌添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品品牌添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        $m=$this->m;
        $data=$this->request->param();
        if(empty($data['name'])){
            $this->error('名称不能为空');
        }
        $url=url('index');
        //处理图片
        $pic='';
        $table=$this->table;
        $time=time();
        $admin=$this->admin;
        
        $data_add=[
            'name'=>$data['name'],
            'dsc'=>$data['dsc'],
            'pic'=>'',
            'sort'=>intval($data['sort']),
            'status'=>1,
            'aid'=>$admin['id'],
            'atime'=>$time,
            'time'=>$time,
        ];
        $m->startTrans();
        $id=$m->insertGetId($data_add);
        //处理图片
        $path=getcwd().'/upload/';
        $path1=$table.'/'.$id.'/';
        $data_update=[
            'path'=>$path1, 
        ];
        if(!empty($data['pic']) && is_file($path.$data['pic'])){
            if(!is_dir($path.$path1)){
                mkdir($path.$path1);
            } 
            $pic_conf=config('pic_'.$table);
            $data_update['pic']=$path1.'/'.$admin['id'].'-'.$time.'.jpg';
            zz_set_image($data['pic'], $data_update['pic'], $pic_conf[0], $pic_conf[1],$pic_conf[2]);
            unlink($path.$data['pic']);
        }
        
        $m->where('id',$id)->update($data_update);
        //记录操作记录
        $flag=$this->flag;
        $table=$this->table;
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>'添加'.$flag.$id.'-'.$data['name'],
            'table'=>$table,
            'type'=>'add',
            'pid'=>$id,
            'link'=>url('admin/'.$table.'/edit',['id'=>$id]),
            'shop'=>$admin['shop'],
        ];
        db('action')->insert($data_action);
        $m->commit();
        $this->success('添加成功',$url);
    }
    
    
    
    
    /**
     * 产品品牌批量删除
     * @adminMenu(
     *     'name'   => '产品品牌批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品品牌批量删除',
     *     'param'  => ''
     * )
     */
    public function del_all()
    {
        if(empty($_POST['ids'])){
            $this->error('未选中信息');
        }
        $ids=$_POST['ids'];
        
        $m=$this->m;
        $flag=$this->flag;
        $table=$this->table;
        $admin=$this->admin;
        $time=time();
        //检查是否有产品
        $where=['brand'=>['in',$ids]];
        $tmp=db('goods')->where($where)->find();
        if(!empty($tmp)){
            $this->error($flag.$tmp['brand'].'下有产品'.$tmp['name'].$tmp['code']);
        }
        //彻底删除
        $where=['id'=>['in',$ids]];
        $m->startTrans();
        $tmp=$m->where($where)->delete();
        if($tmp>0){
            //记录操作记录 
            $idss=implode(',',$ids);
            $data_action=[
                'aid'=>$admin['id'],
                'time'=>$time,
                'ip'=>get_client_ip(),
                'action'=>'批量删除'.$flag.'('.$idss.')',
                'table'=>$table,
                'type'=>'del',
                'link'=>'',
                'shop'=>$admin['shop'],
            ];
            db('action')->insert($data_action);
            
            //删除关联编辑记录
            $where_edit=[
                'table'=>['eq',$table],
                'pid'=>['in',$ids],
            ];
            db('edit')->where($where_edit)->delete();
            $m->commit();
            //删除图片
            $path=getcwd().'/upload/';
            foreach($ids as $v){
                $path1=$path.$table.'/'.$v;
                zz_dirdel($path1);
            }
            $this->success('成功删除数据'.$tmp.'条');
        }else{
            $this->error('没有删除数据');
        }
        
    }
   
     
}
