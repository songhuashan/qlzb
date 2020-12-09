//添加直播课堂
admin.addLive=function() {
var school_id = $('#form_mhm_id option:selected').val();
console.log(school_id);
if(school_id != 0){
$.ajax({
type: 'POST',
url: U('classroom/AdminVideo/findSchoolTeacher'),
data: {mhm_id: school_id,teacher_id:$('#form_teacher_id option:selected').val()},
dataType: "json",
cache: false,
success: function (data) {
$('#dl_teacher_id').html(data.info).show();
}
});
}else{
$('#dl_teacher_id').hide();
}
}
admin.choiceSchool=function(obj){
var school_id = obj.value;
if(school_id != 0){
$.ajax({
type: 'POST',
url: U('classroom/AdminVideo/findSchoolTeacher'),
data: {mhm_id: school_id},
dataType: "json",
cache: false,
success: function (data) {
$('#dl_teacher_id').html(data.info).show();
}
});
}else{
$('#dl_teacher_id').hide();
}
}
//检查添加直播课堂表单提交
admin.checkLive=function(form) {
if(form.video_title.value.replace(/^ +| +$/g,'')==''){
ui.error('直播课堂名称不能为空!');
return false;
}
if($('.mzTopLevel option:selected').val() <= 0){
ui.error('请选择直播课堂分类!');
return false;
}
if(form.mhm_id.value.replace(/^ +| +$/g,'')==''){
ui.error('机构ID不能为空!');
return false;
}
if(form.cover.value.replace(/^ +| +$/g,'')==''){
ui.error('请上传直播课堂封面!');
return false;
}
if(form.video_intro.value.replace(/^ +| +$/g,'')==''){
ui.error('直播课堂信息不能为空!');
return false;
}
if(form.t_price.value.replace(/^ +| +$/g,'')==''){
ui.error('直播课堂价格不能为空!');
return false;
}
if(isNaN(form.t_price.value)){
ui.error('直播课堂价格必须为数字!');
return false;
}
if(form.listingtime.value.replace(/^ +| +$/g,'')==''){
ui.error('上架时间不能为空!');
return false;
}
if(form.uctime.value.replace(/^ +| +$/g,'')==''){
ui.error('下架时间不能为空!');
return false;
}

return true;
};

/**
*
* @param id
* @returns {boolean}
* 直播操作
*/
admin.doaction = function(id,action,type,status){


if("undefined" == typeof(id) || id=='')
id = admin.getChecked();
if(id == ''){
ui.error( '请选择直播课堂' );return false;
}
if(confirm( '确定操作该课堂直播？' )){
$.post(U('live/AdminLive/doaction'+action),{id:id,type:type,status:status},function(obj){
admin.ajaxReloads(obj);
},'json');
}
};

admin.jump = function(type,id){
window.location.href = U('live/AdminLive/add'+type+'LiveRoom')+"&id="+id;
};
//获取别的直播间课堂
admin.associate = function(id,live_id){
$("#associate").attr("data-live",live_id);
var dp = $('#'+id).css("display");
if(dp == "none"){
$("#associate").css("display","block");
}else{
$("#associate").css("display","none");
}
}

//转移live_id
function  shift_live_id(){

    var now_live_id = $("#associate").attr("data-live");
    var shift_live = $("#shift_live").val();
    if(shift_live.lenght == 0){
        alert("请填写要复制的直播ID");
    }
    $.post(U('live/AdminLive/associate'),{"now_live_id":now_live_id,"shift_live":shift_live},function(msg){
        alert(msg.msg);
        window.location.href = 'https://www.qiluzhibo.com/index.php?app=live&mod=AdminLive&act=ccLiveRoom&id='+now_live_id;
    },"json");


}

/**
* 处理ajax返回数据之后的刷新操作
*/
admin.ajaxReloads = function(obj){
if(obj.status == 1){
ui.success(obj.info,3);
window.location.reload();
}else{
ui.error(obj.info,3);
}
};

//检查排课提交
admin.checkCourse=function(form) {
if(form.shool_id.value.replace(/^ +| +$/g,'')==''){
ui.error('机构名称不能为空!');
return false;
}
if(form.con_num.value.replace(/^ +| +$/g,'')==''){
ui.error('请输入并发量!');
return false;
}
if(form.beginTime.value.replace(/^ +| +$/g,'')==''){
ui.error('请输入开始时间!');
return false;
}
if(form.school_id.value.replace(/^ +| +$/g,'')==''){
ui.error('请输入结束时间!');
return false;
}
};

