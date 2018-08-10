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

 