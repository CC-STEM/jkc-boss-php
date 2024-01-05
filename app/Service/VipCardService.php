<?php

declare(strict_types=1);

namespace App\Service;

use App\Constants\VipCardConstant;
use App\Event\VipCardOrderRefundRegistered;
use App\Lib\WeChat\WeChatPayFactory;
use App\Logger\Log;
use App\Model\CourseOfflineOrder;
use App\Model\PayApply;
use App\Model\Teacher;
use App\Model\VipCard;
use App\Model\VipCardDynamicCourse;
use App\Model\VipCardOrder;
use App\Model\VipCardOrderDynamicCourse;
use App\Model\VipCardOrderPhysicalStore;
use App\Model\VipCardOrderRefund;
use App\Model\VipCardPhysicalStore;
use App\Model\VipCardPrivilege;
use App\Constants\ErrorCode;
use App\Model\VipCardSort;
use App\Snowflake\IdGenerator;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\EventDispatcher\EventDispatcherInterface;

class VipCardService extends BaseService
{
    #[Inject]
    private EventDispatcherInterface $eventDispatcher;

    /**
     * 添加会员卡
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function addVipCard(array $params): array
    {
        $privilege = $params['privilege'] ?? [];
        $course1 = $params['course1'] ?? 0;
        $course2 = $params['course2'] ?? 0;
        $course3 = $params['course3'] ?? 0;
        $applicableStore = $params['applicable_store'] ?? [];
        $apieceQuota = $params['apiece_quota'] ?? 0;
        $currencyCourse = $params['currency_course'] ?? 0;
        $themeType = $params['theme_type'] ?? 1;
        $commissionRate = $params['commission_rate'] ?? 0;
        $dynamicCourse = $params['dynamic_course'] ?? [];
        $rule = ['course1'=>$course1,'course2'=>$course2,'course3'=>$course3,'currency_course'=>$currencyCourse];
        $weekEnum = ['周一'=>1,'周二'=>2,'周三'=>3,'周四'=>4,'周五'=>5,'周六'=>6,'周日'=>7];
        $totalCourseType = 3;
        $totalCourseType -= count($dynamicCourse);
        if($course1>0){
            $totalCourseType--;
        }
        if($course2>0){
            $totalCourseType--;
        }
        if($course3>0){
            $totalCourseType--;
        }
        if($totalCourseType<0){
            return ['code'=>ErrorCode::WARNING,'msg'=>"课程类型最多3个",'data'=>null];
        }

        $vipCardId = IdGenerator::generate();
        $applicableStoreType = 1;
        $insertVipCardPhysicalStoreData = [];
        if(!empty($applicableStore)){
            $applicableStoreType = 2;
            foreach($applicableStore as $value){
                $vipCardPhysicalStoreData = [];
                $vipCardPhysicalStoreData['id'] = IdGenerator::generate();
                $vipCardPhysicalStoreData['vip_card_id'] = $vipCardId;
                $vipCardPhysicalStoreData['physical_store_id'] = $value;
                $insertVipCardPhysicalStoreData[] = $vipCardPhysicalStoreData;
            }
        }
        if($params['type'] == 3){
            $vipCardList3 = VipCard::query()
                ->select(['id','start_at','end_at'])
                ->where(['type'=>3,'applicable_store_type'=>$applicableStoreType,'is_deleted'=>0])
                ->get();
            $vipCardList3 = $vipCardList3->toArray();
            foreach($vipCardList3 as $value){
                if($applicableStoreType == 2){
                    $vipCardPhysicalStoreList3 = VipCardPhysicalStore::query()
                        ->select(['physical_store_id'])
                        ->where(['vip_card_id'=>$value['id']])
                        ->get();
                    $vipCardPhysicalStoreList3 = $vipCardPhysicalStoreList3->toArray();
                    $PhysicalStoreIdArray3 = array_column($vipCardPhysicalStoreList3,'physical_store_id');
                    $applicableStoreIntersect3 = array_intersect($applicableStore,$PhysicalStoreIdArray3);
                    if(empty($applicableStoreIntersect3)){
                        continue;
                    }
                }
                if($params['start_at']<=$value['start_at'] && $params['end_at']>=$value['start_at']){
                    return ['code'=>ErrorCode::WARNING,'msg'=>"新人礼包时间不可重叠5",'data'=>null];
                }
                if($params['start_at']<=$value['end_at'] && $params['end_at']>=$value['end_at']){
                    return ['code'=>ErrorCode::WARNING,'msg'=>"新人礼包时间不可重叠6",'data'=>null];
                }
                if($params['start_at']<=$value['start_at'] && $params['end_at']>=$value['end_at']){
                    return ['code'=>ErrorCode::WARNING,'msg'=>"新人礼包时间不可重叠7",'data'=>null];
                }
                if($params['start_at']>$value['start_at'] && $params['end_at']<$value['end_at']){
                    return ['code'=>ErrorCode::WARNING,'msg'=>"新人礼包时间不可重叠8",'data'=>null];
                }
            }
        }

        $insertVipCardData['id'] = $vipCardId;
        $insertVipCardData['name'] = $params['name'];
        $insertVipCardData['price'] = $params['price'];
        $insertVipCardData['original_price'] = $params['original_price'] ?? 0;
        $insertVipCardData['expire'] = $params['expire'];
        $insertVipCardData['rule'] = json_encode($rule,JSON_UNESCAPED_UNICODE);
        $insertVipCardData['thum_img_url'] = $params['thum_img_url'] ?? '';
        $insertVipCardData['img_url'] = $params['img_url'] ?? '';
        $insertVipCardData['type'] = $params['type'];
        $insertVipCardData['grade'] = $params['grade'] ?? '';
        $insertVipCardData['explain'] = $params['explain'] ?? '';
        $insertVipCardData['start_at'] = $params['start_at'];
        $insertVipCardData['end_at'] = $params['end_at'];
        $insertVipCardData['applicable_store_type'] = $applicableStoreType;
        $insertVipCardData['apiece_quota'] = $apieceQuota;
        $insertVipCardData['theme_type'] = $themeType;
        $insertVipCardData['commission_rate'] = $commissionRate;
        $insertVipCardData['max_deduction_price'] = $params['max_deduction_price'] ?? 0;

        $insertPrivilegeData = [];
        foreach($privilege as $value){
            $privilegeData = [];
            $privilegeData['id'] = IdGenerator::generate();
            $privilegeData['vip_card_id'] = $vipCardId;
            $privilegeData['title'] = $value['title'];
            $privilegeData['img_url'] = $value['img_url'];
            $privilegeData['describe'] = $value['describe'];
            $insertPrivilegeData[] = $privilegeData;
        }

        $insertVipCardDynamicCourseData = [];
        foreach($dynamicCourse as $value){
            $week = $value['week'];
            $newWeek = [];
            foreach($week as $item){
                $newWeek[] = $weekEnum[$item];
            }
            $vipCardDynamicCourseData['id'] = IdGenerator::generate();
            $vipCardDynamicCourseData['vip_card_id'] = $vipCardId;
            $vipCardDynamicCourseData['name'] = $value['name'];
            $vipCardDynamicCourseData['course'] = $value['course'];
            $vipCardDynamicCourseData['type'] = $value['type'];
            $vipCardDynamicCourseData['week'] = json_encode($newWeek);
            $insertVipCardDynamicCourseData[] = $vipCardDynamicCourseData;
        }

        Db::connection('jkc_edu')->beginTransaction();
        try{
            Db::connection('jkc_edu')->table('vip_card')->insert($insertVipCardData);
            if(!empty($insertPrivilegeData)){
                Db::connection('jkc_edu')->table('vip_card_privilege')->insert($insertPrivilegeData);
            }
            if(!empty($insertVipCardPhysicalStoreData)){
                Db::connection('jkc_edu')->table('vip_card_physical_store')->insert($insertVipCardPhysicalStoreData);
            }
            if(!empty($insertVipCardDynamicCourseData)){
                Db::connection('jkc_edu')->table('vip_card_dynamic_course')->insert($insertVipCardDynamicCourseData);
            }
            Db::connection('jkc_edu')->commit();
        } catch(\Throwable $e){
            Db::connection('jkc_edu')->rollBack();
            throw new \Exception($e->getMessage(), 1);
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 编辑会员卡
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function editVipCard(array $params): array
    {
        $vipCardId = $params['id'];
        $privilege = $params['privilege'] ?? [];
        $course1 = $params['course1'] ?? 0;
        $course2 = $params['course2'] ?? 0;
        $course3 = $params['course3'] ?? 0;
        $applicableStore = $params['applicable_store'] ?? [];
        $apieceQuota = $params['apiece_quota'] ?? 0;
        $currencyCourse = $params['currency_course'] ?? 0;
        $themeType = $params['theme_type'] ?? 1;
        $commissionRate = $params['commission_rate'] ?? 0;
        $dynamicCourse = $params['dynamic_course'] ?? [];
        $rule = ['course1'=>$course1,'course2'=>$course2,'course3'=>$course3,'currency_course'=>$currencyCourse];
        $weekEnum = ['周一'=>1,'周二'=>2,'周三'=>3,'周四'=>4,'周五'=>5,'周六'=>6,'周日'=>7];
        $totalCourseType = 3;
        $totalCourseType -= count($dynamicCourse);
        if($course1>0){
            $totalCourseType--;
        }
        if($course2>0){
            $totalCourseType--;
        }
        if($course3>0){
            $totalCourseType--;
        }
        if($totalCourseType<0){
            return ['code'=>ErrorCode::WARNING,'msg'=>"课程类型最多3个",'data'=>null];
        }

        $applicableStoreType = 1;
        $insertVipCardPhysicalStoreData = [];
        if(!empty($applicableStore)){
            $applicableStoreType = 2;
            foreach($applicableStore as $value){
                $vipCardPhysicalStoreData = [];
                $vipCardPhysicalStoreData['id'] = IdGenerator::generate();
                $vipCardPhysicalStoreData['vip_card_id'] = $vipCardId;
                $vipCardPhysicalStoreData['physical_store_id'] = $value;
                $insertVipCardPhysicalStoreData[] = $vipCardPhysicalStoreData;
            }
        }

        if($params['type'] == 3){
            $vipCardList3 = VipCard::query()
                ->select(['id','start_at','end_at'])
                ->where([['type','=',3],['id','<>',$vipCardId],['applicable_store_type','=',$applicableStoreType],['is_deleted','=',0]])
                ->get();
            $vipCardList3 = $vipCardList3->toArray();
            foreach($vipCardList3 as $value){
                if($applicableStoreType == 2){
                    $vipCardPhysicalStoreList3 = VipCardPhysicalStore::query()
                        ->select(['physical_store_id'])
                        ->where(['vip_card_id'=>$value['id']])
                        ->get();
                    $vipCardPhysicalStoreList3 = $vipCardPhysicalStoreList3->toArray();
                    $PhysicalStoreIdArray3 = array_column($vipCardPhysicalStoreList3,'physical_store_id');
                    $applicableStoreIntersect3 = array_intersect($applicableStore,$PhysicalStoreIdArray3);
                    if(empty($applicableStoreIntersect3)){
                        continue;
                    }
                }
                if($params['start_at']<=$value['start_at'] && $params['end_at']>=$value['start_at']){
                    return ['code'=>ErrorCode::WARNING,'msg'=>"新人礼包时间不可重叠5",'data'=>null];
                }
                if($params['start_at']<=$value['end_at'] && $params['end_at']>=$value['end_at']){
                    return ['code'=>ErrorCode::WARNING,'msg'=>"新人礼包时间不可重叠6",'data'=>null];
                }
                if($params['start_at']<=$value['start_at'] && $params['end_at']>=$value['end_at']){
                    return ['code'=>ErrorCode::WARNING,'msg'=>"新人礼包时间不可重叠7",'data'=>null];
                }
                if($params['start_at']>$value['start_at'] && $params['end_at']<$value['end_at']){
                    return ['code'=>ErrorCode::WARNING,'msg'=>"新人礼包时间不可重叠8",'data'=>null];
                }
            }
        }

        $updateVipCardData['name'] = $params['name'];
        $updateVipCardData['price'] = $params['price'];
        $updateVipCardData['original_price'] = $params['original_price'] ?? 0;
        $updateVipCardData['expire'] = $params['expire'];
        $updateVipCardData['rule'] = json_encode($rule,JSON_UNESCAPED_UNICODE);
        $updateVipCardData['thum_img_url'] = $params['thum_img_url'] ?? '';
        $updateVipCardData['img_url'] = $params['img_url'] ?? '';
        $updateVipCardData['type'] = $params['type'];
        $updateVipCardData['grade'] = $params['grade'] ?? '';
        $updateVipCardData['explain'] = $params['explain'] ?? '';
        $updateVipCardData['start_at'] = $params['start_at'];
        $updateVipCardData['end_at'] = $params['end_at'];
        $updateVipCardData['applicable_store_type'] = $applicableStoreType;
        $updateVipCardData['apiece_quota'] = $apieceQuota;
        $updateVipCardData['theme_type'] = $themeType;
        $updateVipCardData['commission_rate'] = $commissionRate;
        $updateVipCardData['max_deduction_price'] = $params['max_deduction_price'] ?? 0;

        $insertPrivilegeData = [];
        foreach($privilege as $value){
            $privilegeData = [];
            $privilegeData['id'] = IdGenerator::generate();
            $privilegeData['vip_card_id'] = $vipCardId;
            $privilegeData['title'] = $value['title'];
            $privilegeData['img_url'] = $value['img_url'];
            $privilegeData['describe'] = $value['describe'];
            $insertPrivilegeData[] = $privilegeData;
        }

        $insertVipCardDynamicCourseData = [];
        foreach($dynamicCourse as $value){
            $week = $value['week'];
            $newWeek = [];
            foreach($week as $item){
                $newWeek[] = $weekEnum[$item];
            }
            $vipCardDynamicCourseData['id'] = IdGenerator::generate();
            $vipCardDynamicCourseData['vip_card_id'] = $vipCardId;
            $vipCardDynamicCourseData['name'] = $value['name'];
            $vipCardDynamicCourseData['course'] = $value['course'];
            $vipCardDynamicCourseData['type'] = $value['type'];
            $vipCardDynamicCourseData['week'] = json_encode($newWeek);
            $insertVipCardDynamicCourseData[] = $vipCardDynamicCourseData;
        }

        Db::connection('jkc_edu')->beginTransaction();
        try{
            Db::connection('jkc_edu')->table('vip_card_dynamic_course')->where(['vip_card_id'=>$vipCardId])->delete();
            Db::connection('jkc_edu')->table('vip_card_privilege')->where(['vip_card_id'=>$vipCardId])->delete();
            Db::connection('jkc_edu')->table('vip_card_physical_store')->where(['vip_card_id'=>$vipCardId])->delete();
            Db::connection('jkc_edu')->table('vip_card')->where(['id'=>$vipCardId])->update($updateVipCardData);
            if(!empty($insertPrivilegeData)){
                Db::connection('jkc_edu')->table('vip_card_privilege')->insert($insertPrivilegeData);
            }
            if(!empty($insertVipCardPhysicalStoreData)){
                Db::connection('jkc_edu')->table('vip_card_physical_store')->insert($insertVipCardPhysicalStoreData);
            }
            if(!empty($insertVipCardDynamicCourseData)){
                Db::connection('jkc_edu')->table('vip_card_dynamic_course')->insert($insertVipCardDynamicCourseData);
            }
            Db::connection('jkc_edu')->commit();
        } catch(\Throwable $e){
            Db::connection('jkc_edu')->rollBack();
            throw new \Exception($e->getMessage(), 1);
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 会员卡排序
     * @param array $params
     * @return array
     */
    public function vipCardSort(array $params): array
    {
        $ids = $params['ids'];
        $physicalStoreId = $params['physical_store_id'];

        $insertVipCardSortData = [];
        $i = 1;
        foreach($ids as $value){
            $vipCardSortData['vip_card_id'] = $value;
            $vipCardSortData['physical_store_id'] = $physicalStoreId;
            $vipCardSortData['sort'] = $i;
            $insertVipCardSortData[] = $vipCardSortData;
            $i++;
        }

        VipCardSort::query()->where(['physical_store_id'=>$physicalStoreId])->delete();
        VipCardSort::query()->insert($insertVipCardSortData);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 会员卡列表
     * @param array $params
     * @return array
     */
    public function vipCardList(array $params): array
    {
        $type = $params['type'];
        $physicalStore = $params['physical_store'];
        $themeType = $params['theme_type'];
        $physicalStoreId = $physicalStore[0];
        $weekArray = [7=>"每周日",1=>"每周一",2=>"每周二",3=>"每周三",4=>"每周四",5=>"每周五",6=>"每周六"];

        if($type == 0){
            $vipCardList = VipCard::query()
                ->select(['id','name','price','expire','original_price','rule','grade','created_at','explain','start_at','end_at','applicable_store_type'])
                ->where(['is_deleted'=>0])
                ->get();
        }else{
            $vipCardSortList = VipCardSort::query()
                ->select(['vip_card_id','sort'])
                ->where(['physical_store_id'=>$physicalStoreId])
                ->orderBy('sort')
                ->get();
            $vipCardSortList = $vipCardSortList->toArray();
            $combineVipCardSortKey = array_column($vipCardSortList,'vip_card_id');
            $vipCardSortList = array_combine($combineVipCardSortKey,$vipCardSortList);

            $vipCardPhysicalStoreList = VipCardPhysicalStore::query()
                ->select(['vip_card_id'])
                ->where(['physical_store_id'=>$physicalStoreId])
                ->get();
            $vipCardPhysicalStoreList = $vipCardPhysicalStoreList->toArray();
            $vipCardIdArray = array_values(array_unique(array_column($vipCardPhysicalStoreList,'vip_card_id')));

            $vipCardList = VipCard::query()
                ->select(['id','name','price','expire','original_price','rule','grade','created_at','explain','start_at','end_at','applicable_store_type'])
                ->where(['type'=>$type,'theme_type'=>$themeType,'is_deleted'=>0])
                ->where(function ($query) use($vipCardIdArray){
                    $query->where(['applicable_store_type'=>1])
                        ->orWhereIn('id',$vipCardIdArray);
                })
                ->get();
        }
        $vipCardList = $vipCardList->toArray();
        $vipCardIdArray = array_column($vipCardList,'id');
        $vipCardDynamicCourseList = VipCardDynamicCourse::query()
            ->select(['vip_card_id','name','week','course'])
            ->whereIn('vip_card_id',$vipCardIdArray)
            ->get();
        $vipCardDynamicCourseList = $vipCardDynamicCourseList->toArray();
        foreach($vipCardDynamicCourseList as $key=>$value){
            $newWeek = [];
            $week = json_decode($value['week'],true);
            foreach($week as $item){
                $newWeek[] = $weekArray[$item];
            }
            $vipCardDynamicCourseList[$key]['week'] = implode(',',$newWeek);
        }
        $vipCardDynamicCourseList = $this->functions->arrayGroupBy($vipCardDynamicCourseList,'vip_card_id');

        foreach($vipCardList as $key=>$value){
            $id = $value['id'];
            $dynamicCourse = $vipCardDynamicCourseList[$id] ?? [];
            $rule = json_decode($value['rule'],true);
            $course1 = $rule['course1'] ?? 0;
            $course2 = $rule['course2'] ?? 0;
            $course3 = $rule['course3'] ?? 0;
            unset($vipCardList[$key]['rule']);
            $vipCardList[$key]['course1'] = $course1;
            $vipCardList[$key]['course2'] = $course2;
            $vipCardList[$key]['course3'] = $course3;

            $purchaseNum = VipCardOrder::query()->where(['vip_card_id'=>$id,'pay_status'=>1])->count('id');
            $useNumArray = Db::connection('jkc_edu')->select('SELECT sum(course1_used) as sum1,sum(course2_used) as sum2,sum(course3_used) as sum3 FROM vip_card_order WHERE vip_card_id = ? AND pay_status=?', [$id,1]);
            $useNumArray = $useNumArray[0];
            $useNum = array_sum($useNumArray);

            $vipCardPhysicalStore = '全部门店';
            if($value['applicable_store_type'] == 2){
                $vipCardPhysicalStoreList = VipCardPhysicalStore::query()
                    ->leftJoin('physical_store', 'vip_card_physical_store.physical_store_id', '=', 'physical_store.id')
                    ->select(['physical_store.name'])
                    ->where(['vip_card_physical_store.vip_card_id'=>$id])
                    ->get();
                $vipCardPhysicalStoreList = $vipCardPhysicalStoreList->toArray();
                $vipCardPhysicalStore = implode('  ',array_column($vipCardPhysicalStoreList,'name'));
            }
            $vipCardList[$key]['dynamic_course'] = $dynamicCourse;
            $vipCardList[$key]['purchase_num'] = $purchaseNum;
            $vipCardList[$key]['use_num'] = $useNum;
            $vipCardList[$key]['physical_store'] = $vipCardPhysicalStore;
            $vipCardList[$key]['sort'] = $vipCardSortList[$id]['sort'] ?? 1;
        }
        array_multisort(array_column($vipCardList,'sort'), SORT_ASC, $vipCardList);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $vipCardList];
    }

