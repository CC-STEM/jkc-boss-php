<?php
declare(strict_types=1);

namespace App\Task;

use App\Constants\ErrorCode;
use App\Logger\Log;
use App\Model\AsyncTask;
use App\Model\CourseOfflineClassroomSituation;
use App\Model\CourseOfflineOrder;
use App\Model\CourseOfflinePlan;
use App\Model\OrderGoods;
use App\Model\OrderInfo;
use App\Model\OrderRefund;
use App\Model\PayApply;
use App\Model\SalaryTemplateLevel;
use App\Model\Teacher;
use App\Model\TeacherSalaryBill;
use App\Model\TeacherSalaryBillDetailed;
use App\Model\TeacherSalaryCalculateRecord;
use App\Model\VipCardOrder;
use App\Model\VipCardOrderRefund;
use App\Snowflake\IdGenerator;
use Hyperf\DbConnection\Db;

class TeacherSalaryBillTask extends BaseTask
{
    /**
     * 老师薪资月度初始化
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function salaryBillMonthlyInitExecute(): void
    {
        try{
            $month = date('Ym');

            $teacherList = Teacher::query()
                ->select(['id'])
                ->where(['is_deleted'=>0])
                ->get();
            $teacherList = $teacherList->toArray();

            foreach($teacherList as $value){
                $teacherId = $value['id'];

                $teacherSalaryBillExists = TeacherSalaryBill::query()->where(['month'=>$month,'teacher_id'=>$teacherId])->exists();
                if($teacherSalaryBillExists === true){
                    continue;
                }
                $teacherRankDataResult = $this->getTeacherRankData((int)$teacherId);
                if($teacherRankDataResult['code'] === ErrorCode::WARNING){
                    $msg = $teacherRankDataResult['msg'];
                    Log::get()->info("salaryBillMonthlyInitExecute[$msg]:".$teacherId);
                    continue;
                }
                $teacherRankData = $teacherRankDataResult['data'];
                $teacherSalaryBillId = IdGenerator::generate();

                $insertTeacherSalaryBillData['id'] = $teacherSalaryBillId;
                $insertTeacherSalaryBillData['teacher_id'] = $teacherId;
                $insertTeacherSalaryBillData['month'] = $month;
                $insertTeacherSalaryBillData['basic_salary'] = $teacherRankData['basic_salary'];
                $insertTeacherSalaryBillData['rank_level'] = $teacherRankData['rank_level'];
                $insertTeacherSalaryBillData['rank_status'] = $teacherRankData['rank_status'];
                $insertTeacherSalaryBillData['physical_store_id'] = $teacherRankData['physical_store_id'];
                TeacherSalaryBill::query()->insert($insertTeacherSalaryBillData);
            }
        } catch (\Throwable $e) {
            $error = ['tag'=>'salaryBillMonthlyInitExecute','msg'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()];
            Log::get()->error(json_encode($error));
        }
    }

    /**
     * 线下课程结果回查确认
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function salaryBill3ResultReviewExecute(): void
    {
        try{
            $nowDate = date('Y-m-d H:i:s');
            $newScanAt = date('Y-m-d H:i:s',strtotime('+5 minute'));

            $asyncTaskList = AsyncTask::query()
                ->select(['id','data'])
                ->where([['status','=',-1],['type','=',3],['scan_at','<=',$nowDate]])
                ->offset(0)->limit(100)
                ->get();
            $asyncTaskList = $asyncTaskList->toArray();

            foreach($asyncTaskList as $value){
                $data = json_decode($value['data'],true);
                $courseOfflinePlanId = $data['course_offline_plan_id'];
                $courseOfflineClassroomSituationExists = CourseOfflineClassroomSituation::query()->where(['course_offline_plan_id'=>$courseOfflinePlanId])->exists();
                if($courseOfflineClassroomSituationExists === true){
                    AsyncTask::query()->where(['id'=>$value['id']])->update(['status'=>0]);
                }else{
                    AsyncTask::query()->where(['id'=>$value['id']])->update(['scan_at'=>$newScanAt]);
                }
            }
        } catch (\Throwable $e) {
            $error = ['tag'=>'salaryBill3ResultReviewExecute','msg'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()];
            Log::get()->error(json_encode($error));
        }
    }

    /**
     * 线下课程薪资计算
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function salaryBill3SalaryCalculateExecute(): void
    {
        try{
            $nowDate = date('Y-m-d H:i:s');
            $teacherSalaryCalculateRecord = 1;

            $asyncTaskList = AsyncTask::query()
                ->select(['id','data'])
                ->where([['status','=',0],['type','=',3],['scan_at','<=',$nowDate]])
                ->offset(0)->limit(50)
                ->get();
            $asyncTaskList = $asyncTaskList->toArray();

            foreach($asyncTaskList as $value){
                $data = json_decode($value['data'],true);
                $courseOfflinePlanId = $data['course_offline_plan_id'];
                //幂等校验
                $teacherSalaryCalculateRecordExists = TeacherSalaryCalculateRecord::query()->where(['outer_id'=>$courseOfflinePlanId,'type'=>$teacherSalaryCalculateRecord])->exists();
                if($teacherSalaryCalculateRecordExists === true){
                    AsyncTask::query()->where(['id'=>$value['id']])->update(['status'=>1]);
                    continue;
                }
                //排课信息
                $courseOfflinePlanInfo = CourseOfflinePlan::query()
                    ->select(['teacher_id','class_start_time','physical_store_id'])
                    ->where(['id'=>$courseOfflinePlanId])
                    ->first();
                if(empty($courseOfflinePlanInfo)){
                    Log::get()->info('salaryBill3SalaryCalculateExecute[排课数据缺失]:'.$value['id']);
                    continue;
                }
                $courseOfflinePlanInfo = $courseOfflinePlanInfo->toArray();
                $month = date('Ym',$courseOfflinePlanInfo['class_start_time']);
                $teacherId = $courseOfflinePlanInfo['teacher_id'];
                //老师信息
                $teacherInfo = Teacher::query()
                    ->select(['rank_status'])
                    ->where(['id'=>$teacherId,'is_deleted'=>0])
                    ->first();
                $teacherInfo = $teacherInfo?->toArray();
                if($teacherInfo === null){
                    AsyncTask::query()->where(['id'=>$value['id']])->update(['status'=>1]);
                    continue;
                }
                //薪资账单数据
                $teacherSalaryBillInfo = TeacherSalaryBill::query()
                    ->select(['id'])
                    ->where(['month'=>$month,'teacher_id'=>$teacherId])
                    ->first();
                if(empty($teacherSalaryBillInfo)){
                    AsyncTask::query()->where(['id'=>$value['id']])->update(['status'=>1]);
                    continue;
                }
                $teacherSalaryBillInfo = $teacherSalaryBillInfo->toArray();
                $teacherSalaryBillId = $teacherSalaryBillInfo['id'];
                //课程订单
                $courseOfflineOrderList = [];
                if($teacherInfo['rank_status'] == 2){
                    $courseOfflineOrderList = CourseOfflineOrder::query()
                        ->select(['id','member_id','price','commission_rate','teacher_id','theme_type','vip_card_order_id','is_sample','vip_card_order_child_id'])
                        ->where(['course_offline_plan_id'=>$courseOfflinePlanId,'pay_status'=>1,'class_status'=>1,'order_status'=>0])
                        ->get();
                    $courseOfflineOrderList = $courseOfflineOrderList->toArray();
                }
                //数据有效性
                $isEffective = 1;
                //老师薪资账单清单数据
                $insertTeacherSalaryBillDetailedData = [];
                foreach($courseOfflineOrderList as $item){
                    $originalCommissionRate = $item['commission_rate'];
                    $commissionRate = bcdiv($originalCommissionRate,'100',4);
                    $vipCardOrderInfo = VipCardOrder::query()
                        ->select(['price','course1','course2','course3','order_type'])
                        ->where(['id'=>$item['vip_card_order_id']])
                        ->first();
                    if(empty($vipCardOrderInfo)){
                        $isEffective = 0;
                        Log::get()->info('salaryBill3SalaryCalculateExecute[会员卡订单数据缺失]:'.$item['id']);
                        break;
                    }
                    $vipCardOrderInfo = $vipCardOrderInfo->toArray();
                    if($item['is_sample'] == 1 && $vipCardOrderInfo['order_type'] != 3){
                        continue;
                    }
                    $source = 1;
                    $averagePrice = '0';
                    if($item['is_sample'] == 0 && $item['vip_card_order_child_id'] == 0){
                        $totalSections = $vipCardOrderInfo['course1']+$vipCardOrderInfo['course2']+$vipCardOrderInfo['course3'];
                        $averagePrice = $totalSections > 0 ? bcdiv((string)$vipCardOrderInfo['price'],(string)$totalSections,2) : '0';
                        $commissionAmount = bcmul($averagePrice,$commissionRate,2);
                    }else{
                        $source = $item['is_sample'] == 1 ? 2 : 3;
                        $commissionAmount = 10;
                        $originalCommissionRate = 0;
                    }
                    if($commissionAmount<=0){
                        continue;
                    }
                    $teacherSalaryBillDetailedData['id'] = IdGenerator::generate();
                    $teacherSalaryBillDetailedData['teacher_salary_bill_id'] = $teacherSalaryBillId;
                    $teacherSalaryBillDetailedData['teacher_id'] = $item['teacher_id'];
                    $teacherSalaryBillDetailedData['outer_id'] = $item['id'];
                    $teacherSalaryBillDetailedData['outer_parent_id'] = $courseOfflinePlanId;
                    $teacherSalaryBillDetailedData['amount'] = $averagePrice;
                    $teacherSalaryBillDetailedData['commission'] = $commissionAmount;
                    $teacherSalaryBillDetailedData['commission_rate'] = $originalCommissionRate;
                    $teacherSalaryBillDetailedData['type'] = $item['theme_type'];
                    $teacherSalaryBillDetailedData['member_id'] = $item['member_id'];
                    $teacherSalaryBillDetailedData['source'] = $source;
                    $insertTeacherSalaryBillDetailedData[] = $teacherSalaryBillDetailedData;
                }
                //老师薪资计算记录数据
                $insertTeacherSalaryCalculateRecordData['id'] = IdGenerator::generate();
                $insertTeacherSalaryCalculateRecordData['outer_id'] = $courseOfflinePlanId;
                $insertTeacherSalaryCalculateRecordData['type'] = $teacherSalaryCalculateRecord;
                //数据有效性校验
                if($isEffective === 0){
                    continue;
                }

                Db::connection('jkc_edu')->beginTransaction();
                try{
                    $asyncTaskAffected = AsyncTask::query()->where(['id'=>$value['id'],'status'=>0])->update(['status'=>1]);
                    if(!$asyncTaskAffected){
                        Db::connection('jkc_edu')->rollBack();
                        Log::get()->info("salaryBill3SalaryCalculateExecute[数据修改失败]:".$value['id']);
                        continue;
                    }
                    TeacherSalaryCalculateRecord::query()->insert($insertTeacherSalaryCalculateRecordData);
                    if(!empty($insertTeacherSalaryBillDetailedData)){
                        TeacherSalaryBillDetailed::query()->insert($insertTeacherSalaryBillDetailedData);
                    }
                    Db::connection('jkc_edu')->commit();
                } catch(\Throwable $e){
                    Db::connection('jkc_edu')->rollBack();
                    $error = ['tag'=>"salaryBill3SalaryCalculateExecute",'msg'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()];
                    Log::get()->error(json_encode($error));
                }
            }
        } catch(\Throwable $e){
            $error = ['tag'=>"salaryBill3SalaryCalculateExecute",'msg'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()];
            Log::get()->error(json_encode($error));
        }
    }

    /**
     * 商品订单结果回查确认
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function salaryBill4ResultReviewExecute(): void
    {
        try{
            $nowDate = date('Y-m-d H:i:s');
            $newScanAt = date('Y-m-d H:i:s',strtotime('+5 minute'));

            $asyncTaskList = AsyncTask::query()
                ->select(['id','data'])
                ->where([['status','=',-1],['type','=',4],['scan_at','<=',$nowDate]])
                ->offset(0)->limit(100)
                ->get();
            $asyncTaskList = $asyncTaskList->toArray();

            foreach($asyncTaskList as $value){
                $data = json_decode($value['data'],true);
                $outTradeNo = $data['out_trade_no'];

                $payApplyInfoExists = PayApply::query()->where(['out_trade_no'=>$outTradeNo,'status'=>1])->exists();
                if($payApplyInfoExists === true){
                    AsyncTask::query()->where(['id'=>$value['id']])->update(['status'=>0]);
                }else{
                    AsyncTask::query()->where(['id'=>$value['id']])->update(['scan_at'=>$newScanAt]);
                }
            }
        } catch (\Throwable $e) {
            $error = ['tag'=>'salaryBill4ResultReviewExecute','msg'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()];
            Log::get()->error(json_encode($error));
        }
    }

    /**
     * 商品订单薪资计算
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function salaryBill4SalaryCalculateExecute(): void
    {
        $nowDate = date('Y-m-d H:i:s');
        $teacherSalaryCalculateRecord = 2;

        $asyncTaskList = AsyncTask::query()
            ->select(['id','data'])
            ->where([['status','=',0],['type','=',4],['scan_at','<=',$nowDate]])
            ->offset(0)->limit(50)
            ->get();
        $asyncTaskList = $asyncTaskList->toArray();

        foreach($asyncTaskList as $value){
            $data = json_decode($value['data'],true);
            $outTradeNo = $data['out_trade_no'];

            $payApplyInfo = PayApply::query()->select(['order_no'])->where(['out_trade_no'=>$outTradeNo])->first();
            $payApplyInfo = $payApplyInfo->toArray();
            $orderNo = $payApplyInfo['order_no'];
            $orderInfo = OrderInfo::query()
                ->select(['id','created_at','recommend_teacher_id'])
                ->where(['order_no'=>$orderNo,'pay_status'=>1])
                ->first();
            if(empty($orderInfo)){
                Log::get()->info("salaryBill4SalaryCalculateExecute[订单数据缺失]:".$orderNo);
                continue;
            }
            $orderInfo = $orderInfo->toArray();
            $orderId = $orderInfo['id'];
            $month = date('Ym',strtotime($orderInfo['created_at']));
            $teacherId = $orderInfo['recommend_teacher_id'];
            if($teacherId == 0){
                AsyncTask::query()->where(['id'=>$value['id']])->update(['status'=>1]);
                continue;
            }
            //老师信息
            $teacherInfo = Teacher::query()
                ->select(['rank_status'])
                ->where(['id'=>$teacherId,'is_deleted'=>0])
                ->first();
            $teacherInfo = $teacherInfo?->toArray();
            if($teacherInfo === null){
                AsyncTask::query()->where(['id'=>$value['id']])->update(['status'=>1]);
                continue;
            }
            //幂等校验
            $teacherSalaryCalculateRecordExists = TeacherSalaryCalculateRecord::query()->where(['outer_id'=>$orderId,'type'=>$teacherSalaryCalculateRecord])->exists();
            if($teacherSalaryCalculateRecordExists === true){
                AsyncTask::query()->where(['id'=>$value['id']])->update(['status'=>1]);
                continue;
            }
            //薪资账单数据
            $teacherSalaryBillInfo = TeacherSalaryBill::query()
                ->select(['id'])
                ->where(['month'=>$month,'teacher_id'=>$teacherId])
                ->first();
            if(empty($teacherSalaryBillInfo)){
                AsyncTask::query()->where(['id'=>$value['id']])->update(['status'=>1]);
                continue;
            }
            $teacherSalaryBillInfo = $teacherSalaryBillInfo->toArray();
            $teacherSalaryBillId = $teacherSalaryBillInfo['id'];
            //订单商品
            $orderGoodsList = [];
            if($teacherInfo['rank_status'] == 2){
                $orderGoodsList = OrderGoods::query()
                    ->select(['id','member_id','pay_price','commission_rate','amount'])
                    ->where(['order_info_id'=>$orderId])
                    ->get();
                $orderGoodsList = $orderGoodsList->toArray();
            }
            //老师薪资账单清单数据
            $insertTeacherSalaryBillDetailedData = [];
            foreach($orderGoodsList as $item){
                $commissionRate = bcdiv($item['commission_rate'],'100',4);
                $commissionAmount = bcmul($item['amount'],$commissionRate,2);
                if($commissionAmount<=0){
                    continue;
                }
                $teacherSalaryBillDetailedData['id'] = IdGenerator::generate();
                $teacherSalaryBillDetailedData['teacher_salary_bill_id'] = $teacherSalaryBillId;
                $teacherSalaryBillDetailedData['teacher_id'] = $teacherId;
                $teacherSalaryBillDetailedData['outer_id'] = $item['id'];
                $teacherSalaryBillDetailedData['outer_parent_id'] = $orderId;
                $teacherSalaryBillDetailedData['amount'] = $item['amount'];
                $teacherSalaryBillDetailedData['commission'] = $commissionAmount;
                $teacherSalaryBillDetailedData['commission_rate'] = $item['commission_rate'];
                $teacherSalaryBillDetailedData['member_id'] = $item['member_id'];
                $teacherSalaryBillDetailedData['type'] = 4;
                $insertTeacherSalaryBillDetailedData[] = $teacherSalaryBillDetailedData;
            }
            //老师薪资计算记录数据
            $insertTeacherSalaryCalculateRecordData['id'] = IdGenerator::generate();
            $insertTeacherSalaryCalculateRecordData['outer_id'] = $orderId;
            $insertTeacherSalaryCalculateRecordData['type'] = $teacherSalaryCalculateRecord;

            Db::connection('jkc_edu')->beginTransaction();
            try{
                $asyncTaskAffected = AsyncTask::query()->where(['id'=>$value['id'],'status'=>0])->update(['status'=>1]);
                if(!$asyncTaskAffected){
                    Db::connection('jkc_edu')->rollBack();
                    Log::get()->info("salaryBill4SalaryCalculateExecute[数据修改失败]:".$value['id']);
                    continue;
                }
                TeacherSalaryCalculateRecord::query()->insert($insertTeacherSalaryCalculateRecordData);
                if(!empty($insertTeacherSalaryBillDetailedData)){
                    TeacherSalaryBillDetailed::query()->insert($insertTeacherSalaryBillDetailedData);
                }
                Db::connection('jkc_edu')->commit();
            } catch(\Throwable $e){
                Db::connection('jkc_edu')->rollBack();
                $error = ['tag'=>"salaryBill4SalaryCalculateExecute",'msg'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()];
                Log::get()->error(json_encode($error));
            }
        }
    }

    /**
     * 会员卡订单结果回查确认
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function salaryBill5ResultReviewExecute(): void
    {
        try{
            $nowDate = date('Y-m-d H:i:s');
            $newScanAt = date('Y-m-d H:i:s',strtotime('+5 minute'));

            $asyncTaskList = AsyncTask::query()
                ->select(['id','data'])
                ->where([['status','=',-1],['type','=',5],['scan_at','<=',$nowDate]])
                ->offset(0)->limit(100)
                ->get();
            $asyncTaskList = $asyncTaskList->toArray();

            foreach($asyncTaskList as $value){
                $data = json_decode($value['data'],true);
                $outTradeNo = $data['out_trade_no'];

                $payApplyInfoExists = PayApply::query()->where(['out_trade_no'=>$outTradeNo,'status'=>1])->exists();
                if($payApplyInfoExists === true){
                    AsyncTask::query()->where(['id'=>$value['id']])->update(['status'=>0]);
                }else{
                    AsyncTask::query()->where(['id'=>$value['id']])->update(['scan_at'=>$newScanAt]);
                }
            }
        } catch (\Throwable $e) {
            $error = ['tag'=>'salaryBill5ResultReviewExecute','msg'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()];
            Log::get()->error(json_encode($error));
        }
    }

    /**
     * 会员卡订单薪资计算
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function salaryBill5SalaryCalculateExecute(): void
    {
        $nowDate = date('Y-m-d H:i:s');
        $teacherSalaryCalculateRecord = 3;

        $asyncTaskList = AsyncTask::query()
            ->select(['id','data'])
            ->where([['status','=',0],['type','=',5],['scan_at','<=',$nowDate]])
            ->offset(0)->limit(50)
            ->get();
        $asyncTaskList = $asyncTaskList->toArray();

        foreach($asyncTaskList as $value){
            $data = json_decode($value['data'],true);
            $outTradeNo = $data['out_trade_no'];

            $payApplyInfo = PayApply::query()->select(['order_no'])->where(['out_trade_no'=>$outTradeNo])->first();
            $payApplyInfo = $payApplyInfo->toArray();
            $orderNo = $payApplyInfo['order_no'];
            $vipCardOrderInfo = VipCardOrder::query()
                ->select(['id','member_id','commission_rate','created_at','price','recommend_teacher_id'])
                ->where(['order_no'=>$orderNo,'pay_status'=>1])
                ->first();
            if(empty($vipCardOrderInfo)){
                Log::get()->info("salaryBill5SalaryCalculateExecute[会员卡订单数据缺失]:".$orderNo);
                continue;
            }
            $vipCardOrderInfo = $vipCardOrderInfo->toArray();
            $vipCardOrderId = $vipCardOrderInfo['id'];
            $commissionRate = bcdiv($vipCardOrderInfo['commission_rate'],'100',4);
            $month = date('Ym',strtotime($vipCardOrderInfo['created_at']));
            $teacherId = $vipCardOrderInfo['recommend_teacher_id'];
            if($teacherId == 0){
                AsyncTask::query()->where(['id'=>$value['id']])->update(['status'=>1]);
                continue;
            }
            //老师信息
            $teacherInfo = Teacher::query()
                ->select(['rank_status'])
                ->where(['id'=>$teacherId,'is_deleted'=>0])
                ->first();
            $teacherInfo = $teacherInfo?->toArray();
            if($teacherInfo === null){
                AsyncTask::query()->where(['id'=>$value['id']])->update(['status'=>1]);
                continue;
            }
            //幂等校验
            $teacherSalaryCalculateRecordExists = TeacherSalaryCalculateRecord::query()->where(['outer_id'=>$vipCardOrderId,'type'=>$teacherSalaryCalculateRecord])->exists();
            if($teacherSalaryCalculateRecordExists === true){
                AsyncTask::query()->where(['id'=>$value['id']])->update(['status'=>1]);
                continue;
            }
            //薪资账单数据
            $teacherSalaryBillInfo = TeacherSalaryBill::query()
                ->select(['id'])
                ->where(['month'=>$month,'teacher_id'=>$teacherId])
                ->first();
            if(empty($teacherSalaryBillInfo)){
                AsyncTask::query()->where(['id'=>$value['id']])->update(['status'=>1]);
                continue;
            }
            $teacherSalaryBillInfo = $teacherSalaryBillInfo->toArray();
            $teacherSalaryBillId = $teacherSalaryBillInfo['id'];
            //佣金金额
            $commissionAmount = 0;
            if($teacherInfo['rank_status'] == 2){
                $commissionAmount = bcmul($vipCardOrderInfo['price'],$commissionRate,2);
            }
            //老师薪资账单清单数据
            if($commissionAmount>0){
                $insertTeacherSalaryBillDetailedData['id'] = IdGenerator::generate();
                $insertTeacherSalaryBillDetailedData['teacher_salary_bill_id'] = $teacherSalaryBillId;
                $insertTeacherSalaryBillDetailedData['teacher_id'] = $teacherId;
                $insertTeacherSalaryBillDetailedData['outer_id'] = $vipCardOrderId;
                $insertTeacherSalaryBillDetailedData['amount'] = $vipCardOrderInfo['price'];
                $insertTeacherSalaryBillDetailedData['commission'] = $commissionAmount;
                $insertTeacherSalaryBillDetailedData['commission_rate'] = $vipCardOrderInfo['commission_rate'];
                $insertTeacherSalaryBillDetailedData['member_id'] = $vipCardOrderInfo['member_id'];
                $insertTeacherSalaryBillDetailedData['type'] = 5;
            }
            //老师薪资计算记录数据
            $insertTeacherSalaryCalculateRecordData['id'] = IdGenerator::generate();
            $insertTeacherSalaryCalculateRecordData['outer_id'] = $vipCardOrderId;
            $insertTeacherSalaryCalculateRecordData['type'] = $teacherSalaryCalculateRecord;

            Db::connection('jkc_edu')->beginTransaction();
            try{
                $asyncTaskAffected = AsyncTask::query()->where(['id'=>$value['id'],'status'=>0])->update(['status'=>1]);
                if(!$asyncTaskAffected){
                    Db::connection('jkc_edu')->rollBack();
                    Log::get()->info("salaryBill5SalaryCalculateExecute[数据修改失败]:".$value['id']);
                    continue;
                }
                TeacherSalaryCalculateRecord::query()->insert($insertTeacherSalaryCalculateRecordData);
                if(!empty($insertTeacherSalaryBillDetailedData)){
                    TeacherSalaryBillDetailed::query()->insert($insertTeacherSalaryBillDetailedData);
                }
                Db::connection('jkc_edu')->commit();
            } catch(\Throwable $e){
                Db::connection('jkc_edu')->rollBack();
                $error = ['tag'=>"salaryBill5SalaryCalculateExecute",'msg'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()];
                Log::get()->error(json_encode($error));
            }
        }
    }

    /**
     * 商品订单退款结果回查确认
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function salaryBill7ResultReviewExecute(): void
    {
        try{
            $nowDate = date('Y-m-d H:i:s');
            $newScanAt = date('Y-m-d H:i:s',strtotime('+5 minute'));

            $asyncTaskList = AsyncTask::query()
                ->select(['id','data'])
                ->where([['status','=',-1],['type','=',7],['scan_at','<=',$nowDate]])
                ->offset(0)->limit(100)
                ->get();
            $asyncTaskList = $asyncTaskList->toArray();

            foreach($asyncTaskList as $value){
                $data = json_decode($value['data'],true);
                $orderRefundId = $data['order_refund_id'];

                $orderRefundExists = OrderRefund::query()->where(['id'=>$orderRefundId,'status'=>25])->exists();
                if($orderRefundExists === true){
                    AsyncTask::query()->where(['id'=>$value['id']])->update(['status'=>0]);
                }else{
                    AsyncTask::query()->where(['id'=>$value['id']])->update(['scan_at'=>$newScanAt]);
                }
            }
        } catch (\Throwable $e) {
            $error = ['tag'=>'salaryBill7ResultReviewExecute','msg'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()];
            Log::get()->error(json_encode($error));
        }
    }

    /**
     * 商品订单退款薪资计算
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function salaryBill7SalaryCalculateExecute(): void
    {
        $nowDate = date('Y-m-d H:i:s');
        $nowTime = time();
        $teacherSalaryCalculateRecord = 5;

        $asyncTaskList = AsyncTask::query()
            ->select(['id','data','created_at'])
            ->where([['status','=',0],['type','=',7],['scan_at','<=',$nowDate]])
            ->offset(0)->limit(50)
            ->get();
        $asyncTaskList = $asyncTaskList->toArray();

        foreach($asyncTaskList as $value){
            $data = json_decode($value['data'],true);
            $orderRefundId = $data['order_refund_id'];
            $createdTime = strtotime($value['created_at']);
            $timeDifference = $nowTime-$createdTime;

            $orderRefundInfo = OrderRefund::query()
                ->select(['order_goods_id'])
                ->where(['id'=>$orderRefundId,'status'=>25])
                ->first();
            if(empty($orderRefundInfo)){
                Log::get()->info("salaryBill7SalaryCalculateExecute[退款数据缺失]:".$value['id']);
                continue;
            }
            $orderGoodsId = $orderRefundInfo['order_goods_id'];
            //幂等校验
            $teacherSalaryCalculateRecordExists = TeacherSalaryCalculateRecord::query()->where(['outer_id'=>$orderRefundId,'type'=>$teacherSalaryCalculateRecord])->exists();
            if($teacherSalaryCalculateRecordExists === true){
                AsyncTask::query()->where(['id'=>$value['id']])->update(['status'=>1]);
                continue;
            }
            $teacherSalaryBillDetailedExists = TeacherSalaryBillDetailed::query()->where(['outer_id'=>$orderGoodsId,'type'=>4,'status'=>1])->exists();
            if($teacherSalaryBillDetailedExists === false){
                if($timeDifference>=3600){
                    AsyncTask::query()->where(['id'=>$value['id']])->update(['status'=>1]);
                }
                continue;
            }

            //老师薪资账单数据
            $updateTeacherSalaryBillDetailedData['status'] = 2;
            //老师薪资计算记录数据
            $insertTeacherSalaryCalculateRecordData['id'] = IdGenerator::generate();
            $insertTeacherSalaryCalculateRecordData['outer_id'] = $orderRefundId;
            $insertTeacherSalaryCalculateRecordData['type'] = $teacherSalaryCalculateRecord;

            Db::connection('jkc_edu')->beginTransaction();
            try{
                $asyncTaskAffected = AsyncTask::query()->where(['id'=>$value['id'],'status'=>0])->update(['status'=>1]);
                if(!$asyncTaskAffected){
                    Db::connection('jkc_edu')->rollBack();
                    Log::get()->info("salaryBill7SalaryCalculateExecute[AsyncTask数据修改失败]:".$value['id']);
                    continue;
                }
                $teacherSalaryBillDetailedAffected = TeacherSalaryBillDetailed::query()->where(['outer_id'=>$orderGoodsId,'type'=>4,'status'=>1])->update($updateTeacherSalaryBillDetailedData);
                if(!$teacherSalaryBillDetailedAffected){
                    Db::connection('jkc_edu')->rollBack();
                    Log::get()->info("salaryBill7SalaryCalculateExecute[TeacherSalaryBillDetailed数据修改失败]:".$value['id']);
                    continue;
                }
                TeacherSalaryCalculateRecord::query()->insert($insertTeacherSalaryCalculateRecordData);

                Db::connection('jkc_edu')->commit();
            } catch(\Throwable $e){
                Db::connection('jkc_edu')->rollBack();
                $error = ['tag'=>"salaryBill7SalaryCalculateExecute",'msg'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()];
                Log::get()->error(json_encode($error));
            }
        }
    }

    /**
     * 会员卡订单退款结果回查确认
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function salaryBill8ResultReviewExecute(): void
    {
        try{
            $nowDate = date('Y-m-d H:i:s');
            $newScanAt = date('Y-m-d H:i:s',strtotime('+5 minute'));

            $asyncTaskList = AsyncTask::query()
                ->select(['id','data'])
                ->where([['status','=',-1],['type','=',8],['scan_at','<=',$nowDate]])
                ->offset(0)->limit(100)
                ->get();
            $asyncTaskList = $asyncTaskList->toArray();

            foreach($asyncTaskList as $value){
                $data = json_decode($value['data'],true);
                $vipCardOrderId = $data['vip_card_order_id'];

                $vipCardOrderRefundExists = VipCardOrderRefund::query()->where(['id'=>$vipCardOrderId,'status'=>25])->exists();
                if($vipCardOrderRefundExists === true){
                    AsyncTask::query()->where(['id'=>$value['id']])->update(['status'=>0]);
                }else{
                    AsyncTask::query()->where(['id'=>$value['id']])->update(['scan_at'=>$newScanAt]);
                }
            }
        } catch (\Throwable $e) {
            $error = ['tag'=>'salaryBill8ResultReviewExecute','msg'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()];
            Log::get()->error(json_encode($error));
        }
    }

    /**
     * 会员卡订单退款薪资计算
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function salaryBill8SalaryCalculateExecute(): void
    {
        $nowDate = date('Y-m-d H:i:s');
        $nowTime = time();
        $teacherSalaryCalculateRecord = 6;

        $asyncTaskList = AsyncTask::query()
            ->select(['id','data','created_at'])
            ->where([['status','=',0],['type','=',8],['scan_at','<=',$nowDate]])
            ->offset(0)->limit(50)
            ->get();
        $asyncTaskList = $asyncTaskList->toArray();

        foreach($asyncTaskList as $value){
            $createdTime = strtotime($value['created_at']);
            $data = json_decode($value['data'],true);
            $vipCardOrderId = $data['vip_card_order_id'];
            $timeDifference = $nowTime-$createdTime;

            $teacherSalaryBillDetailedExists = TeacherSalaryBillDetailed::query()->where(['outer_id'=>$vipCardOrderId,'type'=>5,'status'=>1])->exists();
            if($teacherSalaryBillDetailedExists === false){
                if($timeDifference>=3600){
                    AsyncTask::query()->where(['id'=>$value['id']])->update(['status'=>1]);
                }
                continue;
            }
            $vipCardOrderRefundExists = VipCardOrderRefund::query()->where(['vip_card_order_id'=>$vipCardOrderId,'status'=>25])->exists();
            if(empty($vipCardOrderRefundExists)){
                Log::get()->info("salaryBill8SalaryCalculateExecute[退款数据缺失]:".$value['id']);
                continue;
            }
            //幂等校验
            $teacherSalaryCalculateRecordExists = TeacherSalaryCalculateRecord::query()->where(['outer_id'=>$vipCardOrderId,'type'=>$teacherSalaryCalculateRecord])->exists();
            if($teacherSalaryCalculateRecordExists === true){
                AsyncTask::query()->where(['id'=>$value['id']])->update(['status'=>1]);
                continue;
            }

            //老师薪资账单数据
            $updateTeacherSalaryBillDetailedData['status'] = 2;
            //老师薪资计算记录数据
            $insertTeacherSalaryCalculateRecordData['id'] = IdGenerator::generate();
            $insertTeacherSalaryCalculateRecordData['outer_id'] = $vipCardOrderId;
            $insertTeacherSalaryCalculateRecordData['type'] = $teacherSalaryCalculateRecord;

            Db::connection('jkc_edu')->beginTransaction();
            try{
                $asyncTaskAffected = AsyncTask::query()->where(['id'=>$value['id'],'status'=>0])->update(['status'=>1]);
                if(!$asyncTaskAffected){
                    Db::connection('jkc_edu')->rollBack();
                    Log::get()->info("salaryBill8SalaryCalculateExecute[AsyncTask数据修改失败]:".$value['id']);
                    continue;
                }
                $teacherSalaryBillDetailedAffected = TeacherSalaryBillDetailed::query()->where(['outer_id'=>$vipCardOrderId,'type'=>5,'status'=>1])->update($updateTeacherSalaryBillDetailedData);
                if(!$teacherSalaryBillDetailedAffected){
                    Db::connection('jkc_edu')->rollBack();
                    Log::get()->info("salaryBill8SalaryCalculateExecute[TeacherSalaryBillDetailed数据修改失败]:".$value['id']);
                    continue;
                }
                TeacherSalaryCalculateRecord::query()->insert($insertTeacherSalaryCalculateRecordData);

                Db::connection('jkc_edu')->commit();
            } catch(\Throwable $e){
                Db::connection('jkc_edu')->rollBack();
                $error = ['tag'=>"salaryBill8SalaryCalculateExecute",'msg'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()];
                Log::get()->error(json_encode($error));
            }
        }
    }

    /**
     * 获取老师职级数据
     * @param int $teacherId
     * @return array
     */
    private function getTeacherRankData(int $teacherId): array
    {
        $teacherInfo = Teacher::query()
            ->select(['salary_template_id','rank_level','rank_status','physical_store_id'])
            ->where(['id'=>$teacherId])
            ->first();
        if(empty($teacherInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '老师信息错误', 'data' => null];
        }
        $teacherInfo = $teacherInfo->toArray();
        if($teacherInfo['salary_template_id'] == 0){
            return ['code' => ErrorCode::WARNING, 'msg' => '老师未设置薪资模板', 'data' => null];
        }
        if($teacherInfo['rank_status'] == 0){
            return ['code' => ErrorCode::WARNING, 'msg' => '老师未设置当前状态', 'data' => null];
        }
        //薪资模板信息
        $salaryTemplateLevelInfo = SalaryTemplateLevel::query()
            ->select(['protected_period_salary','formal_period_salary'])
            ->where(['salary_template_id'=>$teacherInfo['salary_template_id'],'level'=>$teacherInfo['rank_level']])
            ->first();
        if(empty($salaryTemplateLevelInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '薪资模板信息错误', 'data' => null];
        }
        $salaryTemplateLevelInfo = $salaryTemplateLevelInfo->toArray();
        //保底薪资
        $basicSalary = $teacherInfo['rank_status'] == 1 ? $salaryTemplateLevelInfo['protected_period_salary'] : $salaryTemplateLevelInfo['formal_period_salary'];

        $teacherInfo['basic_salary'] = $basicSalary;
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $teacherInfo];
    }

    private function asyncTaskUpdateScanAt(int $id): void
    {
        $scanAt = date('Y-m-d H:i:s',strtotime('+10 minute'));
        AsyncTask::query()->where(['id'=>$id])->update(['scan_at'=>$scanAt]);
    }
}

