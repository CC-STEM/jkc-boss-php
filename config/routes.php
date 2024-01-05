<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
use Hyperf\HttpServer\Router\Router;
use App\Middleware\AuthMiddleware;

//登录
Router::addGroup('/login/',function (){
    Router::post('code',[\App\Controller\AuthController::class, 'smsCodeSend']);
    Router::post('mobile',[\App\Controller\AuthController::class, 'mobileLogin']);
    Router::post('selected_identity',[\App\Controller\AuthController::class, 'selectedAdminsIdentity']);
    Router::post('out',[\App\Controller\AuthController::class, 'loginOut'],['middleware' => [AuthMiddleware::class]]);

});

//首页
Router::get('/', [\App\Controller\IndexController::class, 'index'], ['middleware' => [AuthMiddleware::class]]);

//课程分类
Router::addGroup('/course_category/',function (){
    Router::post('add_offline',[\App\Controller\CourseCategoryController::class, 'addCourseOfflineCategory']);
    Router::get('list_offline',[\App\Controller\CourseCategoryController::class, 'courseOfflineCategoryList']);
    Router::post('del_offline',[\App\Controller\CourseCategoryController::class, 'deleteCourseOfflineCategory']);
    Router::get('list_online',[\App\Controller\CourseCategoryController::class, 'courseOnlineCategoryList']);
    Router::post('edit_offline_name',[\App\Controller\CourseCategoryController::class, 'editCourseOfflineCategoryName']);

}, ['middleware' => [AuthMiddleware::class]]);

//线下课程
Router::addGroup('/offline_course/',function (){
    Router::post('add',[\App\Controller\CourseController::class, 'addCourseOffline']);
    Router::post('edit',[\App\Controller\CourseController::class, 'editCourseOffline']);
    Router::get('list',[\App\Controller\CourseController::class, 'courseOfflineList']);
    Router::get('detail',[\App\Controller\CourseController::class, 'courseOfflineDetail']);
    Router::post('del',[\App\Controller\CourseController::class, 'deleteCourseOffline']);
    Router::post('plan_add',[\App\Controller\CourseController::class, 'addCourseOfflinePlan']);
    Router::post('plan_edit',[\App\Controller\CourseController::class, 'editCourseOfflinePlan']);
    Router::get('plan_list',[\App\Controller\CourseController::class, 'courseOfflinePlanList']);
    Router::get('plan_detail',[\App\Controller\CourseController::class, 'courseOfflinePlanDetail']);
    Router::post('plan_del',[\App\Controller\CourseController::class, 'deleteCourseOfflinePlan']);
    Router::get('plan_sign_up_student',[\App\Controller\CourseController::class, 'courseOfflinePlanSignUpStudent']);
    Router::get('plan_arrive_student',[\App\Controller\CourseController::class, 'courseOfflinePlanArriveStudent']);
    Router::get('plan_classroom_situation',[\App\Controller\CourseController::class, 'courseOfflinePlanClassroomSituation']);
    Router::post('age_tag_add',[\App\Controller\CourseController::class, 'addCourseOfflineAgeTag']);
    //Router::post('age_tag_edit',[\App\Controller\CourseController::class, 'editCourseOfflineAgeTag']);
    //Router::post('age_tag_del',[\App\Controller\CourseController::class, 'deleteCourseOfflineAgeTag']);
    Router::get('age_tag',[\App\Controller\CourseController::class, 'courseOfflineAgeTag']);

}, ['middleware' => [AuthMiddleware::class]]);

//线上课程
Router::addGroup('/online_course/',function (){
    Router::post('add',[\App\Controller\CourseController::class, 'addCourseOnline']);
    Router::post('edit',[\App\Controller\CourseController::class, 'editCourseOnline']);
    Router::get('list',[\App\Controller\CourseController::class, 'courseOnlineList']);
    Router::get('detail',[\App\Controller\CourseController::class, 'courseOnlineDetail']);
    Router::post('del',[\App\Controller\CourseController::class, 'deleteCourseOnline']);
    Router::post('child_add',[\App\Controller\CourseController::class, 'addCourseOnlineChild']);
    Router::post('child_edit',[\App\Controller\CourseController::class, 'editCourseOnlineChild']);
    Router::get('child_list',[\App\Controller\CourseController::class, 'courseOnlineChildList']);
    Router::get('child_detail',[\App\Controller\CourseController::class, 'courseOnlineChildDetail']);
    Router::post('child_del',[\App\Controller\CourseController::class, 'deleteCourseOnlineChild']);
    Router::get('collect_list',[\App\Controller\CourseController::class, 'courseOnlineChildCollectList']);
    Router::get('collect_detail',[\App\Controller\CourseController::class, 'courseOnlineChildCollectDetail']);
    Router::post('collect_handle',[\App\Controller\CourseController::class, 'handleCourseOnlineChildCollect']);

}, ['middleware' => [AuthMiddleware::class]]);

