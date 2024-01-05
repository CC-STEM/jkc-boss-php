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

use App\Service\CouponService;

class CouponController extends AbstractController
{
    /**
     * 添加优惠券模板
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function addCouponTemplate()
    {
        try {
            $params = $this->request->post();
            $couponService = new CouponService();
            $result = $couponService->addCouponTemplate($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'addCouponTemplate');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 编辑优惠券模板
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function editCouponTemplate()
    {
        try {
            $params = $this->request->post();
            $couponService = new CouponService();
            $result = $couponService->editCouponTemplate($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'editCouponTemplate');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 优惠券模板列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function couponTemplateList()
    {
        try {
            [$page, $pageSize, $offset] = $this->getPagingParams();
            $couponService = new CouponService();
            $couponService->offset = $offset;
            $couponService->limit = $pageSize;
            $result = $couponService->couponTemplateList();
            $data = [
                'list' => $result['data']['list'],
                'page' => ['page' => $page, 'page_size' => $pageSize,'count' => $result['data']['count']],
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'couponTemplateList');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 优惠券发放
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function issuedCoupon()
    {
        try {
            $params = $this->request->post();
            $couponService = new CouponService();
            $result = $couponService->issuedCoupon($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'issuedCoupon');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 门店成员优惠券发放
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function physicalStoreAllStaffIssuedCoupon()
    {
        try {
            $params = $this->request->post();
            $couponService = new CouponService();
            $result = $couponService->physicalStoreAllStaffIssuedCoupon($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'physicalStoreAllStaffIssuedCoupon');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 优惠券模板下放门店
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function couponTemplateDecentralizePhysicalStore()
    {
        try {
            $params = $this->request->post();
            $couponService = new CouponService();
            $result = $couponService->couponTemplateDecentralizePhysicalStore($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'couponTemplateDecentralizePhysicalStore');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 优惠券模板详情
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function couponTemplateDetail()
    {
        try {
            $id = $this->request->query('id');
            $couponService = new CouponService();
            $result = $couponService->couponTemplateDetail((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'couponTemplateDetail');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 删除优惠券模板
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function deleteCouponTemplate()
    {
        try {
            $params = $this->request->post();
            $id = $params['id'];
            $couponService = new CouponService();
            $result = $couponService->deleteCouponTemplate((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'deleteCouponTemplate');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 优惠券发放列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function issuedCouponList()
    {
        try {
            [$page, $pageSize, $offset] = $this->getPagingParams();
            $id = $this->request->query('id');

            $params = ['id'=>$id];
            $couponService = new CouponService();
            $couponService->offset = $offset;
            $couponService->limit = $pageSize;
            $result = $couponService->issuedCouponList($params);
            $data = [
                'list' => $result['data']['list'],
                'page' => ['page' => $page, 'page_size' => $pageSize,'count' => $result['data']['count']],
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'issuedCouponList');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }
}
