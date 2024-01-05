<?php
declare(strict_types=1);

namespace App\Task;

use App\Model\CourseOfflineOrder;
use App\Model\MemberBelongTo;
use App\Model\MemberRegisterCoordinate;
use App\Model\PhysicalStore;
use App\Model\VipCardOrder;
use App\Snowflake\IdGenerator;

class MemberBelongToTask extends BaseTask
{
    public function memberBelongToAllocationExecute(): void
    {
        $minutesAgo30 = date('Y-m-d H:i:s',strtotime('-30 minutes'));

        $memberRegisterCoordinateList = MemberRegisterCoordinate::query()
            ->leftJoin('member_belong_to','member_register_coordinate.member_id','=','member_belong_to.member_id')
            ->select(['member_register_coordinate.member_id','member_register_coordinate.longitude','member_register_coordinate.latitude'])
            ->whereNull('member_belong_to.id')
            ->where([['member_register_coordinate.created_at','<=',$minutesAgo30]])
            ->get();
        $memberRegisterCoordinateList = $memberRegisterCoordinateList->toArray();

        foreach($memberRegisterCoordinateList as $value){
            $memberId = $value['member_id'];
            $memberLatitude = $value['latitude'];
            $memberLongitude = $value['longitude'];

            $courseOfflineOrderInfo = CourseOfflineOrder::query()
                ->select(['physical_store_id'])
                ->where(['member_id'=>$memberId,'pay_status'=>1,'order_status'=>0])
                ->orderBy('created_at','desc')
                ->first();

            $vipCardOrderInfo = VipCardOrder::query()
                ->select(['physical_store_id'])
                ->where(['member_id'=>$memberId,'pay_status'=>1,'order_status'=>0])
                ->orderBy('created_at')
                ->first();

            if(!empty($courseOfflineOrderInfo)){
                $insertMemberBelongToData['id'] = IdGenerator::generate();
                $insertMemberBelongToData['member_id'] = $memberId;
                $insertMemberBelongToData['physical_store_id'] = $courseOfflineOrderInfo['physical_store_id'];
                MemberBelongTo::query()->insert($insertMemberBelongToData);
            }else if(!empty($vipCardOrderInfo)){
                $vipCardOrderInfo = $vipCardOrderInfo->toArray();
                $insertMemberBelongToData['id'] = IdGenerator::generate();
                $insertMemberBelongToData['member_id'] = $memberId;
                $insertMemberBelongToData['physical_store_id'] = $vipCardOrderInfo['physical_store_id'];
                MemberBelongTo::query()->insert($insertMemberBelongToData);
            }else{
                if($memberLatitude == 0 && $memberLongitude == 0){
                    continue;
                }
                $physicalStoreList = PhysicalStore::query()
                    ->select(['id','longitude','latitude'])
                    ->where(['is_deleted'=>0])
                    ->get();
                $physicalStoreList = $physicalStoreList->toArray();
                if(empty($physicalStoreList)){
                    continue;
                }
                foreach($physicalStoreList as $key => $item){
                    $linearDistance = 0;
                    if($memberLatitude != 0 && $memberLongitude != 0){
                        $linearDistance = $this->functions->linearDistance($memberLatitude,$memberLongitude,$item['latitude'],$item['longitude']);
                    }
                    $physicalStoreList[$key]['distance'] = bcdiv((string)$linearDistance,'1000',2);
                }
                array_multisort(array_column($physicalStoreList,'distance'), SORT_ASC, $physicalStoreList);
                $physicalStoreId = $physicalStoreList[0]['id'] ?? 0;

                if($physicalStoreId != 0){
                    $insertMemberBelongToData['id'] = IdGenerator::generate();
                    $insertMemberBelongToData['member_id'] = $memberId;
                    $insertMemberBelongToData['physical_store_id'] = $physicalStoreId;
                    MemberBelongTo::query()->insert($insertMemberBelongToData);
                }
            }
        }
    }


}

