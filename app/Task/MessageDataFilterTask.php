<?php
declare(strict_types=1);

namespace App\Task;

use App\Constants\MessageConstant;
use App\Model\DiscountTicket;
use App\Model\Member;
use App\Model\Message;
use App\Model\MessageIdem;
use App\Model\OrderGoods;
use App\Model\PhysicalStoreAdmins;
use App\Model\VipCardOrder;
use Hyperf\DbConnection\Db;

class MessageDataFilterTask extends BaseTask
{

    public function yesterdayStoreRevenue(): void
    {
        $startAt = date('Y-m-d 00:00:00',strtotime('-1 day'));
        $endAt = date('Y-m-d 23:59:59',strtotime('-1 day'));

        $physicalStoreAdminsList = PhysicalStoreAdmins::query()
            ->select(['mobile','physical_store_id'])
            ->where(['is_store_manager'=>1,'is_deleted'=>0])
            ->groupBy('physical_store_id')
            ->get();
        $physicalStoreAdminsList = $physicalStoreAdminsList->toArray();

        foreach($physicalStoreAdminsList as $value){
            $physicalStoreId = $value['physical_store_id'];
            $mobile = $value['mobile'];

            //会员卡订单
            $vipCardOrderList = VipCardOrder::query()
                ->select(['price'])
                ->where(['order_status'=>0,'pay_status'=>1,'recommend_physical_store_id'=>$physicalStoreId])
                ->whereBetween('created_at',[$startAt,$endAt])
                ->get();
            $vipCardOrderList = $vipCardOrderList->toArray();
            $vipCardOrderCount = count($vipCardOrderList);
            $vipCardOrderAmount = (string)array_sum(array_column($vipCardOrderList,'price'));

            //商品订单
            $orderGoodsList = OrderGoods::query()
                ->leftJoin('order_info','order_goods.order_info_id','=','order_info.id')
                ->select(Db::connection('jkc_edu')->raw('SUM(order_goods.pay_price*order_goods.quantity) as amount_sum'))
                ->where(['order_info.recommend_physical_store_id'=>$physicalStoreId,'order_goods.order_status'=>0,'order_goods.pay_status'=>1])
                ->whereBetween('order_goods.created_at',[$startAt,$endAt])
                ->get();
            $orderGoodsList = $orderGoodsList->toArray();
            $orderGoodsCount = count($orderGoodsList);
            $orderGoodsAmount = (string)array_sum(array_column($orderGoodsList,'amount_sum'));

            $revenueOrder = $vipCardOrderCount+$orderGoodsCount;
            $revenueAmount = bcadd($vipCardOrderAmount,$orderGoodsAmount,2);

            $memberInfo = Member::query()
                ->select(['mini_openid'])
                ->where(['mobile'=>$mobile])
                ->first();
            $memberInfo = $memberInfo?->toArray();
            if($memberInfo === null){
                return;
            }
        }
    }

    /**
     * 抵扣券到期提醒
     * @return void
     */
    public function message1004Execute(): void
    {
        $nowDate = date('Y-m-d H:i:s');
        $dayAfter3 = date('Y-m-d H:i:s',strtotime('+3 day'));

        $discountTicketList = DiscountTicket::query()
            ->select(['id','member_id','name','end_at'])
            ->where(['status'=>0])
            ->whereBetween('end_at',[$nowDate,$dayAfter3])
            ->get();
        $discountTicketList = $discountTicketList->toArray();

        $insertMessageIdemData = [];
        $insertWeixinMessageData = [];
        foreach($discountTicketList as $value){
            $messageIdemExists = MessageIdem::query()->where(['bid'=>$value['id'],'code'=>MessageConstant::MESSAGE1004])->exists();
            if($messageIdemExists === true){
                continue;
            }
            $memberInfo = Member::query()
                ->select(['mini_openid'])
                ->where(['id'=>$value['member_id']])
                ->first();
            $memberInfo = $memberInfo?->toArray();
            $endAt = date('Y年m月d日 H:i',strtotime($value['end_at']));

            $data = [
                'thing7'=>[
                    'value'=>$value['name']
                ],
                'time3'=>[
                    'value'=>$endAt
                ],
                'thing4'=>[
                    'value'=>'请及时使用避免过期失效，点击即可开始约课！'
                ]
            ];
            $weixinMessageData = [
                'touser'=>$memberInfo['mini_openid'],
                'code' => MessageConstant::MESSAGE1004,
                'data'=>json_encode($data),
                'message_type'=>2,
                'send_at'=>$nowDate
            ];
            $messageIdemData = [
                'bid'=>$value['id'],
                'code' => MessageConstant::MESSAGE1004
            ];
            $insertMessageIdemData[] = $messageIdemData;
            $insertWeixinMessageData[] = $weixinMessageData;
        }

        if(!empty($insertWeixinMessageData)){
            Message::query()->insert($insertWeixinMessageData);
            MessageIdem::query()->insert($insertMessageIdemData);
        }
    }
}

