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

use App\Service\OrganizationService;

class OrganizationController extends AbstractController
{
    /**
     * 添加管理员
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function addAdmins()
    {
        try {
            $params = $this->request->post();
            $organizationService = new OrganizationService();
            $result = $organizationService->addAdmins($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'addAdmins');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 编辑管理员
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function editAdmins()
    {
        try {
            $params = $this->request->post();
            $organizationService = new OrganizationService();
            $result = $organizationService->editAdmins($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'editAdmins');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 删除管理员
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function deleteAdmins()
    {
        try {
            $params = $this->request->post();
            $id = $params['id'];
            $organizationService = new OrganizationService();
            $result = $organizationService->deleteAdmins((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'deleteAdmins');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 管理员列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function adminsList()
    {
        try {
            $mobile = $this->request->query('mobile');

            $params = ['mobile' => $mobile];
            $organizationService = new OrganizationService();
            $result = $organizationService->adminsList($params);
            $data = [
                'list' => $result['data']
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'adminsList');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 管理员详情
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function adminsDetail()
    {
        try {
            $id = $this->request->query('id');
            $organizationService = new OrganizationService();
            $result = $organizationService->adminsDetail((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'adminsDetail');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }
}
