<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\OrderRefund;
use App\Model\RefundApply;
use App\Constants\ErrorCode;
use App\Lib\WeChat\WeChatPayFactory;
USE App\Logger\Log;
use App\Model\VipCardOrderRefund;
use Hyperf\DbConnection\Db;

class PayService extends BaseService
{
    /**
     * 商品退款回调
     * @param array $params
     * @return array
     * @throws \Exception
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function goodsRefundCallback(array $params): array
    {
        $outRefundNo = $params['out_refund_no'];
        $refundStatus = $params['refund_status'];
        $bodyResource = $params['body_resource'];
        $paramsString = $bodyResource !== null ? json_encode($bodyResource,JSON_UNESCAPED_UNICODE) : '';

        if($refundStatus !== 'SUCCESS'){
            return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
        }
        $refundApplyInfo = RefundApply::query()->select(['order_refund_id'])->where(['out_refund_no'=>$outRefundNo])->first();
        if(empty($refundApplyInfo)){
            return ['code' => ErrorCode::FAILURE, 'msg' => '订单信息不存在', 'data' => null];
        }
        $refundApplyInfo = $refundApplyInfo->toArray();
        $orderRefundId = $refundApplyInfo['order_refund_id'];

        $orderRefundInfo = OrderRefund::query()->select(['id','order_goods_id'])->where(['id'=>$orderRefundId,'status'=>24])->first();
        if(empty($orderRefundInfo)){
            Log::get()->info("goodsOrderRefundCallbackWx[{$outRefundNo}]:订单信息不存在");
            return ['code' => ErrorCode::FAILURE, 'msg' => '订单信息不存在', 'data' => null];
        }
        $orderRefundInfo = $orderRefundInfo->toArray();
        $orderGoodsId = $orderRefundInfo['order_goods_id'];
        $date = date('Y-m-d H:i:s');

        Db::connection('jkc_edu')->beginTransaction();
        try{
            $orderRefundAffected = Db::connection('jkc_edu')->table('order_refund')->where(['id'=>$orderRefundId,'status'=>24])->update(['status'=>25,'operated_at'=>$date]);
            if(!$orderRefundAffected){
                Db::connection('jkc_edu')->rollBack();
                return ['code' => ErrorCode::FAILURE, 'msg' => '售后订单操作异常-1', 'data' => null];
            }
            $orderGoodsAffected = Db::connection('jkc_edu')->table('order_goods')->where(['id'=>$orderGoodsId,'order_status'=>0,'pay_status'=>1])->update(['order_status'=>3,'is_refund'=>0]);
            if(!$orderGoodsAffected){
                Db::connection('jkc_edu')->rollBack();
                return ['code' => ErrorCode::FAILURE, 'msg' => '售后订单操作异常-2', 'data' => null];
            }
            $refundApplyAffected = Db::connection('jkc_edu')->table('refund_apply')->where(['out_refund_no'=>$outRefundNo,'status'=>0,'order_type'=>3])->update(['status'=>1,'resp_data'=>$paramsString]);
            if(!$refundApplyAffected){
                Db::connection('jkc_edu')->rollBack();
                return ['code' => ErrorCode::FAILURE, 'msg' => '售后订单操作异常-3', 'data' => null];
            }

            Db::connection('jkc_edu')->commit();
        } catch(\Throwable $e){
            Db::connection('jkc_edu')->rollBack();
            throw new \Exception($e->getMessage(), 1);
        }

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 会员卡退款回调
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function vipCardRefundCallback(array $params): array
    {
        $outRefundNo = $params['out_refund_no'];
        $refundStatus = $params['refund_status'];
        $bodyResource = $params['body_resource'];
        $paramsString = $bodyResource !== null ? json_encode($bodyResource,JSON_UNESCAPED_UNICODE) : '';

        if($refundStatus !== 'SUCCESS'){
            return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
        }
        $refundApplyInfo = RefundApply::query()->select(['order_refund_id'])->where(['out_refund_no'=>$outRefundNo])->first();
        if(empty($refundApplyInfo)){
            return ['code' => ErrorCode::FAILURE, 'msg' => '订单信息不存在', 'data' => null];
        }
        $refundApplyInfo = $refundApplyInfo->toArray();
        $orderRefundId = $refundApplyInfo['order_refund_id'];

        $vipCardOrderRefundInfo = VipCardOrderRefund::query()->select(['id','vip_card_order_id'])->where(['id'=>$orderRefundId,'status'=>24])->first();
        if(empty($vipCardOrderRefundInfo)){
            Log::get()->info("vipCardRefundCallbackWx[{$outRefundNo}]:订单信息不存在");
            return ['code' => ErrorCode::FAILURE, 'msg' => '订单信息不存在', 'data' => null];
        }
        $vipCardOrderRefundInfo = $vipCardOrderRefundInfo->toArray();
        $vipCardOrderId = $vipCardOrderRefundInfo['vip_card_order_id'];
        $date = date('Y-m-d H:i:s');

        Db::connection('jkc_edu')->beginTransaction();
        try{
            $vipCardOrderRefundAffected = Db::connection('jkc_edu')->table('vip_card_order_refund')->where(['id'=>$orderRefundId,'status'=>24])->update(['status'=>25,'operated_at'=>$date]);
            if(!$vipCardOrderRefundAffected){
                Db::connection('jkc_edu')->rollBack();
                return ['code' => ErrorCode::FAILURE, 'msg' => '售后订单操作异常-1', 'data' => null];
            }
            $vipCardOrderAffected = Db::connection('jkc_edu')->table('vip_card_order')->where(['id'=>$vipCardOrderId,'order_status'=>0])->update(['order_status'=>3]);
            if(!$vipCardOrderAffected){
                Db::connection('jkc_edu')->rollBack();
                return ['code' => ErrorCode::FAILURE, 'msg' => '订单退款操作异常-2', 'data' => null];
            }
            $refundApplyAffected = Db::connection('jkc_edu')->table('refund_apply')->where(['out_refund_no'=>$outRefundNo,'status'=>0,'order_type'=>2])->update(['status'=>1,'resp_data'=>$paramsString]);
            if(!$refundApplyAffected){
                Db::connection('jkc_edu')->rollBack();
                return ['code' => ErrorCode::FAILURE, 'msg' => '售后订单操作异常-3', 'data' => null];
            }

            Db::connection('jkc_edu')->commit();
        } catch(\Throwable $e){
            Db::connection('jkc_edu')->rollBack();
            throw new \Exception($e->getMessage(), 1);
        }

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 微信支付回调验签
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function weChatVerify(array $params): array
    {
        $weChatPayFactory = new WeChatPayFactory();
        $weChatPayFactory->inWechatpaySignature = $params['inWechatpaySignature'];
        $weChatPayFactory->inWechatpayTimestamp = $params['inWechatpayTimestamp'];
        $weChatPayFactory->inWechatpayNonce = $params['inWechatpayNonce'];
        $weChatPayFactory->inWechatpaySerial = $params['inWechatpaySerial'];
        $weChatPayFactory->inWechatpayBody = $params['inWechatpayBody'];
        $result = $weChatPayFactory->verify();
        if($result['code'] === ErrorCode::FAILURE){
            Log::get()->info("weChatVerify：签名验证失败:".json_encode($params));
        }
        return $result;
    }


}