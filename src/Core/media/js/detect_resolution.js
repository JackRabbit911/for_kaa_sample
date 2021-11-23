$( document ).ready(function() {
    $.post('/~test/image/upload', { width: screen.width, height:screen.height }, function(json) {
        if(json.outcome == 'success') {
            // запрос прошёл успешно
        } else {
            alert('Unable to let PHP know what the screen resolution is!');
        }
    },'json');
        
});