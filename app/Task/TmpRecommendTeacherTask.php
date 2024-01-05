<?php
declare(strict_types=1);

namespace App\Task;

use App\Logger\Log;
use App\Model\CommissionRelation;
use App\Model\CourseOfflineOrder;
use App\Model\Member;
use App\Model\MemberBelongTo;
use App\Model\OrderGoods;
use App\Model\OrderInfo;
use App\Model\OrderRefund;
use App\Model\Teacher;
use App\Model\VipCardOrder;
use App\Model\VipCardOrderRefund;
use App\Snowflake\IdGenerator;

class TmpRecommendTeacherTask extends BaseTask
{
    public function recommendTeacherExecute(): void
    {
        $orderInfoList = OrderInfo::query()
            ->select(['id','recommend_member_id'])
            ->where([['recommend_member_id','<>',0],['recommend_teacher_id','=',0]])
            ->get();
        $orderInfoList = $orderInfoList->toArray();

        foreach($orderInfoList as $value){
            $recommendMemberId = $value['recommend_member_id'];
            $teacherId = 0;
            $physicalStoreId = 0;

            $recommendMemberInfo = Member::query()->select(['mobile'])->where(['id'=>$recommendMemberId])->first();
            $recommendMemberInfo = $recommendMemberInfo?->toArray();
            $recommendMemberMobile = $recommendMemberInfo['mobile'] ?? null;
            if($recommendMemberMobile !== null){
                //推荐老师信息
                $recommendTeacherInfo = Teacher::query()->select(['id','physical_store_id'])->where(['mobile'=>$recommendMemberMobile])->first();
                $recommendTeacherInfo = $recommendTeacherInfo?->toArray();
                $teacherId = $recommendTeacherInfo['id'] ?? 0;
                $physicalStoreId = $recommendTeacherInfo['physical_store_id'] ?? 0;
            }
            if($teacherId !== 0){
                OrderInfo::query()->where(['id'=>$value['id'],'recommend_teacher_id'=>0])->update(['recommend_teacher_id'=>$teacherId,'recommend_physical_store_id'=>$physicalStoreId]);
            }
        }


        $vipCardOrderList = VipCardOrder::query()
            ->select(['id','recommend_member_id'])
            ->where([['recommend_member_id','<>',0],['recommend_teacher_id','=',0]])
            ->get();
        $vipCardOrderList = $vipCardOrderList->toArray();

        foreach($vipCardOrderList as $value){
            $recommendMemberId = $value['recommend_member_id'];
            $teacherId = 0;
            $physicalStoreId = 0;

            $recommendMemberInfo = Member::query()->select(['mobile'])->where(['id'=>$recommendMemberId])->first();
            $recommendMemberInfo = $recommendMemberInfo?->toArray();
            $recommendMemberMobile = $recommendMemberInfo['mobile'] ?? null;
            if($recommendMemberMobile !== null){
                //推荐老师信息
                $recommendTeacherInfo = Teacher::query()->select(['id','physical_store_id'])->where(['mobile'=>$recommendMemberMobile])->first();
                $recommendTeacherInfo = $recommendTeacherInfo?->toArray();
                $teacherId = $recommendTeacherInfo['id'] ?? 0;
                $physicalStoreId = $recommendTeacherInfo['physical_store_id'] ?? 0;
            }
            if($teacherId !== 0){
                VipCardOrder::query()->where(['id'=>$value['id'],'recommend_teacher_id'=>0])->update(['recommend_teacher_id'=>$teacherId,'recommend_physical_store_id'=>$physicalStoreId]);
            }
        }
    }

    public function vipCardOrderRefundExecute(): void
    {
        $vipCardOrderRefundList = VipCardOrderRefund::query()
            ->select(['id','vip_card_order_id'])
            ->where(['member_id'=>0])
            ->get();
        $vipCardOrderRefundList = $vipCardOrderRefundList->toArray();

        foreach($vipCardOrderRefundList as $value){
            $vipCardOrderInfo = VipCardOrder::query()
                ->select(['member_id'])
                ->where(['id'=>$value['vip_card_order_id']])
                ->first();
            $vipCardOrderInfo = $vipCardOrderInfo->toArray();
            VipCardOrderRefund::query()->where(['id'=>$value['id'],'member_id'=>0])->update(['member_id'=>$vipCardOrderInfo['member_id']]);
        }
    }

    public function vipCardOrderCourseUnitPriceExecute(): void
    {
        $vipCardOrderList = VipCardOrder::query()
            ->select(['id','course1','course2','course3','currency_course','price'])
            ->where(['course_unit_price'=>0])
            ->get();
        $vipCardOrderList = $vipCardOrderList->toArray();

        foreach($vipCardOrderList as $value){
            $totalCourse = $value['course1']+$value['course2']+$value['course3']+$value['currency_course'];
            if($value['price'] != 0 && $totalCourse != 0){
                $courseUnitPrice = bcdiv((string)$value['price'],(string)$totalCourse,2);
                VipCardOrder::query()->where(['id'=>$value['id'],'course_unit_price'=>0])->update(['course_unit_price'=>$courseUnitPrice]);
            }
        }

        $vipCardOrderList2 = VipCardOrder::query()
            ->select(['id','course_unit_price'])
            ->where([['course_unit_price','<>',0]])
            ->get();
        $vipCardOrderList2 = $vipCardOrderList2->toArray();

        foreach($vipCardOrderList2 as $value){
            $courseOfflineOrderExists = CourseOfflineOrder::query()->where(['vip_card_order_id'=>$value['id'],'course_unit_price'=>0])->exists();
            if($value['course_unit_price'] != 0 && $courseOfflineOrderExists === true){
                CourseOfflineOrder::query()->where(['vip_card_order_id'=>$value['id'],'course_unit_price'=>0])->update(['course_unit_price'=>$value['course_unit_price']]);
            }
        }

    }

