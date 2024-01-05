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
namespace App\Controller;

use App\Service\PayService;
use App\Constants\ErrorCode;

class PayController extends AbstractController
{
    /**
     * 商品退款回调(微信)
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function goodsRefundCallbackWx()
    {
        try {
            $weChatPaySignature = $this->request->header('Wechatpay-Signature', '');
            $weChatPayTimestamp = $this->request->header('Wechatpay-Timestamp', '');
            $weChatPayNonce = $this->request->header('Wechatpay-Nonce', '');
            $weChatPaySerial = $this->request->header('Wechatpay-Serial', '');
            $body = $this->request->post();
            $body = is_array($body) ? json_encode($body, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) : $body;

            $payService = new PayService();
            $verifyData = [
                'inWechatpaySignature' => $weChatPaySignature,
                'inWechatpayTimestamp' => $weChatPayTimestamp,
                'inWechatpayNonce' => $weChatPayNonce,
                'inWechatpaySerial' => $weChatPaySerial,
                'inWechatpayBody' => $body,

            ];
            $verfifyResult = $payService->weChatVerify($verifyData);
            if($verfifyResult['code'] === ErrorCode::FAILURE){
                return $this->response->withStatus(ErrorCode::SERVER_ERROR)->json(['code' => 'FAIL', 'message' => '失败']);
            }
            $inBodyResource = $verfifyResult['data'];
            $params = ['out_refund_no'=>$inBodyResource['out_refund_no'],'refund_status'=>$inBodyResource['refund_status'],'body_resource'=>$inBodyResource];

            $result = $payService->goodsRefundCallback($params);
            if($result['code'] === ErrorCode::FAILURE){
                return $this->response(['code' => 'FAIL', 'message' => '失败'],500);
            }
        } catch (\Throwable $e) {
            return $this->response(['code' => 'FAIL', 'message' => '失败'],500,$e,'goodsRefundCallbackWx');
        }
        return $this->response(['code' => 'SUCCESS', 'message' => '成功']);
    }

    /**
     * 会员卡退款回调(微信)
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function vipCardRefundCallbackWx()
    {
        try {
            $weChatPaySignature = $this->request->header('Wechatpay-Signature', '');
            $weChatPayTimestamp = $this->request->header('Wechatpay-Timestamp', '');
            $weChatPayNonce = $this->request->header('Wechatpay-Nonce', '');
            $weChatPaySerial = $this->request->header('Wechatpay-Serial', '');
            $body = $this->request->post();
            $body = is_array($body) ? json_encode($body, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) : $body;

            $payService = new PayService();
            $verifyData = [
                'inWechatpaySignature' => $weChatPaySignature,
                'inWechatpayTimestamp' => $weChatPayTimestamp,
                'inWechatpayNonce' => $weChatPayNonce,
                'inWechatpaySerial' => $weChatPaySerial,
                'inWechatpayBody' => $body,

            ];
            $verfifyResult = $payService->weChatVerify($verifyData);
            if($verfifyResult['code'] === ErrorCode::FAILURE){
                return $this->response->withStatus(ErrorCode::SERVER_ERROR)->json(['code' => 'FAIL', 'message' => '失败']);
            }
            $inBodyResource = $verfifyResult['data'];
            $params = ['out_refund_no'=>$inBodyResource['out_refund_no'],'refund_status'=>$inBodyResource['refund_status'],'body_resource'=>$inBodyResource];

            $result = $payService->vipCardRefundCallback($params);
            if($result['code'] === ErrorCode::FAILURE){
                return $this->response(['code' => 'FAIL', 'message' => '失败'],500);
            }
        } catch (\Throwable $e) {
            return $this->response(['code' => 'FAIL', 'message' => '失败'],500,$e,'vipCardRefundCallbackWx');
        }
        return $this->response(['code' => 'SUCCESS', 'message' => '成功']);
    }
}
