<?php

declare(strict_types=1);

namespace App\Service;

use App\Lib\WeChat\MessageFactory;
use App\Logger\Log;
use App\Model\AdminPermissions;
use App\Model\AdminPermissionsRoute;
use App\Model\AdminRoute;
use App\Model\PhysicalStoreAdminPermissions;
use App\Model\PhysicalStoreAdminPermissionsRoute;
use App\Snowflake\IdGenerator;
use App\Constants\ErrorCode;
use Hyperf\DbConnection\Db;

class AdminPermissionsService extends BaseService
{
    /**
     * 添加权限
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function addAdminPermissions(array $params): array
    {
        $adminRoute = $params['admin_route_id'];
        //管理后台权限数据
        $adminPermissionsId = IdGenerator::generate();
        $insertAdminPermissionsData['id'] = $adminPermissionsId;
        $insertAdminPermissionsData['name'] = $params['name'];
        //管理后台权限路由数据
        $insertAdminPermissionsRouteData = [];
        foreach($adminRoute as $value){
            $adminPermissionsRouteData = [];
            $adminPermissionsRouteData['id'] = IdGenerator::generate();
            $adminPermissionsRouteData['admin_permissions_id'] = $adminPermissionsId;
            $adminPermissionsRouteData['admin_route_id'] = $value;
            $insertAdminPermissionsRouteData[] = $adminPermissionsRouteData;
        }

        AdminPermissions::query()->insert($insertAdminPermissionsData);
        AdminPermissionsRoute::query()->insert($insertAdminPermissionsRouteData);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 编辑权限
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function editAdminPermissions(array $params): array
    {
        $id = $params['id'];
        $adminRoute = $params['admin_route_id'];

        //管理后台权限数据
        $updateAdminPermissionsData['name'] = $params['name'];
        //管理后台权限路由数据
        $insertAdminPermissionsRouteData = [];
        foreach($adminRoute as $value){
            $adminPermissionsRouteData = [];
            $adminPermissionsRouteData['id'] = IdGenerator::generate();
            $adminPermissionsRouteData['admin_permissions_id'] = $id;
            $adminPermissionsRouteData['admin_route_id'] = $value;
            $insertAdminPermissionsRouteData[] = $adminPermissionsRouteData;
        }

        Db::connection('jkc_edu')->beginTransaction();
        try{
            Db::connection('jkc_edu')->table('admin_permissions_route')->where(['admin_permissions_id'=>$id])->delete();
            Db::connection('jkc_edu')->table('admin_permissions')->where(['id'=>$id])->update($updateAdminPermissionsData);
            Db::connection('jkc_edu')->table('admin_permissions_route')->insert($insertAdminPermissionsRouteData);
            Db::connection('jkc_edu')->commit();
        } catch(\Throwable $e){
            Db::connection('jkc_edu')->rollBack();
            throw new \Exception($e->getMessage(), 1);
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 删除权限
     * @param int $id
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function deleteAdminPermissions(int $id): array
    {
        AdminPermissions::query()->where(['id'=>$id])->delete();
        AdminPermissionsRoute::query()->where(['admin_permissions_id'=>$id])->delete();
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 权限列表
     * @return array
     */
    public function adminPermissionsList(): array
    {
        $adminPermissionsList = AdminPermissions::query()->select(['id','name','created_at'])->get();
        $adminPermissionsList = $adminPermissionsList->toArray();

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $adminPermissionsList];
    }

    /**
     * 权限详情
     * @param int $id
     * @return array
     */
    public function adminPermissionsDetail(int $id): array
    {
        $adminPermissionsInfo = AdminPermissions::query()->select(['id','name'])->where(['id'=>$id])->first();
        if(empty($adminPermissionsInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '信息错误', 'data' => null];
        }
        $adminPermissionsInfo = $adminPermissionsInfo->toArray();
        $adminPermissionsId = $adminPermissionsInfo['id'];

        $adminPermissionsRouteList = AdminPermissionsRoute::query()->select(['admin_route_id'])->where(['admin_permissions_id'=>$adminPermissionsId])->get();
        $adminPermissionsRouteList = $adminPermissionsRouteList->toArray();
        $adminPermissionsRouteList = array_column($adminPermissionsRouteList,'admin_route_id');

        $adminPermissionsInfo['route'] = $adminPermissionsRouteList;
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $adminPermissionsInfo];
    }

    /**
     * 路由列表
     * @return array
     */
    public function adminRouteList(): array
    {
        $adminRouteList = AdminRoute::query()->select(['id','parent_id','name','identify','path'])->get();
        $adminRouteList = $adminRouteList->toArray();
        $adminRouteListGroup = $this->functions->arrayGroupBy($adminRouteList,'parent_id');
        $parentAdminRoute = $adminRouteListGroup['0'];

        foreach($parentAdminRoute as $key=>$value){
            $id = $value['id'];
            $child = $adminRouteListGroup[$id] ?? [];
            unset($parentAdminRoute[$key]['parent_id']);
            $parentAdminRoute[$key]['child'] = $child;
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $parentAdminRoute];
    }

    /**
     * 管理后台超级管理员新增路由
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function adminPermissionsAddRouteBoss(array $params): array
    {
        $adminRouteId = $params['admin_route_id'];

        $adminPermissionsInfo = AdminPermissions::query()
            ->select(['id'])
            ->where(['name'=>'超级管理员'])
            ->first();
        $adminPermissionsInfo = $adminPermissionsInfo->toArray();
        if(empty($adminPermissionsInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '超级管理员权限未找到', 'data' => null];
        }
        $adminPermissionsRouteExists = AdminPermissionsRoute::query()->where(['admin_permissions_id'=>$adminPermissionsInfo['id'],'admin_route_id'=>$adminRouteId])->exists();
        if($adminPermissionsRouteExists === true){
            return ['code' => ErrorCode::WARNING, 'msg' => '权限路由已存在', 'data' => null];
        }
        $insertAdminPermissionsRouteData['id'] = IdGenerator::generate();
        $insertAdminPermissionsRouteData['admin_permissions_id'] = $adminPermissionsInfo['id'];
        $insertAdminPermissionsRouteData['admin_route_id'] = $adminRouteId;

        AdminPermissionsRoute::query()->insert($insertAdminPermissionsRouteData);

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 门店管理后台超级管理员新增路由
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function adminPermissionsAddRouteBusiness(array $params): array
    {
        $physicalStoreAdminRouteId = $params['physical_store_admin_route_id'];

        $physicalStoreAdminPermissionsList = PhysicalStoreAdminPermissions::query()
            ->select(['id'])
            ->where(['name'=>'超级管理员'])
            ->get();
        $physicalStoreAdminPermissionsList = $physicalStoreAdminPermissionsList->toArray();
        if(empty($physicalStoreAdminPermissionsList)){
            return ['code' => ErrorCode::WARNING, 'msg' => '超级管理员权限未找到', 'data' => null];
        }

        $insertPhysicalStoreAdminPermissionsRouteData = [];
        foreach($physicalStoreAdminPermissionsList as $value){
            $physicalStoreAdminPermissionsRouteData = [];

            $physicalStoreAdminPermissionsRouteExists = PhysicalStoreAdminPermissionsRoute::query()->where(['physical_store_admin_permissions_id'=>$value['id'],'physical_store_admin_route_id'=>$physicalStoreAdminRouteId])->exists();
            if($physicalStoreAdminPermissionsRouteExists === true){
                continue;
            }
            $physicalStoreAdminPermissionsRouteData['id'] = IdGenerator::generate();
            $physicalStoreAdminPermissionsRouteData['physical_store_admin_permissions_id'] = $value['id'];
            $physicalStoreAdminPermissionsRouteData['physical_store_admin_route_id'] = $physicalStoreAdminRouteId;
            $insertPhysicalStoreAdminPermissionsRouteData[] = $physicalStoreAdminPermissionsRouteData;
        }
        $physicalStoreAdminPermissionsRouteExists = PhysicalStoreAdminPermissionsRoute::query()->where(['physical_store_admin_permissions_id'=>1000,'physical_store_admin_route_id'=>$physicalStoreAdminRouteId])->exists();
        if($physicalStoreAdminPermissionsRouteExists === false){
            $insertPhysicalStoreAdminPermissionsRouteData[] = [
                'id' => IdGenerator::generate(),
                'physical_store_admin_permissions_id' => 1000,
                'physical_store_admin_route_id' => $physicalStoreAdminRouteId
            ];
        }
        if(!empty($insertPhysicalStoreAdminPermissionsRouteData)){
            PhysicalStoreAdminPermissionsRoute::query()->insert($insertPhysicalStoreAdminPermissionsRouteData);
        }

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }
}