    public function orderRefundExecute(): void
    {
        $orderRefundList = OrderRefund::query()
            ->select(['id','order_goods_id'])
            ->where(['order_info_id'=>0])
            ->get();
        $orderRefundList = $orderRefundList->toArray();

        foreach($orderRefundList as $value){

            $orderGoodsInfo = OrderGoods::query()
                ->select(['order_info_id'])
                ->where(['id'=>$value['order_goods_id']])
                ->first();
            $orderGoodsInfo = $orderGoodsInfo->toArray();

            OrderRefund::query()->where(['id'=>$value['id'],'order_info_id'=>0])->update(['order_info_id'=>$orderGoodsInfo['order_info_id']]);
        }
    }

    public function memberBelongToExecute(): void
    {
        $nowTime = date('Y-m-d H:i:s');
        $memberList = Member::query()
            ->select(['id'])
            ->where([['created_at','<=','2023-09-25 12:00:00']])
            ->get();
        $memberList = $memberList->toArray();

        foreach($memberList as $value){
            $memberId = $value['id'];
            $teacherId = 0;
            $physicalStoreId = 0;
            $insertMemberBelongToData = [];

            $memberBelongToExists = MemberBelongTo::query()->where(['member_id'=>$memberId])->exists();
            if($memberBelongToExists === true){
                continue;
            }
            $commissionRelationInfo = CommissionRelation::query()
                ->select(['parent_member_id'])
                ->where([['member_id','=',$memberId],['parent_member_id','<>',0],['expire_at','>=',$nowTime]])
                ->orderBy('expire_at','desc')
                ->first();

            $courseOfflineOrderInfo = CourseOfflineOrder::query()
                ->select(['physical_store_id'])
                ->where(['member_id'=>$memberId,'pay_status'=>1,'order_status'=>0,'class_status'=>1])
                ->orderBy('start_at','desc')
                ->first();

            $courseOfflineOrderInfo2 = CourseOfflineOrder::query()
                ->select(['physical_store_id'])
                ->where(['member_id'=>$memberId,'pay_status'=>1,'order_status'=>0])
                ->orderBy('created_at','desc')
                ->first();

            $vipCardOrderInfo = VipCardOrder::query()
                ->select(['physical_store_id'])
                ->where(['member_id'=>$memberId,'pay_status'=>1,'order_status'=>0])
                ->orderBy('created_at')
                ->first();

            if(!empty($commissionRelationInfo)){
                $commissionRelationInfo = $commissionRelationInfo->toArray();
                $parentId = $commissionRelationInfo['parent_member_id'];

                $recommendMemberInfo = Member::query()->select(['mobile'])->where(['id'=>$parentId])->first();
                $recommendMemberInfo = $recommendMemberInfo?->toArray();
                $recommendMemberMobile = $recommendMemberInfo['mobile'] ?? null;
                if($recommendMemberMobile !== null){
                    //推荐老师信息
                    $recommendTeacherInfo = Teacher::query()->select(['id','physical_store_id'])->where(['mobile'=>$recommendMemberMobile])->first();
                    $recommendTeacherInfo = $recommendTeacherInfo?->toArray();
                    $teacherId = $recommendTeacherInfo['id'] ?? 0;
                    $physicalStoreId = $recommendTeacherInfo['physical_store_id'] ?? 0;
                }
                if($teacherId !== 0){
                    $insertMemberBelongToData['id'] = IdGenerator::generate();
                    $insertMemberBelongToData['member_id'] = $memberId;
                    $insertMemberBelongToData['physical_store_id'] = $physicalStoreId;
                    $insertMemberBelongToData['teacher_id'] = $teacherId;
                    $insertMemberBelongToData['teacher_member_id'] = $parentId;
                    MemberBelongTo::query()->insert($insertMemberBelongToData);
                }
            }else if(!empty($courseOfflineOrderInfo)){
                $courseOfflineOrderInfo = $courseOfflineOrderInfo->toArray();
                $insertMemberBelongToData['id'] = IdGenerator::generate();
                $insertMemberBelongToData['member_id'] = $memberId;
                $insertMemberBelongToData['physical_store_id'] = $courseOfflineOrderInfo['physical_store_id'];
                MemberBelongTo::query()->insert($insertMemberBelongToData);
            }else if(!empty($courseOfflineOrderInfo2)){
                $courseOfflineOrderInfo2 = $courseOfflineOrderInfo2->toArray();
                $insertMemberBelongToData['id'] = IdGenerator::generate();
                $insertMemberBelongToData['member_id'] = $memberId;
                $insertMemberBelongToData['physical_store_id'] = $courseOfflineOrderInfo2['physical_store_id'];
                MemberBelongTo::query()->insert($insertMemberBelongToData);
            }else if(!empty($vipCardOrderInfo)){
                $vipCardOrderInfo = $vipCardOrderInfo->toArray();
                $insertMemberBelongToData['id'] = IdGenerator::generate();
                $insertMemberBelongToData['member_id'] = $memberId;
                $insertMemberBelongToData['physical_store_id'] = $vipCardOrderInfo['physical_store_id'];
                MemberBelongTo::query()->insert($insertMemberBelongToData);
            }
        }


    }
}

