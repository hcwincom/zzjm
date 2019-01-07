
$(function(){
	$('#shop').change(function(){  
		 $(this).parents('form.well').submit();   
	});
});

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
	if($area){
		$city.change(function(){  
			city=$(this).val();
			 get_citys($area,city,0);  
		});
	}
	
}
//获取cate信息
function get_cates($select,fid=0,id=0){
	var options;
	if(fid==0){
		options='<option value="0">一级分类</option>';
	}else{
		options='<option value="0">二级分类</option>';
	} 
	$.post(get_cates_url,{'fid':fid},function(data){
		if(data.code!=1){ 
			return false;
		} 
		var list=data.data; 
		for(var i in list){
			options+='<option value="'+i+'">'+list[i]+'</option>';
		} 
		$select.html(options); 
		if(id>0){
			$select.val(id);
		}
		
	},'json');
}
//获取goods信息
function get_goods($select,cid=0,id=0){
	var options='<option value="0">产品</option>';
	 
	if(cid==0){
		$select.html(options); 
		return false;
	} 
	$.post(get_goods_url,{'cid':cid},function(data){ 
		if(data.code!=1){ 
			return false;
		} 
		var list=data.data; 
		for(var i in list){
			options+='<option value="'+i+'">'+list[i]+'</option>';
		} 
		$select.html(options); 
		if(id>0){
			$select.val(id);
		}
		
	},'json');
} 
//cate选择js初始化
function cate_js($cate1,cate1,$cate2,cate2,$goods=null,goods=0){
	get_cates($cate1,0,cate1);
	if(cate1>0){
		get_cates($cate2,cate1,cate2);
	}
	if($goods && cate2>0){
		get_goods($goods,cate2,goods); 
	}
	$cate1.change(function(){
		cate1=$(this).val();
		if(cate1==0){
			$cate2.html('<option value="0">二级分类</option>');
		}else{
			get_cates($cate2,cate1,0);
		} 
		if($goods){
			get_goods($goods,0,0); 
		}
	});
	if($goods){
		$cate2.change(function(){  
			cate2=$(this).val();
			console.log(cate2);
			get_goods($goods,cate2,0);  
		});
	}
	
}
function msg(text,type=0){
	switch(type){
	case 1:
		$('body').append(text);
		break;
	case 0:
		alert(text);
		break;
	}
}

function is_check(txt='批量删除',isconfirm=1,check='.js-check',error='.error'){
	
	var i=0;
	$(check).each(function(){
		if($(this).prop('checked')){
			i++; 
			return false;
		}
	}); 
	if(i==0){
		$(error).text('未选中数据');
		return false;
	}else{
		$(error).text('');
		if(isconfirm==1 && !confirm('确认'+txt+'吗？')){
			return false;
		}
		return true;
	}
}
