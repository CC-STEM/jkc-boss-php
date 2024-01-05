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

use App\Service\AdminPermissionsService;

class AdminPermissionsController extends AbstractController
{
    /**
     * 添加权限
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function addAdminPermissions()
    {
        try {
            $params = $this->request->post();
            $adminPermissionsService = new AdminPermissionsService();
            $result = $adminPermissionsService->addAdminPermissions($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'addAdminPermissions');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 编辑权限
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function editAdminPermissions()
    {
        try {
            $params = $this->request->post();
            $adminPermissionsService = new AdminPermissionsService();
            $result = $adminPermissionsService->editAdminPermissions($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'editAdminPermissions');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 删除权限
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function deleteAdminPermissions()
    {
        try {
            $params = $this->request->post();
            $id = $params['id'];
            $adminPermissionsService = new AdminPermissionsService();
            $result = $adminPermissionsService->deleteAdminPermissions((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'deleteAdminPermissions');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 权限列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function adminPermissionsList()
    {
        try {
            $adminPermissionsService = new AdminPermissionsService();
            $result = $adminPermissionsService->adminPermissionsList();
            $data = [
                'list' => $result['data']
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'adminPermissionsList');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 权限详情
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function adminPermissionsDetail()
    {
        try {
            $id = $this->request->query('id');
            $adminPermissionsService = new AdminPermissionsService();
            $result = $adminPermissionsService->adminPermissionsDetail((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'adminPermissionsDetail');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 路由列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function adminRouteList()
    {
        try {
            $adminPermissionsService = new AdminPermissionsService();
            $result = $adminPermissionsService->adminRouteList();
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'adminRouteList');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 管理后台超级管理员新增路由
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function adminPermissionsAddRouteBoss()
    {
        try {
            $params = $this->request->post();
            $adminPermissionsService = new AdminPermissionsService();
            $result = $adminPermissionsService->adminPermissionsAddRouteBoss($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'adminPermissionsAddRouteBoss');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 门店管理后台超级管理员新增路由
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function adminPermissionsAddRouteBusiness()
    {
        try {
            $params = $this->request->post();
            $adminPermissionsService = new AdminPermissionsService();
            $result = $adminPermissionsService->adminPermissionsAddRouteBusiness($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'adminPermissionsAddRouteBusiness');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }
}
