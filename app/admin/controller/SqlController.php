<?php


namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use PHPExcel_IOFactory;
use PHPExcel;
use PHPExcel_Cell_DataType;
use PHPExcel_Style_Border;

use PHPExcel_Worksheet_Drawing;
use PHPExcel_Style_Alignment;

class SqlController extends AdminbaseController {
    
    
    private $dir;
    private $line;
    private $log;
    public function _initialize() {
        parent::_initialize();
        $this->dir=getcwd().'/data/';
        $this->line="\r\n";
        $this->log="sql.txt";
        $aid=session('ADMIN_ID');
        if($aid!=1){
            $this->error('开发者功能，不要操作');
        }
    }
    
    /**
     * 数据库操作
     * @adminMenu(
     *     'name'   => '数据库操作',
     *     'parent' => '',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 100,
     *     'icon'   => '',
     *     'remark' => '数据库操作',
     *     'param'  => ''
     * )
     */ 
    public function index(){
        
        $dir=$this->dir;
        $files=scandir($dir);
        $list=[];
        foreach($files as $v){ 
            if(is_file($dir.$v) && substr($v,strrpos($v, '.'))=='.sqlsql'){ 
                $list[]=$v;
            } 
        }
        if($list){
            rsort($list);
        }
       
        $this->assign('list',$list);
       return $this->fetch();
       
        
    }
    
    /**
     * 数据库备份
     * @adminMenu(
     *     'name'   => '数据库备份',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '数据库备份',
     *     'param'  => ''
     * )
     */
    public function add(){
        
        //设置超时时间为0，表示一直执行。当php在safe mode模式下无效，此时可能会导致导入超时，此时需要分段导入
        set_time_limit(0);
        $db=config('database');
        $dname=$db['database'];
        $dir=$this->dir; 
       
        import('SqlBack',EXTEND_PATH);
        $msqlback=new \SqlBack($db['hostname'], $db['username'], $db['password'], $dname,  $db['hostport'],$db['charset'],$dir);
        $url=url('index');
        if($msqlback->backup()){
            zz_log('管理员'.session('name').'备份了数据库',$this->log);
            $this->success('数据备份成功',$url);
        }else{
            echo "备份失败! <a href='.$url.'>返回</a>";
        }
        exit();
        
    }
    
    
    /**
     * 数据库还原
     * @adminMenu(
     *     'name'   => '数据库还原',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '数据库还原',
     *     'param'  => ''
     * )
     */
    public function restore()
    {
        $filename=$this->request->param('id','');
        set_time_limit(0);
        $db=config('database');
        $dname=$db['database'];
        $dir=$this->dir;
        $filename=$dir.$filename;
        if(file_exists($filename)){
            import('SqlBack',EXTEND_PATH);
            $msqlback=new \SqlBack($db['hostname'], $db['username'], $db['password'], $dname,  $db['hostport'],$db['charset'],$dir);
            $url=url('index');
            
             if($msqlback->restore($filename)){
                 zz_log('管理员'.session('name').'还原了数据库'.$filename,$this->log);
                 $this->success('数据还原成功',$url);
            }else{
                echo "还原失败! <a href='.$url.'>返回</a>";
            }
        }else{
            echo "文件不存在! <a href='.$url.'>返回</a>";
        }
        exit;
        
    }
    /**
     * 数据库删除备份
     * @adminMenu(
     *     'name'   => '数据库删除备份',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '数据库删除备份',
     *     'param'  => ''
     * )
     */
    public function del(){
        $file=$this->request->param('id','');
        if(unlink(($this->dir).$file)===true){
             zz_log('管理员'.session('name').'删除了备份数据库'.$file,$this->log);
            $this->success('备份已删除');
        }else{
            $this->error('删除失败');
        }
        
    }
    /**
     * 数据库批量删除备份
     * @adminMenu(
     *     'name'   => '数据库批量删除备份',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '数据库批量删除备份',
     *     'param'  => ''
     * )
     */
    public function dels(){
      
        $date=$this->request->param(); 
        $dir=$this->dir;
        foreach($date['ids'] as $file){
            if(unlink($dir.$file)===false){
                $this->error('删除失败');
            }
        }
        zz_log('管理员'.session('name').'批量删除了数据库',$this->log);
         
        $this->success('备份已删除');
         
    }
    
