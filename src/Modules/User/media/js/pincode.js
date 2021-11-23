$('input').keydown(function(e){
	$(this).val('');
});
 
$('input').keyup(function(e){
	var $wrap = $(this).closest('.pincode');
	var $inputs = $wrap.find('input[type="number"]');	
	var val = $(this).val();
	
	// Ввод только цифр
	if(val == val.replace(/[0-9]/, '')) {
		$(this).val('');
		return false;
	}
	
	// Передача фокуса следующему innput
	$inputs.eq($inputs.index(this) + 1).focus();
 
	// Заполнение input hidden
	var fullval = '';
	$inputs.each(function(){
		fullval = fullval + (parseInt($(this).val()) || '0');
	});
	$wrap.find('input[type="hidden"]').val(fullval);
});