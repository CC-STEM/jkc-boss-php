<?php

declare(strict_types=1);

namespace App\Service;

use App\Constants\ErrorCode;
use App\Logger\Log;
use App\Model\Coupon;
use App\Model\CouponTemplate;
use App\Model\CouponTemplateGoods;
use App\Model\CouponTemplatePhysicalStore;
use App\Model\Member;
use App\Model\MemberBelongTo;
use App\Model\PhysicalStore;
use App\Model\PhysicalStoreCouponTemplate;
use App\Snowflake\IdGenerator;
use Hyperf\DbConnection\Db;

class CouponService extends BaseService
{
    /**
     * 添加优惠券模板
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function addCouponTemplate(array $params): array
    {
        $physicalStore = $params['physical_store'] ?? [];
        $totality = $params['totality'] ?? -1;
        $goods = $params['goods'] ?? [];

        $couponTemplateId = IdGenerator::generate();
        $insertCouponTemplateData['id'] = $couponTemplateId;
        $insertCouponTemplateData['name'] = $params['name'];
        $insertCouponTemplateData['threshold_amount'] = $params['threshold_amount'];
        $insertCouponTemplateData['amount'] = $params['amount'];
        $insertCouponTemplateData['totality'] = $totality;
        $insertCouponTemplateData['end_at'] = $params['end_at'];
        $insertCouponTemplateData['applicable_store_type'] = !empty($physicalStore) ? 2 : 1;
        $insertCouponTemplateData['applicable_theme_type'] = $params['applicable_theme_type'];

        $insertCouponTemplatePhysicalStoreData = [];
        foreach($physicalStore as $value){
            $couponTemplatePhysicalStoreData['id'] = IdGenerator::generate();
            $couponTemplatePhysicalStoreData['coupon_template_id'] = $couponTemplateId;
            $couponTemplatePhysicalStoreData['physical_store_id'] = $value;
            $insertCouponTemplatePhysicalStoreData[] = $couponTemplatePhysicalStoreData;
        }

        $insertCouponTemplateGoodsData = [];
        foreach($goods as $value){
            $couponTemplateGoodsData['id'] = IdGenerator::generate();
            $couponTemplateGoodsData['coupon_template_id'] = $couponTemplateId;
            $couponTemplateGoodsData['goods_id'] = $value;
            $insertCouponTemplateGoodsData[] = $couponTemplateGoodsData;
        }

        CouponTemplate::query()->insert($insertCouponTemplateData);
        if(!empty($insertCouponTemplatePhysicalStoreData)){
            CouponTemplatePhysicalStore::query()->insert($insertCouponTemplatePhysicalStoreData);
        }
        if(!empty($insertCouponTemplateGoodsData)){
            CouponTemplateGoods::query()->insert($insertCouponTemplateGoodsData);
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 编辑优惠券模板
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function editCouponTemplate(array $params): array
    {
        $physicalStore = $params['physical_store'] ?? [];
        $totality = $params['totality'] ?? -1;
        $couponTemplateId = $params['id'];
        $goods = $params['goods'] ?? [];

        $updateCouponTemplateData['name'] = $params['name'];
        $updateCouponTemplateData['threshold_amount'] = $params['threshold_amount'];
        $updateCouponTemplateData['amount'] = $params['amount'];
        $updateCouponTemplateData['totality'] = $totality;
        $updateCouponTemplateData['end_at'] = $params['end_at'];
        $updateCouponTemplateData['applicable_store_type'] = !empty($physicalStore) ? 2 : 1;
        $updateCouponTemplateData['applicable_theme_type'] = $params['applicable_theme_type'];

        $insertCouponTemplatePhysicalStoreData = [];
        foreach($physicalStore as $value){
            $couponTemplatePhysicalStoreData['id'] = IdGenerator::generate();
            $couponTemplatePhysicalStoreData['coupon_template_id'] = $couponTemplateId;
            $couponTemplatePhysicalStoreData['physical_store_id'] = $value;
            $insertCouponTemplatePhysicalStoreData[] = $couponTemplatePhysicalStoreData;
        }

        $insertCouponTemplateGoodsData = [];
        foreach($goods as $value){
            $couponTemplateGoodsData['id'] = IdGenerator::generate();
            $couponTemplateGoodsData['coupon_template_id'] = $couponTemplateId;
            $couponTemplateGoodsData['goods_id'] = $value;
            $insertCouponTemplateGoodsData[] = $couponTemplateGoodsData;
        }

        CouponTemplateGoods::query()->where(['coupon_template_id'=>$couponTemplateId])->delete();
        CouponTemplatePhysicalStore::query()->where(['coupon_template_id'=>$couponTemplateId])->delete();
        CouponTemplate::query()->where(['id'=>$couponTemplateId])->update($updateCouponTemplateData);
        if(!empty($insertCouponTemplatePhysicalStoreData)){
            CouponTemplatePhysicalStore::query()->insert($insertCouponTemplatePhysicalStoreData);
        }
        if(!empty($insertCouponTemplateGoodsData)){
            CouponTemplateGoods::query()->insert($insertCouponTemplateGoodsData);
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 删除优惠券模板
     * @param int $id
     * @return array
     */
    public function deleteCouponTemplate(int $id): array
    {
        CouponTemplate::query()->where(['id'=>$id])->update(['is_deleted'=>1]);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 优惠券模板列表
     * @return array
     */
    public function couponTemplateList(): array
    {
        $offset = $this->offset;
        $limit = $this->limit;

        $couponTemplateList = CouponTemplate::query()
            ->select(['id','name','threshold_amount','amount','totality','end_at','issued_quantity','applicable_theme_type'])
            ->where(['is_deleted'=>0])
            ->offset($offset)->limit($limit)
            ->get();
        $couponTemplateList = $couponTemplateList->toArray();
        $count = CouponTemplate::query()->where(['is_deleted'=>0])->count();

        foreach($couponTemplateList as $key=>$value){
            $surplusQuantity = '无';
            $physicalStore = '无';
            if($value['totality']>0){
                $surplusQuantity = $value['totality']-$value['issued_quantity'];
            }
            $couponTemplatePhysicalStoreList = CouponTemplatePhysicalStore::query()
                ->select(['physical_store_id'])
                ->where(['coupon_template_id'=>$value['id']])
                ->get();
            $couponTemplatePhysicalStoreList = $couponTemplatePhysicalStoreList->toArray();
            if(!empty($couponTemplatePhysicalStoreList)){
                $physicalStoreIdArray = array_column($couponTemplatePhysicalStoreList,'physical_store_id');
                $physicalStoreList = PhysicalStore::query()
                    ->select(['name'])
                    ->whereIn('id',$physicalStoreIdArray)
                    ->get();
                $physicalStoreList = $physicalStoreList->toArray();
                $physicalStore = implode(' ',array_column($physicalStoreList,'name'));
            }

            $couponTemplateList[$key]['physical_store'] = $physicalStore;
            $couponTemplateList[$key]['surplus_quantity'] = $surplusQuantity;
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => ['list'=>$couponTemplateList,'count'=>$count]];
    }

    /**
     * 优惠券模板详情
     * @param int $id
     * @return array
     */
    public function couponTemplateDetail(int $id): array
    {
        $couponTemplateInfo = CouponTemplate::query()
            ->select(['id','name','threshold_amount','amount','totality','end_at','applicable_theme_type'])
            ->where(['id'=>$id])
            ->first();
        $couponTemplateInfo = $couponTemplateInfo->toArray();

        $couponTemplatePhysicalStoreList = CouponTemplatePhysicalStore::query()
            ->select(['physical_store_id'])
            ->where(['coupon_template_id'=>$id])
            ->get();
        $couponTemplatePhysicalStoreList = $couponTemplatePhysicalStoreList->toArray();
        $physicalStoreIdArray = !empty($couponTemplatePhysicalStoreList) ? array_column($couponTemplatePhysicalStoreList,'physical_store_id') : [];
        $couponTemplateInfo['physical_store'] = $physicalStoreIdArray;

        $couponTemplateGoodsList = CouponTemplateGoods::query()
            ->leftJoin('goods','coupon_template_goods.goods_id','=','goods.id')
            ->select(['goods.id','goods.name','goods.img_url','goods.min_price'])
            ->where(['coupon_template_goods.coupon_template_id'=>$id])
            ->get();
        $couponTemplateGoodsList = $couponTemplateGoodsList->toArray();
        $couponTemplateInfo['goods'] = $couponTemplateGoodsList;

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $couponTemplateInfo];
    }

