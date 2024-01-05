<?php
declare(strict_types=1);

namespace App\Task;

use App\Constants\VipCardConstant;
use App\Logger\Log;
use App\Model\AsyncTask;
use App\Model\Coupon;
use App\Model\CourseOfflineOrder;
use App\Model\DiscountTicket;
use App\Model\VipCardOrder;
use App\Model\VipCardOrderMonthlyStatistics;
use App\Model\VipCardOrderOfferInfo;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Psr\EventDispatcher\EventDispatcherInterface;

class VipCardOrderTask extends BaseTask
{
    #[Inject]
    private EventDispatcherInterface $eventDispatcher;

    /**
     * 会员卡过期预处理
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function vipCardOrderExpirePrepareExecute(): void
    {
        try{
            $asyncTaskList = AsyncTask::query()
                ->select(['id','data'])
                ->where([['status','=',-1],['type','=',2]])
                ->offset(0)->limit(100)
                ->get();
            $asyncTaskList = $asyncTaskList->toArray();

            foreach($asyncTaskList as $value){
                $data = json_decode($value['data'],true);
                $vipCardOrderInfo = VipCardOrder::query()
                    ->select(['expire_at'])
                    ->where(['id'=>$data['vip_card_order_id']])
                    ->first();
                $vipCardOrderInfo = $vipCardOrderInfo->toArray();
                if($vipCardOrderInfo['expire_at'] !== VipCardConstant::DEFAULT_EXPIRE_AT){
                    AsyncTask::query()->where(['id'=>$value['id']])->delete();
                }else{
                    AsyncTask::query()->where(['id'=>$value['id']])->update(['status'=>0]);
                }
            }
        } catch (\Throwable $e) {
            $error = ['tag'=>'vipCardOrderExpirePrepareExecute','msg'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()];
            Log::get()->error(json_encode($error));
        }
    }

    /**
     * 会员卡过期计时
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function vipCardOrderExpireExecute(): void
    {
        try{
            $nowDate = date('Y-m-d H:i:s');
            $asyncTaskList = AsyncTask::query()
                ->select(['id','data'])
                ->where([['status','=',0],['type','=',2],['scan_at','<=',$nowDate]])
                ->offset(0)->limit(50)
                ->get();
            $asyncTaskList = $asyncTaskList->toArray();

            foreach($asyncTaskList as $value){
                $data = json_decode($value['data'],true);
                $courseOfflineOrderInfo = CourseOfflineOrder::query()
                    ->select(['start_at','order_status'])
                    ->where(['id'=>$data['course_offline_order_id']])
                    ->first();
                $courseOfflineOrderInfo = $courseOfflineOrderInfo->toArray();
                if($courseOfflineOrderInfo['order_status'] == 0){
                    $vipCardOrderInfo = VipCardOrder::query()
                        ->select(['expire'])
                        ->where(['id'=>$data['vip_card_order_id']])
                        ->first();
                    $vipCardOrderInfo = $vipCardOrderInfo->toArray();
                    $expire = $vipCardOrderInfo['expire'];
                    $expireAt = date("Y-m-d H:i:s",strtotime("+{$expire} day",strtotime($courseOfflineOrderInfo['start_at'])));
                    VipCardOrder::query()->where(['id'=>$data['vip_card_order_id'],'expire_at'=>VipCardConstant::DEFAULT_EXPIRE_AT])->update(['expire_at'=>$expireAt]);
                }
                AsyncTask::query()->where(['id'=>$value['id']])->update(['status'=>1]);
            }
        } catch (\Throwable $e) {
            $error = ['tag'=>'vipCardOrderExpireExecute','msg'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()];
            Log::get()->error(json_encode($error));
        }
    }

    /**
     * 订单过期关闭
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function vipCardOrderNotPayExecute(): void
    {
        $expireAt = date('Y-m-d H:i:s',strtotime('-15 minute'));
        $nowDate = date('Y-m-d H:i:s');

        $vipCardOrderList = VipCardOrder::query()
            ->select(['id'])
            ->where([['pay_status','=',0],['order_status','=',0],['created_at','<=',$expireAt]])
            ->get();
        $vipCardOrderList = $vipCardOrderList->toArray();

        foreach($vipCardOrderList as $value){
            $vipCardOrderId = $value['id'];
            //优惠券
            $vipCardOrderOfferInfoInfo = VipCardOrderOfferInfo::query()
                ->select(['offer_info_id'])
                ->where(['vip_card_order_id'=>$vipCardOrderId,'type'=>1])
                ->first();
            $vipCardOrderOfferInfoInfo = $vipCardOrderOfferInfoInfo?->toArray();
            //减免券
            $vipCardOrderOfferInfoList = VipCardOrderOfferInfo::query()
                ->select(['offer_info_id'])
                ->where(['vip_card_order_id'=>$vipCardOrderId,'type'=>2])
                ->get();
            $vipCardOrderOfferInfoList = $vipCardOrderOfferInfoList->toArray();
            $discountTicketIdArray = array_column($vipCardOrderOfferInfoList,'offer_info_id');

            Db::connection('jkc_edu')->beginTransaction();
            try{
                $vipCardOrderAffected = VipCardOrder::query()->where(['id'=>$vipCardOrderId,'pay_status'=>0,'order_status'=>0])->update(['order_status'=>2,'closed_at'=>$nowDate]);
                if(!$vipCardOrderAffected){
                    Db::connection('jkc_edu')->rollBack();
                    Log::get()->info("vipCardOrderNotPayExecute[数据修改失败]:".$vipCardOrderId);
                    continue;
                }
                if(!empty($vipCardOrderOfferInfoInfo)){
                    Coupon::query()->where(['id'=>$vipCardOrderOfferInfoInfo['offer_info_id'],'is_used'=>1])->update(['is_used'=>0,'used_at'=>'0000-00-00 00:00:00']);
                }
                if(!empty($vipCardOrderOfferInfoList)){
                    DiscountTicket::query()->whereIn('id',$discountTicketIdArray)->where(['status'=>1])->update(['status'=>0,'used_at'=>'0000-00-00 00:00:00']);
                }
                Db::connection('jkc_edu')->commit();
            } catch(\Throwable $e){
                Db::connection('jkc_edu')->rollBack();
                $error = ['tag'=>"vipCardOrderNotPayExecute",'msg'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()];
                Log::get()->error(json_encode($error));
            }
        }
    }

    /**
     * 会员卡订单月度清算
     * @return void
     */
    public function vipCardOrderMonthlyStatisticsExecute(): void
    {
        $nowDate = date('Y-m-d H:i:s');
        $month = date("Ym", strtotime(date('Y-m-01 00:00:00')." -1 month"));

        $vipCardOrderList = VipCardOrder::query()
            ->selectRaw('member_id,sum(course1) as course1_sum,sum(course2) as course2_sum,sum(course3) as course3_sum,sum(currency_course) as currency_course_sum,sum(course1_used) as course1_used_sum,sum(course2_used) as course2_used_sum,sum(course3_used) as course3_used_sum,sum(currency_course_used) as currency_course_used_sum')
            ->where([['pay_status','=',1],['order_status','=',0],['expire_at','>',$nowDate]])
            ->groupBy('member_id')
            ->get();
        $vipCardOrderList = $vipCardOrderList->toArray();

        $insertVipCardOrderMonthlyStatisticsData = [];
        foreach($vipCardOrderList as $value){
            $vipCardOrderMonthlyStatisticsExists = VipCardOrderMonthlyStatistics::query()->where(['member_id'=>$value['member_id'],'month'=>$month])->exists();
            if($vipCardOrderMonthlyStatisticsExists === true){
                continue;
            }
            $surplusCourse = $value['course1_sum']+$value['course2_sum']+$value['course3_sum']+$value['currency_course_sum']-$value['course1_used_sum']-$value['course2_used_sum']-$value['course3_used_sum']-$value['currency_course_used_sum'];
            $vipCardOrderMonthlyStatisticsData['member_id'] = $value['member_id'];
            $vipCardOrderMonthlyStatisticsData['month'] = $month;
            $vipCardOrderMonthlyStatisticsData['course'] = $surplusCourse;
            $insertVipCardOrderMonthlyStatisticsData[] = $vipCardOrderMonthlyStatisticsData;
        }
        if(!empty($insertVipCardOrderMonthlyStatisticsData)){
            VipCardOrderMonthlyStatistics::query()->insert($insertVipCardOrderMonthlyStatisticsData);
        }
    }

}