//课程订单
Router::addGroup('/course_order/',function (){
    Router::get('reservation_member',[\App\Controller\CourseOrderController::class, 'courseOfflineOrderCreateMemberInfo']);
    Router::get('reservation_screen_list',[\App\Controller\CourseOrderController::class, 'courseOfflineOrderCreateScreenList']);
    Router::post('submit',[\App\Controller\CourseOrderController::class, 'courseOfflineCreateOrder']);
    Router::get('list',[\App\Controller\CourseOrderController::class, 'courseOfflineOrderList']);
    Router::post('readjust',[\App\Controller\CourseOrderController::class, 'courseOfflineOrderReadjust']);
    Router::get('readjust_screen_list',[\App\Controller\CourseOrderController::class, 'courseOfflineOrderReadjustScreenList']);
    Router::get('offline_order_readjust_list',[\App\Controller\CourseOrderController::class, 'courseOfflineOrderReadjustList']);
    Router::get('offline_order_readjust_detail',[\App\Controller\CourseOrderController::class, 'courseOfflineOrderReadjustDetail']);
    Router::post('offline_order_readjust_handle',[\App\Controller\CourseOrderController::class, 'handleCourseOfflineOrderReadjust']);
    Router::post('cancel',[\App\Controller\CourseOrderController::class, 'courseOfflineOrderCancel']);
    Router::get('offline_order_export',[\App\Controller\CourseOrderController::class, 'courseOfflineOrderExport']);
    Router::get('evaluation_list',[\App\Controller\CourseOrderController::class, 'courseOfflineOrderEvaluationList']);

}, ['middleware' => [AuthMiddleware::class]]);
Router::post('/course_order/cancel_back_door',[\App\Controller\CourseOrderController::class, 'courseOfflineOrderCancelBackDoor']);

//教具商品
Router::addGroup('/ta_goods/',function (){
    Router::post('add',[\App\Controller\GoodsController::class, 'addTeachingAidsGoods']);
    Router::post('edit',[\App\Controller\GoodsController::class, 'editTeachingAidsGoods']);
    Router::post('online',[\App\Controller\GoodsController::class, 'onlineTeachingAidsGoods']);
    Router::post('offline',[\App\Controller\GoodsController::class, 'offlineTeachingAidsGoods']);
    Router::post('del',[\App\Controller\GoodsController::class, 'deleteTeachingAidsGoods']);
    Router::post('topping',[\App\Controller\GoodsController::class, 'toppingTeachingAidsGoods']);
    Router::get('list',[\App\Controller\GoodsController::class, 'teachingAidsGoodsList']);
    Router::get('detail',[\App\Controller\GoodsController::class, 'teachingAidsGoodsDetail']);
    Router::post('add_prop_name',[\App\Controller\GoodsController::class, 'addPropName']);
    Router::get('prop_name_list',[\App\Controller\GoodsController::class, 'propNameList']);
    Router::post('add_prop_value',[\App\Controller\GoodsController::class, 'addPropValue']);
    Router::get('prop_value_list',[\App\Controller\GoodsController::class, 'propValueList']);
    Router::post('update_sham_csale',[\App\Controller\GoodsController::class, 'updateShamCsale']);

}, ['middleware' => [AuthMiddleware::class]]);

