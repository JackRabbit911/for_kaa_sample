$( document ).ready(function() {

    $('samp').on('click', 'button[data-ajax=true]', function(e){
        e.preventDefault();
        let form = $(this).closest('form');
        let formdata = new FormData(form[0]);
        let xhr = new XMLHttpRequest();
        xhr.open('POST', form.attr('action'));
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.send(formdata);
        xhr.onload = function() {
            form.replaceWith(xhr.response);
        };
    });

    // $('samp').on('submit', 'form', function(e){
    //     e.preventDefault();
    //     let form = $(this);
    //     let formdata = new FormData(form[0]);
    //     let xhr = new XMLHttpRequest();
    //     xhr.open('POST', form.attr('action'));
    //     xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    //     xhr.send(formdata);
    //     xhr.onload = function() {
    //         form.replaceWith(xhr.response);
    //     };
    // });

    $('samp').on('change', 'input[type="file"]', function(e){
        e.preventDefault();
        regexp = new RegExp('^.{12}');
        let fileName = $(this).val().replace(regexp, '');
        $(this).next('.custom-file-label').html(fileName);
        $(this).removeClass('is-invalid');
    });
});