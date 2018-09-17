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
	var options='<option value="0">请选择</option>';
	if(fid==0){
		$select.html(options); 
		return false;
	}
	$.post(city_url,{'fid':fid},function(data){
		if(data.code!=1){ 
			return false;
		}
		
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
 
//城市选择js初始化
function city_js($province,province,$city,city,$area=null,area=0){
	get_citys($province,1,province);
	if(province>0){
		get_citys($city,province,city);
	}
	if($area && city>0){
		get_citys($area,city,area); 
	}
	$province.change(function(){
		province=$(this).val();
		get_citys($city,province,0);
		if($area){
			 get_citys($area,0,0); 
		}
	});
	$city.change(function(){ 
		if($area){
			city=$(this).val();
			 get_citys($area,city,0); 
		}
	});
}