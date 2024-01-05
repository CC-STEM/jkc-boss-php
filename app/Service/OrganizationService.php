<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Admins;
use App\Constants\ErrorCode;
use App\Snowflake\IdGenerator;

class OrganizationService extends BaseService
{
    /**
     * 添加管理员
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function addAdmins(array $params): array
    {
        $mobile = $params['mobile'];

        $insertAdminsData['id'] = IdGenerator::generate();
        $insertAdminsData['name'] = $params['name'];
        $insertAdminsData['mobile'] = $mobile;
        $insertAdminsData['admin_permissions_id'] = $params['admin_permissions_id'];

        Admins::query()->insert($insertAdminsData);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 编辑管理员
     * @param array $params
     * @return array
     */
    public function editAdmins(array $params): array
    {
        $id = $params['id'];
        $mobile = $params['mobile'];

        $updateAdminsData['name'] = $params['name'];
        $updateAdminsData['mobile'] = $mobile;
        $updateAdminsData['admin_permissions_id'] = $params['admin_permissions_id'];

        Admins::query()->where(['id'=>$id])->update($updateAdminsData);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 删除管理员
     * @param int $id
     * @return array
     */
    public function deleteAdmins(int $id): array
    {
        Admins::query()->where(['id'=>$id])->delete();
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 管理员列表
     * @param array $params
     * @return array
     */
    public function adminsList(array $params): array
    {
        $mobile = $params['mobile'];

        $where = [];
        if($mobile !== null){
            $where['admins.mobile'] = $mobile;
        }
        $adminsList = Admins::query()
            ->leftJoin('admin_permissions','admins.admin_permissions_id','=','admin_permissions.id')
            ->select(['admins.id','admins.name','admins.mobile','admins.created_at','admins.admin_permissions_id','admin_permissions.name as permissions_name'])
            ->where($where)
            ->get();
        $adminsList = $adminsList->toArray();

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $adminsList];
    }

    /**
     * 管理员详情
     * @param int $id
     * @return array
     */
    public function adminsDetail(int $id): array
    {
        $adminsInfo = Admins::query()
            ->select(['id','name','mobile','admin_permissions_id'])
            ->where(['id'=>$id])
            ->first();
        if(empty($adminsInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '信息错误', 'data' => null];
        }
        $adminsInfo = $adminsInfo->toArray();

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $adminsInfo];
    }

}