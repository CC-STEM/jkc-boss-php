<?php

namespace App\Listener;

use App\Constants\MessageConstant;
use App\Event\GoodsRefundRegistered;
use App\Event\VipCardOrderRefundRegistered;
use App\Model\Member;
use App\Model\OrderRefund;
use App\Model\PhysicalStoreAdmins;
use App\Model\VipCardOrderRefund;
use App\Model\Message;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;

#[Listener]
class MessageListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            //GoodsRefundRegistered::class,
            //VipCardOrderRefundRegistered::class
        ];
    }

    public function process(object $event): void
    {
        if($event instanceof VipCardOrderRefundRegistered){
            go(function ()use($event){
                $vipCardOrderRefundId = $event->vipCardOrderRefundId;

                $vipCardOrderRefundInfo = VipCardOrderRefund::query()
                    ->leftJoin('vip_card_order','vip_card_order_refund.vip_card_order_id','=','vip_card_order.id')
                    ->select(['vip_card_order.recommend_physical_store_id','vip_card_order.order_title','vip_card_order_refund.amount','vip_card_order_refund.operated_at'])
                    ->where(['vip_card_order_refund.id'=>$vipCardOrderRefundId])
                    ->first();
                $vipCardOrderRefundInfo = $vipCardOrderRefundInfo?->toArray();
                if($vipCardOrderRefundInfo === null || $vipCardOrderRefundInfo['recommend_physical_store_id'] == 0){
                    return;
                }
                $recommendPhysicalStoreId = $vipCardOrderRefundInfo['recommend_physical_store_id'];
                $operatedAt = date('Y年m月d日 H:i',strtotime($vipCardOrderRefundInfo['operated_at']));

                $physicalStoreAdminsInfo = PhysicalStoreAdmins::query()
                    ->select(['physical_store_admins.mobile'])
                    ->leftJoin('physical_store_admins_physical_store','physical_store_admins.id','=','physical_store_admins_physical_store.physical_store_admins_id')
                    ->where(['physical_store_admins_physical_store.physical_store_id'=>$recommendPhysicalStoreId,'physical_store_admins.is_store_manager'=>1])
                    ->first();
                $physicalStoreAdminsInfo = $physicalStoreAdminsInfo?->toArray();
                if($physicalStoreAdminsInfo === null){
                    return;
                }

                $memberInfo = Member::query()
                    ->select(['mini_openid'])
                    ->where(['mobile'=>$physicalStoreAdminsInfo['mobile']])
                    ->first();
                $memberInfo = $memberInfo?->toArray();
                if($memberInfo === null){
                    return;
                }

                $data = [
                    'thing8'=>[
                        'value'=>$vipCardOrderRefundInfo['order_title']
                    ],
                    'amount2'=>[
                        'value'=>$vipCardOrderRefundInfo['amount']
                    ],
                    'time3'=>[
                        'value'=>$operatedAt
                    ]
                ];
                $insertWeixinMessageData = [
                    'touser'=>$memberInfo['mini_openid'],
                    'code' => MessageConstant::MESSAGE2001,
                    'data'=>json_encode($data),
                    'message_type'=>1,
                    'send_at'=>date('Y-m-d H:i:s')
                ];
                Message::query()->insert($insertWeixinMessageData);
            });
        }else if($event instanceof GoodsRefundRegistered){
            $orderRefundId = $event->orderRefundId;

            $orderRefundInfo = OrderRefund::query()
                ->leftJoin('order_info','order_refund.order_info_id','=','order_info.id')
                ->select(['order_info.recommend_physical_store_id','order_info.order_title','order_refund.amount','order_refund.operated_at'])
                ->where(['order_refund.id'=>$orderRefundId])
                ->first();
            $orderRefundInfo = $orderRefundInfo?->toArray();
            if($orderRefundInfo === null || $orderRefundInfo['recommend_physical_store_id'] == 0){
                return;
            }
            $recommendPhysicalStoreId = $orderRefundInfo['recommend_physical_store_id'];
            $operatedAt = date('Y年m月d日 H:i',strtotime($orderRefundInfo['operated_at']));

            $physicalStoreAdminsInfo = PhysicalStoreAdmins::query()
                ->select(['physical_store_admins.mobile'])
                ->leftJoin('physical_store_admins_physical_store','physical_store_admins.id','=','physical_store_admins_physical_store.physical_store_admins_id')
                ->where(['physical_store_admins_physical_store.physical_store_id'=>$recommendPhysicalStoreId,'physical_store_admins.is_store_manager'=>1])
                ->first();
            $physicalStoreAdminsInfo = $physicalStoreAdminsInfo?->toArray();
            if($physicalStoreAdminsInfo === null){
                return;
            }

            $memberInfo = Member::query()
                ->select(['mini_openid'])
                ->where(['mobile'=>$physicalStoreAdminsInfo['mobile']])
                ->first();
            $memberInfo = $memberInfo?->toArray();
            if($memberInfo === null){
                return;
            }

            $data = [
                'thing8'=>[
                    'value'=>$orderRefundInfo['order_title']
                ],
                'amount2'=>[
                    'value'=>$orderRefundInfo['amount']
                ],
                'time3'=>[
                    'value'=>$operatedAt
                ]
            ];
            $insertWeixinMessageData = [
                'touser'=>$memberInfo['mini_openid'],
                'code' => MessageConstant::MESSAGE2001,
                'data'=>json_encode($data),
                'message_type'=>1,
                'send_at'=>date('Y-m-d H:i:s')
            ];
            Message::query()->insert($insertWeixinMessageData);
        }
    }
}