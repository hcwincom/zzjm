<?php
 
namespace app\goods\controller;

use think\Db; 
use app\common\controller\AdminInfo0Controller;
/*
 * 和admininfo功能相同，为了单个进程代码更简洁，复制了一份
 */
class GoodsbaseController extends AdminInfo0Controller
{
     
    /* 添加 */
    public function add_do()
    {
        $m=$this->m;
        $data=$this->request->param();
        if(empty($data['name'])){
            $this->error('名称不能为空');
        } 
        $table=$this->table;
        $time=time();
        $admin=$this->admin; 
        //判断是否有店铺
        if($this->isshop){
            $data_add['shop']=($admin['shop']==1)?2:$admin['shop'];
        } elseif($admin['shop']!=1){
            $this->error('店铺不能添加系统数据');
        }
        $data_add=$data;
        $data_add['sort']=intval($data['sort']);
        $data_add['status']=1;
        
        $data_add['aid']=$admin['id'];
        $data_add['atime']=$time;
        $data_add['time']=$time;
        
        switch ($table){
            case 'goods_fee':
                if(empty($data['type'])){
                    $this->error('类型必须选择');
                }
                $data_add['fee']=round($data['fee'],4);
                break;
            case 'param':
                if(empty($data['type'])){
                    $this->error('类型必须选择');
                }
                //清除不规范输入导致的空格
                if(!empty($data['content'])){
                    //3是自由输入
                    if($data['type']==3){
                        $data_add['content']='';
                    }else{
                        //清除不规范输入导致的空格
                        $data_add['content']=zz_delimiter($data['content']);
                    }
                }
                break;
            case 'price':
                //价格关联的参数
               
                unset($data_add['fee1']);
                unset($data_add['dsc1']);
                unset($data_add['type1']);
                break;
            case 'template':
                //关联的参数
                if(empty($_POST['ids'])){
                    $this->error('没有选择参数项');
                }
                
                unset($data_add['ids']);
                break;
        }
        $m->startTrans();
        $id=$m->insertGetId($data_add);
        //插入后
        switch ($table){
            case 'price':
                //价格关联的参数
                $data_fee=[];
                foreach($data['fee1'] as $k=>$v){
                    $data_fee[]=[
                        't_id'=>$id,
                        'p_id'=>$k,
                        'fee'=>round($v,4),
                        'dsc'=>$data['dsc1'][$k],
                        'type'=>$data['type1'][$k],
                    ];
                } 
                Db::name('price_fee')->insertAll($data_fee);
                break;
            case 'template':
                //关联的参数 
                $ids=$data['ids'];
                $data_param=[];
                foreach($ids as $v){
                    $data_param[]=[
                        'p_id'=>$v,
                        't_id'=>$id,
                    ];
                }
                Db::name('template_param')->insertAll($data_param);
                break; 
        }
        
        //记录操作记录
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'添加'.($this->flag).$id.'-'.$data['name'],
            'table'=>($this->table),
            'type'=>'add',
            'pid'=>$id,
            'link'=>url('edit',['id'=>$id]),
            'shop'=>$admin['shop'],
            
        ];
        zz_action($data_action,['department'=>$admin['department']]);
        $m->commit();
        
