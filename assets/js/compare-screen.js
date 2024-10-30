jQuery(function ($) {

    var header = $(".header").outerHeight();
    var list = $(".list").outerHeight();
    var thead = $(".t_head");

    var alllink = header + list;
    $(window).scroll(function(){
        if ($(window).scrollTop() >= 30) {
            $(thead).addClass('fixed-thead');
        }else {
            $(thead).removeClass('fixed-thead');
        }
    });
});