    /**
     * 数据库查询
     * @adminMenu(
     *     'name'   => ' 数据库查询',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => ' 数据库查询',
     *     'param'  => ''
     * )
     */
    public function query(){
        $data=$this->request->param('','','trim');
        if(empty($data['type'])){
            $data['type']=0;
        }
       if(empty($data['sql'])){
           $data['sql']='';
       }else{
           
           try {
               if($data['type']==0){
                   $list=Db::query($data['sql']);
                   $row=count($list);
                   $this->assign('list',$list);
               }else{
                   $row=Db::execute($data['sql']);
               }
           } catch (\Exception $e) {
               $msg=$e->getMessage();
               $this->assign('msg',$msg);
           }
           if(empty($row)){
               $row=0;
           }
           $this->assign('row',$row);
           zz_log('管理员'.session('name').'使用了Sql语句'.($this->line).$data['sql'],$this->log);
           
       }
        $this->assign('data',$data);
         
        return $this->fetch();
        
    }
     
    /**
     * 数据字典导出
     * @adminMenu(
     *     'name'   => ' 数据字典导出',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '数据字典导出',
     *     'param'  => ''
     * )
     */
    public function export()
    {
        header("Content-type:text/html;charset=utf-8");
        $line="\n";
        $filename='数据字典'.date('Y-m-d-H-i-s').'.xls';
        $phpexcel = new PHPExcel();
        //链接数据库
        $db=config('database'); 
        $dname='genele';
        $m= mysqli_init();
        $m->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2);//设置超时时间
        $m=mysqli_connect($db['hostname'],$db['username'],$db['password'],$dname,$db['hostport']);
          
        //查询表
        mysqli_set_charset($m,'utf8');
        $tables=$m->query("show tables");
        $j=0;
        while($tmp=($tables->fetch_assoc())){
            $table=$tmp['Tables_in_'.$dname];
            $creat=$m->query('show create table '.$table);
            $tmp_sql=$creat->fetch_assoc();
            if(empty($tmp_sql)){
                zz_log('empty$table'.$table);
                continue;
            }
            //创建数据库表结构语句
            $sql=$tmp_sql['Create Table'];
            $phpexcel->createSheet();
            $phpexcel->setActiveSheetIndex($j);
            $sheet= $phpexcel->getActiveSheet();
            //设置sheet表名2
            $sheet->setTitle($table);
            // 所有单元格默认高度
            $sheet->getDefaultRowDimension()->setRowHeight(20);
            $sheet->getDefaultColumnDimension()->setWidth(20);
            //单个宽度设置
            $sheet->getColumnDimension('C')->setWidth(30);
            $sheet->getColumnDimension('D')->setWidth(30);
            //截取需要的字符串
            //
            $first=strpos($sql,'(')+1;
            $end=strrpos($sql,')')+1;
            $end0=strrpos($sql,'utf8')+4;
            $sql_end=trim(substr($sql,$end,$end0-$end));
            //截取外键字段
            $first1=stripos($sql,'PRIMARY');
            $end1=strripos($sql,')')-1;
            $sql_end1=trim(substr($sql,$first1,$end1-$first1));
            //截取数据表引擎字段
            $first2=strrpos($sql,'utf8')+4;
            $end2=strlen($sql);
            $sql_end2=trim(substr($sql,$first2,$end2-$first2));
            
            $i=1;
            $j++;
            $sheet
            ->mergeCells('B1:D1')
            ->setCellValue('A'.$i, $j.'表'.$table)
            ->setCellValue('B'.$i,$sql_end2);
            $i=2;
            $sheet
            ->mergeCells('A2:D2')
            ->setCellValue('A'.$i,$sql_end);
            $i=3;
            $sheet
            ->mergeCells('A3:D3')
            ->setCellValue('A'.$i,$sql_end1);
            $i++;
            $sheet
            ->setCellValue('A'.$i, '字段名')
            ->setCellValue('B'.$i, '类型')
            ->setCellValue('C'.$i, '是否为空和默认值')
            ->setCellValue('D'.$i, '备注说明');
            
            //截取字段名 类型 是否为空 备注说明
            $sql=trim(substr($sql,$first,$first1-$first-4));
            $fields=explode(",".$line,$sql);
            //遍历对应的字段名 类型 是否为空 备注说明
            foreach($fields as $k=>$v){
                $v=trim($v);
                
                $field_end=strpos($v,'`',1);
                $field=trim(substr($v,1,$field_end-1));
                
                $type_end=strpos($v,')');
                $type=trim(substr($v,$field_end+1,$type_end-$field_end));
                
                $comment_start=strpos($v,'COMMENT');
                if($comment_start=== false){
                    $default=trim(substr($v,$type_end+1));
                    $comment='';
                }else{
                    $default=trim(substr($v,$type_end+1,$comment_start-$type_end-1));
                    $comment=trim(substr($v,$comment_start+8));
                }
                $i++;
                $sheet
                ->setCellValue('A'.$i, $field)
                ->setCellValue('B'.$i, $type)
                ->setCellValue('C'.$i, $default)
                ->setCellValue('D'.$i, $comment);
            }
           
            
        }
        
