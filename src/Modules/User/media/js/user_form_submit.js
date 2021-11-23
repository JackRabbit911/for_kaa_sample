$( document ).ready(function() {
    $('body').on('submit', 'form', function(e){
        e.preventDefault();
        let form = $(this).closest('form');
        let formdata = new FormData(form[0]);
        let xhr = new XMLHttpRequest();
        // alert(form.attr('action'));
        xhr.open('POST', form.attr('action'));
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.send(formdata);
        xhr.onload = function() {
            // alert(xhr.getResponseHeader('Content-Type').indexOf('application/json') !== -1);
            // alert('qq');
            try {
                o = JSON.parse(xhr.response);
                if(o.action === 'redirect')
                    window.location.replace(o.uri);
                else if(o.action === 'reload')
                    document.location.reload();
            } catch {
                form.replaceWith(xhr.response);
            }           
        };
    });
});