//教具订单
Router::addGroup('/ta_order/',function (){
    Router::get('list',[\App\Controller\OrderController::class, 'teachingAidsOrderList']);
    Router::get('detail',[\App\Controller\OrderController::class, 'teachingAidsOrderDetail']);
    Router::post('extend_receipt',[\App\Controller\OrderController::class, 'teachingAidsOrderExtendReceipt']);
    Router::post('shipment',[\App\Controller\OrderController::class, 'teachingAidsOrderShipment']);
    Router::get('refund_list',[\App\Controller\OrderController::class, 'teachingAidsRefundOrderList']);
    Router::get('refund_detail',[\App\Controller\OrderController::class, 'teachingAidsRefundOrderDetail']);
    Router::post('refund_handle',[\App\Controller\OrderController::class, 'handleTeachingAidsRefundOrder']);
    Router::get('export',[\App\Controller\OrderController::class, 'teachingAidsOrderExport']);

}, ['middleware' => [AuthMiddleware::class]]);

//会员卡
Router::addGroup('/vip_card/',function (){
    Router::post('add',[\App\Controller\VipCardController::class, 'addVipCard']);
    Router::post('edit',[\App\Controller\VipCardController::class, 'editVipCard']);
    Router::get('list',[\App\Controller\VipCardController::class, 'vipCardList']);
    Router::get('newcomer_list',[\App\Controller\VipCardController::class, 'newcomerVipCardList']);
    Router::get('detail',[\App\Controller\VipCardController::class, 'vipCardDetail']);
    Router::post('delete',[\App\Controller\VipCardController::class, 'deleteVipCard']);
    Router::get('order_list',[\App\Controller\VipCardController::class, 'vipCardOrderList']);
    Router::get('order_export',[\App\Controller\VipCardController::class, 'vipCardOrderExport']);
    Router::post('order_refund',[\App\Controller\VipCardController::class, 'vipCardOrderRefund']);
    Router::post('sort',[\App\Controller\VipCardController::class, 'vipCardSort']);
    Router::get('gift_order_list',[\App\Controller\VipCardController::class, 'giftVipCardOrderList']);
    Router::get('gift_order_detail',[\App\Controller\VipCardController::class, 'giftVipCardOrderDetail']);

}, ['middleware' => [AuthMiddleware::class]]);

//门店
Router::addGroup('/physical_store/',function (){
    Router::post('add',[\App\Controller\PhysicalStoreController::class, 'addPhysicalStore']);
    Router::post('edit',[\App\Controller\PhysicalStoreController::class, 'editPhysicalStore']);
    Router::get('list',[\App\Controller\PhysicalStoreController::class, 'physicalStoreList']);
    Router::get('detail',[\App\Controller\PhysicalStoreController::class, 'physicalStoreDetail']);
    Router::post('delete',[\App\Controller\PhysicalStoreController::class, 'deletePhysicalStore']);
    Router::post('address_verify',[\App\Controller\PhysicalStoreController::class, 'addressVerify']);
    Router::post('goal_setting',[\App\Controller\PhysicalStoreController::class, 'goalSetting']);

}, ['middleware' => [AuthMiddleware::class]]);

//教室
Router::addGroup('/classroom/',function (){
    Router::get('list',[\App\Controller\ClassroomController::class, 'classroomList']);

}, ['middleware' => [AuthMiddleware::class]]);

//老师
Router::addGroup('/teacher/',function (){
    Router::post('add',[\App\Controller\TeacherController::class, 'addTeacher']);
    Router::post('edit',[\App\Controller\TeacherController::class, 'editTeacher']);
    Router::post('edit_rank',[\App\Controller\TeacherController::class, 'editTeacherRank']);
    Router::post('del',[\App\Controller\TeacherController::class, 'deleteTeacher']);
    Router::get('list',[\App\Controller\TeacherController::class, 'teacherList']);
    Router::get('search_list',[\App\Controller\TeacherController::class, 'teacherSearchList']);
    Router::get('rank',[\App\Controller\TeacherController::class, 'teacherRankData']);
    Router::get('platform_search_list',[\App\Controller\TeacherController::class, 'platformTeacherSearchList']);

}, ['middleware' => [AuthMiddleware::class]]);

