$( document ).ready(function() {

    // let menuItem = 'body nav ul li a';
    // let classActive = 'active';

    function navigate(url, json) {

        $(menuItem).removeClass(classActive);
        $(menuItem + `[href="${url}"]`).addClass(classActive);

        $.getJSON(url, {block: json}, function( data ) {
            $.each( data, function( key, val ) {
                if($(key).length) {
                    $(key).html(val);
                } else {
                    $(`meta[name="${key}"]`).attr("content", val);
                }
            });            
        });
    }

    $('body').on('click', `a[data-json]`, function(e){
        e.preventDefault();

        let link = $(e.target);
        let url = link.attr("href");
        let json = link.data("json");
        navigate(url, json);
        history.pushState(null, null, url);
        return false;
    });

    $(window).on('popstate', function(e) {
        e.preventDefault();

        let url = $(location).attr('pathname');
        let json = $(`a[href="${url}"]`).data("json");
        navigate(url, json);
        return false;
    });

    $('body div[data-url], p[data-url]').each(function(i, elem) {
        $.get($(elem).data("url"), function(data) {
            $(elem).html(data);
        });
        return false;
    });

    $('body').on('click', 'a[data-target]', function(e){
        e.preventDefault();

        let link = $(e.target);
        let target = link.data("target");

        $.get(link.attr("href"), function(data) {
            $(target).html(data);
        });

        // $.getJSON(link.attr("href"), {"target": target}, function( data ) {
        //     $.each( data, function( key, val ) {
        //         $(key).html(val);
        //     });
        // });

        return false;
    });

    $('body').on('click', 'a[data-modal]', function(e){
        e.preventDefault();

        let link = $(e.target);
        let target = link.data("modal");
        let url = link.attr("href");

        $.ajax({
            url: url,
            success: function(data) {
                $(target).empty().html(data);
            },
            complete: function() {
                if($(target).hasClass('show') == false) {
                    let myModal = new bootstrap.Modal(document.querySelector(target))
                    myModal.show();
                }
            }
        });

        return false;
    });
});