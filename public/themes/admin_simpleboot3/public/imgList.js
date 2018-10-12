$(function () {
    // 点击弹出图片列表

    var h2 = $("#mytable tr:nth-child(2)").height();
    $("table tr td").find(".tdImg").click(function () {

        $(this).next(".listposi").fadeIn().parent().parent().siblings().find(".listposi").fadeOut();
        var index = $(this).parent().parent().index();
        $(".listposi").css("top", 136 + (h2 * index));
    });

    $(".imglunclose").click(function () {
        $(".listposi").fadeOut();
    });

    // 点击弹出大图
    var listImgUrl = "{:cmf_get_image_url('')}";
    $(".imgList li").click(function () {
        $("#outdiv").fadeIn();
        $(document.body).toggleClass("html_overflow");
        var inbigimgUrl = $(this).find("input").val();
        $("#inbigImg img").attr("src", inbigimgUrl);
    });

    $("#innerdiv #imgClose").click(function () {
        $("#outdiv").fadeOut();
        $(document.body).toggleClass("html_overflow");
    });
    
    // 轮播
    var imgListli = $('.imgList>li');
    var imgul = $('.imgList');
    var next = $('.imgGroup .next');
    var prev = $('.imgGroup .prev');

    var n = 0;
    var withli = $('.imgList>li').width() + 5;
    var length = $('.imgList>li').length;
    imgul.width(length * withli);
    //右按钮
    next.click(function () {
        if (n < (length - 6)) {
            n++;
            moveLeft();
        }

    });
    //左按钮
    prev.click(function () {
        if (n > 0) {
            n--;
            moveLeft();
        }
    });

    function moveLeft() {
        var len = n * withli;
        imgul.css('left', -len + 20);
    }




});