    /**
     * 新人礼包会员卡列表
     * @return array
     */
    public function newcomerVipCardList(): array
    {
        $vipCardList = VipCard::query()
            ->select(['id','name','price','expire','original_price','rule','grade','created_at','explain','start_at','end_at','applicable_store_type'])
            ->where(['type'=>3,'is_deleted'=>0])
            ->get();
        $vipCardList = $vipCardList->toArray();

        foreach($vipCardList as $key=>$value){
            $id = $value['id'];
            $rule = json_decode($value['rule'],true);
            $currencyCourse = isset($rule['currency_course']) ? $rule['currency_course'] : 0;
            unset($vipCardList[$key]['rule']);
            $vipCardList[$key]['currency_course'] = $currencyCourse;

            $vipCardPhysicalStore = '全部门店';
            if($value['applicable_store_type'] == 2){
                $vipCardPhysicalStoreList = VipCardPhysicalStore::query()
                    ->leftJoin('physical_store', 'vip_card_physical_store.physical_store_id', '=', 'physical_store.id')
                    ->select(['physical_store.name'])
                    ->where(['vip_card_physical_store.vip_card_id'=>$id])
                    ->get();
                $vipCardPhysicalStoreList = $vipCardPhysicalStoreList->toArray();
                $vipCardPhysicalStore = implode('  ',array_column($vipCardPhysicalStoreList,'name'));
            }
            $vipCardList[$key]['physical_store'] = $vipCardPhysicalStore;
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $vipCardList];
    }

