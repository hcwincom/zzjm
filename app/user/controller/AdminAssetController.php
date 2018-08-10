<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace app\user\controller;

use think\Db;
use cmf\controller\AdminBaseController;

class AdminAssetController extends AdminBaseController
{
    /**
     * 资源管理列表
     * @adminMenu(
     *     'name'   => '资源管理',
     *     'parent' => '',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => 'file',
     *     'remark' => '资源管理列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $join   = [
            ['__USER__ u', 'a.user_id = u.id']
        ];
        $result = Db::name('asset')->field('a.*,u.user_login,u.user_email,u.user_nickname')
            ->alias('a')->join($join)
            ->order('create_time', 'DESC')
            ->paginate(10);
        $this->assign('assets', $result->items());
        $this->assign('page', $result->render());
        return $this->fetch();
    }

    /**
     * 删除文件
     * @adminMenu(
     *     'name'   => '删除文件',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '删除文件',
     *     'param'  => ''
     * )
     */
    public function delete()
    {
        $id            = $this->request->param('id');
        $file_filePath = Db::name('asset')->where('id', $id)->value('file_path');
        $file          = 'upload/' . $file_filePath;
        $res = true;
        if (file_exists($file)) {
            $res = unlink($file);
        }
        if ($res) {
            Db::name('asset')->where('id', $id)->delete();
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }
    
    /**
     * 清空失效资源
     * @adminMenu(
     *     'name'   => '清空失效资源',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '清空失效资源',
     *     'param'  => ''
     * )
     */
    public function clear()
    {
        set_time_limit(300);
        $m=Db::name('asset');
        $list=$m->column('id,file_path');
        $path='upload/';
        $ids=[];
        foreach($list as $k=>$v){
            $file=$path.$v;
            if (!file_exists($file)) {
                $ids[]=$k;
            }
        }
        if(empty($ids)){
            $this->success('没有要清空的数据');
        }else{
            $rows=$m->where('id','in',$ids)->delete();
            $this->success('清空数据'.$rows.'条');
        }
        
    }

}