//处理排课
admin.mzArcourse=function(_id,action,title,type){
var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
if(confirm(L('是否执行此操作',{'title':title,'type':type}))) {
$.post(U('live/ArrangeCourse/' + action), {id: id}, function (msg) {
admin.ajaxReload(msg);
}, 'json');
}
};
/**
* 展示互动
* ————————————————————————————
*/


/**
* 光慧
* ————————————————————————————
*/

/**
* CC
* ————————————————————————————
*/
//检查CC添加直播间表单提交
admin.addCcLiveRoom=function(form) {
if(form.subject.value.replace(/^ +| +$/g,'')==''){
ui.error('直播课时名称不能为空!');
return false;
}

if(form.speaker_id.value.replace(/^ +| +$/g,'')==''){
ui.error('演讲人不能为空!');
return false;
}
if(form.startDate.value.replace(/^ +| +$/g,'')==''){
ui.error('开始时间不能为空!');
return false;
}
if(form.invalidDate.value.replace(/^ +| +$/g,'')==''){
ui.error('结束时间不能为空!');
return false;
}
if(form.maxNum.value.replace(/^ +| +$/g,'')==''){
ui.error('最大并发不能为空!');
return false;
}
if(form.uiMode.value.replace(/^ +| +$/g,'')==''){
ui.error('直播模版不能为空!');
return false;
}
if(form.teacherToken.value.replace(/^ +| +$/g,'')==''){
ui.error('老师口令不能为空!');
return false;
}
if(isNaN(form.assistantToken.value)){
ui.error('助教口令不能为空!');
return false;
}
if(form.studentToken.value.replace(/^ +| +$/g,'')==''){
ui.error('学生口令不能为空!');
return false;
}
if(form.description.value.replace(/^ +| +$/g,'')==''){
ui.error('直播课时信息不能为空!');
return false;
}

return true;
};

//cc开启推流  只有视频的可以开启
admin.uiMode=function(obj) {
var uiMode = obj.value;
console.log(uiMode);
if(uiMode == 1){
$('#dl_webJoin').show();
}else{
$('#dl_webJoin').hide();
}
}
admin.checkLoadCC=function() {
$('#dl_webJoin').hide();
}

admin.openMount= function(id,property) {
if(!id){
ui.error("请选择操作的课程");
return false;
}
if( confirm('你确定要挂载此课程吗？') ){
$.post(U('classroom/AdminVideo/openMount'),{id:id,is_mount:property},function(txt){
if(txt.status == 0){
ui.error(txt.info);
} else {
ui.success(txt.info);
window.location.href = window.location.href;
}
},'json');
}
return true;
};

admin.closeMount= function(id,property) {
if(!id){
ui.error("请选择操作的课程");
return false;
}
if( confirm('你确定要取消挂载此课程吗？') ){
$.post(U('classroom/AdminVideo/closeMount'),{id:id,is_mount:property},function(txt){
if(txt.status == 0){
ui.error(txt.info);
} else {
ui.success(txt.info);
window.location.href = window.location.href;
}
},'json');
}
return true;
};



//处理折扣
admin.mzPrice = function(_id,action,title,type){
var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
$.post(U('live/AdminConPrice/'+action),{id:id},function(msg){
admin.ajaxReload(msg);
},'json');
};
////处理折扣
admin.delPrice=function(_id,action){
var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
if(id==''){
ui.error("折扣ID不能为空");
return false;
}
if(!confirm("是否确认删除此折扣？")){
return false;
}
if(_id) {
$.post(U('live/AdminConPrice/' + action), {id: id}, function (msg) {
admin.ajaxReload(msg);
}, 'json');
}else
{
$.post(U('live/AdminConPrice/' + action), {ids: id}, function (msg) {
admin.ajaxReload(msg);
}, 'json');
}
}

//直播课审核
admin.crossVideo = function(id,cross,title,uid){
if(!id){
ui.error("id不能为null");
return false;
}
if(!confirm("是否确认？")){
return false;
}

if(cross){
var dotype = '已审核';
}

var videotype = '课程';
var uid = uid;
var content =title;

$.post(U('public/Message/doPost'),{to:uid,content:content,dotype:dotype,videotype:videotype},function(msg) {

},'json');


$.post(U('classroom/AdminVideo/crossVideo'),{id:id,cross:cross},function(txt){
if(txt.status == 0){
ui.error(txt.info);
} else {
ui.success(txt.info);
window.location.href = window.location.href;
}
},'json');
};
