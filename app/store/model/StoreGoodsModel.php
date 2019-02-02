<?php
 
namespace app\store\model;

use think\Model;
use think\Db;
use app\admin\model\UserModel;
use app\msg\model\MsgModel;
class StoreGoodsModel extends Model
{
    /**
     * 是否允许入库
     * @param $city收货地
     * @param $shop店铺
     */ 
    public function is_enough($goods,$num,$shop,$store=0){
       //数量》0表示入库
        if($num>=0){
            return 1;
        }
        $where=[
            'goods'=>$goods,
            'store'=>$store,
            'shop'=>$shop,
        ];
        $num=$this->where($where)->value('num');
        if(empty($num) || $num<(abs($num))){
            return '库存不足';
        }else{
            return 1;
        }
    }
     
    /**
     * 添加入库
     * @param array $data入库数据
     * @param number $num_ok是否严格审核库存和料位，1严格，2不审核
     * @param string $sns一货一码输入
     * @return string|number 有库存变化时返回入库id,否则返回1
     */
    function instore0($data,$num_ok=1,$sns=''){
        //统一文件锁
        $file = "log/store_lock.txt";
        $fp = fopen($file, "r");
        if (flock($fp, LOCK_EX )){
            //统一默认为待审核
            $data['rstatus']=1;
            //名称暂时不要，不要结果通知
           /*  if(empty($data['name'])){
                $types=config('store_in_type');
                $data['name']=$types[$data['type']][0].$data['about_name'];
            } */
           /*  if(!isset($data['rstatus'])){
                $data['rstatus']=1;
            } */
            $where=[
                'store'=>['eq',$data['store']],
                'goods'=>['eq',$data['goods']], 
                'shop'=>['eq',$data['shop']], 
            ];
           
            $tmp=$this->where($where)->find();
            if(empty($data['box'])){
                $where['status']=['eq',2];
                $box=Db::name('store_box')->where($where)->order('sort asc')->value('id');
                if($box>0){
                    $data['box']=$box;
                }
            }
            if($data['num']!=0){
                //要检查库存
                if($num_ok==1 && $data['num']<0 && (empty($tmp['num']) || abs($data['num'])>$tmp['num']) ){
                    flock($fp,LOCK_UN);
                    fclose($fp);
                    return '库存不足，请选择其他产品或仓库';
                }
                //入库记录
                $store_in_id=Db::name('store_in')->insertGetId($data);
                //一货一码
                if(!empty($sns)){
                    $m_goods_sn=Db::name('goods_sn');
                    $sns=explode(',', $sns);
                    //去除重复
                    $sns=array_unique($sns);
                    $sns0=$m_goods_sn->where('store_in',$store_in_id)->column('sn');
                    //不要的去掉
                    $sn_delete=array_diff($sns0, $sns);
                    if(!empty($sn_delete[0])){
                        $where_delete=[
                            'store_in'=>$store_in_id,
                            'sn'=>['in',$sn_delete]
                        ];
                        $m_goods_sn->where($where_delete)->delete();
                    }
                    //新增
                    $sn_add=array_diff($sns,$sns0);
                    if(!empty($sn_add[0])){
                        $data_sn=[];
                        foreach($sn_add as $v){
                            if(empty($v)){
                                continue;
                            }
                            $data_sn[]=[
                                'sn'=>$v,
                                'store_in'=>$store_in_id,
                                'shop'=>$data['shop'],
                                'goods'=>$data['goods'],
                            ];
                        }
                        $m_goods_sn->insertAll($data_sn);
                    }
                    
                }
            }else{
                $store_in_id=1;
            }
            if(empty($tmp)){ 
                //不存在，要添加.总库存也要添加
               $data_store=[ 
                       'store'=>$data['store'],
                       'goods'=>$data['goods'], 
                       'shop'=>$data['shop'],
                       'time'=>$data['atime'],
                       'num1'=>$data['num'], 
               ];
               $this->insert($data_store);
               //总库存是否添加
               $where=[
                   'store'=>['eq',0],
                   'goods'=>['eq',$data['goods']],
                   'shop'=>['eq',$data['shop']], 
               ];
               $tmp=$this->where($where)->find();
               if(empty($tmp)){
                   $data_store=[
                           'store'=>0,
                           'goods'=>$data['goods'],
                           'shop'=>$data['shop'],
                           'time'=>$data['atime'],
                           'num1'=>$data['num'], 
                       ];
                   $this->insert($data_store);
               }else{
                   $this->where('id',$tmp['id'])->inc('num1',$data['num'])->setField('time',$data['atime']);
               } 
            }else{ 
                //已存在要更新
                $where=[ 
                    'goods'=>['eq',$data['goods']],
                    'shop'=>['eq',$data['shop']],
                    'store'=>['in',[0,$data['store']]],
                ];
                $this->where($where)->inc('num1',$data['num'])->setField('time',$data['atime']);
                
            }
        }
        flock($fp,LOCK_UN);
        fclose($fp);
        return $store_in_id;
    }
    /**
     * 审核同意入库
     * 
     * @param array $info 
     * @param number $box料位号
     * @param string $sns一货一码输入
     * @param number $num_ok是否严格检查库存
     * @return string|number严格检查库存时返回料位id,否则返回1
     *  */
    public function instore2($info,$box=0,$sns='',$num_ok=1){
        
        //统一文件锁
        $file = "log/store_lock.txt";
        $fp = fopen($file, "r");
        if (flock($fp, LOCK_EX )){ 
            //先检查料位
            $m_box=Db::name('store_box');
            if(empty($box)){
                if(empty($info['box'])){
                    //未选择料位则自动选择
                    $where=[
                        'store'=>$info['store'],
                        'goods'=>$info['goods'],
                        'shop'=>$info['shop'],
                        'status'=>2,
                    ];
                    $box=$m_box->where($where)->order('sort asc')->value('id');
                    if(empty($box) ){
                        $box=0;
                        if($num_ok==1){
                            flock($fp,LOCK_UN);
                            fclose($fp);
                            return '暂时没有适合存放的料位，请选择料位或等待料位审核';
                        }
                    } 
                }else{
                    $box=$info['box'];
                } 
            }
            //更新料位库存
            if($box>0){
                //更新料位库存
                $update_info=[
                    'time'=>time(),
                ];
                $where=[
                    'id'=>$box,
                    'goods'=>$info['goods']
                ];
               
                $row=$m_box->where($where)->inc('num',$info['num'])->update($update_info);
                if($row!==1){
                    flock($fp,LOCK_UN);
                    fclose($fp);
                    return '料位信息更新失败，请刷新后重试';
                }
             }
            
            //一货一码
            if(!empty($sns)){
                $m_goods_sn=Db::name('goods_sn');
                $sns=explode(',', $sns);
                //去除重复
                $sns=array_unique($sns);
                $sns0=$m_goods_sn->where('store_in',$info['id'])->column('sn');
                //不要的去掉
                $sn_delete=array_diff($sns0, $sns);
                if(!empty($sn_delete[0])){
                    $where_delete=[
                        'store_in'=>$info['id'],
                        'sn'=>['in',$sn_delete]
                    ];
                    $m_goods_sn->where($where_delete)->delete();
                }
                //新增
                $sn_add=array_diff($sns,$sns0);
                if(!empty($sn_add[0])){
                    $data_sn=[];
                    foreach($sn_add as $v){
                        if(empty($v)){
                            continue;
                        }
                        $data_sn[]=[
                            'sn'=>$v,
                            'store_in'=>$info['id'],
                            'shop'=>$info['shop'],
                            'goods'=>$info['goods'],
                        ];
                    }
                    $m_goods_sn->insertAll($data_sn);
                }
                
            }
            $where=[ 
                'goods'=>$info['goods'],
                'shop'=>$info['shop'],
                'store'=>['in',[0,$info['store']]],
            ];
            //库存判断 //更新仓库和总库存 
            $update_info=[
                'time'=>time(),
            ];
            if($info['num']<0){
                //如num--3，数据库减去负数会出错，转化绝对值
                $num=abs($info['num']);
                //是否严格库存
                if($num_ok==1){
                    $where['num']=['egt',$num];
                } 
                $row=$this->where($where)->dec('num',$num)->inc('num1',$num)->update($update_info); 
            }else{
                $row=$this->where($where)->inc('num',$info['num'])->dec('num1',$info['num'])->update($update_info); 
            }
        }   
        flock($fp,LOCK_UN);
        fclose($fp);
        //出库操作要检查安全库存
        if($info['num']<0 ){
            $where=[
                'sg.goods'=>$info['goods'],
                'sg.shop'=>$info['shop'],
                'sg.store'=>['eq',$info['store']],
            ];
            $safe=$this
            ->alias('sg')
            ->where($where)
            ->field('sg.id,sg.safe,sg.num')
            ->find();
            if($safe['safe']<=$safe['num']){
                $store_name=Db::name('store')->where('id',$info['store'])->value('name');
                $goods=Db::name('goods')->where('id',$info['goods'])->field('id,name,code')->find();
                if(!empty($goods) && !empty($store_name)){ 
                    //提示库存不足,采购添加权限 
                    $m_msg=new MsgModel();
                    $data=[
                        'dsc'=>'仓库'.$store_name.'产品'.$goods['code'].$goods['name'].'库存要补充', 
                        'link'=>url('store/AdminGoods/index',['type1'=>'code','name'=>$goods['code'],'shop'=>$info['shop']]),
                        'shop'=>$info['shop'],
                    ];
                    $m_msg->auth_send('ordersup/AdminOrdersup/add_do',$data);
                }
            }
        }
        if($row===2){
            //返回料位
            if($num_ok==1){ 
                return $box;
            }else{ 
                return 1;
            } 
        }else{
            return '库存信息更新失败，可能是库存不足';
        }
    }
    
    
   /**
    * 确认不入库
    * @param array $info 
    * @return number|string
    */
    public function instore3($info){
        //统一文件锁
        $file = "log/store_lock.txt";
        $fp = fopen($file, "r");
        if (flock($fp, LOCK_EX )){
            //更新料位库存
            $update_info=[
                'time'=>time(), 
            ];
            if(empty($info['num'])){
                flock($fp,LOCK_UN);
                fclose($fp);
                return 1;
            }
            
            if($info['rstatus']!=1 ){
                flock($fp,LOCK_UN);
                fclose($fp);
                return '入库状态错误';
            }
            $where=[
                'goods'=>['eq',$info['goods']],
                'shop'=>['eq',$info['shop']],
                'store'=>['in',[0,$info['store']]],
            ];
            
            //更新仓库和总库存
            if($info['num']<0){
                //如num--3，数据库减去负数会出错，转化绝对值
                $num=abs($info['num']);
                $row=$this->where($where)->inc('num1',$num)->update($update_info);
            }else{
                $row=$this->where($where)->dec('num1',$info['num'])->update($update_info);
            } 
        }
        flock($fp,LOCK_UN);
        fclose($fp);
        if($row!==2){ 
            return '库存信息更新失败，请刷新后重试';
        } 
        return 1;
        
    }
    /**
     * 确认废弃
     * @param array $info
     * @return number|string
     */
    public function instore5($info){
        //统一文件锁
        $file = "log/store_lock.txt";
        $fp = fopen($file, "r");
        if (flock($fp, LOCK_EX )){
            //更新料位库存
            $update_info=[
                'time'=>time(),
            ];
            if(empty($info['num'])){
                flock($fp,LOCK_UN);
                fclose($fp);
                return 1;
            }
            
            if($info['rstatus']==2){
                flock($fp,LOCK_UN);
                fclose($fp);
                return '不能废弃已审核出库记录';
            }
            //已废弃和审核不通过的不用更新库存了
            if($info['rstatus']==3 || $info['rstatus']==5){
                flock($fp,LOCK_UN);
                fclose($fp);
                return 1;
            }
            $where=[
                'goods'=>['eq',$info['goods']],
                'shop'=>['eq',$info['shop']],
                'store'=>['in',[0,$info['store']]],
            ]; 
            //更新仓库和总库存
            if($info['num']<0){
                //如num--3，数据库减去负数会出错，转化绝对值
                $num=abs($info['num']);
                $row=$this->where($where)->inc('num1',$num)->update($update_info);
            }else{
                $row=$this->where($where)->dec('num1',$info['num'])->update($update_info);
            } 
        }  
        flock($fp,LOCK_UN);
        fclose($fp);
        if($row!==2){
            return '库存信息更新失败，请刷新后重试';
        }
        
        return 1;
        
    }
    /**
     * 还原已审核出入库
     * @param array $info
     * @return number|string
     */
    public function instore_back($info){
        //统一文件锁
        $file = "log/store_lock.txt";
        $fp = fopen($file, "r");
        if (flock($fp, LOCK_EX )){
            //更新料位库存
            $update_info=[
                'time'=>time(),
            ];
            if(empty($info['num'])){
                fclose($fp);
                return 1;
            }
             
            $where=[
                'goods'=>['eq',$info['goods']],
                'shop'=>['eq',$info['shop']],
                'store'=>['in',[0,$info['store']]],
            ];
            
            //更新仓库和总库存,已通过的要把库存还原
            if($info['rstatus']==2){
                if($info['num']<0){
                    $num=abs($info['num']);
                    $row=$this->where($where)->inc('num',$num)->dec('num1',$num)->update($update_info);
                    //料位还要还原
                    Db::name('store_box')->where('id',$num)->inc('num',$num)->update($update_info);
                }else{
                    $row=$this->where($where)->dec('num',$info['num'])->inc('num1',$info['num'])->update($update_info);
                    //料位还要还原
                    Db::name('store_box')->where('id',$info['box'])->dec('num',$info['num'])->update($update_info);
                } 
            }elseif($info['rstatus']==3){
                $row=$this->where($where)->inc('num1',$info['num'])->update($update_info);
            }else{
                flock($fp,LOCK_UN);
                fclose($fp);
                return '只能还原审核通过和审核不通过的记录';
            }
        }
        flock($fp,LOCK_UN);
        fclose($fp);
        if($row!==2){
            return '库存信息更新失败，请刷新后重试';
        } 
        return 1; 
    }
    //更新料位
    public function box_num($store,$goods,$shop){
        $m_box=Db::name('store_box');
        $time=time();
        //先得到更新的仓库的料位数
        $where_box=[
            'status'=>2,
            'store'=>$store,
            'goods'=>$goods
        ]; 
        $box_num=$m_box->where($where_box)->count();
        //更新仓库的料位数
        $where_sg=[
            'store'=>$store,
            'goods'=>$goods 
        ];
        $update=[
            'box_num'=>$box_num,
            'time'=>$time,
        ];
        $this->where($where_sg)->update($update);
        //总库存料位更新
        $where_box=[
            'status'=>2,
            'shop'=>$shop,
            'goods'=>$goods,
            'store'=>['gt',0],
        ];
        $box_num=$m_box->where($where_box)->count();
        $where_sg=[
            'shop'=>$shop,
            'goods'=>$goods,
            'store'=>0,
        ];
        $update=[
            'box_num'=>$box_num,
            'time'=>$time,
        ];
        $this->where($where_sg)->update($update);
    }
}
