<?php

declare(strict_types=1);

namespace App\Service;

use App\Event\CourseOfflinePayRegistered;
use App\Logger\Log;
use App\Model\CourseOffline;
use App\Model\CourseOfflineClassroomSituation;
use App\Model\CourseOfflineOrder;
use App\Model\CourseOfflineOrderEvaluation;
use App\Model\CourseOfflineOrderReadjust;
use App\Constants\ErrorCode;
use App\Model\CourseOfflinePlan;
use App\Model\Member;
use App\Model\PhysicalStore;
use App\Model\VipCardOrder;
use App\Model\VipCardOrderDynamicCourse;
use App\Model\VipCardOrderPhysicalStore;
use App\Snowflake\IdGenerator;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\EventDispatcher\EventDispatcherInterface;

class CourseOrderService extends BaseService
{
    #[Inject]
    private EventDispatcherInterface $eventDispatcher;

    /**
     * 约课筛选列表
     * @param array $params
     * @return array
     */
    public function courseOfflineOrderCreateScreenList(array $params): array
    {
        $courseType = $params['course_type'];
        $physicalStoreId = $params['physical_store_id'];
        $teacherId = $params['teacher_id'];
        $classStartDateMin = $params['class_start_date_min'];
        $classStartDateMax = $params['class_start_date_max'];
        $nowTime = time();
        $offset = $this->offset;
        $limit = $this->limit;

        if($physicalStoreId === null || $courseType === null){
            return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => ['list'=>[],'count'=>0]];
        }
        $model = CourseOfflinePlan::query()
            ->leftJoin('course_offline', 'course_offline_plan.course_offline_id', '=', 'course_offline.id');
        $where = [['course_offline_plan.is_deleted','=',0],['course_offline_plan.class_end_time','>',$nowTime],['course_offline_plan.physical_store_id','=',$physicalStoreId]];
        if (!empty($teacherId)) {
            $where[] = ['course_offline_plan.teacher_id','=',$teacherId];
        }
        if($courseType != 4){
            $where[] = ['course_offline.type','=',$courseType];
        }
        if($classStartDateMin !== null && $classStartDateMax !== null){
            $model->whereBetween('course_offline_plan.class_start_time',[strtotime($classStartDateMin),strtotime($classStartDateMax)]);
        }
        $count = $model->where($where)->count();
        $courseOfflinePlanList = $model
            ->select(['course_offline.name','course_offline.type','course_offline.suit_age_min','course_offline.suit_age_max','course_offline_plan.id','course_offline_plan.batch_no','course_offline_plan.class_start_time', 'course_offline_plan.teacher_name'])
            ->where($where)
            ->whereRaw('course_offline_plan.classroom_capacity > course_offline_plan.sign_up_num')
            ->offset($offset)->limit($limit)
            ->get();
        $courseOfflinePlanList = $courseOfflinePlanList->toArray();
        foreach($courseOfflinePlanList as $key=>$value){
            $courseOfflinePlanList[$key]['class_start_time'] = date('Y-m-d H:i',$value['class_start_time']);
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => ['list'=>$courseOfflinePlanList,'count'=>$count]];
    }

    /**
     * 约课会员信息
     * @param array $params
     * @return array
     */
    public function courseOfflineOrderCreateMemberInfo(array $params): array
    {
        $mobile = $params['mobile'];
        $memberId = $params['member_id'];
        $nowDate = date('Y-m-d H:i:s');

        if (!empty($mobile)) {
            $where = ['mobile'=>$mobile];
        } else if (!empty($memberId)) {
            $where = ['id' => $memberId];
        } else {
            return ['code' => ErrorCode::WARNING, 'msg' => '手机号和昵称必须填写一个！', 'data' => null];
        }
        $memberInfo = Member::query()
            ->select(['id','age'])
            ->where($where)
            ->first();
        if(empty($memberInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '用户信息错误', 'data' => null];
        }
        $memberInfo = $memberInfo->toArray();
        $memberId = $memberInfo['id'];

        //会员卡信息
        $vipCardOrderList = VipCardOrder::query()
            ->select(['id','course1','course1_used','course2','course2_used','course3','course3_used','currency_course','currency_course_used'])
            ->where([['member_id','=',$memberId],['pay_status','=',1],['order_status','=',0],['expire_at','>',$nowDate]])
            ->orderBy('expire_at')
            ->get();
        $vipCardOrderList = $vipCardOrderList->toArray();
        $vipCardOrderIdArray = array_column($vipCardOrderList,'id');
        $vipCardOrderDynamicCourseList = VipCardOrderDynamicCourse::query()
            ->select(['vip_card_order_id','course','course_used','type'])
            ->whereIn('vip_card_order_id',$vipCardOrderIdArray)
            ->get();
        $vipCardOrderDynamicCourseList = $vipCardOrderDynamicCourseList->toArray();
        $vipCardOrderDynamicCourseList = $this->functions->arrayGroupBy($vipCardOrderDynamicCourseList,'vip_card_order_id');
        //会员卡账户信息
        $totalCourse1 = 0;
        $totalCourse2 = 0;
        $totalCourse3 = 0;
        $totalCurrencyCourse = 0;
        $totalDynamicCourse1 = 0;
        $totalDynamicCourse2 = 0;
        $totalDynamicCourse3 = 0;
        foreach($vipCardOrderList as $value){
            $vipCardOrderDynamicCourse = $vipCardOrderDynamicCourseList[$value['id']] ?? [];
            foreach($vipCardOrderDynamicCourse as $item){
                $surplusSectionDynamicCourse = $item['course']-$item['course_used'];
                if($item['type'] == 1){
                    $totalDynamicCourse1 += $surplusSectionDynamicCourse;
                }else if($item['type'] == 2){
                    $totalDynamicCourse2 += $surplusSectionDynamicCourse;
                }else if($item['type'] == 3){
                    $totalDynamicCourse3 += $surplusSectionDynamicCourse;
                }
            }
            $surplusSectionCourse1 = $value['course1']-$value['course1_used'];
            $surplusSectionCourse2 = $value['course2']-$value['course2_used'];
            $surplusSectionCourse3 = $value['course3']-$value['course3_used'];
            $surplusSectionCurrencyCourse = $value['currency_course']-$value['currency_course_used'];
            $totalCourse1 = $surplusSectionCourse1>0 ? $totalCourse1+$surplusSectionCourse1 : $totalCourse1;
            $totalCourse2 = $surplusSectionCourse2>0 ? $totalCourse2+$surplusSectionCourse2 : $totalCourse2;
            $totalCourse3 = $surplusSectionCourse3>0 ? $totalCourse3+$surplusSectionCourse3 : $totalCourse3;
            $totalCurrencyCourse = $surplusSectionCurrencyCourse>0 ? $totalCurrencyCourse+$surplusSectionCurrencyCourse : $totalCurrencyCourse;
        }
        $totalCourse1 += $totalDynamicCourse1;
        $totalCourse2 += $totalDynamicCourse2;
        $totalCourse3 += $totalDynamicCourse3;
        $returnData = [
            'id' => $memberInfo['id'],
            'age' => $memberInfo['age'],
            'course1' => $totalCourse1,
            'course2' => $totalCourse2,
            'course3' => $totalCourse3,
            'currency_course' => $totalCurrencyCourse
        ];
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $returnData];
    }

    /**
     * 创建线下课程订单
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function courseOfflineCreateOrder(array $params): array
    {
        $isSample = $params['is_sample'] ?? 0;
        $courseType = $params['course_type'] ?? 0;
        $memberId = $params['member_id'];
        $courseOfflinePlan = $params['course_offline_plan'];
        $nowTime = time();
        $nowDate = date('Y-m-d H:i:s');
        $weekArray = [7,1,2,3,4,5,6];

        //排课信息
        $courseOfflinePlanList = CourseOfflinePlan::query()
            ->select(['id','course_offline_id','course_category_id','physical_store_id','teacher_id','classroom_id','classroom_name','teacher_name','class_start_time','class_end_time','batch_no','section_no','theme_type'])
            ->whereIn('id',$courseOfflinePlan)->where(['is_deleted'=>0])
            ->get();
        $courseOfflinePlanList = $courseOfflinePlanList->toArray();
        if(empty($courseOfflinePlanList)){
            return ['code' => ErrorCode::WARNING, 'msg' => '课程信息错误1', 'data' => null];
        }
        $physicalStoreId = $courseOfflinePlanList[0]['physical_store_id'];
        $themeType = $courseOfflinePlanList[0]['theme_type'];

        //数据校验
        $courseOfflinePlanIdArray = array_column($courseOfflinePlanList,'id');
        $courseOfflineOrderExists = CourseOfflineOrder::query()->where(['member_id'=>$memberId,'pay_status'=>1,'order_status'=>0])->whereIn('course_offline_plan_id',$courseOfflinePlanIdArray)->exists();
        if($courseOfflineOrderExists === true){
            return ['code' => ErrorCode::WARNING, 'msg' => '课程不能重复预约', 'data' => null];
        }
        //课程信息
        $courseOfflineIdArray = array_column($courseOfflinePlanList,'course_offline_id');
        if($isSample == 1){
            $courseOfflineList = CourseOffline::query()
                ->select(['id','name','price','img_url','type'])
                ->whereIn('id',$courseOfflineIdArray)
                ->get();
        }else{
            $courseOfflineList = CourseOffline::query()
                ->select(['id','name','price','img_url','type'])
                ->whereIn('id',$courseOfflineIdArray)
                ->where(['type'=>$courseType,'theme_type'=>$themeType])
                ->get();
        }
        $courseOfflineList = $courseOfflineList->toArray();
        $combineCourseOfflineKey = array_column($courseOfflineList,'id');
        $courseOfflineList = array_combine($combineCourseOfflineKey,$courseOfflineList);

        //门店信息
        $physicalStoreInfo = PhysicalStore::query()
            ->select(['name'])
            ->where(['id'=>$physicalStoreId])
            ->first();
        if(empty($physicalStoreInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '门店信息错误', 'data' => null];
        }
        $physicalStoreInfo = $physicalStoreInfo->toArray();

        //课程总金额
        $totalPrice = '0';
        //线下课程节数数据
        $courseOfflineSectionData = [];
        foreach($courseOfflinePlanList as $key=>$value){
            $courseOfflineId = $value['course_offline_id'];
            $classStartTime = $value['class_start_time'];
            if(!isset($courseOfflineSectionData[$courseOfflineId])){
                $courseOfflineSectionData[$courseOfflineId] = 0;
            }
            if(!isset($courseOfflineList[$courseOfflineId])){
                return ['code' => ErrorCode::WARNING, 'msg' => '课程信息错误2', 'data' => null];
            }
            $courseOfflineInfo = $courseOfflineList[$courseOfflineId];
            if($value['class_start_time']<=$nowTime){
                return ['code' => ErrorCode::WARNING, 'msg' => '课程'.$courseOfflineInfo['name'].'已开课，已无法报名', 'data' => null];
            }
            $courseOfflineSectionData[$courseOfflineId] += 1;
            $price = $courseOfflineInfo['price'];
            $totalPrice = bcadd($totalPrice,$price,2);

            $courseOfflinePlanList[$key]['w'] = $weekArray[date("w",$classStartTime)];
            $courseOfflinePlanList[$key]['course_type'] = $courseOfflineInfo['type'];
            $courseOfflinePlanList[$key]['course_name'] = $courseOfflineInfo['name'];
            $courseOfflinePlanList[$key]['img_url'] = $courseOfflineInfo['img_url'];
            $courseOfflinePlanList[$key]['price'] = $price;
        }
        //会员卡信息
        if($isSample == 1){
            $vipCardOrderPhysicalStoreList = VipCardOrderPhysicalStore::query()
                ->leftJoin('vip_card_order','vip_card_order_physical_store.vip_card_order_id','=','vip_card_order.id')
                ->select(['vip_card_order_physical_store.vip_card_order_id'])
                ->where([['vip_card_order.member_id','=',$memberId],['vip_card_order.pay_status','=',1],['vip_card_order.expire_at','>=',$nowDate],['vip_card_order.order_status','=',0],['vip_card_order_physical_store.physical_store_id','=',$physicalStoreId]])
                ->whereIn('vip_card_order.order_type',[2,3])
                ->get();
            $vipCardOrderPhysicalStoreList = $vipCardOrderPhysicalStoreList->toArray();
            $vipCardOrderIdPhysicalStoreArray = array_column($vipCardOrderPhysicalStoreList,'vip_card_order_id');
            if(empty($vipCardOrderIdPhysicalStoreArray)){
                $vipCardOrderModel = VipCardOrder::query()
                    ->select(['id','currency_course','currency_course_used','course_unit_price'])
                    ->where([['member_id','=',$memberId],['pay_status','=',1],['expire_at','>=',$nowDate],['order_status','=',0],['applicable_store_type','=',1]])
                    ->whereIn('order_type',[2,3]);
            }else{
                $vipCardOrderModel = VipCardOrder::query()
                    ->select(['id','currency_course','currency_course_used','course_unit_price'])
                    ->whereIn('id',$vipCardOrderIdPhysicalStoreArray)->orWhere(function ($query)use($memberId,$nowDate) {
                        $query->where([['member_id','=',$memberId],['pay_status','=',1],['expire_at','>=',$nowDate],['order_status','=',0],['applicable_store_type','=',1]])
                            ->whereIn('order_type',[2,3]);
                    });
            }
        }else{
            $vipCardOrderPhysicalStoreList = VipCardOrderPhysicalStore::query()
                ->leftJoin('vip_card_order','vip_card_order_physical_store.vip_card_order_id','=','vip_card_order.id')
                ->select(['vip_card_order_physical_store.vip_card_order_id'])
                ->where([['vip_card_order.member_id','=',$memberId],['vip_card_order.pay_status','=',1],['vip_card_order.expire_at','>=',$nowDate],['vip_card_order.order_status','=',0],['vip_card_order.card_theme_type','=',$themeType],['vip_card_order_physical_store.physical_store_id','=',$physicalStoreId]])
                ->whereIn('vip_card_order.order_type',[1,4])
                ->get();
            $vipCardOrderPhysicalStoreList = $vipCardOrderPhysicalStoreList->toArray();
            $vipCardOrderIdPhysicalStoreArray = array_column($vipCardOrderPhysicalStoreList,'vip_card_order_id');
            if(empty($vipCardOrderIdPhysicalStoreArray)){
                $vipCardOrderModel = VipCardOrder::query()
                    ->select(['id','course1','course1_used','course2','course2_used','course3','course3_used','course_unit_price'])
                    ->where([['member_id','=',$memberId],['pay_status','=',1],['expire_at','>=',$nowDate],['order_status','=',0],['applicable_store_type','=',1],['card_theme_type','=',$themeType]])
                    ->whereIn('order_type',[1,4]);
            }else{
                $vipCardOrderModel = VipCardOrder::query()
                    ->select(['id','course1','course1_used','course2','course2_used','course3','course3_used','course_unit_price'])
                    ->whereIn('id',$vipCardOrderIdPhysicalStoreArray)->orWhere(function ($query)use($memberId,$nowDate,$themeType) {
                        $query->where([['member_id','=',$memberId],['pay_status','=',1],['expire_at','>=',$nowDate],['order_status','=',0],['applicable_store_type','=',1],['card_theme_type','=',$themeType]])
                            ->whereIn('order_type',[1,4]);
                    });
            }
        }
        $vipCardOrderList = $vipCardOrderModel->orderBy('expire_at')->get();
        $vipCardOrderList = $vipCardOrderList->toArray();
        $vipCardOrderIdArray = array_column($vipCardOrderList,'id');
        $vipCardOrderDynamicCourseList = VipCardOrderDynamicCourse::query()
            ->select(['id','vip_card_order_id','course','course_used','type','week'])
            ->whereIn('vip_card_order_id',$vipCardOrderIdArray)
            ->get();
        $vipCardOrderDynamicCourseList = $vipCardOrderDynamicCourseList->toArray();
        $vipCardOrderDynamicCourseList = $this->functions->arrayGroupBy($vipCardOrderDynamicCourseList,'vip_card_order_id');

        $useVipCardOrderList = [];
        $useVipCardOrderChildList = [];
        foreach($courseOfflinePlanList as $key=>$value){
            $isPass = 0;
            $w = $value['w'];
            $useVipCardOrderInfo = [];
            $useVipCardOrderChildInfo = [];
            $useCourseUnitPrice = 0;
            $useVipCardOrderId = 0;
            $useVipCardOrderChildId = 0;
            foreach($vipCardOrderList as $k=>$item){
                $vipCardOrderId = $item['id'];
                $dynamicCourse = $vipCardOrderDynamicCourseList[$vipCardOrderId] ?? [];
                if($isSample == 1){
                    if($item['currency_course']>$item['currency_course_used']){
                        $vipCardOrderList[$k]['currency_course_used'] = $item['currency_course_used']+1;
                        $isPass = 1;
                        $useVipCardOrderInfo = ['id'=>$vipCardOrderId];
                        $useCourseUnitPrice = $item['course_unit_price'];
                        $useVipCardOrderId = $vipCardOrderId;
                    }
                }else{
                    switch ($courseType){
                        case 1:
                            foreach($dynamicCourse as $k1=>$item1){
                                $applyWeek = json_decode($item1['week'],true);
                                if($item1['type'] == 1 && $item1['course']>$item1['course_used'] && in_array($w,$applyWeek)){
                                    $dynamicCourse[$k1]['course_used'] = $item1['course_used']+1;
                                    $isPass = 1;
                                    $useVipCardOrderChildInfo = ['id'=>$item1['id']];
                                    $useVipCardOrderChildId = $item1['id'];
                                    break;
                                }
                            }
                            if($isPass === 0 && $item['course1']>$item['course1_used']){
                                $vipCardOrderList[$k]['course1_used'] = $item['course1_used']+1;
                                $isPass = 1;
                                $useVipCardOrderInfo = ['id'=>$vipCardOrderId];
                            }
                            if($isPass === 1){
                                $vipCardOrderDynamicCourseList[$vipCardOrderId] = $dynamicCourse;
                                $useCourseUnitPrice = $item['course_unit_price'];
                                $useVipCardOrderId = $vipCardOrderId;
                            }
                            break;
                        case 2:
                            foreach($dynamicCourse as $k1=>$item1){
                                $applyWeek = json_decode($item1['week'],true);
                                if($item1['type'] == 2 && $item1['course']>$item1['course_used'] && in_array($w,$applyWeek)){
                                    $dynamicCourse[$k1]['course_used'] = $item1['course_used']+1;
                                    $isPass = 1;
                                    $useVipCardOrderChildInfo = ['id'=>$item1['id']];
                                    $useVipCardOrderChildId = $item1['id'];
                                    break;
                                }
                            }
                            if($isPass === 0 && $item['course2']>$item['course2_used']){
                                $vipCardOrderList[$k]['course2_used'] = $item['course2_used']+1;
                                $isPass = 1;
                                $useVipCardOrderInfo = ['id'=>$vipCardOrderId];
                            }
                            if($isPass === 1){
                                $vipCardOrderDynamicCourseList[$vipCardOrderId] = $dynamicCourse;
                                $useCourseUnitPrice = $item['course_unit_price'];
                                $useVipCardOrderId = $vipCardOrderId;
                            }
                            break;
                        case 3:
                            foreach($dynamicCourse as $k1=>$item1){
                                $applyWeek = json_decode($item1['week'],true);
                                if($item1['type'] == 3 && $item1['course']>$item1['course_used'] && in_array($w,$applyWeek)){
                                    $dynamicCourse[$k1]['course_used'] = $item1['course_used']+1;
                                    $isPass = 1;
                                    $useVipCardOrderChildInfo = ['id'=>$item1['id']];
                                    $useVipCardOrderChildId = $item1['id'];
                                    break;
                                }
                            }
                            if($isPass === 0 && $item['course3']>$item['course3_used']){
                                $vipCardOrderList[$k]['course3_used'] = $item['course3_used']+1;
                                $isPass = 1;
                                $useVipCardOrderInfo = ['id'=>$vipCardOrderId];
                            }
                            if($isPass === 1){
                                $vipCardOrderDynamicCourseList[$vipCardOrderId] = $dynamicCourse;
                                $useCourseUnitPrice = $item['course_unit_price'];
                                $useVipCardOrderId = $vipCardOrderId;
                            }
                            break;
                    }
                }
                if($isPass === 1){
                    break;
                }
            }
            if($isPass === 0){
                return ['code' => ErrorCode::WARNING, 'msg' => '会员卡可预约次数不足', 'data' => null];
            }
            $courseOfflinePlanList[$key]['vip_card_order_id'] = $useVipCardOrderId;
            $courseOfflinePlanList[$key]['course_unit_price'] = $useCourseUnitPrice;
            $courseOfflinePlanList[$key]['vip_card_order_child_id'] = $useVipCardOrderChildId;
            if(!empty($useVipCardOrderInfo)){
                $useVipCardOrderList[] = $useVipCardOrderInfo;
            }
            if(!empty($useVipCardOrderChildInfo)){
                $useVipCardOrderChildList[] = $useVipCardOrderChildInfo;
            }
        }

        //订单数据
        $payCode = 'PPPAY';
        $payStatus = 1;
        $orderNo = $this->functions->orderNo();
        $insertCourseOfflineOrderData = [];
        foreach($courseOfflinePlanList as $value){
            $courseOfflineOrderInfo = [];
            $classStartTime = date('Y-m-d H:i:s',$value['class_start_time']);
            $classEndTime = date('Y-m-d H:i:s',$value['class_end_time']);
            if($value['vip_card_order_id'] == 0){
                return ['code' => ErrorCode::WARNING, 'msg' => '会员卡可预约次数不足', 'data' => null];
            }

            $courseOfflineOrderId = IdGenerator::generate();
            $courseOfflineOrderInfo['id'] = $courseOfflineOrderId;
            $courseOfflineOrderInfo['order_no'] = $orderNo;
            $courseOfflineOrderInfo['member_id'] = $memberId;
            $courseOfflineOrderInfo['batch_no'] = $value['batch_no'];
            $courseOfflineOrderInfo['section_no'] = $value['section_no'];
            $courseOfflineOrderInfo['course_category_id'] = $value['course_category_id'];
            $courseOfflineOrderInfo['course_offline_id'] = $value['course_offline_id'];
            $courseOfflineOrderInfo['course_offline_plan_id'] = $value['id'];
            $courseOfflineOrderInfo['classroom_id'] = $value['classroom_id'];
            $courseOfflineOrderInfo['teacher_id'] = $value['teacher_id'];
            $courseOfflineOrderInfo['physical_store_id'] = $value['physical_store_id'];
            $courseOfflineOrderInfo['physical_store_name'] = $physicalStoreInfo['name'];
            $courseOfflineOrderInfo['course_name'] = $value['course_name'];
            $courseOfflineOrderInfo['classroom_name'] = $value['classroom_name'];
            $courseOfflineOrderInfo['teacher_name'] = $value['teacher_name'];
            $courseOfflineOrderInfo['price'] = $value['price'];
            $courseOfflineOrderInfo['start_at'] = $classStartTime;
            $courseOfflineOrderInfo['end_at'] = $classEndTime;
            $courseOfflineOrderInfo['course_type'] = $courseType;
            $courseOfflineOrderInfo['pay_status'] = $payStatus;
            $courseOfflineOrderInfo['pay_code'] = $payCode;
            $courseOfflineOrderInfo['img_url'] = $value['img_url'];
            $courseOfflineOrderInfo['vip_card_order_id'] = $value['vip_card_order_id'];
            $courseOfflineOrderInfo['theme_type'] = $themeType;
            $courseOfflineOrderInfo['course_unit_price'] = $value['course_unit_price'];
            $courseOfflineOrderInfo['is_sample'] = $isSample;
            $courseOfflineOrderInfo['vip_card_order_child_id'] = $value['vip_card_order_child_id'];
            $insertCourseOfflineOrderData[] = $courseOfflineOrderInfo;
        }

        Db::connection('jkc_edu')->beginTransaction();
        try{
            Db::connection('jkc_edu')->table('course_offline_order')->insert($insertCourseOfflineOrderData);
            foreach($courseOfflinePlanList as $value){
                $_courseOfflinePlanId = $value['id'];
                $courseOfflinePlanAffected = Db::connection('jkc_edu')->update("UPDATE course_offline_plan SET sign_up_num = sign_up_num + ? WHERE id = ? AND classroom_capacity >= sign_up_num+1", [1, $_courseOfflinePlanId]);
                if(!$courseOfflinePlanAffected){
                    Db::connection('jkc_edu')->rollBack();
                    Log::get()->info("courseOfflineCreateOrder[{$memberId}#{$_courseOfflinePlanId}]:排课信息修改失败");
                    return ['code' => ErrorCode::FAILURE, 'msg' => '购买失败请重试', 'data' => null];
                }
            }
            foreach($courseOfflineSectionData as $id=>$value){
                Db::connection('jkc_edu')->update("UPDATE course_offline SET sign_up_num = sign_up_num + ? WHERE id = ?", [$value, $id]);
            }
            if($isSample == 1){
                foreach($useVipCardOrderList as $value){
                    $vipCardOrderId = $value['id'];
                    $deductNum = 1;
                    $vipCardOrderAffected = Db::connection('jkc_edu')->update("UPDATE vip_card_order SET currency_course_used = currency_course_used + ? WHERE id = ? AND currency_course >= currency_course_used+{$deductNum}", [$deductNum, $vipCardOrderId]);
                    if(!$vipCardOrderAffected){
                        Db::connection('jkc_edu')->rollBack();
                        Log::get()->info("courseOfflineCreateOrder[{$memberId}#{$vipCardOrderId}]:会员卡信息修改失败");
                        return ['code' => ErrorCode::FAILURE, 'msg' => '购买失败请重试', 'data' => null];
                    }
                }
            }else if($courseType == 1){
                foreach($useVipCardOrderList as $value){
                    $vipCardOrderId = $value['id'];
                    $vipCardOrderAffected = Db::connection('jkc_edu')->update("UPDATE vip_card_order SET course1_used = course1_used + ? WHERE id = ? AND course1 >= course1_used+1", [1, $vipCardOrderId]);
                    if(!$vipCardOrderAffected){
                        Db::connection('jkc_edu')->rollBack();
                        Log::get()->info("courseOfflineCreateOrder[{$memberId}#{$vipCardOrderId}]:会员卡信息修改失败");
                        return ['code' => ErrorCode::FAILURE, 'msg' => '购买失败请重试', 'data' => null];
                    }
                }
                foreach($useVipCardOrderChildList as $value){
                    $vipCardOrderId = $value['id'];
                    $vipCardOrderAffected = Db::connection('jkc_edu')->update("UPDATE vip_card_order_dynamic_course SET course_used = course_used + ? WHERE id = ? AND course >= course_used+1 AND `type`=1", [1, $vipCardOrderId]);
                    if(!$vipCardOrderAffected){
                        Db::connection('jkc_edu')->rollBack();
                        Log::get()->info("courseOfflineCreateOrder[{$memberId}#{$vipCardOrderId}]:会员卡信息修改失败2");
                        return ['code' => ErrorCode::FAILURE, 'msg' => '购买失败请重试', 'data' => null];
                    }
                }
            }else if($courseType == 2){
                foreach($useVipCardOrderList as $value){
                    $vipCardOrderId = $value['id'];
                    $vipCardOrderAffected = Db::connection('jkc_edu')->update("UPDATE vip_card_order SET course2_used = course2_used + ? WHERE id = ? AND course2 >= course2_used+1", [1, $vipCardOrderId]);
                    if(!$vipCardOrderAffected){
                        Db::connection('jkc_edu')->rollBack();
                        Log::get()->info("courseOfflineCreateOrder[{$memberId}#{$vipCardOrderId}]:会员卡信息修改失败");
                        return ['code' => ErrorCode::FAILURE, 'msg' => '购买失败请重试', 'data' => null];
                    }
                }
                foreach($useVipCardOrderChildList as $value){
                    $vipCardOrderId = $value['id'];
                    $vipCardOrderAffected = Db::connection('jkc_edu')->update("UPDATE vip_card_order_dynamic_course SET course_used = course_used + ? WHERE id = ? AND course >= course_used+1 AND `type`=2", [1, $vipCardOrderId]);
                    if(!$vipCardOrderAffected){
                        Db::connection('jkc_edu')->rollBack();
                        Log::get()->info("courseOfflineCreateOrder[{$memberId}#{$vipCardOrderId}]:会员卡信息修改失败2");
                        return ['code' => ErrorCode::FAILURE, 'msg' => '购买失败请重试', 'data' => null];
                    }
                }
            }else if($courseType == 3){
                foreach($useVipCardOrderList as $value){
                    $vipCardOrderId = $value['id'];
                    $vipCardOrderAffected = Db::connection('jkc_edu')->update("UPDATE vip_card_order SET course3_used = course3_used + ? WHERE id = ? AND course3 >= course3_used+1", [1, $vipCardOrderId]);
                    if(!$vipCardOrderAffected){
                        Db::connection('jkc_edu')->rollBack();
                        Log::get()->info("courseOfflineCreateOrder[{$memberId}#{$vipCardOrderId}]:会员卡信息修改失败");
                        return ['code' => ErrorCode::FAILURE, 'msg' => '购买失败请重试', 'data' => null];
                    }
                }
                foreach($useVipCardOrderChildList as $value){
                    $vipCardOrderId = $value['id'];
                    $vipCardOrderAffected = Db::connection('jkc_edu')->update("UPDATE vip_card_order_dynamic_course SET course_used = course_used + ? WHERE id = ? AND course >= course_used+1 AND `type`=3", [1, $vipCardOrderId]);
                    if(!$vipCardOrderAffected){
                        Db::connection('jkc_edu')->rollBack();
                        Log::get()->info("courseOfflineCreateOrder[{$memberId}#{$vipCardOrderId}]:会员卡信息修改失败2");
                        return ['code' => ErrorCode::FAILURE, 'msg' => '购买失败请重试', 'data' => null];
                    }
                }
            }
            Db::connection('jkc_edu')->commit();
        } catch(\Throwable $e){
            Db::connection('jkc_edu')->rollBack();
            throw new \Exception($e->getMessage(), 1);
        }
        $this->eventDispatcher->dispatch(new CourseOfflinePayRegistered((int)$memberId,(int)$isSample,$orderNo));
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 调课筛选列表
     * @param array $params
     * @return array
     */
    public function courseOfflineOrderReadjustScreenList(array $params): array
    {
        $courseOfflineOrderId = $params['course_offline_order_id'];
        $classStartDateMin = $params['class_start_date_min'];
        $classStartDateMax = $params['class_start_date_max'];
        $nowTime = time();

        $courseOfflineOrderInfo = CourseOfflineOrder::query()
            ->select(['course_offline_id','physical_store_id'])
            ->where(['id'=>$courseOfflineOrderId,'order_status'=>0,'pay_status'=>1])
            ->first();
        if(empty($courseOfflineOrderInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '订单信息错误', 'data' => null];
        }
        $courseOfflineOrderInfo = $courseOfflineOrderInfo->toArray();
        $courseOfflineId = $courseOfflineOrderInfo['course_offline_id'];
        $physicalStoreId = $courseOfflineOrderInfo['physical_store_id'];

        $courseOfflineInfo = CourseOffline::query()
            ->select(['type','course_category_id'])
            ->where(['id'=>$courseOfflineId])
            ->first();
        if(empty($courseOfflineInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '课程信息错误', 'data' => null];
        }
        $courseOfflineInfo = $courseOfflineInfo->toArray();
        $courseType = $courseOfflineInfo['type'];
        $courseCategoryId = $courseOfflineInfo['course_category_id'];

        if($courseType == 3){
            $courseOfflinePlanList = CourseOfflinePlan::query()
                ->leftJoin('course_offline', 'course_offline_plan.course_offline_id', '=', 'course_offline.id')
                ->select(['course_offline.name','course_offline.suit_age_min','course_offline.suit_age_max','course_offline_plan.id','course_offline_plan.class_start_time','course_offline_plan.batch_no'])
                ->where([['course_offline_plan.course_category_id','=',$courseCategoryId],['course_offline_plan.class_end_time','>',$nowTime],['course_offline_plan.physical_store_id','=',$physicalStoreId],['course_offline_plan.section_no','=',1],['course_offline_plan.is_deleted','=',0]])
                ->whereRaw('course_offline_plan.classroom_capacity > course_offline_plan.sign_up_num')
                ->get();
        }else{
            $model = CourseOfflinePlan::query();
            if($classStartDateMin !== null && $classStartDateMax !== null){
                $model->whereBetween('course_offline_plan.class_start_time',[strtotime($classStartDateMin),strtotime($classStartDateMax)]);
            }
            $courseOfflinePlanList = $model
                ->leftJoin('course_offline', 'course_offline_plan.course_offline_id', '=', 'course_offline.id')
                ->select(['course_offline.name','course_offline.suit_age_min','course_offline.suit_age_max','course_offline_plan.id','course_offline_plan.class_start_time'])
                ->where([['course_offline_plan.course_offline_id','=',$courseOfflineId],['course_offline_plan.class_end_time','>',$nowTime],['course_offline_plan.physical_store_id','=',$physicalStoreId],['course_offline_plan.is_deleted','=',0]])
                ->whereRaw('course_offline_plan.classroom_capacity > course_offline_plan.sign_up_num')
                ->get();
        }
        $courseOfflinePlanList = $courseOfflinePlanList->toArray();

        foreach($courseOfflinePlanList as $key=>$value){
            $courseOfflinePlanList[$key]['class_start_time'] = date('Y-m-d H:i',$value['class_start_time']);
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $courseOfflinePlanList];
    }

    /**
     * 调课
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function courseOfflineOrderReadjust(array $params): array
    {
        $oldCourseOfflineOrderId = $params['course_offline_order_id'];
        $courseOfflinePlanId = $params['course_offline_plan'];
        $batchNo = $params['batch_no'];
        $nowTime = time();

        $courseOfflineOrderInfo = CourseOfflineOrder::query()
            ->select(['member_id','course_type','pay_code','order_no','course_offline_id','course_offline_plan_id'])
            ->where(['id'=>$oldCourseOfflineOrderId,'order_status'=>0,'pay_status'=>1])
            ->first();
        if(empty($courseOfflineOrderInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '订单信息错误', 'data' => null];
        }
        $courseOfflineOrderInfo = $courseOfflineOrderInfo->toArray();
        $memberId = $courseOfflineOrderInfo['member_id'];
        $courseType = $courseOfflineOrderInfo['course_type'];
        $payCode = $courseOfflineOrderInfo['pay_code'];
        $oldOrderNo = $courseOfflineOrderInfo['order_no'];
        $oldCourseOfflinePlanId = $courseOfflineOrderInfo['course_offline_plan_id'];
        $courseOfflineId = $courseOfflineOrderInfo['course_offline_id'];

        //老排课订单
        if($courseType == 3){
            $oldCourseOfflineOrderList = CourseOfflineOrder::query()
                ->select(['course_offline_plan_id'])
                ->where(['order_no'=>$oldOrderNo])
                ->get();
            $oldCourseOfflineOrderList = $oldCourseOfflineOrderList->toArray();
        }
        //排课信息
        if($courseType == 3){
            $courseOfflineInfo = CourseOffline::query()
                ->select(['course_category_id'])
                ->where(['id'=>$courseOfflineId])
                ->first();
            if(empty($courseOfflineInfo)){
                return ['code' => ErrorCode::WARNING, 'msg' => '课程信息错误', 'data' => null];
            }
            $courseOfflineInfo = $courseOfflineInfo->toArray();
            $courseCategoryId = $courseOfflineInfo['course_category_id'];

            $courseOfflinePlanList = CourseOfflinePlan::query()
                ->select(['id','course_offline_id','course_category_id','physical_store_id','teacher_id','classroom_id','classroom_name','teacher_name','class_start_time','class_end_time'])
                ->where(['batch_no'=>$batchNo,'course_category_id'=>$courseCategoryId,'is_deleted'=>0])
                ->get();
        }else{
            $courseOfflinePlanList = CourseOfflinePlan::query()
                ->select(['id','course_offline_id','course_category_id','physical_store_id','teacher_id','classroom_id','classroom_name','teacher_name','class_start_time','class_end_time'])
                ->where(['id'=>$courseOfflinePlanId,'course_offline_id'=>$courseOfflineId,'is_deleted'=>0])
                ->get();
        }
        $courseOfflinePlanList = $courseOfflinePlanList->toArray();
        if(empty($courseOfflinePlanList)){
            return ['code' => ErrorCode::WARNING, 'msg' => '课程信息错误1', 'data' => null];
        }
        $physicalStoreId = $courseOfflinePlanList[0]['physical_store_id'];

        //课程信息
        $courseOfflineIdArray = array_column($courseOfflinePlanList,'course_offline_id');
        $courseOfflineList = CourseOffline::query()
            ->select(['id','price','img_url','name'])
            ->whereIn('id',$courseOfflineIdArray)->where(['type'=>$courseType])
            ->get();
        $courseOfflineList = $courseOfflineList->toArray();
        $combineCourseOfflineKey = array_column($courseOfflineList,'id');
        $courseOfflineList = array_combine($combineCourseOfflineKey,$courseOfflineList);

        //门店信息
        $physicalStoreInfo = PhysicalStore::query()
            ->select(['name'])
            ->where(['id'=>$physicalStoreId])
            ->first();
        if(empty($physicalStoreInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '门店信息错误', 'data' => null];
        }
        $physicalStoreInfo = $physicalStoreInfo->toArray();

        foreach($courseOfflinePlanList as $key=>$value){
            $courseOfflineId = $value['course_offline_id'];
            if(!isset($courseOfflineList[$courseOfflineId])){
                return ['code' => ErrorCode::WARNING, 'msg' => '课程信息错误2', 'data' => null];
            }
            $courseOfflineInfo = $courseOfflineList[$courseOfflineId];
            if($value['class_start_time']<=$nowTime){
                return ['code' => ErrorCode::WARNING, 'msg' => '课程'.$courseOfflineInfo['name'].'已开课，已无法报名', 'data' => null];
            }
            $courseOfflinePlanList[$key]['course_name'] = $courseOfflineInfo['name'];
            $courseOfflinePlanList[$key]['img_url'] = $courseOfflineInfo['img_url'];
            $courseOfflinePlanList[$key]['price'] = $courseOfflineInfo['price'];
        }

        $orderNo = $this->functions->orderNo();
        $insertCourseOfflineOrderData = [];
        foreach($courseOfflinePlanList as $value){
            $courseOfflineOrderInfo = [];
            $classStartTime = date('Y-m-d H:i:s',$value['class_start_time']);
            $classEndTime = date('Y-m-d H:i:s',$value['class_end_time']);

            $courseOfflineOrderId = IdGenerator::generate();
            $courseOfflineOrderInfo['id'] = $courseOfflineOrderId;
            $courseOfflineOrderInfo['order_no'] = $orderNo;
            $courseOfflineOrderInfo['member_id'] = $memberId;
            $courseOfflineOrderInfo['course_category_id'] = $value['course_category_id'];
            $courseOfflineOrderInfo['course_offline_id'] = $value['course_offline_id'];
            $courseOfflineOrderInfo['course_offline_plan_id'] = $value['id'];
            $courseOfflineOrderInfo['classroom_id'] = $value['classroom_id'];
            $courseOfflineOrderInfo['teacher_id'] = $value['teacher_id'];
            $courseOfflineOrderInfo['physical_store_id'] = $value['physical_store_id'];
            $courseOfflineOrderInfo['physical_store_name'] = $physicalStoreInfo['name'];
            $courseOfflineOrderInfo['course_name'] = $value['course_name'];
            $courseOfflineOrderInfo['classroom_name'] = $value['classroom_name'];
            $courseOfflineOrderInfo['teacher_name'] = $value['teacher_name'];
            $courseOfflineOrderInfo['price'] = $value['price'];
            $courseOfflineOrderInfo['start_at'] = $classStartTime;
            $courseOfflineOrderInfo['end_at'] = $classEndTime;
            $courseOfflineOrderInfo['course_type'] = $courseType;
            $courseOfflineOrderInfo['pay_status'] = 1;
            $courseOfflineOrderInfo['pay_code'] = $payCode;
            $courseOfflineOrderInfo['img_url'] = $value['img_url'];
            $insertCourseOfflineOrderData[] = $courseOfflineOrderInfo;
        }

        Db::connection('jkc_edu')->beginTransaction();
        try{
            Db::connection('jkc_edu')->table('course_offline_order')->insert($insertCourseOfflineOrderData);
            if($courseType == 3){
                $courseOfflineOrderAffected = Db::connection('jkc_edu')->table('course_offline_order')->where(['member_id'=>$memberId,'order_no'=>$oldOrderNo,'pay_status'=>1,'order_status'=>0])->update(['order_status'=>1]);
                if(!$courseOfflineOrderAffected){
                    Db::connection('jkc_edu')->rollBack();
                    return ['code' => ErrorCode::FAILURE, 'msg' => '调课失败请重试', 'data' => null];
                }

                foreach($courseOfflinePlanList as $value){
                    $_courseOfflinePlanId = $value['id'];
                    $courseOfflinePlanAffected = Db::connection('jkc_edu')->update("UPDATE course_offline_plan SET sign_up_num = sign_up_num + ? WHERE id = ? AND classroom_capacity >= sign_up_num+1", [1, $_courseOfflinePlanId]);
                    if(!$courseOfflinePlanAffected){
                        Db::connection('jkc_edu')->rollBack();
                        Log::get()->info("courseOfflineCreateOrder[{$memberId}#{$_courseOfflinePlanId}]:排课信息修改失败");
                        return ['code' => ErrorCode::FAILURE, 'msg' => '购买失败请重试', 'data' => null];
                    }
                }
                foreach($oldCourseOfflineOrderList as $value){
                    $_courseOfflinePlanId = $value['course_offline_plan_id'];
                    Db::connection('jkc_edu')->update("UPDATE course_offline_plan SET sign_up_num = sign_up_num - ? WHERE id = ?", [1, $_courseOfflinePlanId]);
                }
            }else{
                $courseOfflineOrderAffected = Db::connection('jkc_edu')->table('course_offline_order')->where(['member_id'=>$memberId,'id'=>$oldCourseOfflineOrderId,'pay_status'=>1,'order_status'=>0])->update(['order_status'=>1]);
                if(!$courseOfflineOrderAffected){
                    Db::connection('jkc_edu')->rollBack();
                    return ['code' => ErrorCode::FAILURE, 'msg' => '调课失败请重试', 'data' => null];
                }

                foreach($courseOfflinePlanList as $value){
                    $_courseOfflinePlanId = $value['id'];
                    $courseOfflinePlanAffected = Db::connection('jkc_edu')->update("UPDATE course_offline_plan SET sign_up_num = sign_up_num + ? WHERE id = ? AND classroom_capacity >= sign_up_num+1", [1, $_courseOfflinePlanId]);
                    if(!$courseOfflinePlanAffected){
                        Db::connection('jkc_edu')->rollBack();
                        Log::get()->info("courseOfflineCreateOrder[{$memberId}#{$_courseOfflinePlanId}]:排课信息修改失败");
                        return ['code' => ErrorCode::FAILURE, 'msg' => '购买失败请重试', 'data' => null];
                    }
                }
                Db::connection('jkc_edu')->update("UPDATE course_offline_plan SET sign_up_num = sign_up_num - ? WHERE id = ?", [1, $oldCourseOfflinePlanId]);
            }
            Db::connection('jkc_edu')->commit();
        } catch(\Throwable $e){
            Db::connection('jkc_edu')->rollBack();
            throw new \Exception($e->getMessage(), 1);
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 线下课程订单列表
     * @param array $params
     * @return array
     */
    public function courseOfflineOrderList(array $params): array
    {
        $classStatus = $params['class_status'];
        $physicalStoreId = $params['physical_store_id'];
        $classroomId = $params['classroom_id'];
        $teacherId = $params['teacher_id'];
        $classStartDateMin = $params['class_start_date_min'];
        $classStartDateMax = $params['class_start_date_max'];
        $memberName = $params['member_name'];
        $mobile = $params['mobile'];
        $offset = $this->offset;
        $limit = $this->limit;
        $nowDate = date('Y-m-d H:i:');

        $model = CourseOfflineOrder::query()
            ->leftJoin('member','course_offline_order.member_id','=','member.id')
            ->leftJoin('course_offline','course_offline_order.course_offline_id','=','course_offline.id')
            ->leftJoin('course_offline_plan','course_offline_order.course_offline_plan_id','=','course_offline_plan.id')
            ->leftJoin('vip_card_order','course_offline_order.vip_card_order_id','=','vip_card_order.id');
        $where = [['course_offline_order.pay_status','=',1]];
        if($classStatus == 0){
            $where[] = ['course_offline_order.end_at','>',$nowDate];
            $where[] = ['course_offline_order.order_status','=',0];
        }else{
            $model->where(function($query)use($nowDate){
                $query->where([['course_offline_order.end_at','<=',$nowDate]])->orWhere('course_offline_order.order_status', '=', 2);
            });
        }
        if($physicalStoreId !== null){
            $where[] = ['course_offline_order.physical_store_id','=',$physicalStoreId];
        }
        if($classroomId !== null){
            $where[] = ['course_offline_plan.classroom_id','=',$classroomId];
        }
        if($teacherId !== null){
            $where[] = ['course_offline_order.teacher_id','=',$teacherId];
        }
        if($memberName !== null){
            $where[] = ['member.name','like',"%{$memberName}%"];
        }
        if($mobile !== null){
            $where[] = ['member.mobile','=',$mobile];
        }
        if($classStartDateMin !== null && $classStartDateMax !== null){
            $model->whereBetween('course_offline_order.start_at',[$classStartDateMin,$classStartDateMax]);
        }
        $count = $model->where($where)->count();
        $courseOfflineOrderList = $model
            ->select(['course_offline_order.id','course_offline_order.course_offline_plan_id','course_offline_order.course_name','course_offline_order.course_offline_id','course_offline_order.start_at','course_offline_order.end_at','course_offline_order.physical_store_name','course_offline_order.classroom_name','course_offline_order.teacher_name','course_offline_order.class_status','course_offline_order.order_status','course_offline.type','course_offline.suit_age_min','course_offline.duration','course_offline.video_url','course_offline.suit_age_max','member.name as member_name','member.mobile','vip_card_order.recommend_code','vip_card_order.price','vip_card_order.course1','vip_card_order.course2','vip_card_order.course3'])
            ->offset($offset)->limit($limit)
            ->orderBy('course_offline_order.id','desc')
            ->get();
        $courseOfflineOrderList = $courseOfflineOrderList->toArray();

        foreach($courseOfflineOrderList as $key=>$value){
            $isSubmitWorks = 0;
            $checkResult = CourseOfflineClassroomSituation::query()->where(['course_offline_plan_id'=>$value['course_offline_plan_id']])->exists();
            if($checkResult !== false){
                $isSubmitWorks = 1;
            }
            $totalCourse = $value['course1']+$value['course2']+$value['course3'];
            $unitPrice = $totalCourse>0 ? bcdiv($value['price'],(string)$totalCourse) : '0';

            $courseOfflineOrderList[$key]['unit_price'] = $unitPrice;
            $courseOfflineOrderList[$key]['is_submit_works'] = $isSubmitWorks;
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => ['list'=>$courseOfflineOrderList,'count'=>$count]];
    }

    /**
     * 线下课程订单导出
     * @param array $params
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function courseOfflineOrderExport(array $params): array
    {
        $classStatus = $params['class_status'];
        $physicalStoreId = $params['physical_store_id'];
        $classroomId = $params['classroom_id'];
        $teacherId = $params['teacher_id'];
        $classStartDateMin = $params['class_start_date_min'];
        $classStartDateMax = $params['class_start_date_max'];
        $memberName = $params['member_name'];
        $mobile = $params['mobile'];
        $nowDate = date('Y-m-d H:i:');
        $fileName = 'coo'.date('YmdHis');

        $model = CourseOfflineOrder::query()
            ->leftJoin('member','course_offline_order.member_id','=','member.id')
            ->leftJoin('course_offline','course_offline_order.course_offline_id','=','course_offline.id')
            ->leftJoin('course_offline_plan','course_offline_order.course_offline_plan_id','=','course_offline_plan.id')
            ->leftJoin('vip_card_order','course_offline_order.vip_card_order_id','=','vip_card_order.id');
        $where = [['course_offline_order.pay_status','=',1]];
        if($classStatus == 0){
            $where[] = ['course_offline_order.end_at','>',$nowDate];
            $where[] = ['course_offline_order.order_status','=',0];
        }else{
            $model->where(function($query)use($nowDate){
                $query->where([['course_offline_order.end_at','<=',$nowDate]])->orWhere('course_offline_order.order_status', '=', 2);
            });
        }
        if($physicalStoreId !== null){
            $where[] = ['course_offline_order.physical_store_id','=',$physicalStoreId];
        }
        if($classroomId !== null){
            $where[] = ['course_offline_plan.classroom_id','=',$classroomId];
        }
        if($teacherId !== null){
            $where[] = ['course_offline_order.teacher_id','=',$teacherId];
        }
        if($memberName !== null){
            $where[] = ['member.name','=',$memberName];
        }
        if($mobile !== null){
            $where[] = ['member.mobile','=',$mobile];
        }
        if($classStartDateMin !== null && $classStartDateMax !== null){
            $model->whereBetween('course_offline_order.start_at',[$classStartDateMin,$classStartDateMax]);
        }
        $courseOfflineOrderList = $model
            ->select(['course_offline_order.id','course_offline_order.course_offline_plan_id','course_offline_order.course_name','course_offline_order.course_offline_id','course_offline_order.start_at','course_offline_order.end_at','course_offline_order.physical_store_name','course_offline_order.classroom_name','course_offline_order.teacher_name','course_offline_order.class_status','course_offline_order.order_status','course_offline.type','course_offline.suit_age_min','course_offline.duration','course_offline.video_url','course_offline.suit_age_max','member.name as member_name','member.mobile','vip_card_order.recommend_code','vip_card_order.price','vip_card_order.course1','vip_card_order.course2','vip_card_order.course3'])
            ->where($where)
            ->get();
        $courseOfflineOrderList = $courseOfflineOrderList->toArray();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', '孩子姓名')
            ->setCellValue('B1', '手机号')
            ->setCellValue('C1', '教室')
            ->setCellValue('D1', '老师')
            ->setCellValue('E1', '课程名称')
            ->setCellValue('F1', '课程ID')
            ->setCellValue('G1', '归类')
            ->setCellValue('H1', '适龄')
            ->setCellValue('I1', '上课时间')
            ->setCellValue('J1', '是否到场')
            ->setCellValue('K1', '推荐码')
            ->setCellValue('L1', '单次价')
            ->setCellValue('M1', '是否取消');
        $i=2;
        $courseTypeEnum = ['1'=>'常规课','2'=>'活动课','3'=>'专业课'];
        foreach($courseOfflineOrderList as $item){
            $totalCourse = $item['course1']+$item['course2']+$item['course3'];
            $unitPrice = $totalCourse>0 ? bcdiv($item['price'],(string)$totalCourse) : '0';
            $classTime = $item['start_at'].' '.$item['end_at'];
            $classStatusTxt = $item['class_status'] == 0 ? '否' : '是';
            $orderStatusTxt = $item['order_status'] == 0 ? '否' : '是';

            $sheet->setCellValue('A'.$i, $item['member_name'])
                ->setCellValue('B'.$i, $item['mobile'])
                ->setCellValue('C'.$i, $item['classroom_name'])
                ->setCellValue('D'.$i, $item['teacher_name'])
                ->setCellValue('E'.$i, $item['course_name'])
                ->setCellValue('F'.$i, $item['course_offline_id'])
                ->setCellValue('G'.$i, $courseTypeEnum[$item['type']])
                ->setCellValue('H'.$i, $item['suit_age_min'])
                ->setCellValue('I'.$i, $classTime)
                ->setCellValue('J'.$i, $classStatusTxt)
                ->setCellValue('K'.$i, $item['recommend_code'])
                ->setCellValue('L'.$i, $unitPrice)
                ->setCellValue('M'.$i, $orderStatusTxt);
            $i++;
        }

        $writer = new Xlsx($spreadsheet);
        $localPath = "/tmp/{$fileName}.xlsx";
        $writer->save($localPath);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => ['path'=>$localPath]];
    }

    /**
     * 线下课程调课订单列表
     * @return array
     */
    public function courseOfflineOrderReadjustList(): array
    {
        $offset = $this->offset;
        $limit = $this->limit;

        $courseOfflineOrderReadjustList = CourseOfflineOrderReadjust::query()
            ->leftJoin('member', 'course_offline_order_readjust.member_id', '=', 'member.id')
            ->leftJoin('course_offline_plan', 'course_offline_order_readjust.course_offline_plan_id', '=', 'course_offline_plan.id')
            ->leftJoin('physical_store', 'course_offline_plan.physical_store_id', '=', 'physical_store.id')
            ->leftJoin('course_offline', 'course_offline_plan.course_offline_id', '=', 'course_offline.id')
            ->select(['course_offline_order_readjust.id','course_offline_order_readjust.created_at','course_offline_order_readjust.status','member.name as member_name','member.mobile','physical_store.name as physical_store_name','course_offline_plan.classroom_name','course_offline_plan.teacher_name','course_offline.name as course_name','course_offline.id as course_id','course_offline_plan.class_start_time','course_offline_plan.class_end_time','course_offline.type','course_offline.suit_age_min','course_offline.suit_age_max'])
            ->offset($offset)->limit($limit)
            ->get();
        $courseOfflineOrderReadjustList = $courseOfflineOrderReadjustList->toArray();

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $courseOfflineOrderReadjustList];
    }

    /**
     * 线下课程调课订单详情
     * @param int $id
     * @return array
     */
    public function courseOfflineOrderReadjustDetail(int $id): array
    {
        $courseOfflineOrderReadjustInfo = CourseOfflineOrderReadjust::query()
            ->select(['member_remarks','examine_remarks'])
            ->where(['id'=>$id])
            ->first();
        if(empty($courseOfflineOrderReadjustInfo)){
            return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
        }
        $courseOfflineOrderReadjustInfo = $courseOfflineOrderReadjustInfo->toArray();

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $courseOfflineOrderReadjustInfo];

    }

    /**
     * 线下课程调课订单处理
     * @param array $params
     * @return array
     */
    public function handleCourseOfflineOrderReadjust(array $params): array
    {
        $id = $params['id'];

        $updateCourseOfflineOrderReadjustData['examine_remarks'] = $params['examine_remarks'];
        $updateCourseOfflineOrderReadjustData['status'] = 1;
        CourseOfflineOrderReadjust::query()->where(['id'=>$id])->update($updateCourseOfflineOrderReadjustData);

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];

    }

    /**
     * 线下课程订单取消
     * @param int $id
     * @return array
     * @throws \Exception
     */
    public function courseOfflineOrderCancel(int $id): array
    {
        $nowDate = date('Y-m-d H:i:s');

        $courseOfflineOrderInfo = CourseOfflineOrder::query()
            ->select(['vip_card_order_id','start_at','course_type','is_sample','course_offline_plan_id','batch_no','order_no','vip_card_order_child_id'])
            ->where(['id'=>$id,'pay_status'=>1,'order_status'=>0])
            ->first();
        if(empty($courseOfflineOrderInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '订单信息错误', 'data' => null];
        }
        $courseOfflineOrderInfo = $courseOfflineOrderInfo->toArray();
        if($nowDate >= $courseOfflineOrderInfo['start_at']){
            return ['code' => ErrorCode::WARNING, 'msg' => '已开课，无法取消', 'data' => null];
        }

        Db::connection('jkc_edu')->beginTransaction();
        try{
            $courseOfflineOrderAffected = CourseOfflineOrder::query()->where(['id'=>$id,'pay_status'=>1,'order_status'=>0])->update(['order_status'=>2]);
            if(!$courseOfflineOrderAffected){
                Db::connection('jkc_edu')->rollBack();
                return ['code' => ErrorCode::FAILURE, 'msg' => '课程订单修改失败', 'data' => null];
            }
            Db::connection('jkc_edu')->update('UPDATE course_offline_plan SET sign_up_num=sign_up_num-1 WHERE id=?', [$courseOfflineOrderInfo['course_offline_plan_id']]);
            if($courseOfflineOrderInfo['is_sample'] == 1){
                Db::connection('jkc_edu')->update('UPDATE vip_card_order SET currency_course_used=currency_course_used-1 WHERE id=?', [$courseOfflineOrderInfo['vip_card_order_id']]);
            }else{
                if($courseOfflineOrderInfo['vip_card_order_child_id'] != 0){
                    Db::connection('jkc_edu')->update('UPDATE vip_card_order_dynamic_course SET course_used=course_used-1 WHERE id=?', [$courseOfflineOrderInfo['vip_card_order_child_id']]);
                }else{
                    if($courseOfflineOrderInfo['course_type'] == 1){
                        Db::connection('jkc_edu')->update('UPDATE vip_card_order SET course1_used=course1_used-1 WHERE id=?', [$courseOfflineOrderInfo['vip_card_order_id']]);
                    }else if($courseOfflineOrderInfo['course_type'] == 2){
                        Db::connection('jkc_edu')->update('UPDATE vip_card_order SET course2_used=course2_used-1 WHERE id=?', [$courseOfflineOrderInfo['vip_card_order_id']]);
                    }else if($courseOfflineOrderInfo['course_type'] == 3){
                        Db::connection('jkc_edu')->update('UPDATE vip_card_order SET course3_used=course3_used-1 WHERE id=?', [$courseOfflineOrderInfo['vip_card_order_id']]);
                    }
                }
            }
            Db::connection('jkc_edu')->commit();
        } catch(\Throwable $e){
            Db::connection('jkc_edu')->rollBack();
            throw new \Exception($e->getMessage(), 1);
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 线下课程订单取消(特殊场景手工入口)
     * @param int $id
     * @return array
     * @throws \Exception
     */
    public function courseOfflineOrderCancelBackDoor(int $id): array
    {
        $nowDate = date('Y-m-d H:i:s');
        //$todayDate = date('Ymd');

        $courseOfflineOrderInfo = CourseOfflineOrder::query()
            ->select(['vip_card_order_id','start_at','course_type','is_sample','course_offline_plan_id','batch_no','order_no'])
            ->where(['id'=>$id,'pay_status'=>1,'order_status'=>0])
            ->first();
        if(empty($courseOfflineOrderInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '订单信息错误', 'data' => null];
        }
        $courseOfflineOrderInfo = $courseOfflineOrderInfo->toArray();
        /*$startDate = date('Ymd',strtotime($courseOfflineOrderInfo['start_at']));
        if($todayDate >= $startDate){
            return ['code' => ErrorCode::WARNING, 'msg' => '距离开课时间不足，无法取消', 'data' => null];
        }*/
        if($courseOfflineOrderInfo['course_type'] == 3 && $courseOfflineOrderInfo['is_sample'] == 0){
            $courseOfflineOrderCheck = CourseOfflineOrder::query()
                ->select(['start_at'])
                ->where(['order_no'=>$courseOfflineOrderInfo['order_no'],'batch_no'=>$courseOfflineOrderInfo['batch_no'],'section_no'=>1])
                ->first();
            $courseOfflineOrderCheck = $courseOfflineOrderCheck->toArray();
            if($courseOfflineOrderCheck['start_at'] <= $nowDate){
                return ['code' => ErrorCode::WARNING, 'msg' => '少儿课暂不支持取消，请联系官方平台，不便之处，敬请谅解！', 'data' => null];
            }
        }

        Db::connection('jkc_edu')->beginTransaction();
        try{
            if($courseOfflineOrderInfo['course_type'] == 3 && $courseOfflineOrderInfo['is_sample'] == 0){
                $courseOfflineOrderAffected = CourseOfflineOrder::query()->where(['pay_status'=>1,'order_status'=>0,'order_no'=>$courseOfflineOrderInfo['order_no'],'batch_no'=>$courseOfflineOrderInfo['batch_no']])->update(['order_status'=>2]);
            }else{
                $courseOfflineOrderAffected = CourseOfflineOrder::query()->where(['id'=>$id,'pay_status'=>1,'order_status'=>0])->update(['order_status'=>2]);
            }
            if(!$courseOfflineOrderAffected){
                Db::connection('jkc_edu')->rollBack();
                return ['code' => ErrorCode::FAILURE, 'msg' => '课程订单修改失败', 'data' => null];
            }
            Db::connection('jkc_edu')->update('UPDATE course_offline_plan SET sign_up_num=sign_up_num-1 WHERE id=?', [$courseOfflineOrderInfo['course_offline_plan_id']]);
            if($courseOfflineOrderInfo['is_sample'] == 1){
                Db::connection('jkc_edu')->update('UPDATE vip_card_order SET currency_course_used=currency_course_used-1 WHERE id=?', [$courseOfflineOrderInfo['vip_card_order_id']]);
            }else{
                if($courseOfflineOrderInfo['course_type'] == 1){
                    Db::connection('jkc_edu')->update('UPDATE vip_card_order SET course1_used=course1_used-1 WHERE id=?', [$courseOfflineOrderInfo['vip_card_order_id']]);
                }else if($courseOfflineOrderInfo['course_type'] == 2){
                    Db::connection('jkc_edu')->update('UPDATE vip_card_order SET course2_used=course2_used-1 WHERE id=?', [$courseOfflineOrderInfo['vip_card_order_id']]);
                }else if($courseOfflineOrderInfo['course_type'] == 3){
                    Db::connection('jkc_edu')->update('UPDATE vip_card_order SET course3_used=course3_used-1 WHERE id=?', [$courseOfflineOrderInfo['vip_card_order_id']]);
                }
            }
            Db::connection('jkc_edu')->commit();
        } catch(\Throwable $e){
            Db::connection('jkc_edu')->rollBack();
            throw new \Exception($e->getMessage(), 1);
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 评价管理列表
     * @param array $params
     * @return array
     */
    public function courseOfflineOrderEvaluationList(array $params): array
    {
        $memberName = $params['member_name'];
        $mobile = $params['mobile'];
        $classStartDateMin = $params['class_start_date_min'];
        $classStartDateMax = $params['class_start_date_max'];
        $createdAtStart = $params['created_at_start'];
        $createdAtEnd = $params['created_at_end'];
        $physicalStoreId = $params['physical_store_id'];
        $teacherId = $params['teacher_id'];
        $remark = $params['remark'];
        $grade = $params['grade'];
        $offset = $this->offset;
        $limit = $this->limit;

        $model = CourseOfflineOrderEvaluation::query()
            ->leftJoin('course_offline_order', 'course_offline_order_evaluation.course_offline_order_id', '=', 'course_offline_order.id')
            ->leftJoin('course_offline_plan', 'course_offline_order.course_offline_plan_id', '=', 'course_offline_plan.id')
            ->leftJoin('physical_store', 'course_offline_order_evaluation.physical_store_id', '=', 'physical_store.id')
            ->leftJoin('teacher', 'course_offline_order_evaluation.teacher_id', '=', 'teacher.id')
            ->leftJoin('member', 'course_offline_order_evaluation.member_id', '=', 'member.id');


        $where = [];
        if (!empty($memberName)) {
            $where[] = ['member.name', 'like', "%{$memberName}%"];
        }
        if (!empty($mobile)) {
            $where[] = ['member.mobile', '=', $mobile];
        }
        if ($classStartDateMin !== null && $classStartDateMax !== null) {
            $model->whereBetween('course_offline_plan.class_start_time', [strtotime($classStartDateMin), strtotime($classStartDateMax)]);
        }
        if ($createdAtStart !== null && $createdAtEnd !== null) {
            $model->whereBetween('course_offline_order_evaluation.created_at', [$createdAtStart, $createdAtEnd]);
        }
        if (!empty($physicalStoreId)) {
            $where[] = ['course_offline_order_evaluation.physical_store_id', '=', $physicalStoreId];
        }
        if (!empty($teacherId)) {
            $where[] = ['course_offline_order_evaluation.teacher_id', '=', $teacherId];
        }
        if ($remark !== null) {
            if ($remark == 0) {
                $where[] = ['course_offline_order_evaluation.remark', '=', ''];
            } else {
                $where[] = ['course_offline_order_evaluation.remark', '!=', ''];
            }
        }
        if (!empty($grade)) {
            $where[] = ['course_offline_order_evaluation.grade', '=', $grade];
        }

        $fields = [
            'course_offline_order_evaluation.id',
            'member.name AS member_name',
            'member.mobile',
            'course_offline_order.course_name',
            'physical_store.name AS store_name',
            'teacher.name AS teacher_name',
            'course_offline_plan.class_start_time',
            'course_offline_plan.class_end_time',
            'course_offline_order_evaluation.grade',
            'course_offline_order_evaluation.tag_text',
            'course_offline_order_evaluation.remark',
            'course_offline_order_evaluation.created_at',
        ];
        $count = $model->where($where)->count();
        $courseOfflineOrderEvaluationList = $model
            ->select($fields)
            ->where($where)
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($limit)
            ->get();
        $courseOfflineOrderEvaluationList = $courseOfflineOrderEvaluationList->toArray();
        foreach ($courseOfflineOrderEvaluationList as $key => $value) {
            $courseOfflineOrderEvaluationList[$key]['created_at'] = date('Y.m.d H:i', strtotime($value['created_at']));
            $classTimeText = date('Y.m.d H:i', $value['class_start_time']) . '-' . date('H:i', $value['class_end_time']);
            $courseOfflineOrderEvaluationList[$key]['class_time_text'] = $classTimeText;
            $courseOfflineOrderEvaluationList[$key]['grade_text'] = $value['grade'] . '星';
        }

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => ['list' => $courseOfflineOrderEvaluationList, 'count' => $count]];
    }

}