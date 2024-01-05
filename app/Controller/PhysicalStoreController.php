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

use App\Service\PhysicalStoreService;

class PhysicalStoreController extends AbstractController
{
    /**
     * 地址校验
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function addressVerify()
    {
        try {
            $params = $this->request->post();
            $physicalStoreService = new PhysicalStoreService();
            $result = $physicalStoreService->addressVerify($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'addressVerify');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 添加门店
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function addPhysicalStore()
    {
        try {
            $params = $this->request->post();
            $physicalStoreService = new PhysicalStoreService();
            $result = $physicalStoreService->addPhysicalStore($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'addPhysicalStore');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 编辑门店
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function editPhysicalStore()
    {
        try {
            $params = $this->request->post();
            $physicalStoreService = new PhysicalStoreService();
            $result = $physicalStoreService->editPhysicalStore($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'editPhysicalStore');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 门店列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function physicalStoreList()
    {
        try {
            [$page, $pageSize, $offset] = $this->getPagingParams();
            $mobile = $this->request->query('mobile');

            $params = ['mobile'=>$mobile];
            $physicalStoreService = new PhysicalStoreService();
            $physicalStoreService->offset = $offset;
            $physicalStoreService->limit = $pageSize;
            $result = $physicalStoreService->physicalStoreList($params);
            $data = [
                'list' => $result['data'],
                'page' => ['page' => $page, 'page_size' => $pageSize],
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'physicalStoreList');
        }
        return $this->responseSuccess($data);
    }

    /**
     * 门店详情
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function physicalStoreDetail()
    {
        try {
            $id = $this->request->query('id');
            $physicalStoreService = new PhysicalStoreService();
            $result = $physicalStoreService->physicalStoreDetail((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'physicalStoreDetail');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 删除详情
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function deletePhysicalStore()
    {
        try {
            $params = $this->request->post();
            $id = $params['id'];
            $physicalStoreService = new PhysicalStoreService();
            $result = $physicalStoreService->deletePhysicalStore((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'deletePhysicalStore');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 目标设定
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function goalSetting()
    {
        try {
            $params = $this->request->post();
            $physicalStoreService = new PhysicalStoreService();
            $result = $physicalStoreService->goalSetting($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'goalSetting');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

}
