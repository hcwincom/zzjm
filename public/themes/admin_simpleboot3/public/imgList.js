$(function () {
    // 点击弹出图片列表
 
    var h2 = $("tbody tr:nth-child(2)").height();
    $("table tr td").find(".tdImg").click(function () {

        $(this).next(".listposi").fadeIn().parent().parent().siblings().find(".listposi").fadeOut();
        var index = $(this).parent().parent().index();
        $(".listposi").css("top", 136 + (h2 * index));
    });

    $(".imglunclose").click(function () {
        $(".listposi").fadeOut();
    });

    // 点击弹出大图

    $(document).on("click", '.imgList li', function () {
        var inbigimgUrl = $(this).find("input").val();
        $(".lbigImg").attr("src", inbigimgUrl);
        
        $("<img />").attr("src", inbigimgUrl).load(function () {
            var imgWidth = this.width,
                imgHeight = this.height;
            $("#backBigImg").css({
                "width": imgWidth,
                "height": imgHeight,
                "background-image": "url(" + inbigimgUrl + ")"
            });
        });

        $("#outdiv").fadeIn();
        $(document.body).toggleClass("html_overflow");
    });

    $("#innerdiv #imgClose").click(function () {
        $("#outdiv").fadeOut();
        $(document.body).toggleClass("html_overflow");
    });

    // 订单详细分类弹窗
    $("table tr td").find(".goInfo").click(function (e) {
        e.stopPropagation();
        var widthG = $(this).attr("data-width");
        if (widthG == 0) {
            $(this).next(".goodsInfo").width(600);
        } else {
            $(this).next(".goodsInfo").width("auto");
        }
        $(this).next(".goodsInfo").toggle().parent().parent().siblings().find("goodsInfo").hide();
        e.stopPropagation();
    });

    $(document).click(function (e) {
        var drag = $(".goodsInfo"),
            dragel = $(".goodsInfo")[0],
            target = e.target;
        if (dragel !== target && !$.contains(dragel, target)) {
            drag.hide();
        }
        e.stopPropagation();
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