    /**
     * 优惠券模板下放门店
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function couponTemplateDecentralizePhysicalStore(array $params): array
    {
        $couponTemplateId = $params['id'];
        $issuedQuantity = $params['totality'];

        $couponTemplateInfo = CouponTemplate::query()
            ->select(['totality'])
            ->where(['id'=>$couponTemplateId,'is_deleted'=>0])
            ->first();
        $couponTemplateInfo = $couponTemplateInfo->toArray();

        $insertPhysicalStoreCouponTemplateData['id'] = IdGenerator::generate();
        $insertPhysicalStoreCouponTemplateData['physical_store_id'] = $params['totality'];
        $insertPhysicalStoreCouponTemplateData['coupon_template_id'] = $couponTemplateId;
        $insertPhysicalStoreCouponTemplateData['totality'] = $issuedQuantity;

        Db::connection('jkc_edu')->beginTransaction();
        try{
            PhysicalStoreCouponTemplate::query()->insert($insertPhysicalStoreCouponTemplateData);
            if($couponTemplateInfo['totality']>=0){
                $couponTemplateAffected = Db::connection('jkc_edu')->update("UPDATE coupon_template SET issued_quantity=issued_quantity + ? WHERE id = ? AND totality >= issued_quantity+?", [$issuedQuantity,$couponTemplateId,$issuedQuantity]);
                if(!$couponTemplateAffected){
                    Db::connection('jkc_edu')->rollBack();
                    Log::get()->info("issuedCouponBatch:优惠券批量发行失败");
                    return ['code' => ErrorCode::FAILURE, 'msg' => '优惠券发行失败', 'data' => null];
                }
            }else{
                Db::connection('jkc_edu')->update("UPDATE coupon_template SET issued_quantity=issued_quantity + ? WHERE id = ?", [$issuedQuantity,$couponTemplateId]);
            }
            Db::connection('jkc_edu')->commit();
        } catch(\Throwable $e){
            Db::connection('jkc_edu')->rollBack();
            throw new \Exception($e->getMessage(), 1);
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 优惠券发放
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function issuedCoupon(array $params): array
    {
        $mobile = $params['mobile'];
        $couponTemplateId = $params['id'];

        $memberList = Member::query()
            ->select(['id'])
            ->whereIn('mobile',$mobile)
            ->get();
        $memberList = $memberList->toArray();
        if(empty($memberList)){
            return ['code' => ErrorCode::WARNING, 'msg' => '手机号不存在', 'data' => null];
        }
        $issuedQuantity = count($memberList);

        $couponTemplateInfo = CouponTemplate::query()
            ->select(['id','name','threshold_amount','amount','end_at','totality','applicable_store_type','applicable_theme_type'])
            ->where(['id'=>$couponTemplateId,'is_deleted'=>0])
            ->first();
        $couponTemplateInfo = $couponTemplateInfo->toArray();

        $couponTemplatePhysicalStoreList = CouponTemplatePhysicalStore::query()
            ->select(['physical_store_id'])
            ->where(['coupon_template_id'=>$couponTemplateId])
            ->get();
        $couponTemplatePhysicalStoreList = $couponTemplatePhysicalStoreList->toArray();

        $couponTemplateGoodsList = CouponTemplateGoods::query()
            ->select(['goods_id'])
            ->where(['coupon_template_id'=>$couponTemplateId])
            ->get();
        $couponTemplateGoodsList = $couponTemplateGoodsList->toArray();

        $insertCouponData = [];
        $insertCouponPhysicalStoreData = [];
        $insertCouponGoodsData = [];
        foreach($memberList as $value){
            $couponId = IdGenerator::generate();
            $couponData['id'] = $couponId;
            $couponData['member_id'] = $value['id'];
            $couponData['coupon_template_id'] = $couponTemplateInfo['id'];
            $couponData['name'] = $couponTemplateInfo['name'];
            $couponData['threshold_amount'] = $couponTemplateInfo['threshold_amount'];
            $couponData['amount'] = $couponTemplateInfo['amount'];
            $couponData['end_at'] = $couponTemplateInfo['end_at'];
            $couponData['applicable_store_type'] = $couponTemplateInfo['applicable_store_type'];
            $couponData['applicable_theme_type'] = $couponTemplateInfo['applicable_theme_type'];
            $insertCouponData[] = $couponData;

            foreach($couponTemplatePhysicalStoreList as $item){
                $couponPhysicalStoreData['id'] = IdGenerator::generate();
                $couponPhysicalStoreData['coupon_id'] = $couponId;
                $couponPhysicalStoreData['physical_store_id'] = $item['physical_store_id'];
                $insertCouponPhysicalStoreData[] = $couponPhysicalStoreData;
            }

            foreach($couponTemplateGoodsList as $item){
                $couponGoodsData['id'] = IdGenerator::generate();
                $couponGoodsData['coupon_id'] = $couponId;
                $couponGoodsData['goods_id'] = $item['goods_id'];
                $insertCouponGoodsData[] = $couponGoodsData;
            }
        }

        Db::connection('jkc_edu')->beginTransaction();
        try{
            Db::connection('jkc_edu')->table('coupon')->insert($insertCouponData);
            if(!empty($insertCouponPhysicalStoreData)){
                Db::connection('jkc_edu')->table('coupon_physical_store')->insert($insertCouponPhysicalStoreData);
            }
            if(!empty($insertCouponGoodsData)){
                Db::connection('jkc_edu')->table('coupon_goods')->insert($insertCouponGoodsData);
            }
            if($couponTemplateInfo['totality']>=0){
                $couponTemplateAffected = Db::connection('jkc_edu')->update("UPDATE coupon_template SET issued_quantity=issued_quantity + ? WHERE id = ? AND totality >= issued_quantity+?", [$issuedQuantity,$couponTemplateId,$issuedQuantity]);
                if(!$couponTemplateAffected){
                    Db::connection('jkc_edu')->rollBack();
                    Log::get()->info("issuedCouponBatch:优惠券批量发行失败");
                    return ['code' => ErrorCode::FAILURE, 'msg' => '优惠券发行失败', 'data' => null];
                }
            }else{
                Db::connection('jkc_edu')->update("UPDATE coupon_template SET issued_quantity=issued_quantity + ? WHERE id = ?", [$issuedQuantity,$couponTemplateId]);
            }
            Db::connection('jkc_edu')->commit();
        } catch(\Throwable $e){
            Db::connection('jkc_edu')->rollBack();
            throw new \Exception($e->getMessage(), 1);
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 门店成员优惠券发放
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function physicalStoreAllStaffIssuedCoupon(array $params): array
    {
        $physicalStoreId = $params['physical_store_id'];
        $couponTemplateId = $params['id'];

        $memberList = MemberBelongTo::query()
            ->select(['member_id as id'])
            ->where(['physical_store_id'=>$physicalStoreId])
            ->get();
        $memberList = $memberList->toArray();
        if(empty($memberList)){
            return ['code' => ErrorCode::WARNING, 'msg' => '门店会员为空', 'data' => null];
        }
        $issuedQuantity = count($memberList);

        $couponTemplateInfo = CouponTemplate::query()
            ->select(['id','name','threshold_amount','amount','end_at','totality','applicable_store_type','applicable_theme_type'])
            ->where(['id'=>$couponTemplateId,'is_deleted'=>0])
            ->first();
        $couponTemplateInfo = $couponTemplateInfo->toArray();

        $couponTemplatePhysicalStoreList = CouponTemplatePhysicalStore::query()
            ->select(['physical_store_id'])
            ->where(['coupon_template_id'=>$couponTemplateId])
            ->get();
        $couponTemplatePhysicalStoreList = $couponTemplatePhysicalStoreList->toArray();

        $couponTemplateGoodsList = CouponTemplateGoods::query()
            ->select(['goods_id'])
            ->where(['coupon_template_id'=>$couponTemplateId])
            ->get();
        $couponTemplateGoodsList = $couponTemplateGoodsList->toArray();

        $insertCouponData = [];
        $insertCouponPhysicalStoreData = [];
        $insertCouponGoodsData = [];
        foreach($memberList as $value){
            $couponId = IdGenerator::generate();
            $couponData['id'] = $couponId;
            $couponData['member_id'] = $value['id'];
            $couponData['coupon_template_id'] = $couponTemplateInfo['id'];
            $couponData['name'] = $couponTemplateInfo['name'];
            $couponData['threshold_amount'] = $couponTemplateInfo['threshold_amount'];
            $couponData['amount'] = $couponTemplateInfo['amount'];
            $couponData['end_at'] = $couponTemplateInfo['end_at'];
            $couponData['applicable_store_type'] = $couponTemplateInfo['applicable_store_type'];
            $couponData['applicable_theme_type'] = $couponTemplateInfo['applicable_theme_type'];
            $insertCouponData[] = $couponData;

            foreach($couponTemplatePhysicalStoreList as $item){
                $couponPhysicalStoreData['id'] = IdGenerator::generate();
                $couponPhysicalStoreData['coupon_id'] = $couponId;
                $couponPhysicalStoreData['physical_store_id'] = $item['physical_store_id'];
                $insertCouponPhysicalStoreData[] = $couponPhysicalStoreData;
            }

            foreach($couponTemplateGoodsList as $item){
                $couponGoodsData['id'] = IdGenerator::generate();
                $couponGoodsData['coupon_id'] = $couponId;
                $couponGoodsData['goods_id'] = $item['goods_id'];
                $insertCouponGoodsData[] = $couponGoodsData;
            }
        }

        Db::connection('jkc_edu')->beginTransaction();
        try{
            Db::connection('jkc_edu')->table('coupon')->insert($insertCouponData);
            if(!empty($insertCouponPhysicalStoreData)){
                Db::connection('jkc_edu')->table('coupon_physical_store')->insert($insertCouponPhysicalStoreData);
            }
            if(!empty($insertCouponGoodsData)){
                Db::connection('jkc_edu')->table('coupon_goods')->insert($insertCouponGoodsData);
            }
            if($couponTemplateInfo['totality']>=0){
                $couponTemplateAffected = Db::connection('jkc_edu')->update("UPDATE coupon_template SET issued_quantity=issued_quantity + ? WHERE id = ? AND totality >= issued_quantity+?", [$issuedQuantity,$couponTemplateId,$issuedQuantity]);
                if(!$couponTemplateAffected){
                    Db::connection('jkc_edu')->rollBack();
                    Log::get()->info("issuedCouponBatch:优惠券批量发行失败");
                    return ['code' => ErrorCode::FAILURE, 'msg' => '优惠券发行失败', 'data' => null];
                }
            }else{
                Db::connection('jkc_edu')->update("UPDATE coupon_template SET issued_quantity=issued_quantity + ? WHERE id = ?", [$issuedQuantity,$couponTemplateId]);
            }
            Db::connection('jkc_edu')->commit();
        } catch(\Throwable $e){
            Db::connection('jkc_edu')->rollBack();
            throw new \Exception($e->getMessage(), 1);
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 优惠券发放列表
     * @param array $params
     * @return array
     */
    public function issuedCouponList(array $params): array
    {
        $couponTemplateId = $params['id'];
        $offset = $this->offset;
        $limit = $this->limit;

        $couponList = Coupon::query()
            ->leftJoin('member','coupon.member_id','=','member.id')
            ->select(['member.mobile','member.name as member_name','coupon.created_at','coupon.is_used'])
            ->where(['coupon.coupon_template_id'=>$couponTemplateId])
            ->offset($offset)->limit($limit)
            ->get();
        $couponList = $couponList->toArray();
        $count = Coupon::query()->where(['coupon_template_id'=>$couponTemplateId])->count();
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => ['list'=>$couponList,'count'=>$count]];
    }
}