//装修
Router::addGroup('/fit_up/',function (){
    Router::post('add_home_ad',[\App\Controller\FitUpController::class, 'addHomeAd']);
    Router::get('home_ad_list',[\App\Controller\FitUpController::class, 'homeAdList']);
    Router::post('delete_home_ad',[\App\Controller\FitUpController::class, 'deleteHomeAd']);
    Router::post('add_home_boutique_course',[\App\Controller\FitUpController::class, 'addHomeBoutiqueCourse']);
    Router::get('home_boutique_course_list',[\App\Controller\FitUpController::class, 'homeBoutiqueCourseList']);
    Router::post('delete_home_boutique_course',[\App\Controller\FitUpController::class, 'deleteHomeBoutiqueCourse']);
    Router::post('add_market_info',[\App\Controller\FitUpController::class, 'addMarketInfo']);
    Router::post('edit_market_info',[\App\Controller\FitUpController::class, 'editMarketInfo']);
    Router::get('market_info_list',[\App\Controller\FitUpController::class, 'marketInfoList']);
    Router::get('market_info_detail',[\App\Controller\FitUpController::class, 'marketInfoDetail']);
    Router::post('delete_market_info',[\App\Controller\FitUpController::class, 'deleteMarketInfo']);
    Router::post('add_article_theme',[\App\Controller\FitUpController::class, 'addArticleTheme']);
    Router::get('article_theme_list',[\App\Controller\FitUpController::class, 'articleThemeList']);
    Router::post('delete_article_theme',[\App\Controller\FitUpController::class, 'deleteArticleTheme']);
    Router::post('add_article',[\App\Controller\FitUpController::class, 'addArticle']);
    Router::post('edit_article',[\App\Controller\FitUpController::class, 'editArticle']);
    Router::get('article_list',[\App\Controller\FitUpController::class, 'articleList']);
    Router::get('article_detail',[\App\Controller\FitUpController::class, 'articleDetail']);
    Router::post('delete_article',[\App\Controller\FitUpController::class, 'deleteArticle']);
    Router::post('add_course_detail_set_up',[\App\Controller\FitUpController::class, 'addCourseDetailSetUp']);
    Router::get('course_detail_set_up_list',[\App\Controller\FitUpController::class, 'courseDetailSetUpList']);

}, ['middleware' => [AuthMiddleware::class]]);

//会员
Router::addGroup('/member/',function (){
    Router::get('list',[\App\Controller\MemberController::class, 'memberList']);
    Router::get('detail',[\App\Controller\MemberController::class, 'memberDetail']);
    Router::get('course_online',[\App\Controller\MemberController::class, 'courseOnlineCollectList']);
    Router::get('course_online_child',[\App\Controller\MemberController::class, 'courseOnlineChildCollectList']);
    Router::get('course_offline',[\App\Controller\MemberController::class, 'courseOfflineOrderList']);
    Router::get('teaching_aids',[\App\Controller\MemberController::class, 'teachingAidsOrderList']);
    Router::get('vip_card',[\App\Controller\MemberController::class, 'vipCardOrderList']);
    Router::post('vip_card_send',[\App\Controller\MemberController::class, 'vipCardOrderCreate']);
    Router::get('invitation_relation',[\App\Controller\MemberController::class, 'invitationRelationTree']);
    Router::post('edit_name',[\App\Controller\MemberController::class, 'editMemberName']);
    Router::get('search_list',[\App\Controller\MemberController::class, 'memberSearchList']);
    Router::post('add_virtual',[\App\Controller\MemberController::class, 'addVirtualMember']);
    Router::get('export',[\App\Controller\MemberController::class, 'memberExport']);

}, ['middleware' => [AuthMiddleware::class]]);

//优惠券
Router::addGroup('/coupon/',function (){
    Router::post('add',[\App\Controller\CouponController::class, 'addCouponTemplate']);
    Router::post('edit',[\App\Controller\CouponController::class, 'editCouponTemplate']);
    Router::get('list',[\App\Controller\CouponController::class, 'couponTemplateList']);
    Router::get('detail',[\App\Controller\CouponController::class, 'couponTemplateDetail']);
    Router::post('delete',[\App\Controller\CouponController::class, 'deleteCouponTemplate']);
    Router::get('issued_list',[\App\Controller\CouponController::class, 'issuedCouponList']);
    Router::post('issued',[\App\Controller\CouponController::class, 'issuedCoupon']);
    Router::post('decentralize',[\App\Controller\CouponController::class, 'couponTemplateDecentralizePhysicalStore']);
    Router::post('allstaff_issued',[\App\Controller\CouponController::class, 'physicalStoreAllStaffIssuedCoupon']);

}, ['middleware' => [AuthMiddleware::class]]);