        //$m->colse();
        //在浏览器输出
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=$filename");
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        
        $objwriter = PHPExcel_IOFactory::createWriter($phpexcel, 'Excel5');
        $objwriter->save('php://output');
        
        exit('index');
    }
    /**
     * 数据字典导出1
     * @adminMenu(
     *     'name'   => ' 数据字典导出1',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '数据字典导出1',
     *     'param'  => ''
     * )
     */
    public function export1()
    {
        header("Content-type:text/html;charset=utf-8");
        $line="\n";
        $filename='数据字典部分'.date('Y-m-d-H-i-s').'.xls';
        $phpexcel = new PHPExcel();
        //链接数据库
        $db=config('database');
        $dname='genele';
        $m= mysqli_init();
        $m->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2);//设置超时时间
        $m=mysqli_connect($db['hostname'],$db['username'],$db['password'],$dname,$db['hostport']);
        
        //查询表
        mysqli_set_charset($m,'utf8');
        //需要导出的表
        $tables=['sp_category2','sp_codegoods','sp_goods_photo'];
        $j=0;
        foreach($tables as $k=>$table){
            
            $creat=$m->query('show create table '.$table);
            $tmp_sql=$creat->fetch_assoc();
            if(empty($tmp_sql)){
                zz_log('empty$table'.$table);
                continue;
            }
            //创建数据库表结构语句
            $sql=$tmp_sql['Create Table'];
            $phpexcel->createSheet();
            $phpexcel->setActiveSheetIndex($j);
            $sheet= $phpexcel->getActiveSheet();
            //设置sheet表名2
            $sheet->setTitle($table);
            // 所有单元格默认高度
            $sheet->getDefaultRowDimension()->setRowHeight(20);
            $sheet->getDefaultColumnDimension()->setWidth(20);
            //单个宽度设置
            $sheet->getColumnDimension('C')->setWidth(30);
            $sheet->getColumnDimension('D')->setWidth(30);
            //截取需要的字符串
            //
            $first=strpos($sql,'(')+1;
            $end=strrpos($sql,')')+1;
            $end0=strrpos($sql,'utf8')+4;
            $sql_end=trim(substr($sql,$end,$end0-$end));
            //截取外键字段
            $first1=stripos($sql,'PRIMARY');
            $end1=strripos($sql,')')-1;
            $sql_end1=trim(substr($sql,$first1,$end1-$first1));
            //截取数据表引擎字段
            $first2=strrpos($sql,'utf8')+4;
            $end2=strlen($sql);
            $sql_end2=trim(substr($sql,$first2,$end2-$first2));
            
            $i=1;
            $j++;
            $sheet
            ->mergeCells('B1:D1')
            ->setCellValue('A'.$i, $j.'表'.$table)
            ->setCellValue('B'.$i,$sql_end2);
            $i=2;
            $sheet
            ->mergeCells('A2:D2')
            ->setCellValue('A'.$i,$sql_end);
            $i=3;
            $sheet
            ->mergeCells('A3:D3')
            ->setCellValue('A'.$i,$sql_end1);
            $i++;
            $sheet
            ->setCellValue('A'.$i, '字段名')
            ->setCellValue('B'.$i, '类型')
            ->setCellValue('C'.$i, '是否为空和默认值')
            ->setCellValue('D'.$i, '备注说明');
            
            //截取字段名 类型 是否为空 备注说明
            $sql=trim(substr($sql,$first,$first1-$first-4));
            $fields=explode(",".$line,$sql);
            //遍历对应的字段名 类型 是否为空 备注说明
            foreach($fields as $k=>$v){
                $v=trim($v);
                
                $field_end=strpos($v,'`',1);
                $field=trim(substr($v,1,$field_end-1));
                
                $type_end=strpos($v,')');
                $type=trim(substr($v,$field_end+1,$type_end-$field_end));
                
                $comment_start=strpos($v,'COMMENT');
                if($comment_start=== false){
                    $default=trim(substr($v,$type_end+1));
                    $comment='';
                }else{
                    $default=trim(substr($v,$type_end+1,$comment_start-$type_end-1));
                    $comment=trim(substr($v,$comment_start+8));
                }
                $i++;
                $sheet
                ->setCellValue('A'.$i, $field)
                ->setCellValue('B'.$i, $type)
                ->setCellValue('C'.$i, $default)
                ->setCellValue('D'.$i, $comment);
            }
           
            
        }
        
        //$m->colse();
        //在浏览器输出
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=$filename");
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        
        $objwriter = PHPExcel_IOFactory::createWriter($phpexcel, 'Excel5');
        $objwriter->save('php://output');
        
        exit('index');
    }
    
}

?>