<?php

declare(strict_types=1);

namespace App\Service;

use App\Constants\ErrorCode;
use App\Model\PhysicalStoreAdmins;
use App\Model\PhysicalStoreAdminsPhysicalStore;
use App\Snowflake\IdGenerator;

class PhysicalStoreAdminsService extends BaseService
{
    /**
     * 添加门店大管理员
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function addPhysicalStoreSeniorAdmins(array $params): array
    {
        $mobile = $params['mobile'];
        $physicalStore = $params['physical_store'];

        $physicalStoreAdminsExists = PhysicalStoreAdmins::query()->where(['mobile'=>$mobile])->exists();
        if($physicalStoreAdminsExists === true){
            return ['code' => ErrorCode::WARNING, 'msg' => '该手机号已存在', 'data' => null];
        }
        if(empty($physicalStore)){
            return ['code' => ErrorCode::WARNING, 'msg' => '关联门店不能为空', 'data' => null];
        }

        $adminsId = IdGenerator::generate();
        $insertAdminsData['id'] = $adminsId;
        $insertAdminsData['name'] = $params['name'];
        $insertAdminsData['mobile'] = $params['mobile'];
        $insertAdminsData['physical_store_admin_permissions_id'] = 1000;
        $insertAdminsData['senior_admins'] = 1;

        //门店管理员关联门店数据
        $insertPhysicalStoreAdminsPhysicalStoreData = [];
        foreach($physicalStore as $value){
            $physicalStoreAdminsPhysicalStoreData = [];
            $physicalStoreAdminsPhysicalStoreData['id'] = IdGenerator::generate();
            $physicalStoreAdminsPhysicalStoreData['physical_store_admins_id'] = $adminsId;
            $physicalStoreAdminsPhysicalStoreData['physical_store_id'] = $value;
            $insertPhysicalStoreAdminsPhysicalStoreData[] = $physicalStoreAdminsPhysicalStoreData;
        }

        PhysicalStoreAdmins::query()->insert($insertAdminsData);
        PhysicalStoreAdminsPhysicalStore::query()->insert($insertPhysicalStoreAdminsPhysicalStoreData);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 编辑门店大管理员
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function editPhysicalStoreSeniorAdmins(array $params): array
    {
        $id = $params['id'];
        $mobile = $params['mobile'];
        $physicalStore = $params['physical_store'];

        $physicalStoreAdminsExists = PhysicalStoreAdmins::query()->where([['mobile','=',$mobile],['id','<>',$id]])->exists();
        if($physicalStoreAdminsExists === true){
            return ['code' => ErrorCode::WARNING, 'msg' => '该手机号已存在', 'data' => null];
        }
        if(empty($physicalStore)){
            return ['code' => ErrorCode::WARNING, 'msg' => '关联门店不能为空', 'data' => null];
        }

        $updateAdminsData['name'] = $params['name'];
        $updateAdminsData['mobile'] = $mobile;

        //门店管理员关联门店数据
        $insertPhysicalStoreAdminsPhysicalStoreData = [];
        foreach($physicalStore as $value){
            $physicalStoreAdminsPhysicalStoreData = [];
            $physicalStoreAdminsPhysicalStoreData['id'] = IdGenerator::generate();
            $physicalStoreAdminsPhysicalStoreData['physical_store_admins_id'] = $id;
            $physicalStoreAdminsPhysicalStoreData['physical_store_id'] = $value;
            $insertPhysicalStoreAdminsPhysicalStoreData[] = $physicalStoreAdminsPhysicalStoreData;
        }

        PhysicalStoreAdminsPhysicalStore::query()->where(['physical_store_admins_id'=>$id])->delete();
        PhysicalStoreAdmins::query()->where(['id'=>$id])->update($updateAdminsData);
        PhysicalStoreAdminsPhysicalStore::query()->insert($insertPhysicalStoreAdminsPhysicalStoreData);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 删除门店大管理员
     * @param int $id
     * @return array
     */
    public function deletePhysicalStoreSeniorAdmins(int $id): array
    {
        PhysicalStoreAdmins::query()->where(['id'=>$id])->update(['is_deleted'=>1]);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 门店大管理员列表
     * @param array $params
     * @return array
     */
    public function physicalStoreSeniorAdminsList(array $params): array
    {
        $mobile = $params['mobile'];

        $where = ['senior_admins'=>1,'is_deleted'=>0];
        if($mobile !== null){
            $where['mobile'] = $mobile;
        }
        $adminsList = PhysicalStoreAdmins::query()
            ->select(['id','name','mobile','created_at'])
            ->where($where)
            ->get();
        $adminsList = $adminsList->toArray();
        foreach($adminsList as $key=>$value){
            $physicalStoreList = PhysicalStoreAdminsPhysicalStore::query()
                ->select(['physical_store_id'])
                ->where(['physical_store_admins_id'=>$value['id']])
                ->get();
            $physicalStoreList = $physicalStoreList->toArray();
            $physicalStoreIdArray = array_column($physicalStoreList,'physical_store_id');
            $adminsList[$key]['physical_store_count'] = count($physicalStoreIdArray);
            $adminsList[$key]['physical_store'] = $physicalStoreIdArray;
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $adminsList];
    }
}