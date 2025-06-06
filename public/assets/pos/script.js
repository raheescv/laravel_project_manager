/*
Author       : Dreamguys
Template Name: POS - Bootstrap Admin Template
*/


$(document).ready(function () {

    // Variables declarations
    var $wrapper = $('.main-wrapper');
    var $slimScrolls = $('.slimscroll');
    var $pageWrapper = $('.page-wrapper');
    feather.replace();

    // Page Content Height Resize
    $(window).resize(function () {
        if ($('.page-wrapper').length > 0) {
            var height = $(window).height();
            $(".page-wrapper").css("min-height", height);
        }
    });




    //Chat Search Visible
    $('.chat-search-btn').on('click', function () {
        $('.chat-search').addClass('visible-chat');
    });
    $('.close-btn-chat').on('click', function () {
        $('.chat-search').removeClass('visible-chat');
    });
    $(".chat-search .form-control").on("keyup", function () {
        var value = $(this).val().toLowerCase();
        $(".chat .chat-body .messages .chats").filter(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });


});

$(document).ready(function () {
    if ($('#collapse-header').length > 0) {
        document.getElementById('collapse-header').onclick = function () {
            this.classList.toggle('active');
            document.body.classList.toggle('header-collapse');
        }
    }
    // if($('#file-delete').length > 0) {
    // 		document.getElementById('file-delete').onclick = function() {
    // 		document.getElementsByClassName('deleted-table').classList.add("d-none");
    // 		document.getElementsByClassName('deleted-info').classList.add("d-block");
    // 	}
    // }
    if ($('#file-delete').length > 0) {
        $("#file-delete").on("click", function () {
            $('.deleted-table').addClass('d-none');
            $('.deleted-info').addClass('d-block');
        });
    }


    // Increment Decrement

    $(".inc").on('click', function () {
        updateValue(this, 1);
    });
    $(".dec").on('click', function () {
        updateValue(this, -1);
    });
    function updateValue(obj, delta) {
        var item = $(obj).parent().find("input");
        var newValue = parseInt(item.val(), 10) + delta;
        item.val(Math.max(newValue, 0));
    }



});