    /**
     * 会员卡详情
     * @param array $id
     * @return array
     */
    public function vipCardDetail(int $id): array
    {
        $weekEnum = [1=>'周一',2=>'周二',3=>'周三',4=>'周四',5=>'周五',6=>'周六',7=>'周日'];
        $vipCardInfo = VipCard::query()
            ->select(['id','name','price','expire','original_price','rule','thum_img_url','img_url','type','apiece_quota','grade','created_at','explain','start_at','end_at','applicable_store_type','theme_type','commission_rate','max_deduction_price'])
            ->where(['id'=>$id])
            ->first();
        $vipCardInfo = $vipCardInfo->toArray();
        $rule = json_decode($vipCardInfo['rule'],true);
        $course1 = isset($rule['course1']) ? $rule['course1'] : 0;
        $course2 = isset($rule['course2']) ? $rule['course2'] : 0;
        $course3 = isset($rule['course3']) ? $rule['course3'] : 0;
        $currencyCourse = isset($rule['currency_course']) ? $rule['currency_course'] : 0;
        unset($vipCardInfo['rule']);
        $vipCardInfo['course1'] = $course1;
        $vipCardInfo['course2'] = $course2;
        $vipCardInfo['course3'] = $course3;
        $vipCardInfo['currency_course'] = $currencyCourse;

        $vipCardPhysicalStoreList = [];
        if($vipCardInfo['applicable_store_type'] == 2){
            $vipCardPhysicalStoreList = VipCardPhysicalStore::query()
                ->leftJoin('physical_store', 'vip_card_physical_store.physical_store_id', '=', 'physical_store.id')
                ->select(['physical_store.id','physical_store.name'])
                ->where(['vip_card_physical_store.vip_card_id'=>$id])
                ->get();
            $vipCardPhysicalStoreList = $vipCardPhysicalStoreList->toArray();
        }
        $vipCardInfo['applicable_store'] = $vipCardPhysicalStoreList;

        $vipCardPrivilegeList = VipCardPrivilege::query()
            ->select(['title','img_url','describe'])
            ->where(['vip_card_id'=>$id])
            ->get();
        $vipCardPrivilegeList = $vipCardPrivilegeList->toArray();
        $vipCardInfo['privilege'] = $vipCardPrivilegeList;

        $vipCardDynamicCourseList = VipCardDynamicCourse::query()
            ->select(['name','course','type','week'])
            ->where(['vip_card_id'=>$id])
            ->get();
        $vipCardDynamicCourseList = $vipCardDynamicCourseList->toArray();
        foreach($vipCardDynamicCourseList as $key=>$value){
            $week = json_decode($value['week'],true);
            $newWeek = [];
            foreach($week as $item){
                $newWeek[] = $weekEnum[$item];
            }
            $vipCardDynamicCourseList[$key]['week'] = $newWeek;
        }
        $vipCardInfo['dynamic_course'] = $vipCardDynamicCourseList;

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $vipCardInfo];
    }

    /**
     * 删除会员卡
     * @param int $id
     * @return array
     */
    public function deleteVipCard(int $id): array
    {
        VipCard::query()->where(['id'=>$id])->update(['is_deleted'=>1]);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 会员卡订单列表
     * @param array $params
     * @return array
     */
    public function vipCardOrderList(array $params): array
    {
        $mobile = $params['mobile'];
        $cardName = $params['card_name'];
        $startDate = $params['start_date'];
        $endDate = $params['end_date'];
        $status = $params['status'] ?? 0;
        $orderType = $params['order_type'];
        $vipCardId = $params['vip_card_id'];
        $memberName = $params['member_name'];
        $offset = $this->offset;
        $limit = $this->limit;
        $nowDate = date('Y-m-d H:i:s');
        $weekArray = [7=>"每周日",1=>"每周一",2=>"每周二",3=>"每周三",4=>"每周四",5=>"每周五",6=>"每周六"];
        if($startDate === null || $endDate === null){
            $startDate = null;
            $endDate = null;
        }

        $model = VipCardOrder::query()
            ->leftJoin('member', 'vip_card_order.member_id', '=', 'member.id')
            ->select(['member.id as member_id','member.name','member.mobile','vip_card_order.id','vip_card_order.order_title','vip_card_order.price','vip_card_order.expire','vip_card_order.course1','vip_card_order.course2','vip_card_order.course3','vip_card_order.currency_course','vip_card_order.created_at','vip_card_order.order_counter as serial_number','vip_card_order.recommend_code','vip_card_order.order_status','vip_card_order.closed_at as refund_at','vip_card_order.course1_used','vip_card_order.course2_used','vip_card_order.course3_used','vip_card_order.currency_course_used','vip_card_order.applicable_store_type','vip_card_order.expire_at','vip_card_order.card_theme_type','vip_card_order.recommend_teacher_id','vip_card_order.commission_rate']);
        $where = [['vip_card_order.pay_status','=',1]];
        $whereRaw = '';

        switch ($status){
            case 1:
                //未开始
                $where[] = ['vip_card_order.expire_at','=',VipCardConstant::DEFAULT_EXPIRE_AT];
                $startDate = null;
                $endDate = null;
                break;
            case 2:
                //使用中
                if($endDate === null || $endDate<$nowDate){
                    $endDate = VipCardConstant::DEFAULT_EXPIRE_AT;
                }
                if($startDate>$nowDate){
                    $where[] = ['vip_card_order.expire_at','>',$startDate];
                }else{
                    $where[] = ['vip_card_order.expire_at','>',$nowDate];
                }
                $where[] = ['vip_card_order.expire_at','<',$endDate];
                $whereRaw = '(vip_card_order.course1>vip_card_order.course1_used OR vip_card_order.course2>vip_card_order.course2_used OR vip_card_order.course3>vip_card_order.course3_used OR vip_card_order.currency_course>vip_card_order.currency_course_used)';
                $startDate = null;
                $endDate = null;
                break;
            case 3:
                //已用完
                $whereRaw = 'vip_card_order.course1=vip_card_order.course1_used AND vip_card_order.course2=vip_card_order.course2_used AND vip_card_order.course3=vip_card_order.course3_used AND vip_card_order.currency_course=vip_card_order.currency_course_used';
                break;
            case 4:
                //已过期
                if($endDate>$nowDate || $endDate === null){
                    $where[] = ['vip_card_order.expire_at','<=',$nowDate];
                }else{
                    $where[] = ['vip_card_order.expire_at','<=',$endDate];
                }
                if($startDate !== null && $startDate<$nowDate){
                    $where[] = ['vip_card_order.expire_at','>=',$startDate];
                }
                $whereRaw = '(vip_card_order.course1>vip_card_order.course1_used OR vip_card_order.course2>vip_card_order.course2_used OR vip_card_order.course3>vip_card_order.course3_used OR vip_card_order.currency_course>vip_card_order.currency_course_used)';
                $startDate = null;
                $endDate = null;
                break;
        }
        if($mobile !== null){
            $where[] = ['member.mobile','=',$mobile];
        }
        if($cardName !== null){
            $where[] = ['vip_card_order.order_title','like',"%{$cardName}%"];
        }
        if($startDate !== null && $endDate !== null){
            $model->whereBetween('vip_card_order.expire_at',[$startDate,$endDate]);
        }
        if($vipCardId !== null){
            $where[] = ['vip_card_order.vip_card_id','=',$vipCardId];
        }
        if($memberName !== null){
            $where[] = ['member.name','like',"%{$memberName}%"];
        }
        if($orderType === null){
            $model->whereIn('vip_card_order.order_type',[1,2]);
        }else{
            $where[] = ['vip_card_order.order_type','=',$orderType];
        }

        if($whereRaw === ''){
            $count = $model->where($where)->count();
        }else{
            $count = $model->where($where)->whereRaw($whereRaw)->count();
        }
        $vipCardOrderList = $model->orderBy('vip_card_order.id','desc')->offset($offset)->limit($limit)->get();
        $vipCardOrderList = $vipCardOrderList->toArray();
        $vipCardOrderIdArray = array_column($vipCardOrderList,'id');
        $vipCardOrderDynamicCourseList = VipCardOrderDynamicCourse::query()
            ->select(['vip_card_order_id','name','week','course','course_used'])
            ->whereIn('vip_card_order_id',$vipCardOrderIdArray)
            ->get();
        $vipCardOrderDynamicCourseList = $vipCardOrderDynamicCourseList->toArray();
        foreach($vipCardOrderDynamicCourseList as $key=>$value){
            $newWeek = [];
            $week = json_decode($value['week'],true);
            foreach($week as $item){
                $newWeek[] = $weekArray[$item];
            }
            $vipCardOrderDynamicCourseList[$key]['course_surplus'] = $value['course']-$value['course_used'];
            $vipCardOrderDynamicCourseList[$key]['week'] = implode(',',$newWeek);
        }
        $vipCardOrderDynamicCourseList = $this->functions->arrayGroupBy($vipCardOrderDynamicCourseList,'vip_card_order_id');

        foreach($vipCardOrderList as $key=>$value){
            $vipCardOrderId = $value['id'];
            $parentName = '系统';
            $parentMobile = '无';
            $commission = '无';
            $themeType = '常规班';
            $physicalStoreName = '全部门店';
            $dynamicCourse = $vipCardOrderDynamicCourseList[$vipCardOrderId] ?? [];
            if(!empty($value['recommend_teacher_id'])){
                $parentMemberInfo = Teacher::query()->select(['name','mobile'])->where(['id'=>$value['recommend_teacher_id']])->first();
                $parentMemberInfo = $parentMemberInfo?->toArray();
                $parentName = $parentMemberInfo['name'] ?? '系统';
                $parentMobile = $parentMemberInfo['mobile'] ?? '无';
            }
            $refundAmount = 0;
            if($value['order_status'] == 3){
                $refundAmount = VipCardOrderRefund::query()->where(['vip_card_order_id'=>$value['id'],'status'=>25])->sum('amount');
            }
            $surplusSectionCourse1 = $value['course1']-$value['course1_used'];
            $surplusSectionCourse2 = $value['course2']-$value['course2_used'];
            $surplusSectionCourse3 = $value['course3']-$value['course3_used'];
            $surplusSectionCurrencyCourse = $value['currency_course']-$value['currency_course_used'];
            if($value['applicable_store_type'] == 2){
                $vipCardOrderPhysicalStoreList = VipCardOrderPhysicalStore::query()
                    ->leftJoin('physical_store','vip_card_order_physical_store.physical_store_id','=','physical_store.id')
                    ->select(['physical_store.name'])
                    ->where(['vip_card_order_physical_store.vip_card_order_id'=>$value['id']])
                    ->get();
                $vipCardOrderPhysicalStoreList = $vipCardOrderPhysicalStoreList->toArray();
                $physicalStoreName = implode(',',array_column($vipCardOrderPhysicalStoreList,'name'));
            }
            if($value['expire_at'] === VipCardConstant::DEFAULT_EXPIRE_AT){
                $statusText = '未使用';
            }else if($surplusSectionCourse1==0 && $surplusSectionCourse2==0 && $surplusSectionCourse3==0 && $surplusSectionCurrencyCourse==0){
                $statusText = '已用完';
            }else if($value['expire_at'] > $nowDate){
                $statusText = '使用中';
            }else{
                $statusText = '已过期';
            }
            if($value['card_theme_type'] == 2){
                $themeType = '精品小班';
            }else if($value['card_theme_type'] == 3){
                $themeType = '代码编程';
            }
            if($value['recommend_teacher_id'] != 0){
                $commissionRate = bcdiv($value['commission_rate'],'100',4);
                $commission = bcmul($value['price'],$commissionRate,2);
            }

            $vipCardOrderList[$key]['dynamic_course'] = $dynamicCourse;
            $vipCardOrderList[$key]['refund_amount'] = $refundAmount;
            $vipCardOrderList[$key]['parent_name'] = $parentName;
            $vipCardOrderList[$key]['parent_mobile'] = $parentMobile;
            $vipCardOrderList[$key]['surplus_course1'] = $surplusSectionCourse1;
            $vipCardOrderList[$key]['surplus_course2'] = $surplusSectionCourse2;
            $vipCardOrderList[$key]['surplus_course3'] = $surplusSectionCourse3;
            $vipCardOrderList[$key]['surplus_currency_course'] = $surplusSectionCurrencyCourse;
            $vipCardOrderList[$key]['physical_store_name'] = $physicalStoreName;
            $vipCardOrderList[$key]['status_text'] = $statusText;
            $vipCardOrderList[$key]['theme_type'] = $themeType;
            $vipCardOrderList[$key]['commission'] = $commission;
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => ['list'=>$vipCardOrderList,'count'=>$count]];
    }

    /**
     * 会员卡订单导出
     * @param array $params
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function vipCardOrderExport(array $params): array
    {
        $mobile = $params['mobile'];
        $cardName = $params['card_name'];
        $startDate = $params['start_date'];
        $endDate = $params['end_date'];
        $status = $params['status'] ?? 0;
        $fileName = 'vco'.date('YmdHis');
        $nowDate = date('Y-m-d H:i:s');
        if($startDate === null || $endDate === null){
            $startDate = null;
            $endDate = null;
        }

        $model = VipCardOrder::query()
            ->leftJoin('member', 'vip_card_order.member_id', '=', 'member.id')
            ->select(['member.name','member.mobile','vip_card_order.id','vip_card_order.order_title','vip_card_order.price','vip_card_order.expire','vip_card_order.course1','vip_card_order.course2','vip_card_order.course3','vip_card_order.currency_course','vip_card_order.created_at','vip_card_order.order_counter as serial_number','vip_card_order.recommend_code','vip_card_order.course1_used','vip_card_order.course2_used','vip_card_order.course3_used','vip_card_order.currency_course_used','vip_card_order.applicable_store_type','vip_card_order.card_theme_type','vip_card_order.recommend_teacher_id'])
            ->whereIn('vip_card_order.order_type',[1,2]);
        $where = [['vip_card_order.pay_status','=',1],['vip_card_order.order_status','=',0]];
        $whereRaw = '';

        switch ($status){
            case 1:
                //未开始
                $where[] = ['vip_card_order.expire_at','=',VipCardConstant::DEFAULT_EXPIRE_AT];
                $startDate = null;
                $endDate = null;
                break;
            case 2:
                //使用中
                if($endDate === null || $endDate<$nowDate){
                    $endDate = VipCardConstant::DEFAULT_EXPIRE_AT;
                }
                if($startDate>$nowDate){
                    $where[] = ['vip_card_order.expire_at','>',$startDate];
                }else{
                    $where[] = ['vip_card_order.expire_at','>',$nowDate];
                }
                $where[] = ['vip_card_order.expire_at','<',$endDate];
                $whereRaw = '(vip_card_order.course1>vip_card_order.course1_used OR vip_card_order.course2>vip_card_order.course2_used OR vip_card_order.course3>vip_card_order.course3_used OR vip_card_order.currency_course>vip_card_order.currency_course_used)';
                $startDate = null;
                $endDate = null;
                break;
            case 3:
                //已用完
                $whereRaw = 'vip_card_order.course1=vip_card_order.course1_used AND vip_card_order.course2=vip_card_order.course2_used AND vip_card_order.course3=vip_card_order.course3_used AND vip_card_order.currency_course=vip_card_order.currency_course_used';
                break;
            case 4:
                //已过期
                if($endDate>$nowDate || $endDate === null){
                    $where[] = ['vip_card_order.expire_at','<=',$nowDate];
                }else{
                    $where[] = ['vip_card_order.expire_at','<=',$endDate];
                }
                if($startDate !== null && $startDate<$nowDate){
                    $where[] = ['vip_card_order.expire_at','>=',$startDate];
                }
                $whereRaw = '(vip_card_order.course1>vip_card_order.course1_used OR vip_card_order.course2>vip_card_order.course2_used OR vip_card_order.course3>vip_card_order.course3_used OR vip_card_order.currency_course>vip_card_order.currency_course_used)';
                $startDate = null;
                $endDate = null;
                break;
        }
        if($mobile !== null){
            $where[] = ['member.mobile','=',$mobile];
        }
        if($cardName !== null){
            $where[] = ['vip_card_order.order_title','like',"%{$cardName}%"];
        }
        if($startDate !== null && $endDate !== null){
            $model->whereBetween('vip_card_order.expire_at',[$startDate,$endDate]);
        }
        if($whereRaw === ''){
            $vipCardOrderList = $model->where($where)->orderBy('vip_card_order.id','desc')->get();
        }else{
            $vipCardOrderList = $model->where($where)->whereRaw($whereRaw)->orderBy('vip_card_order.id','desc')->get();
        }
        $vipCardOrderList = $vipCardOrderList->toArray();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', '用户名称')
            ->setCellValue('B1', '手机号')
            ->setCellValue('C1', '访问人名称')
            ->setCellValue('D1', '最后访问人手机')
            ->setCellValue('E1', '会员卡名称')
            ->setCellValue('F1', '会员卡类型')
            ->setCellValue('G1', '销售价格')
            ->setCellValue('H1', '限制时间')
            ->setCellValue('I1', '课程定义')
            ->setCellValue('J1', '剩余次数')
            ->setCellValue('K1', '第几次购买')
            ->setCellValue('L1', '付款时间')
            ->setCellValue('M1', '门店名称');
        $i=2;
        foreach($vipCardOrderList as $item){
            $parentName = '系统';
            $parentMobile = '无';
            $themeType = '常规班';
            $physicalStoreName = '全部门店';
            if(!empty($item['recommend_teacher_id'])){
                $parentMemberInfo = Teacher::query()->select(['name','mobile'])->where(['id'=>$item['recommend_teacher_id']])->first();
                $parentMemberInfo = $parentMemberInfo?->toArray();
                $parentName = $parentMemberInfo['name'] ?? '系统';
                $parentMobile = $parentMemberInfo['mobile'] ?? '无';
            }
            $surplusSectionCourse1 = $item['course1']-$item['course1_used'];
            $surplusSectionCourse2 = $item['course2']-$item['course2_used'];
            $surplusSectionCourse3 = $item['course3']-$item['course3_used'];
            $surplusSectionCurrencyCourse = $item['currency_course']-$item['currency_course_used'];
            if($item['applicable_store_type'] == 2){
                $vipCardOrderPhysicalStoreList = VipCardOrderPhysicalStore::query()
                    ->leftJoin('physical_store','vip_card_order_physical_store.physical_store_id','=','physical_store.id')
                    ->select(['physical_store.name'])
                    ->where(['vip_card_order_physical_store.vip_card_order_id'=>$item['id']])
                    ->get();
                $vipCardOrderPhysicalStoreList = $vipCardOrderPhysicalStoreList->toArray();
                $physicalStoreName = implode(',',array_column($vipCardOrderPhysicalStoreList,'name'));
            }
            if($item['card_theme_type'] == 2){
                $themeType = '精品小班';
            }else if($item['card_theme_type'] == 3){
                $themeType = '代码编程';
            }
            $courseRule = '常规课：'.$item['course1'].' 活动课：'.$item['course2'].' 专业课：'.$item['course3'].' 体验课：'.$item['currency_course'];
            $surplusCourse = '常规课：'.$surplusSectionCourse1.' 活动课：'.$surplusSectionCourse2.' 专业课：'.$surplusSectionCourse3.' 体验课：'.$surplusSectionCurrencyCourse;

            $sheet->setCellValue('A'.$i, $item['name'])
                ->setCellValue('B'.$i, $item['mobile'])
                ->setCellValue('C'.$i, $parentName)
                ->setCellValue('D'.$i, $parentMobile)
                ->setCellValue('E'.$i, $item['order_title'])
                ->setCellValue('F'.$i, $themeType)
                ->setCellValue('G'.$i, $item['price'])
                ->setCellValue('H'.$i, $item['expire'])
                ->setCellValue('I'.$i, $courseRule)
                ->setCellValue('J'.$i, $surplusCourse)
                ->setCellValue('K'.$i, $item['serial_number'])
                ->setCellValue('L'.$i, $item['created_at'])
                ->setCellValue('M'.$i, $physicalStoreName);
            $i++;
        }

        $writer = new Xlsx($spreadsheet);
        $localPath = "/tmp/{$fileName}.xlsx";
        $writer->save($localPath);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => ['path'=>$localPath]];
    }

    /**
     * 会员卡订单退款
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function vipCardOrderRefund(array $params): array
    {
        $id = $params['id'];
        $refundAmount = $params['refund_amount'] ?? 0;
        $nowDate = date('Y-m-d H:i:s');

        $vipCardOrderInfo = VipCardOrder::query()
            ->select(['member_id','price','order_no'])
            ->where(['id'=>$id,'order_status'=>0,'pay_status'=>1])
            ->first();
        if(empty($vipCardOrderInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '订单信息错误', 'data' => null];
        }
        $vipCardOrderInfo = $vipCardOrderInfo->toArray();
        //支付信息
        $payApplyInfo = PayApply::query()
            ->select(['out_trade_no','pay_code'])
            ->where(['order_no'=>$vipCardOrderInfo['order_no'],'status'=>1,'order_type'=>2])
            ->first();
        if(empty($payApplyInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '支付信息缺失', 'data' => null];
        }
        $payApplyInfo = $payApplyInfo->toArray();
        $payCode = $payApplyInfo['pay_code'];
        //订单退款检验
        $vipCardOrderRefundExists = VipCardOrderRefund::query()->where(['vip_card_order_id'=>$id])->whereIn('status',[24,25])->exists();
        if($vipCardOrderRefundExists === true){
            return ['code' => ErrorCode::WARNING, 'msg' => '订单不能重复申请退款', 'data' => null];
        }
        //总金额
        $totalAmount = $vipCardOrderInfo['price'];
        $outRefundNo = $this->functions->outTradeNo();
        if($refundAmount<=0 || $refundAmount>$totalAmount){
            return ['code' => ErrorCode::WARNING, 'msg' => '退款金额不可大于实付金额', 'data' => null];
        }

        //会员卡订单退款数据
        $vipCardOrderRefundId = IdGenerator::generate();
        $insertVipCardOrderRefundData['id'] = $vipCardOrderRefundId;
        $insertVipCardOrderRefundData['member_id'] = $vipCardOrderInfo['member_id'];
        $insertVipCardOrderRefundData['vip_card_order_id'] = $id;
        $insertVipCardOrderRefundData['amount'] = $refundAmount;
        $insertVipCardOrderRefundData['status'] = 24;
        $insertVipCardOrderRefundData['operated_at'] = $nowDate;
        //退款申请数据
        $insertRefundApplyData['id'] = IdGenerator::generate();
        $insertRefundApplyData['order_refund_id'] = $vipCardOrderRefundId;
        $insertRefundApplyData['out_refund_no'] = $outRefundNo;
        $insertRefundApplyData['pay_code'] = $payCode;
        $insertRefundApplyData['order_type'] = 2;

        Db::connection('jkc_edu')->beginTransaction();
        try{
            Db::connection('jkc_edu')->table('vip_card_order')->where(['id'=>$id])->update(['closed_at'=>$nowDate]);
            Db::connection('jkc_edu')->table('refund_apply')->insert($insertRefundApplyData);
            Db::connection('jkc_edu')->table('vip_card_order_refund')->insert($insertVipCardOrderRefundData);
            Db::connection('jkc_edu')->commit();
        } catch(\Throwable $e){
            Db::connection('jkc_edu')->rollBack();
            throw new \Exception($e->getMessage(), 1);
        }

        switch ($payCode){
            case 'WXPAY':
                $weChatPayFactory = new WeChatPayFactory();
                $weChatPayFactory->amount = ['total'=>(int)bcmul($totalAmount,"100"),'refund'=>(int)bcmul((string)$refundAmount,"100"),'currency'=>'CNY'];
                $weChatPayFactory->timeExpire = date("c", strtotime("+15 minutes"));
                $weChatPayFactory->outRefundNo = $outRefundNo;
                $weChatPayFactory->outTradeNo = $payApplyInfo['out_trade_no'];
                $weChatPayFactory->notifyUrl = env('APP_DOMAIN').'/api/pay/callback/wx/vip_card_refund';

                $result = $weChatPayFactory->refunds();
                $returnData = $result['data'];
                break;
            case 'ALIPAY':
                $aLiPayConfig = json_decode(env('ALIPAY'), true);
                $merchantPrivateKey = file_get_contents($aLiPayConfig['merchantPrivateKey']);
                $appCertPath = $aLiPayConfig['merchantCertPath']; //应用公钥证书路径（要确保证书文件可读），例如：/home/admin/cert/appCertPublicKey_2019051064521003.crt
                $alipayCertPath = $aLiPayConfig['alipayCertPath']; //支付宝公钥证书路径（要确保证书文件可读），例如：/home/admin/cert/alipayCertPublicKey_RSA2.crt
                $rootCertPath = $aLiPayConfig['alipayRootCertPath']; //支付宝根证书路径（要确保证书文件可读），例如：/home/admin/cert/alipayRootCert.crt
                $c = new \AopCertClient();
                $c->gatewayUrl = "https://openapi.alipay.com/gateway.do";
                $c->appId = $aLiPayConfig['appId'];
                $c->rsaPrivateKey = $merchantPrivateKey;
                $c->signType= "RSA2";
                //调用getPublicKey从支付宝公钥证书中提取公钥
                $c->alipayrsaPublicKey = $c->getPublicKey($alipayCertPath);
                //是否校验自动下载的支付宝公钥证书，如果开启校验要保证支付宝根证书在有效期内
                $c->isCheckAlipayPublicCert = true;
                //调用getCertSN获取证书序列号
                $c->appCertSN = $c->getCertSN($appCertPath);
                //调用getRootCertSN获取支付宝根证书序列号
                $c->alipayRootCertSN = $c->getRootCertSN($rootCertPath);

                $bizContent = [
                    'out_trade_no'=>$payApplyInfo['out_trade_no'],
                    'out_request_no'=>$outRefundNo,
                    'refund_amount'=>(string)$refundAmount
                ];
                $request = new \AlipayTradeRefundRequest();
                $request->setBizContent(json_encode($bizContent));
                $result = $c->execute($request);
                $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
                $resultCode = $result->$responseNode->code;
                if(empty($resultCode) || $resultCode != 10000){
                    Log::get()->info("vipCardOrderRefund[{$id}]:".$result->$responseNode->msg);
                    return ['code' => ErrorCode::WARNING, 'msg' => '退款失败:'.$result->$responseNode->msg, 'data' => null];
                }
                $returnData['data'] = $result->$responseNode->msg;
                break;
            case 'ZERO':
                $payService = new PayService();
                $result = $payService->vipCardRefundCallback(['out_refund_no'=>$outRefundNo,'refund_status'=>'SUCCESS']);
                if($result['code'] === ErrorCode::FAILURE){
                    return ['code' => ErrorCode::WARNING, 'msg' => '退款失败', 'data' => null];
                }
                $returnData['body'] = 'zero';
                break;
            default:
                return ['code' => ErrorCode::WARNING, 'msg' => '退款失败:支付方式错误', 'data' => null];
        }
        $this->eventDispatcher->dispatch(new VipCardOrderRefundRegistered((int)$vipCardOrderInfo['member_id'],$vipCardOrderRefundId));
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $returnData];
    }

    /**
     * 赠送会员卡订单列表
     * @param array $params
     * @return array
     */
    public function giftVipCardOrderList(array $params): array
    {
        $mobile = $params['mobile'] ?? null;
        $startDate = $params['start_date'] ?? null;
        $endDate = $params['end_date'] ?? null;
        $status = $params['status'] ?? 0;
        $physicalStoreId = $params['physical_store_id'] ?? null;
        $memberName = $params['member_name'] ?? null;
        $offset = $this->offset;
        $limit = $this->limit;
        $nowDate = date('Y-m-d H:i:s');
        if($startDate === null || $endDate === null){
            $startDate = null;
            $endDate = null;
        }

        $model = VipCardOrder::query()
            ->leftJoin('member', 'vip_card_order.member_id', '=', 'member.id')
            ->leftJoin('vip_card_order_physical_store', 'vip_card_order.id', '=', 'vip_card_order_physical_store.vip_card_order_id')
            ->select(['member.id as member_id','member.name','member.mobile','vip_card_order.id','vip_card_order.price','vip_card_order.order_title','vip_card_order.expire','vip_card_order.course1','vip_card_order.course2','vip_card_order.course3','vip_card_order.currency_course','vip_card_order.created_at','vip_card_order.order_status','vip_card_order.course1_used','vip_card_order.course2_used','vip_card_order.course3_used','vip_card_order.currency_course_used','vip_card_order.applicable_store_type','vip_card_order.expire_at','vip_card_order.card_theme_type']);
        $where = [['vip_card_order.order_type','=',4]];
        $whereRaw = '';

        switch ($status){
            case 1:
                //未开始
                $where[] = ['vip_card_order.expire_at','=',VipCardConstant::DEFAULT_EXPIRE_AT];
                $startDate = null;
                $endDate = null;
                break;
            case 2:
                //使用中
                if($endDate === null || $endDate<$nowDate){
                    $endDate = VipCardConstant::DEFAULT_EXPIRE_AT;
                }
                if($startDate>$nowDate){
                    $where[] = ['vip_card_order.expire_at','>',$startDate];
                }else{
                    $where[] = ['vip_card_order.expire_at','>',$nowDate];
                }
                $where[] = ['vip_card_order.expire_at','<',$endDate];
                $whereRaw = '(vip_card_order.course1>vip_card_order.course1_used OR vip_card_order.course2>vip_card_order.course2_used OR vip_card_order.course3>vip_card_order.course3_used OR vip_card_order.currency_course>vip_card_order.currency_course_used)';
                $startDate = null;
                $endDate = null;
                break;
            case 3:
                //已用完
                $whereRaw = 'vip_card_order.course1=vip_card_order.course1_used AND vip_card_order.course2=vip_card_order.course2_used AND vip_card_order.course3=vip_card_order.course3_used AND vip_card_order.currency_course=vip_card_order.currency_course_used';
                break;
            case 4:
                //已过期
                if($endDate>$nowDate || $endDate === null){
                    $where[] = ['vip_card_order.expire_at','<=',$nowDate];
                }else{
                    $where[] = ['vip_card_order.expire_at','<=',$endDate];
                }
                if($startDate !== null && $startDate<$nowDate){
                    $where[] = ['vip_card_order.expire_at','>=',$startDate];
                }
                $whereRaw = '(vip_card_order.course1>vip_card_order.course1_used OR vip_card_order.course2>vip_card_order.course2_used OR vip_card_order.course3>vip_card_order.course3_used OR vip_card_order.currency_course>vip_card_order.currency_course_used)';
                $startDate = null;
                $endDate = null;
                break;
        }
        if($mobile !== null){
            $where[] = ['member.mobile','=',$mobile];
        }
        if($startDate !== null && $endDate !== null){
            $model->whereBetween('vip_card_order.expire_at',[$startDate,$endDate]);
        }
        if($physicalStoreId !== null){
            $model->where(function($query) use ($physicalStoreId) {
                $query->where('vip_card_order.applicable_store_type', 1)
                    ->orWhere('vip_card_order_physical_store.physical_store_id', $physicalStoreId);
            });
        }
        if($memberName !== null){
            $where[] = ['member.name','like',"%{$memberName}%"];
        }

        if($whereRaw === ''){
            $count = $model->where($where)->count(Db::connection('jkc_edu')->raw('DISTINCT vip_card_order.id'));
        }else{
            $count = $model->where($where)->whereRaw($whereRaw)->count(Db::connection('jkc_edu')->raw('DISTINCT vip_card_order.id'));
        }
        $vipCardOrderList = $model
            ->orderBy('vip_card_order.id', 'desc')
            ->groupBy('vip_card_order.id')
            ->offset($offset)
            ->limit($limit)
            ->get();
        $vipCardOrderList = $vipCardOrderList->toArray();

        foreach($vipCardOrderList as $key=>$value){
            $themeType = '常规班';
            $physicalStoreName = '全部门店';

            $surplusSectionCourse1 = $value['course1']-$value['course1_used'];
            $surplusSectionCourse2 = $value['course2']-$value['course2_used'];
            $surplusSectionCourse3 = $value['course3']-$value['course3_used'];
            $surplusSectionCurrencyCourse = $value['currency_course']-$value['currency_course_used'];
            $totalCourse = $value['course1'] + $value['course2'] + $value['course3'] + $value['currency_course'];
            $totalCourseUsed = $value['course1_used'] + $value['course2_used'] + $value['course3_used'] + $value['currency_course_used'];
            if ($value['applicable_store_type'] == 2) {
                $vipCardOrderPhysicalStoreList = VipCardOrderPhysicalStore::query()
                    ->leftJoin('physical_store', 'vip_card_order_physical_store.physical_store_id', '=', 'physical_store.id')
                    ->select(['physical_store.name'])
                    ->where(['vip_card_order_physical_store.vip_card_order_id' => $value['id']])
                    ->get();
                $vipCardOrderPhysicalStoreList = $vipCardOrderPhysicalStoreList->toArray();
                $physicalStoreName = implode(',', array_column($vipCardOrderPhysicalStoreList, 'name'));
            }
            if($value['expire_at'] === VipCardConstant::DEFAULT_EXPIRE_AT){
                $statusText = '未使用';
            }else if($surplusSectionCourse1==0 && $surplusSectionCourse2==0 && $surplusSectionCourse3==0 && $surplusSectionCurrencyCourse==0){
                $statusText = '已用完';
            }else if($value['expire_at'] > $nowDate){
                $statusText = '使用中';
            }else{
                $statusText = '已过期';
            }
            if($value['card_theme_type'] == 2){
                $themeType = '精品小班';
            }else if($value['card_theme_type'] == 3){
                $themeType = '代码编程';
            }

            $vipCardOrderList[$key]['total_course'] = $totalCourse;
            $vipCardOrderList[$key]['total_course_used'] = $totalCourseUsed;
            $vipCardOrderList[$key]['physical_store_name'] = $physicalStoreName;
            $vipCardOrderList[$key]['status_text'] = $statusText;
            $vipCardOrderList[$key]['theme_type'] = $themeType;
            $vipCardOrderList[$key]['created_at'] = date('Y.m.d H:i', strtotime($value['created_at']));
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => ['list'=>$vipCardOrderList,'count'=>$count]];
    }


    /**
     * 平台赠送会员卡详情列表
     * @param array $query
     * @return array
     */
    public function giftVipCardOrderDetail(array $query): array
    {
        $id = $query['id'] ?? 0;

        $offset = $this->offset;
        $limit = $this->limit;

        $courseOfflineOrderModel = CourseOfflineOrder::query()
            ->leftJoin('course_offline_plan', 'course_offline_order.course_offline_plan_id', '=', 'course_offline_plan.id')
            ->where('course_offline_order.vip_card_order_id', $id)
            ->where('course_offline_order.order_status', 0)
            ->where('course_offline_order.pay_status', 1);

        $count = $courseOfflineOrderModel->count();

        $fields = [
            'course_offline_order.id', 'course_offline_order.course_name', 'course_offline_order.teacher_name',
            'course_offline_order.physical_store_name', 'course_offline_order.class_status',
            'course_offline_order.start_at', 'course_offline_order.created_at'
        ];
        $courseOfflineOrderList = $courseOfflineOrderModel->select($fields)
            ->offset($offset)
            ->limit($limit)
            ->orderByDesc('course_offline_order.id')
            ->get()
            ->toArray();

        foreach ($courseOfflineOrderList as $index => &$item) {
            $statusText = '已报名';
            if ($item['class_status'] == 1) {
                $statusText = '已上课';
            }

            $item['status_text'] = $statusText;
            $item['start_at'] = date('Y.m.d H:i', strtotime($item['start_at']));
            $item['created_at'] = date('Y.m.d H:i', strtotime($item['created_at']));
        }

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => ['list' => $courseOfflineOrderList, 'count' => $count]];
    }

}