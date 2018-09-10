function check_one(){
	var i=0; 
	var check_id=0;
	$('.js-check').each(function(){ 
		if(this.checked){
			i++; 
			check_id=this.value;
		}
	}); 
	if(i==0){
		$('.error').text('未选中数据');
		return false;
	}else if(i>1){
		$('.error').text('选中了不只一行数据');
		return false;
	}
	return check_id;
 }
//获取城市信息
function get_citys($select,fid=1,id=0){
	$.post(city_url,{'fid':fid},function(data){
		if(data.code!=1){ 
			return false;
		}
		var options='<option value="0">请选择</option>';
		var list=data.data.list;
		
		for(var i in list){
			options+='<option value="'+i+'">'+list[i]+'</option>';
		} 
		$select.html(options); 
		if(id>0){
			$select.val(id);
		}
		
	},'json');
}
 