//薪资模板
Router::addGroup('/salary_template/',function (){
    Router::post('add',[\App\Controller\SalaryTemplateController::class, 'addSalaryTemplate']);
    Router::post('edit',[\App\Controller\SalaryTemplateController::class, 'editSalaryTemplate']);
    Router::get('list',[\App\Controller\SalaryTemplateController::class, 'salaryTemplateList']);
    Router::post('delete',[\App\Controller\SalaryTemplateController::class, 'deleteSalaryTemplate']);
    Router::get('used_list',[\App\Controller\SalaryTemplateController::class, 'salaryTemplateUsedList']);

}, ['middleware' => [AuthMiddleware::class]]);

//薪资管理列表
Router::addGroup('/salary_bill/',function (){
    Router::get('list',[\App\Controller\SalaryBillController::class, 'salaryBillSearchList']);
    Router::post('adjust',[\App\Controller\SalaryBillController::class, 'salaryBillAdjust']);
    Router::get('export',[\App\Controller\SalaryBillController::class, 'salaryBillExport']);
    Router::get('bill_detailed_list',[\App\Controller\SalaryBillController::class, 'salaryBillDetailedList']);

}, ['middleware' => [AuthMiddleware::class]]);

//门店大管理员
Router::addGroup('/store_senior_admins/',function (){
    Router::post('add',[\App\Controller\PhysicalStoreAdminsController::class, 'addPhysicalStoreSeniorAdmins']);
    Router::post('edit',[\App\Controller\PhysicalStoreAdminsController::class, 'editPhysicalStoreSeniorAdmins']);
    Router::post('del',[\App\Controller\PhysicalStoreAdminsController::class, 'deletePhysicalStoreSeniorAdmins']);
    Router::get('list',[\App\Controller\PhysicalStoreAdminsController::class, 'physicalStoreSeniorAdminsList']);

}, ['middleware' => [AuthMiddleware::class]]);

//组织(管理员)
Router::addGroup('/organization/',function (){
    Router::post('add',[\App\Controller\OrganizationController::class, 'addAdmins']);
    Router::post('edit',[\App\Controller\OrganizationController::class, 'editAdmins']);
    Router::post('del',[\App\Controller\OrganizationController::class, 'deleteAdmins']);
    Router::get('list',[\App\Controller\OrganizationController::class, 'adminsList']);
    Router::get('detail',[\App\Controller\OrganizationController::class, 'adminsDetail']);

}, ['middleware' => [AuthMiddleware::class]]);

//权限
Router::addGroup('/permissions/',function (){
    Router::post('add',[\App\Controller\AdminPermissionsController::class, 'addAdminPermissions']);
    Router::post('edit',[\App\Controller\AdminPermissionsController::class, 'editAdminPermissions']);
    Router::post('del',[\App\Controller\AdminPermissionsController::class, 'deleteAdminPermissions']);
    Router::get('list',[\App\Controller\AdminPermissionsController::class, 'adminPermissionsList']);
    Router::get('detail',[\App\Controller\AdminPermissionsController::class, 'adminPermissionsDetail']);
    Router::get('route_list',[\App\Controller\AdminPermissionsController::class, 'adminRouteList']);

}, ['middleware' => [AuthMiddleware::class]]);

//管理员
Router::addGroup('/admins/',function (){
    Router::get('info',[\App\Controller\AdminsController::class, 'adminsInfo']);

}, ['middleware' => [AuthMiddleware::class]]);

//文件上传
Router::addGroup('/upload/',function (){
    Router::post('cos',[\App\Controller\UploadController::class, 'cosUpload']);

}, ['middleware' => [AuthMiddleware::class]]);

//微信支付
Router::addGroup('/pay/callback/wx/',function (){
    Router::post('goods_refund',[\App\Controller\PayController::class, 'goodsRefundCallbackWx']);
    Router::post('vip_card_refund',[\App\Controller\PayController::class, 'vipCardRefundCallbackWx']);
});

