<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\CourseOfflineOrder;
use App\Model\CourseOfflineOrderReadjust;
use App\Model\CourseOfflinePlan;
use App\Model\CourseOnline;
use App\Constants\ErrorCode;
use App\Model\CourseOnlineChildCollect;
use App\Model\Member;
use App\Model\OrderGoods;
use App\Model\OrderInfo;
use App\Model\OrderRefund;
use App\Model\ShareRecord;
use App\Model\VipCardOrder;
use App\Model\VisitRecord;
use Hyperf\DbConnection\Db;

class HomeService extends BaseService
{
    /**
     * 控制台
     * @param array $params
     * @return array
     */
    public function home(array $params): array
    {
        $searchDateTag = $params['date_tag'];
        $searchDateMin = $params['date_min'];
        $searchDateMax = $params['date_max'];
        $nowDate = date('Y-m-d H:i:s');

        $startDate = '2022-01-01 00:00:00';
        $endDate = '2122-12-31 23:59:59';
        if($searchDateTag !== null){
            switch ($searchDateTag){
                case 1:
                    $startDate = date("Y-m-d 00:00:00",strtotime("-1 day"));
                    $endDate = date("Y-m-d 23:59:59",strtotime("-1 day"));
                    break;
                case 2:
                    $startDate = date("Y-m-d H:i:s", mktime(0,0,0,(int)date("m"),(int)date("d")-(int)date("w")+1,(int)date("Y")));
                    $endDate = date("Y-m-d H:i:s", mktime(23,59,59,(int)date("m"),(int)date("d")-(int)date("w")+7,(int)date("Y")));
                    break;
                case 3:
                    $startDate = date("Y-m-d H:i:s",mktime(0,0,0,(int)date('m'),1,(int)date('Y')));
                    $endDate = date("Y-m-d H:i:s",mktime(23,59,59,(int)date('m'),(int)date('t'),(int)date('Y')));
                    break;
            }
        }
        if($searchDateMin !== null && $searchDateMax !== null){
            $startDate = date("Y-m-d 00:00:00",strtotime($searchDateMin));
            $endDate = date("Y-m-d 23:59:59",strtotime($searchDateMax));
        }
        //商品发货
        $orderGoodsDeliverCount = OrderGoods::query()->where(['pay_status'=>1,'order_status'=>0,'shipping_status'=>0])->count();
        //售后处理
        $orderRefundCount = OrderRefund::query()->whereIn('status',[10,15,20])->count();

        //新增用户
        $memberCount = Member::query()->whereBetween('created_at',[$startDate,$endDate])->count();
        //开卡人数
        $vipCardOrderCount0 = Db::connection('jkc_edu')->select('SELECT count(DISTINCT member_id) as cou FROM vip_card_order WHERE pay_status=? AND order_type=? AND created_at BETWEEN ? AND ?',[1,1,$startDate,$endDate]);
        $vipCardOrderCount0 = !empty($vipCardOrderCount0[0]) ? $vipCardOrderCount0[0]['cou'] : 0;
        //会员卡首购人数
        $vipCardOrderCount1 = VipCardOrder::query()->select(['id'])->where(['pay_status'=>1,'order_type'=>1])->whereBetween('created_at',[$startDate,$endDate])->groupBy('member_id')->havingRaw('count(id)=1')->get();
        $vipCardOrderCount1 = count($vipCardOrderCount1);
        //会员卡复购人数
        $vipCardOrderCount2 = VipCardOrder::query()->select(['id'])->where(['pay_status'=>1,'order_type'=>1])->whereBetween('created_at',[$startDate,$endDate])->groupBy('member_id')->havingRaw('count(id)>1')->get();
        $vipCardOrderCount2 = count($vipCardOrderCount2);
        //访问量
        $visitRecordCount = VisitRecord::query()->whereBetween('created_at',[$startDate,$endDate])->count();
        //微信分享量
        $shareRecordCount1 = ShareRecord::query()->where(['type'=>1])->whereBetween('created_at',[$startDate,$endDate])->count();

        //邀请分享量
        $shareRecordCount2 = ShareRecord::query()->where(['type'=>2])->whereBetween('created_at',[$startDate,$endDate])->count();
        //邀请好友体验券
        $invitationExperienceCardCount = VipCardOrder::query()->where(['order_type'=>3])->whereBetween('created_at',[$startDate,$endDate])->count();
        //邀请好友体验券使用
        $invitationExperienceCardUsedCount = VipCardOrder::query()->where(['order_type'=>3,'currency_course_used'=>1])->whereBetween('created_at',[$startDate,$endDate])->count();
        //新人礼包体验券
        $entrantExperienceCardCount = VipCardOrder::query()->where(['order_type'=>2])->whereBetween('created_at',[$startDate,$endDate])->count();
        //新人礼包体验券使用
        $entrantExperienceCardUsedCount = VipCardOrder::query()->where(['order_type'=>2,'currency_course_used'=>1])->whereBetween('created_at',[$startDate,$endDate])->count();

        //线下开课总数
        $courseOfflinePlanCount = CourseOfflinePlan::query()->where(['is_deleted'=>0])->whereBetween('created_at',[$startDate,$endDate])->count();
        //线下开课人数
        $courseOfflineOrderCount = CourseOfflineOrder::query()->where(['pay_status'=>1,'order_status'=>0])->whereBetween('created_at',[$startDate,$endDate])->count();
        //门店实到人数
        $courseOfflineOrderCount2 = CourseOfflineOrder::query()->where(['pay_status'=>1,'order_status'=>0,'class_status'=>1])->whereBetween('created_at',[$startDate,$endDate])->count();
        //旷课人数
        $courseOfflineOrderCount3 = CourseOfflineOrder::query()->where([['pay_status','=',1],['order_status','=',0],['class_status','=',0],['end_at','<',$nowDate]])->whereBetween('created_at',[$startDate,$endDate])->count();
        //取消人数
        $courseOfflineOrderCount4 = CourseOfflineOrder::query()->where([['pay_status','=',1],['order_status','=',2]])->whereBetween('created_at',[$startDate,$endDate])->count();

        //精品小班报名
        $studyPlanEnrollmentCount1 = Db::connection('jkc_edu')->select('SELECT count(DISTINCT member_id) as cou FROM study_plan_enrollment WHERE `type`=? AND created_at BETWEEN ? AND ?',[1,$startDate,$endDate]);
        $studyPlanEnrollmentCount1 = !empty($studyPlanEnrollmentCount1[0]) ? $studyPlanEnrollmentCount1[0]['cou'] : 0;
        //竞赛班报名
        $studyPlanEnrollmentCount2 = Db::connection('jkc_edu')->select('SELECT count(DISTINCT member_id) as cou FROM study_plan_enrollment WHERE `type`=? AND created_at BETWEEN ? AND ?',[2,$startDate,$endDate]);
        $studyPlanEnrollmentCount2 = !empty($studyPlanEnrollmentCount2[0]) ? $studyPlanEnrollmentCount2[0]['cou'] : 0;
        //主题科创班报名
        $studyPlanEnrollmentCount3 = Db::connection('jkc_edu')->select('SELECT count(DISTINCT member_id) as cou FROM study_plan_enrollment WHERE `type`=? AND created_at BETWEEN ? AND ?',[3,$startDate,$endDate]);
        $studyPlanEnrollmentCount3 = !empty($studyPlanEnrollmentCount3[0]) ? $studyPlanEnrollmentCount3[0]['cou'] : 0;

        //营收总数
        $revenueTotal1 = VipCardOrder::query()->where(['pay_status'=>1])->sum('price');
        $revenueTotal2 = OrderInfo::query()->where(['pay_status'=>1])->sum('amount');
        $revenueTotal = bcadd((string)$revenueTotal1,(string)$revenueTotal2,2);
        $courseRevenueTotal = VipCardOrder::query()->where(['pay_status'=>1])->sum('price');
        $goodsRevenueTotal = OrderInfo::query()->where(['pay_status'=>1])->sum('amount');
        $goodsRefundTotal = OrderRefund::query()->where(['status'=>25])->sum('amount');

        $returnData = [
            'order_goods_deliver' => $orderGoodsDeliverCount,
            'order_goods_refund' => $orderRefundCount,
            'vip_card_first_order' => $vipCardOrderCount1,
            'vip_card_double_order' => $vipCardOrderCount2,
            'vip_card_order' => $vipCardOrderCount0,
            'new_member' => $memberCount,
            'visit_count' => $visitRecordCount,
            'share_count' => $shareRecordCount1,
            'invitation_share_count' => $shareRecordCount2,
            'invitation_experience_card_count' => $invitationExperienceCardCount,
            'invitation_experience_card_used_count' => $invitationExperienceCardUsedCount,
            'entrant_experience_card_count' => $entrantExperienceCardCount,
            'entrant_experience_card_used_count' => $entrantExperienceCardUsedCount,
            'course_offline_section' => $courseOfflinePlanCount,
            'course_offline_order' => $courseOfflineOrderCount2,
            'course_offline_order_pre' => $courseOfflineOrderCount,
            'course_offline_order_not' => $courseOfflineOrderCount3,
            'course_offline_order_cancel' => $courseOfflineOrderCount4,
            'revenue_total' => $revenueTotal,
            'course_revenue_total' => $courseRevenueTotal,
            'goods_revenue_total' => $goodsRevenueTotal,
            'goods_refund_total' => $goodsRefundTotal,
            'study_plan_enrollment_count1' => $studyPlanEnrollmentCount1,
            'study_plan_enrollment_count2' => $studyPlanEnrollmentCount2,
            'study_plan_enrollment_count3' => $studyPlanEnrollmentCount3,
        ];
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $returnData];
    }
}