<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\AdminPermissionsRoute;
use App\Model\AdminRoute;
use App\Model\Admins;
use App\Constants\ErrorCode;
use Hyperf\Utils\Context;

class AdminsService extends BaseService
{
    public function __construct()
    {
        $this->adminsInfo = Context::get('AdminsInfo');
    }

    /**
     * 管理员信息
     * @return array
     */
    public function adminsInfo(): array
    {
        $adminsId = $this->adminsInfo['admins_id'];
        $name = $this->adminsInfo['name'];
        $identity = $this->adminsInfo['identity'];

        if($identity == 2){
            $returnData = [
                'id' => (string)$adminsId,
                'name' => $name,
                'permissions' => ['customer_setting'],
                'identity' => $identity
            ];
            return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $returnData];
        }
        $adminsInfo = Admins::query()
            ->select(['admin_permissions_id','is_default'])
            ->where(['id'=>$adminsId])
            ->first();
        $adminsInfo = $adminsInfo->toArray();
        $isDefault = $adminsInfo['is_default'];
        $adminPermissionsId = $adminsInfo['admin_permissions_id'];

        if($isDefault == 1){
            $adminsPermissionsList = AdminRoute::query()->select(['identify'])->where([['identify','<>','']])->get();
        }else{
            $adminsPermissionsList = AdminPermissionsRoute::query()
                ->leftJoin('admin_route','admin_permissions_route.admin_route_id','=','admin_route.id')
                ->select(['admin_route.identify'])
                ->where(['admin_permissions_route.admin_permissions_id'=>$adminPermissionsId])
                ->get();
        }
        $adminsPermissionsList = $adminsPermissionsList->toArray();
        $adminsPermissionsList = array_column($adminsPermissionsList,'identify');

        $returnData = [
            'id' => $adminsId,
            'name' => $name,
            'permissions' => $adminsPermissionsList
        ];
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $returnData];
    }
}