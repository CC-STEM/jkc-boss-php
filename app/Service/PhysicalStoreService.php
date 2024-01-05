<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\PhysicalStoreAdminPermissions;
use App\Model\PhysicalStoreAdminPermissionsRoute;
use App\Model\PhysicalStoreAdminRoute;
use App\Model\PhysicalStoreAdmins;
use App\Model\PhysicalStoreAdminsPhysicalStore;
use App\Model\PhysicalStoreExt;
use App\Model\Region;
use App\Model\PhysicalStore;
use App\Constants\ErrorCode;
use App\Snowflake\IdGenerator;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;

class PhysicalStoreService extends BaseService
{
    #[Inject]
    private ClientFactory $guzzleClientFactory;

    /**
     * 门店地址验证
     * @param array $params
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function addressVerify(array $params): array
    {
        $provinceId = $params['province_id'];
        $cityId = $params['city_id'];
        $districtId = $params['district_id'];
        $regionList = Region::query()->select(['id','name'])->find([$provinceId,$cityId,$districtId]);
        if(empty($regionList)){
            return ['code'=>ErrorCode::WARNING,'msg'=>"数据错误",'data'=>null];
        }
        $regionList = $regionList->toArray();
        $combineRegionKey = array_column($regionList,'id');
        $regionList = array_combine($combineRegionKey,$regionList);
        if(!isset($regionList[$provinceId]) || !isset($regionList[$cityId]) || !isset($regionList[$districtId])){
            return ['code'=>ErrorCode::WARNING,'msg'=>"选择的区域不存在",'data'=>null];
        }

        $client = $this->guzzleClientFactory->create();
        $fullAddress = $regionList[$provinceId]['name'].$regionList[$cityId]['name'].$regionList[$districtId]['name'].$params['address'];
        $url = "https://apis.map.qq.com/ws/geocoder/v1/?address={$fullAddress}&key=ZVEBZ-YULLK-VTWJK-ANWGB-NAJU2-PZFPC";
        $response = $client->request('GET', $url);
        $r = $response->getBody()->getContents();
        $data = json_decode($r,true);
        $longitude = $data['result']['location']['lng'];
        $latitude = $data['result']['location']['lat'];

        $url2 = "https://apis.map.qq.com/ws/geocoder/v1/?location={$latitude},{$longitude}&key=ZVEBZ-YULLK-VTWJK-ANWGB-NAJU2-PZFPC";
        $response2 = $client->request('GET', $url2);
        $r2 = $response2->getBody()->getContents();
        $data2 = json_decode($r2,true);

        $addressComponent = $data2['result']['address_component'];
        $formattedAddresses = $data2['result']['formatted_addresses'];
        $verifyAddress = $addressComponent['province'].$addressComponent['city'].$addressComponent['district'].$formattedAddresses['recommend'];
        $returnData['verify_address'] = $verifyAddress;
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $returnData];
    }

    /**
     * 添加门店
     * @param array $params
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Throwable
     */
    public function addPhysicalStore(array $params): array
    {
        $provinceId = $params['province_id'];
        $cityId = $params['city_id'];
        $districtId = $params['district_id'];
        $regionList = Region::query()->select(['id','name'])->find([$provinceId,$cityId,$districtId]);
        if(empty($regionList)){
            return ['code'=>ErrorCode::WARNING,'msg'=>"数据错误",'data'=>null];
        }
        $regionList = $regionList->toArray();
        $combineRegionKey = array_column($regionList,'id');
        $regionList = array_combine($combineRegionKey,$regionList);
        if(!isset($regionList[$provinceId]) || !isset($regionList[$cityId]) || !isset($regionList[$districtId])){
            return ['code'=>ErrorCode::WARNING,'msg'=>"选择的区域不存在",'data'=>null];
        }
        //经纬度获取
        $client = $this->guzzleClientFactory->create();
        $fullAddress = $regionList[$provinceId]['name'].$regionList[$cityId]['name'].$regionList[$districtId]['name'].$params['address'];
        $url = "https://apis.map.qq.com/ws/geocoder/v1/?address={$fullAddress}&key=ZVEBZ-YULLK-VTWJK-ANWGB-NAJU2-PZFPC";
        $response = $client->request('GET', $url);
        $r = $response->getBody()->getContents();
        $data = json_decode($r,true);
        $longitude = $data['result']['location']['lng'];
        $latitude = $data['result']['location']['lat'];

        $physicalStoreAdminsExists = PhysicalStoreAdmins::query()->where(['mobile'=>$params['mobile'],'is_deleted'=>0])->exists();
        if($physicalStoreAdminsExists === true){
            return ['code' => ErrorCode::WARNING, 'msg' => '该手机号已存在', 'data' => null];
        }
        $physicalStoreAdminRouteList = PhysicalStoreAdminRoute::query()
            ->select(['id'])
            ->where([['identify','<>','']])
            ->get();
        $physicalStoreAdminRouteList = $physicalStoreAdminRouteList->toArray();

        //门店数据
        $physicalStoreId = IdGenerator::generate();
        $insertPhysicalStoreData['id'] = $physicalStoreId;
        $insertPhysicalStoreData['name'] = $params['name'];
        $insertPhysicalStoreData['manager'] = $params['manager'];
        $insertPhysicalStoreData['mobile'] = $params['mobile'];
        $insertPhysicalStoreData['province_id'] = $provinceId;
        $insertPhysicalStoreData['city_id'] = $cityId;
        $insertPhysicalStoreData['district_id'] = $districtId;
        $insertPhysicalStoreData['province_name'] = $regionList[$provinceId]['name'];
        $insertPhysicalStoreData['city_name'] = $regionList[$cityId]['name'];
        $insertPhysicalStoreData['district_name'] = $regionList[$districtId]['name'];
        $insertPhysicalStoreData['address'] = $params['address'];
        $insertPhysicalStoreData['longitude'] = $longitude;
        $insertPhysicalStoreData['latitude'] = $latitude;

        //门店扩展数据
        $insertPhysicalStoreExtData['id'] = IdGenerator::generate();
        $insertPhysicalStoreExtData['physical_store_id'] = $physicalStoreId;
        $insertPhysicalStoreExtData['course_offline_theme2_enabled'] = $params['course_offline_theme2_enabled'] ?? 0;
        $insertPhysicalStoreExtData['course_offline_theme3_enabled'] = $params['course_offline_theme3_enabled'] ?? 0;

        //门店权限数据
        $physicalStoreAdminPermissionsId = IdGenerator::generate();
        $insertPhysicalStoreAdminPermissionsData['id'] = $physicalStoreAdminPermissionsId;
        $insertPhysicalStoreAdminPermissionsData['physical_store_id'] = $physicalStoreId;
        $insertPhysicalStoreAdminPermissionsData['name'] = '超级管理员';

        //门店权限路由数据
        $insertPhysicalStoreAdminPermissionsRouteData = [];
        foreach($physicalStoreAdminRouteList as $value){
            $physicalStoreAdminPermissionsRouteData = [];
            $physicalStoreAdminPermissionsRouteData['id'] = IdGenerator::generate();
            $physicalStoreAdminPermissionsRouteData['physical_store_admin_permissions_id'] = $physicalStoreAdminPermissionsId;
            $physicalStoreAdminPermissionsRouteData['physical_store_admin_route_id'] = $value['id'];
            $insertPhysicalStoreAdminPermissionsRouteData[] = $physicalStoreAdminPermissionsRouteData;
        }

        //门店管理员数据
        $insertPhysicalStoreAdminId = IdGenerator::generate();
        $insertPhysicalStoreAdminData['id'] = $insertPhysicalStoreAdminId;
        $insertPhysicalStoreAdminData['physical_store_id'] = $physicalStoreId;
        $insertPhysicalStoreAdminData['physical_store_admin_permissions_id'] = $physicalStoreAdminPermissionsId;
        $insertPhysicalStoreAdminData['name'] = $params['manager'];
        $insertPhysicalStoreAdminData['mobile'] = $params['mobile'];
        $insertPhysicalStoreAdminData['is_store_manager'] = 1;

        //门店管理员关联门店数据
        $insertPhysicalStoreAdminsPhysicalStoreData['id'] = IdGenerator::generate();
        $insertPhysicalStoreAdminsPhysicalStoreData['physical_store_admins_id'] = $insertPhysicalStoreAdminId;
        $insertPhysicalStoreAdminsPhysicalStoreData['physical_store_id'] = $physicalStoreId;

        Db::connection('jkc_edu')->transaction(function()use($insertPhysicalStoreData,$insertPhysicalStoreAdminData,$insertPhysicalStoreAdminPermissionsData,$insertPhysicalStoreAdminPermissionsRouteData,$insertPhysicalStoreExtData,$insertPhysicalStoreAdminsPhysicalStoreData){
            PhysicalStore::query()->insert($insertPhysicalStoreData);
            PhysicalStoreAdmins::query()->insert($insertPhysicalStoreAdminData);
            PhysicalStoreAdminPermissions::query()->insert($insertPhysicalStoreAdminPermissionsData);
            PhysicalStoreAdminPermissionsRoute::query()->insert($insertPhysicalStoreAdminPermissionsRouteData);
            PhysicalStoreExt::query()->insert($insertPhysicalStoreExtData);
            PhysicalStoreAdminsPhysicalStore::query()->insert($insertPhysicalStoreAdminsPhysicalStoreData);
        });
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 编辑门店
     * @param array $params
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function editPhysicalStore(array $params): array
    {
        $id = $params['id'];
        $provinceId = $params['province_id'];
        $cityId = $params['city_id'];
        $districtId = $params['district_id'];
        $regionList = Region::query()->select(['id','name'])->find([$provinceId,$cityId,$districtId]);
        if(empty($regionList)){
            return ['code'=>ErrorCode::WARNING,'msg'=>"数据错误",'data'=>null];
        }
        $regionList = $regionList->toArray();
        $combineRegionKey = array_column($regionList,'id');
        $regionList = array_combine($combineRegionKey,$regionList);
        if(!isset($regionList[$provinceId]) || !isset($regionList[$cityId]) || !isset($regionList[$districtId])){
            return ['code'=>ErrorCode::WARNING,'msg'=>"选择的区域不存在",'data'=>null];
        }
        //经纬度获取
        $client = $this->guzzleClientFactory->create();
        $fullAddress = $regionList[$provinceId]['name'].$regionList[$cityId]['name'].$regionList[$districtId]['name'].$params['address'];
        $url = "https://apis.map.qq.com/ws/geocoder/v1/?address={$fullAddress}&key=ZVEBZ-YULLK-VTWJK-ANWGB-NAJU2-PZFPC";
        $response = $client->request('GET', $url);
        $r = $response->getBody()->getContents();
        $data = json_decode($r,true);
        $longitude = $data['result']['location']['lng'];
        $latitude = $data['result']['location']['lat'];

        $physicalStoreExtInfo = PhysicalStoreExt::query()
            ->select(['id'])
            ->where(['physical_store_id'=>$id])
            ->first();
        if(empty($physicalStoreExtInfo)){
            $insertPhysicalStoreExtData['id'] = IdGenerator::generate();
            $insertPhysicalStoreExtData['physical_store_id'] = $id;
            $insertPhysicalStoreExtData['course_offline_theme2_enabled'] = $params['course_offline_theme2_enabled'] ?? 0;
            $insertPhysicalStoreExtData['course_offline_theme3_enabled'] = $params['course_offline_theme3_enabled'] ?? 0;
        }else{
            $updatePhysicalStoreExtData['course_offline_theme2_enabled'] = $params['course_offline_theme2_enabled'] ?? 0;
            $updatePhysicalStoreExtData['course_offline_theme3_enabled'] = $params['course_offline_theme3_enabled'] ?? 0;
        }
        //门店店长
        $storeManagerList = PhysicalStoreAdmins::query()
            ->select(['id'])
            ->where(['id'=>$id,'is_store_manager'=>1])
            ->get();
        $storeManagerList = $storeManagerList->toArray();
        $storeManagerIdArray = array_column($storeManagerList,'id');
        $physicalStoreAdminsInfo = PhysicalStoreAdmins::query()
            ->select(['id'])
            ->where(['mobile'=>$params['mobile']])
            ->first();
        if(empty($physicalStoreAdminsInfo)){
            return ['code'=>ErrorCode::WARNING,'msg'=>"该手机号不存在管理员信息",'data'=>null];
        }
        $physicalStoreAdminsInfo = $physicalStoreAdminsInfo->toArray();
        $physicalStoreAdminsId = $physicalStoreAdminsInfo['id'];

        //门店数据
        $updatePhysicalStoreData['name'] = $params['name'];
        $updatePhysicalStoreData['manager'] = $params['manager'];
        $updatePhysicalStoreData['mobile'] = $params['mobile'];
        $updatePhysicalStoreData['province_id'] = $provinceId;
        $updatePhysicalStoreData['city_id'] = $cityId;
        $updatePhysicalStoreData['district_id'] = $districtId;
        $updatePhysicalStoreData['province_name'] = $regionList[$provinceId]['name'];
        $updatePhysicalStoreData['city_name'] = $regionList[$cityId]['name'];
        $updatePhysicalStoreData['district_name'] = $regionList[$districtId]['name'];
        $updatePhysicalStoreData['address'] = $params['address'];
        $updatePhysicalStoreData['longitude'] = $longitude;
        $updatePhysicalStoreData['latitude'] = $latitude;

        PhysicalStore::query()->where(['id'=>$id])->update($updatePhysicalStoreData);
        if(!empty($insertPhysicalStoreExtData)){
            PhysicalStoreExt::query()->insert($insertPhysicalStoreExtData);
        }else{
            PhysicalStoreExt::query()->where(['physical_store_id'=>$id])->update($updatePhysicalStoreExtData);
        }
        if(!in_array($physicalStoreAdminsId,$storeManagerIdArray)){
            PhysicalStoreAdmins::query()->whereIn('id',$storeManagerIdArray)->update(['is_store_manager'=>0]);
            PhysicalStoreAdmins::query()->where(['id'=>$physicalStoreAdminsId])->update(['is_store_manager'=>1]);
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 门店列表
     * @param array $params
     * @return array
     */
    public function physicalStoreList(array $params): array
    {
        $mobile = $params['mobile'];

        $where = ['is_deleted'=>0];
        if($mobile !== null){
            $where['mobile'] = $mobile;
        }
        $physicalStoreList = PhysicalStore::query()
            ->select(['id','name','manager','mobile','province_name','city_name','district_name','address','course_target_amount','revenue_target_amount'])
            ->where($where)
            ->get();
        $physicalStoreList = $physicalStoreList->toArray();
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $physicalStoreList];
    }

    /**
     * 门店详情
     * @param int $id
     * @return array
     */
    public function physicalStoreDetail(int $id): array
    {
        $physicalStoreInfo = PhysicalStore::query()->select(['id','name','manager','mobile','province_name','city_name','district_name','address','province_id','city_id','district_id'])->where(['id'=>$id])->first();
        if(empty($physicalStoreInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '数据错误', 'data' => null];
        }
        $physicalStoreInfo = $physicalStoreInfo->toArray();
        $physicalStoreInfo['course_offline_theme2_enabled'] = 0;
        $physicalStoreInfo['course_offline_theme3_enabled'] = 0;

        $physicalStoreExtInfo = PhysicalStoreExt::query()
            ->select(['course_offline_theme2_enabled','course_offline_theme3_enabled'])
            ->where(['physical_store_id'=>$id])
            ->first();
        if(!empty($physicalStoreExtInfo)){
            $physicalStoreExtInfo = $physicalStoreExtInfo->toArray();
            $physicalStoreInfo['course_offline_theme2_enabled'] = $physicalStoreExtInfo['course_offline_theme2_enabled'];
            $physicalStoreInfo['course_offline_theme3_enabled'] = $physicalStoreExtInfo['course_offline_theme3_enabled'];
        }

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $physicalStoreInfo];
    }

    /**
     * 删除门店
     * @param int $id
     * @return array
     */
    public function deletePhysicalStore(int $id): array
    {
        PhysicalStore::query()->where(['id'=>$id])->update(['is_deleted'=>1]);
        PhysicalStoreAdmins::query()->where(['physical_store_id'=>$id])->update(['is_deleted'=>1]);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 目标设定
     * @param array $params
     * @return array
     */
    public function goalSetting($params): array
    {
        $updateInfo = [
            'course_target_amount' => $params['course_target_amount'],
            'revenue_target_amount' => $params['revenue_target_amount']
        ];
        PhysicalStore::query()->where(['id' => $params['id']])->update($updateInfo);

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }


}