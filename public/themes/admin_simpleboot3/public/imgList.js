$(function () {
    
    // 点击弹出图片列表
    $(document).on("click","td .tdImg",function () {
        $(this).next(".listposi").toggle().parent().parent().siblings().find(".listposi").hide();    
    });
    $(document).on("click", "td .imglunclose", function () {
        $(".listposi").hide();
    });
    
    // 点击弹出大图
    var zj_pic_index;
    var divLen = $(".imgList li").length;
    var zj_pic = $(".imgList").eq(0);

    $(document).on("click", '.imgList li', function () {
        zj_pic = $(this).parents(".imgList");
        zj_pic_index = $(this).index();
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
  
    //上一张
    $("#innerdiv #imgPrev span").click(function () {

        if (zj_pic_index != 0) {
            zj_pic_index--;

            var inputImg = zj_pic.find('input').eq(zj_pic_index).val();

            $(".lbigImg").attr("src", inputImg);

            $("<img />").attr("src", inputImg).load(function () {
                var imgWidth = this.width,
                    imgHeight = this.height;
                $("#backBigImg").css({
                    "width": imgWidth,
                    "height": imgHeight,
                    "background-image": "url(" + inputImg + ")"
                });
            });
        } else {
            alert("已经是最后一张了！！！");
        }
    });

    $("#innerdiv #imgNext span").click(function () {
        if (zj_pic_index <= divLen) {
            zj_pic_index++;

            var inputImg = zj_pic.find('input').eq(zj_pic_index).val();
            $(".lbigImg").attr("src", inputImg);

            $("<img />").attr("src", inputImg).load(function () {
                var imgWidth = this.width,
                    imgHeight = this.height;
                $("#backBigImg").css({
                    "width": imgWidth,
                    "height": imgHeight,
                    "background-image": "url(" + inputImg + ")"
                });
            });
        } else {
            alert("已经是最后一张了！！！");
        }
    });














    // 订单详细分类弹窗
    $(document).on("click","tr .clickInfo", function (e) {
        var widthG = $(this).find(".goInfo").attr("data-width");
        if (widthG == 0) {
            $(this).find(".goodsInfo").width(600);
        } else {
            $(this).find(".goodsInfo").width("auto");
        }
        $(this).find(".goodsInfo").toggle().parent().parent().siblings('tr').find(".goodsInfo").hide();
        stopPropagation(e);
    });
    $(document).click(function (e) {
        $(".goodsInfo").hide();
    });
    $(".goodsInfo").click(function (e) {
        stopPropagation(e);
    });

    function stopPropagation(e) {
        var ev = e || window.event;
        if (ev.stopPropagation) {
            ev.stopPropagation();
        }
        else if (window.event) {
            window.event.cancelBubble = true;//兼容IE
        }
    }
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