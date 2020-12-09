
;$(function(){
  $(".top_box").slide({mainCell:".bd ul",autoPlay:true,effect:"topMarquee",vis:1,interTime:80,trigger:"click"});

    $(".slideTxtBox").slide({
          titCell:".hd ul",
          mainCell:".bd ul",
          autoPage:true,
          effect:"leftLoop",
          interTime:2000,
          delayTime:500,
          autoPlay:true,
          vis:4
      });
/*  $(".btn_use").click(function(){
    $(this).parent().css("background","url(image/bg_none.png) 0 0 no-repeat");
    $(this).html("已<br>领<br>取");
    $(this).css("padding-top","49px")
  })*/

  $(".nav_list dl dd").click(function(){
     var index_box= $(this).index();
     $(".content_box").eq(index_box-1).show().siblings(".content_box").hide();
     $(this).addClass("on").siblings("dd").removeClass("on");
  });
});