//超级管理员新增路由权限刷新
Router::addGroup('/permissions_refresh/',function (){
    Router::post('boss',[\App\Controller\AdminPermissionsController::class, 'adminPermissionsAddRouteBoss']);
    Router::post('business',[\App\Controller\AdminPermissionsController::class, 'adminPermissionsAddRouteBusiness']);

});

//减免卷
Router::addGroup('/discount_ticket/',function (){
    Router::get('template_detail',[\App\Controller\DiscountTicketController::class, 'templateDetail']);
    Router::post('edit_template',[\App\Controller\DiscountTicketController::class, 'editTemplate']);
    Router::get('list',[\App\Controller\DiscountTicketController::class, 'list']);
    Router::post('invalid',[\App\Controller\DiscountTicketController::class, 'invalid']);
    Router::get('detail',[\App\Controller\DiscountTicketController::class, 'detail']);
    Router::get('invite_friend_detail',[\App\Controller\DiscountTicketController::class, 'inviteFriendDetail']);
    Router::post('issued',[\App\Controller\DiscountTicketController::class, 'issued']);

}, ['middleware' => [AuthMiddleware::class]]);

//待处理事项
Router::addGroup('/member_event/',function (){
    Router::get('trigger_action_set_list',[\App\Controller\MemberEventController::class, 'triggerActionSetList']);
    Router::get('auto_handle_judgment_criteria_set_list',[\App\Controller\MemberEventController::class, 'autoHandleJudgmentCriteriaSetList']);
    Router::post('add',[\App\Controller\MemberEventController::class, 'addMemberEvent']);
    Router::post('edit',[\App\Controller\MemberEventController::class, 'editMemberEvent']);
    Router::post('delete',[\App\Controller\MemberEventController::class, 'deleteMemberEvent']);
    Router::get('detail',[\App\Controller\MemberEventController::class, 'memberEventDetail']);
    Router::get('list',[\App\Controller\MemberEventController::class, 'memberEventList']);
    Router::get('all_list',[\App\Controller\MemberEventController::class, 'allMemberEventList']);
    Router::get('customer_table_list',[\App\Controller\MemberEventController::class, 'customerTableList']);
    Router::get('customer_list',[\App\Controller\MemberEventController::class, 'customerList']);
    Router::post('allocation_belong_to',[\App\Controller\MemberEventController::class, 'allocationMemberBelongTo']);
    Router::post('complete_event_followup',[\App\Controller\MemberEventController::class, 'completeEventFollowup']);
    Router::post('trigger_switch',[\App\Controller\MemberEventController::class, 'memberEventSwitch']);
    Router::get('complete_event_detail',[\App\Controller\MemberEventController::class, 'memberEventCompleteDetail']);
    Router::get('followup_list',[\App\Controller\MemberEventController::class, 'followupList']);
    Router::get('complete_list',[\App\Controller\MemberEventController::class, 'memberEventCompleteList']);
    Router::get('member_vip_card_order_list',[\App\Controller\MemberEventController::class, 'memberVipCardOrderList']);
    Router::get('member_course_offline_order_list',[\App\Controller\MemberEventController::class, 'memberCourseOfflineOrderList']);
    Router::post('member_followup_note',[\App\Controller\MemberEventController::class, 'memberFollowupNote']);

}, ['middleware' => [AuthMiddleware::class]]);

//会员标签
Router::addGroup('/member_tag/',function (){
    Router::post('add_template',[\App\Controller\MemberTagController::class, 'addMemberTagTemplate']);
    Router::post('edit_template',[\App\Controller\MemberTagController::class, 'editMemberTagTemplate']);
    Router::post('del_template',[\App\Controller\MemberTagController::class, 'deleteMemberTagTemplate']);
    Router::get('template_list',[\App\Controller\MemberTagController::class, 'memberTagTemplateList']);
    Router::get('template_relation',[\App\Controller\MemberTagController::class, 'memberTagTemplateRelationList']);
    Router::get('template',[\App\Controller\MemberTagController::class, 'memberTagTemplate']);
    Router::post('add',[\App\Controller\MemberTagController::class, 'addMemberTag']);
    Router::post('del',[\App\Controller\MemberTagController::class, 'deleteMemberTag']);

}, ['middleware' => [AuthMiddleware::class]]);


Router::get('/favicon.ico', function () {
    return '';
});
