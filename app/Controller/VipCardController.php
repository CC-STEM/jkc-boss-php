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

use App\Service\VipCardService;

class VipCardController extends AbstractController
{
    /**
     * 添加会员卡
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function addVipCard()
    {
        try {
            $params = $this->request->post();
            $vipCardService = new VipCardService();
            $result = $vipCardService->addVipCard($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'addVipCard');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 编辑会员卡
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function editVipCard()
    {
        try {
            $params = $this->request->post();
            $vipCardService = new VipCardService();
            $result = $vipCardService->editVipCard($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'editVipCard');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 会员卡排序
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function vipCardSort()
    {
        try {
            $params = $this->request->post();
            $vipCardService = new VipCardService();
            $result = $vipCardService->vipCardSort($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'vipCardSort');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 会员卡列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function vipCardList()
    {
        try {
            $type = $this->request->query('type');
            $physicalStore = $this->request->query('physical_store');
            $themeType = $this->request->query('theme_type');

            $params = ['type'=>$type,'physical_store'=>$physicalStore,'theme_type'=>$themeType];
            $vipCardService = new VipCardService();
            $result = $vipCardService->vipCardList($params);
            $data = [
                'list' => $result['data']
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'vipCardList');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 新人礼包会员卡列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function newcomerVipCardList()
    {
        try {
            $vipCardService = new VipCardService();
            $result = $vipCardService->newcomerVipCardList();
            $data = [
                'list' => $result['data']
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'newcomerVipCardList');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 会员卡详情
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function vipCardDetail()
    {
        try {
            $id = $this->request->query('id');
            $vipCardService = new VipCardService();
            $result = $vipCardService->vipCardDetail((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'vipCardDetail');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 删除会员卡
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function deleteVipCard()
    {
        try {
            $params = $this->request->post();
            $id = $params['id'];
            $vipCardService = new VipCardService();
            $result = $vipCardService->deleteVipCard((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'deleteVipCard');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 会员卡订单列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function vipCardOrderList()
    {
        try {
            [$page, $pageSize, $offset] = $this->getPagingParams();
            $mobile = $this->request->query('mobile');
            $cardName = $this->request->query('card_name');
            $startDate = $this->request->query('start_date');
            $endDate = $this->request->query('end_date');
            $status = $this->request->query('status');
            $orderType = $this->request->query('order_type');
            $vipCardId = $this->request->query('vip_card_id');
            $memberName = $this->request->query('member_name');

            $params = [
                'mobile'=>$mobile,
                'card_name'=>$cardName,
                'start_date'=>$startDate,
                'end_date'=>$endDate,
                'status'=>$status,
                'order_type'=>$orderType,
                'vip_card_id'=>$vipCardId,
                'member_name'=>$memberName,
            ];
            $vipCardService = new VipCardService();
            $vipCardService->offset = $offset;
            $vipCardService->limit = $pageSize;
            $result = $vipCardService->vipCardOrderList($params);
            $data = [
                'list' => $result['data']['list'],
                'page' => ['page' => $page, 'page_size' => $pageSize,'count' => $result['data']['count']],
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'vipCardOrderList');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 会员卡订单导出
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function vipCardOrderExport()
    {
        try {
            $mobile = $this->request->query('mobile');
            $cardName = $this->request->query('card_name');
            $startDate = $this->request->query('start_date');
            $endDate = $this->request->query('end_date');

            $params = ['mobile'=>$mobile,'card_name'=>$cardName,'start_date'=>$startDate,'end_date'=>$endDate];
            $vipCardService = new VipCardService();
            $result = $vipCardService->vipCardOrderExport($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'vipCardOrderExport');
        }
        defer(function ()use($data){
            //unlink($data['path']);
        });
        return $this->download($data['path']);
    }

    /**
     * 会员卡订单退款
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function vipCardOrderRefund()
    {
        try {
            $params = $this->request->post();
            $vipCardService = new VipCardService();
            $result = $vipCardService->vipCardOrderRefund($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'vipCardOrderRefund');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 赠送会员卡订单列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function giftVipCardOrderList()
    {
        try {
            [$page, $pageSize, $offset] = $this->getPagingParams();
            $query = $this->request->query();

            $vipCardService = new VipCardService();
            $vipCardService->offset = $offset;
            $vipCardService->limit = $pageSize;
            $result = $vipCardService->giftVipCardOrderList($query);
            $data = [
                'list' => $result['data']['list'],
                'page' => ['page' => $page, 'page_size' => $pageSize,'count' => $result['data']['count']],
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'giftVipCardOrderList');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }


    /**
     * 平台赠送会员卡详情
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function giftVipCardOrderDetail()
    {
        try {
            [$page, $pageSize, $offset] = $this->getPagingParams();
            $query = $this->request->query();

            $vipCardService = new VipCardService();
            $vipCardService->offset = $offset;
            $vipCardService->limit = $pageSize;
            $result = $vipCardService->giftVipCardOrderDetail($query);
            $data = [
                'list' => $result['data']['list'],
                'page' => ['page' => $page, 'page_size' => $pageSize, 'count' => $result['data']['count']],
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e, 'giftVipCardOrderDetail');
        }

        return $this->responseSuccess($data, $result['msg'], $result['code']);
    }

}
