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

use App\Service\OrderService;

class OrderController extends AbstractController
{
    /**
     * 教具订单延长收货
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function teachingAidsOrderExtendReceipt()
    {
        try {
            $params = $this->request->post();
            $orderService = new OrderService();
            $result = $orderService->teachingAidsOrderExtendReceipt($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'teachingAidsOrderExtendReceipt');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 教具订单发货
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function teachingAidsOrderShipment()
    {
        try {
            $params = $this->request->post();
            $orderService = new OrderService();
            $result = $orderService->teachingAidsOrderShipment($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'teachingAidsOrderShipment');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 教具订单列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function teachingAidsOrderList()
    {
        try {
            [$page, $pageSize, $offset] = $this->getPagingParams();
            $orderNo = $this->request->query('order_no');
            $goodsName = $this->request->query('goods_name');
            $goodsId = $this->request->query('goods_id');
            $mobile = $this->request->query('mobile');
            $status = $this->request->query('status');
            $payAtMin = $this->request->query('pay_at_min');
            $payAtMax = $this->request->query('pay_at_max');
            $provinceName = $this->request->query('province_name');
            $cityName = $this->request->query('city_name');
            $districtName = $this->request->query('district_name');
            $memberName = $this->request->query('member_name');

            $params = [
                'order_no'=>$orderNo,
                'goods_name'=>$goodsName,
                'goods_id'=>$goodsId,
                'mobile'=>$mobile,
                'status'=>$status,
                'pay_at_min'=>$payAtMin,
                'pay_at_max'=>$payAtMax,
                'province_name'=>$provinceName,
                'city_name'=>$cityName,
                'district_name'=>$districtName,
                'member_name'=>$memberName,
            ];
            $orderService = new OrderService();
            $orderService->offset = $offset;
            $orderService->limit = $pageSize;
            $result = $orderService->teachingAidsOrderList($params);
            $data = [
                'list' => $result['data']['list'],
                'page' => ['page' => $page, 'page_size' => $pageSize,'count' => $result['data']['count']],
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'teachingAidsOrderList');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 教具订单详情
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function teachingAidsOrderDetail()
    {
        try {
            $id = $this->request->query('id');
            $orderService = new OrderService();
            $result = $orderService->teachingAidsOrderDetail((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'teachingAidsOrderDetail');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 教具订单售后列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function teachingAidsRefundOrderList()
    {
        try {
            [$page, $pageSize, $offset] = $this->getPagingParams();
            $orderNo = $this->request->query('order_no');
            $goodsName = $this->request->query('goods_name');
            $goodsId = $this->request->query('goods_id');
            $mobile = $this->request->query('mobile');
            $status = $this->request->query('status');
            $createdAtMin = $this->request->query('created_at_min');
            $createdAtMax = $this->request->query('created_at_max');
            $provinceName = $this->request->query('province_name');
            $cityName = $this->request->query('city_name');
            $districtName = $this->request->query('district_name');
            $memberName = $this->request->query('member_name');

            $params = [
                'order_no'=>$orderNo,
                'goods_name'=>$goodsName,
                'goods_id'=>$goodsId,
                'mobile'=>$mobile,
                'status'=>$status,
                'created_at_min'=>$createdAtMin,
                'created_at_max'=>$createdAtMax,
                'province_name'=>$provinceName,
                'city_name'=>$cityName,
                'district_name'=>$districtName,
                'member_name'=>$memberName,
            ];
            $orderService = new OrderService();
            $orderService->offset = $offset;
            $orderService->limit = $pageSize;
            $result = $orderService->teachingAidsRefundOrderList($params);
            $data = [
                'list' => $result['data']['list'],
                'page' => ['page' => $page, 'page_size' => $pageSize,'count' => $result['data']['count']],
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'teachingAidsRefundOrderList');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 教具订单售后详情
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function teachingAidsRefundOrderDetail()
    {
        try {
            $id = $this->request->query('id');
            $orderService = new OrderService();
            $result = $orderService->teachingAidsRefundOrderDetail((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'teachingAidsRefundOrderDetail');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 教具订单售后处理
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function handleTeachingAidsRefundOrder()
    {
        try {
            $params = $this->request->post();
            $orderService = new OrderService();
            $result = $orderService->handleTeachingAidsRefundOrder($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'handleTeachingAidsRefundOrder');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 教具订单导出
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function teachingAidsOrderExport()
    {
        try {
            $orderNo = $this->request->query('order_no');
            $goodsName = $this->request->query('goods_name');
            $goodsId = $this->request->query('goods_id');
            $mobile = $this->request->query('mobile');
            $status = $this->request->query('status');
            $payAtMin = $this->request->query('pay_at_min');
            $payAtMax = $this->request->query('pay_at_max');
            $provinceName = $this->request->query('province_name');
            $cityName = $this->request->query('city_name');
            $districtName = $this->request->query('district_name');

            $params = [
                'order_no'=>$orderNo,
                'goods_name'=>$goodsName,
                'goods_id'=>$goodsId,
                'mobile'=>$mobile,
                'status'=>$status,
                'pay_at_min'=>$payAtMin,
                'pay_at_max'=>$payAtMax,
                'province_name'=>$provinceName,
                'city_name'=>$cityName,
                'district_name'=>$districtName,
            ];
            $orderService = new OrderService();
            $result = $orderService->teachingAidsOrderExport($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'teachingAidsOrderExport');
        }
        defer(function ()use($data){
            unlink($data['path']);
        });
        return $this->download($data['path']);
    }

}
