
$(function(){
    $(document).bind("keydown",function(e){   
        e=window.event||e;
        if(e.keyCode==116 || e.keyCode == 123){
            e.keyCode = 0;
            return false; //屏蔽F5刷新键,F12审查元素
        }
    });

    // 禁用右键菜单
	document.oncontextmenu = function(){
	    return false;
	}
	// 禁用网页上选取的内容
	document.onselectstart = function(){
	    return false;
	}
	// 禁止复制
	document.oncopy = function(){
	    return false;
	}
	//提示用户是否离开此页面（关闭、刷新或者点击后退等）

    // window.addEventListener("beforeunload", function (e) {

    //     var confirmationMessage = '刷新或离开将导致试卷异常,请确认';


    //     (e || window.event).returnValue = confirmationMessage;     // Gecko and Trident

    //     return confirmationMessage;                                // Gecko and WebKit

    // });
});
