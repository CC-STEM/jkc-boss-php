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

use App\Service\PhysicalStoreAdminsService;

class PhysicalStoreAdminsController extends AbstractController
{
    /**
     * 添加门店大管理员
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function addPhysicalStoreSeniorAdmins()
    {
        try {
            $params = $this->request->post();
            $physicalStoreAdminsService = new PhysicalStoreAdminsService();
            $result = $physicalStoreAdminsService->addPhysicalStoreSeniorAdmins($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'addPhysicalStoreSeniorAdmins');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 编辑门店大管理员
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function editPhysicalStoreSeniorAdmins()
    {
        try {
            $params = $this->request->post();
            $physicalStoreAdminsService = new PhysicalStoreAdminsService();
            $result = $physicalStoreAdminsService->editPhysicalStoreSeniorAdmins($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'editPhysicalStoreSeniorAdmins');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 删除门店大管理员
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function deletePhysicalStoreSeniorAdmins()
    {
        try {
            $params = $this->request->post();
            $id = $params['id'];
            $physicalStoreAdminsService = new PhysicalStoreAdminsService();
            $result = $physicalStoreAdminsService->deletePhysicalStoreSeniorAdmins((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'deletePhysicalStoreSeniorAdmins');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 门店大管理员列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function physicalStoreSeniorAdminsList()
    {
        try {
            $mobile = $this->request->query('mobile');

            $params = ['mobile' => $mobile];
            $physicalStoreAdminsService = new PhysicalStoreAdminsService();
            $result = $physicalStoreAdminsService->physicalStoreSeniorAdminsList($params);
            $data = [
                'list' => $result['data']
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'physicalStoreSeniorAdminsList');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

}