        //判断是否直接审核
        $rule='review';
        $res=$this->check_review($admin,$rule);
        if($res){
            $this->redirect($rule,['id'=>$id,'status'=>2]);
        }
        $this->success('添加成功',url('index'));
    }
    /**
     * 编辑提交 
     */
    public function edit_do()
    {
        
        $m=$this->m;
        $table=$this->table;
        $flag=$this->flag;
        $data=$this->request->param();
        $info=$m->where('id',$data['id'])->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        $time=time();
        $admin=$this->admin;
        //其他店铺的审核判断 
        if($admin['shop']!=1){
            if(empty($info['shop']) || $info['shop']!=$admin['shop']){
                $this->error('不能编辑其他店铺的信息');
            }
        }
        $update=[
            'pid'=>$info['id'],
            'aid'=>$admin['id'],
            'atime'=>$time, 
            'table'=>$table,
            'url'=>url('edit_info','',false,false),
            'rstatus'=>1,
            'rid'=>0,
            'rtime'=>0,
            'shop'=>$admin['shop'],
        ]; 
        $update['adsc']=(empty($data['adsc']))?'修改了'.$flag.'信息':$data['adsc'];
        $fields=$this->edit;
        
        $content=[];
        //检测改变了哪些字段
        foreach($fields as $k=>$v){
            //如果原信息和$data信息相同就未改变，不为空就记录，？null测试
            if(isset($data[$v]) && $info[$v]!=$data[$v]){ 
                $content[$v]=$data[$v];
            }
             
        }
        switch ($table){
            
            case 'brand':
                //检查名称
                if(isset($content['name']) || isset($content['char'])){
                    if(empty($data['char'])){
                        $char=zz_first_char($data['name']);
                    }else{
                        $char=zz_first_char($data['char']);
                    }
                    
                    if(empty($char)){
                        $this->error('输入非法，无法获取首字母');
                    }
                    
                    $content['char']=$char;
                }
                
                //处理图片
                $path='upload/';
                if(!empty($content['pic'])){
                    if(is_file($path.$content['pic'])){
                        if(!is_dir($path.$info['path'])){
                            mkdir($path.$info['path']);
                        }
                        $pic_conf=config('pic_'.$table);
                        $content['pic']=$info['path'].'/'.$admin['id'].'-'.$time.'.jpg';
                        zz_set_image($data['pic'], $content['pic'], $pic_conf[0], $pic_conf[1],$pic_conf[2]);
                        unlink($path.$data['pic']);
                    }else{
                        unset($content['pic']);
                    }
                    
                }
                break;
            case 'param':
                //清除不规范输入导致的空格
                if(!empty($content['content'])){
                    //3是自由输入
                    if($info['type']==3){
                        unset($content['content']);
                    }else{
                        //清除不规范输入导致的空格
                        $content['content']=zz_delimiter($content['content']);
                    }
                }
                break;
            case 'template':
                //新关联的参数
                if(empty($_POST['ids'])){
                    $this->error('没有选择参数项');
                }
                $ids=$_POST['ids'];
                //原本关联的
                $ids0=Db::name('template_param')->where('t_id',$data['id'])->column('p_id');
                //计算新旧参数的差级，没有差级就是完全一样
                if(!empty(array_diff($ids,$ids0)) ||  !empty(array_diff($ids0,$ids))){
                    $content['content']=implode(',', $ids);
                }
                break;
            case 'goods_fee':
                //过滤费用值
                if(isset($content['fee'])){
                    $content['fee']=round($content['fee'],4);
                }
                break;
            case 'price':
                $fee1=$data['fee1'];
                $type1=$data['type1'];
                $dsc1=$data['dsc1'];
                //获取所有价格参数来比较，暂时获取全部
                $prices=config('prices');
                //模板关联，获取全部，多点也没关系
                $where=['pf.t_id'=>['eq',$data['id']]];
                
                $fees0=Db::name('price_fee')
                ->alias('pf')
                ->where($where)
                ->column('pf.p_id,pf.fee,pf.type,pf.dsc');
                //循环比较
                $data_fees=[];
                foreach($fee1 as $k=>$vv){
                    $v=$fees0[$k];
                    $vv=round($vv,4);
                    if($vv!=$v['fee'] || $type1[$k]!=$v['type'] || $dsc1[$k]!=$v['dsc']){
                        $data_fees[$k]=[
                            'p_id'=>$k,
                            'fee'=>$vv,
                            'type'=>$type1[$k],
                            'dsc'=>$dsc1[$k],
                        ];
                    }
                }
                if(!empty($data_fees)){
                    $content['content']=$data_fees;
                }
                break;
            case 'compare':
                //新关联的参数
                if(empty($_POST['pids'])){
                    $this->error('没有选择对比产品');
                }
                $ids=$_POST['pids'];
                //原本关联的
                $ids0=Db::name('goods_compare')->where('compare_id',$data['id'])->column('pid');
                //计算新旧参数的差级，没有差级就是完全一样
                if(!empty(array_diff($ids,$ids0)) ||  !empty(array_diff($ids0,$ids))){
                    $content['pids']=$ids;
                }
                break;
        }
         
        if(empty($content)){
            $this->error('未修改');
        }
        //保存更改 
        $m_edit=Db::name('edit');
        $m_edit->startTrans();
        $eid=$m_edit->insertGetId($update);
        if($eid>0){
            $data_content=[
                'eid'=>$eid,
                'content'=>json_encode($content),
            ];
            Db::name('edit_info')->insert($data_content);
        }else{
            $m_edit->rollback();
            $this->error('保存数据错误，请重试');
        }
       
        //记录操作记录
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'编辑了'.($this->flag).$info['id'].'-'.$info['name'],
            'table'=>($this->table),
            'type'=>'edit',
            'pid'=>$info['id'],
            'link'=>url('edit_info',['id'=>$eid]),
            'shop'=>$admin['shop'],
        ];
        
        zz_action($data_action,['department'=>$admin['department']]);
        $m_edit->commit();
        //判断是否直接审核
        $rule='edit_review';
        $res=$this->check_review($admin,$rule);
        if($res){
            $this->redirect($rule,['id'=>$eid,'rstatus'=>2,'rdsc'=>'直接审核']);
        }
        $this->success('已提交修改');
    }
    
    
    
    /**
     * 信息编辑审核 
     */
    public function edit_review()
    {
        //审核编辑的信息
        $status=$this->request->param('rstatus',0,'intval');
        $id=$this->request->param('id',0,'intval');
        if(($status!=2 && $status!=3) || $id<=0){
            $this->error('信息错误');
        }
        $m=$this->m;
        $table=$this->table;
        $m_edit=Db::name('edit');
        $info=$m_edit
        ->field('e.*,p.name as pname,a.user_nickname as aname')
        ->alias('e')
        ->join('cmf_'.$table.' p','p.id=e.pid')
        ->join('cmf_user a','a.id=e.aid')
        ->where('e.id',$id)
        ->find();
        if(empty($info)){
            $this->error('无效信息');
        }
        if($info['rstatus']!=1){
            $this->error('编辑信息已被审核！不能重复审核');
        }
       
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1 && $info['shop']!=$admin['shop']){
           $this->error('不能审核其他店铺的信息'); 
        }
        
        $time=time();
        
        $m->startTrans();
        
        $update=[
            'rid'=>$admin['id'],
            'rtime'=>$time,
            'rstatus'=>$status, 
        ];
        $review_status=$this->review_status;
        $rdsc=$this->request->param('rdsc');
        $update['rdsc']=(empty($rdsc))?$review_status[$status]:$rdsc;
        //只有未审核的才能更新
        $where=[
            'id'=>$id,
            'rstatus'=>1,
        ];
        $row=$m_edit->where($where)->update($update);
        if($row!==1){
            $m->rollback();
            $this->error('审核失败，请刷新后重试');
        } 
        //是否更新,2同意，3不同意
        if($status==2){
           
            
            //得到修改的字段
            $change=Db::name('edit_info')->where('eid',$id)->value('content');
            $change=json_decode($change,true);
            //组装更新数据
            $update_info=$change;
            $update_info['time']=$time; 
             
            switch ($table){
                
                case 'compare':
                    //修改对比产品
                    if(isset($change['pids'])){
                        unset($update_info['pids']);
                        $pids=$change['pids'];
                        $data_goods_compare=[];
                        foreach($pids as $k=>$v){
                            $data_goods_compare[]=[
                                'pid'=>$v,
                                'compare_id'=>$info['pid'],
                            ];
                        }
                        Db::name('goods_compare')->where('compare_id',$info['pid'])->delete();
                        Db::name('goods_compare')->insertAll($data_goods_compare);
                    }
                    break;
                case 'price':
                    //价格模板
                    //模板参数变化
                    if(isset($change['content'])){
                        unset($update_info['content']); 
                        $m_price_fee=Db::name('price_fee'); 
                        $data_fees=$change['content'];
                        foreach($data_fees as $kk=>$vv){
                            $tmp_data=[
                                'fee'=>$vv['fee'],
                                'type'=>$vv['type'],
                                'dsc'=>$vv['dsc'],
                            ];
                            $tmp_where=['t_id'=>$info['pid'],'p_id'=>$kk];
                            $m_price_fee->where($tmp_where)->update($tmp_data);
                        }
                    }
                    break;
                case 'template':
                    if(isset($change['content'])){
                        unset($update_info['content']);
                        //模板参数变化
                        $p_ids=explode(',', $change['content']);
                        $m_t_p=Db::name('template_param');
                        $data_t_p=[];
                        foreach($p_ids as $v){
                            $data_t_p[]=[
                                't_id'=>$info['pid'],
                                'p_id'=>$v,
                            ];
                        }
                        $m_t_p->where('t_id',$info['pid'])->delete();
                        $m_t_p->insertAll($data_t_p);
                       
                    }
            }
            $row=$m->where('id',$info['pid'])->update($update_info);
            if($row!==1){
                $m->rollback();
                $this->error('信息更新失败，请刷新后重试');
            }
        }
         
        //审核成功，记录操作记录,发送审核信息
      
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'审核'.$info['aid'].'-'.$info['aname'].'对'.($this->flag).$info['pid'].'-'.$info['pname'].'的编辑为'.$review_status[$status],
            'table'=>$table,
            'type'=>'edit_review',
            'pid'=>$info['pid'],
            'link'=>url('edit_info',['id'=>$info['id']]),
            'shop'=>$admin['shop'],
        ];
        
        zz_action($data_action,['aid'=>$info['aid']]);
        $m->commit(); 
        
        $this->success('审核成功');
    }
    /**
     * 分类等关联信息
     *   */
    public function cates($type=3){
        parent::cates($type);
   
        $table=$this->table;
        $where_cate=['status'=>2];
       
        $cates=[];
        switch($table){
            case 'goods_fee':
            case 'custom':
                
                $cates=Db::name($table.'_cate')->where($where_cate)->column('id,name');
                break;
            case 'brand':
                $cates=config('chars');
                break;
            case 'template':
                //获取大类
                $where_cate['fid']=0;
                $cates=Db::name('cate')->where($where_cate)->order('sort asc,code asc')->column('id,name');
                $this->assign('cates',$cates);
                break;
            case 'compare':
                //获取技术模板
                $templates=Db::name('template')->where($where_cate)->order('cid asc,sort asc')->column('id,cid,name');
                $this->assign('templates',$templates);
                //获取大类
                $where_cate['fid']=0;
                $cates=Db::name('cate')->where($where_cate)->order('sort asc,code asc')->column('id,name');
                $this->assign('cates0',$cates);
                break;
            default:
                break;
        }
        $this->assign('cates',$cates);
    }
    
}
