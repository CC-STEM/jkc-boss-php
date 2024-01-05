<?php
declare(strict_types=1);

namespace App\Task;

use App\Logger\Log;
use App\Model\PayApply;
use App\Model\RefundApply;
use App\Model\VipCardOrderRefund;
use Hyperf\DbConnection\Db;

class PayTask extends BaseTask
{
    /**
     * 会员卡订单支付宝退款结果查询
     * @return bool
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function alipayRefundNotifyExecute(): bool
    {
        $date = date('Y-m-d H:i:s');
        $vipCardOrderRefundList = VipCardOrderRefund::query()
            ->leftJoin('vip_card_order','vip_card_order_refund.vip_card_order_id','=','vip_card_order.id')
            ->select(['vip_card_order_refund.id','vip_card_order_refund.vip_card_order_id','vip_card_order_refund.amount','vip_card_order.order_no'])
            ->where(['vip_card_order.pay_code'=>'ALIPAY','vip_card_order_refund.status'=>24])
            ->offset(0)->limit(5)
            ->get();
        $vipCardOrderRefundList = $vipCardOrderRefundList->toArray();
        if(empty($vipCardOrderRefundList)){
            return true;
        }
        $aLiPayConfig = json_decode(env('ALIPAY'), true);
        $merchantPrivateKey = file_get_contents($aLiPayConfig['merchantPrivateKey']);
        $appCertPath = $aLiPayConfig['merchantCertPath']; //应用公钥证书路径（要确保证书文件可读），例如：/home/admin/cert/appCertPublicKey_2019051064521003.crt
        $alipayCertPath = $aLiPayConfig['alipayCertPath']; //支付宝公钥证书路径（要确保证书文件可读），例如：/home/admin/cert/alipayCertPublicKey_RSA2.crt
        $rootCertPath = $aLiPayConfig['alipayRootCertPath']; //支付宝根证书路径（要确保证书文件可读），例如：/home/admin/cert/alipayRootCert.crt
        $c = new \AopCertClient();
        $c->gatewayUrl = "https://openapi.alipay.com/gateway.do";
        $c->appId = $aLiPayConfig['appId'];
        $c->rsaPrivateKey = $merchantPrivateKey;
        $c->signType= "RSA2";
        //调用getPublicKey从支付宝公钥证书中提取公钥
        $c->alipayrsaPublicKey = $c->getPublicKey($alipayCertPath);
        //是否校验自动下载的支付宝公钥证书，如果开启校验要保证支付宝根证书在有效期内
        $c->isCheckAlipayPublicCert = true;
        //调用getCertSN获取证书序列号
        $c->appCertSN = $c->getCertSN($appCertPath);
        //调用getRootCertSN获取支付宝根证书序列号
        $c->alipayRootCertSN = $c->getRootCertSN($rootCertPath);

        foreach($vipCardOrderRefundList as $value){
            $orderRefundId = $value['id'];
            $vipCardOrderId = $value['vip_card_order_id'];

            $refundApplyInfo = RefundApply::query()->select(['out_refund_no'])->where(['order_refund_id'=>$orderRefundId])->first();
            if(empty($refundApplyInfo)){
                return false;
            }
            $refundApplyInfo = $refundApplyInfo->toArray();
            $outRefundNo = $refundApplyInfo['out_refund_no'];

            $payApplyInfo = PayApply::query()->select(['out_trade_no'])->where(['order_no'=>$value['order_no']])->first();
            if(empty($payApplyInfo)){
                return false;
            }
            $payApplyInfo = $payApplyInfo->toArray();

            $bizContent = [
                'out_trade_no'=>$payApplyInfo['out_trade_no'],
                'out_request_no'=>$outRefundNo,
                'refund_amount'=>$value['amount']
            ];
            $request = new \AlipayTradeFastpayRefundQueryRequest();
            $request->setBizContent(json_encode($bizContent));
            $result = $c->execute($request);
            $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
            if($result->$responseNode->refund_status !== 'REFUND_SUCCESS'){
                Log::get()->info("alipayRefundNotify[{$orderRefundId}]:".$result->$responseNode->msg);
                continue;
            }
            $paramsString = json_encode($result->$responseNode,JSON_UNESCAPED_UNICODE);

            Db::connection('jkc_edu')->beginTransaction();
            try{
                $vipCardOrderRefundAffected = Db::connection('jkc_edu')->table('vip_card_order_refund')->where(['id'=>$orderRefundId,'status'=>24])->update(['status'=>25,'operated_at'=>$date]);
                if(!$vipCardOrderRefundAffected){
                    Db::connection('jkc_edu')->rollBack();
                    return false;
                }
                $vipCardOrderAffected = Db::connection('jkc_edu')->table('vip_card_order')->where(['id'=>$vipCardOrderId,'order_status'=>0])->update(['order_status'=>3]);
                if(!$vipCardOrderAffected){
                    Db::connection('jkc_edu')->rollBack();
                    return false;
                }
                $refundApplyAffected = Db::connection('jkc_edu')->table('refund_apply')->where(['out_refund_no'=>$outRefundNo,'status'=>0,'order_type'=>2])->update(['status'=>1,'resp_data'=>$paramsString]);
                if(!$refundApplyAffected){
                    Db::connection('jkc_edu')->rollBack();
                    return false;
                }

                Db::connection('jkc_edu')->commit();
            } catch(\Throwable $e){
                Db::connection('jkc_edu')->rollBack();
                $error = ['tag'=>"alipayRefundNotify",'msg'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()];
                Log::get()->error(json_encode($error));
            }
        }
        return true;
    }

}

