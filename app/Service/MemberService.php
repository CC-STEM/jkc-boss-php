<?php

declare(strict_types=1);

namespace App\Service;

use App\Constants\VipCardConstant;
use App\Model\MemberBelongTo;
use App\Model\MemberTag;
use App\Model\OrderGoods;
use App\Model\CourseOfflineOrder;
use App\Model\VipCardOrder;
use App\Model\Member;
use App\Constants\ErrorCode;
use App\Model\VipCardOrderPhysicalStore;
use App\Snowflake\IdGenerator;
use Hyperf\Database\Query\JoinClause;
use Hyperf\DbConnection\Db;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MemberService extends BaseService
{
    /**
     * 会员列表
     * @param array $params
     * @return array
     */
    public function memberList(array $params): array
    {
        $mobile = $params['mobile'];
        $name = $params['name'];
        $registerType = $params['register_type'];
        $isBuyVipCard = $params['is_buy_vip_card'];
        $isBuyNewGift = $params['is_buy_new_gift'];
        $memberAttribute = $params['member_attribute'];
        $offset = $this->offset;
        $limit = $this->limit;

        $model = Member::query();
        if($mobile !== null){
            $model->where('member.mobile', $mobile);
        }
        if($name !== null){
            $model->where([['member.name', 'like', "%{$name}%"]]);
        }
        if ($registerType == 1) {
            $model->whereIn('member.register_type', [1, 3]);
        } else if ($registerType == 2) {
            $model->where('member.register_type', 2);
        }
        if ($isBuyVipCard !== null) {
            $buyVipCardFunction = function (JoinClause $join) {
                $join->on('member.id', '=', 'buy_vip_card.member_id')
                    ->where('buy_vip_card.order_type', '=', 1)
                    ->where('buy_vip_card.pay_status', '=', 1)
                    ->whereIn('buy_vip_card.order_status', [0, 3])
                    ->where('buy_vip_card.pay_status', '=', 1);
            };

            if ($isBuyVipCard == 0) {
                $model->leftJoin('vip_card_order AS buy_vip_card', $buyVipCardFunction)->whereNull('buy_vip_card.id');
            } else if ($isBuyVipCard == 1) {
                $model->join('vip_card_order AS buy_vip_card', $buyVipCardFunction);
            }
        }
        if ($isBuyNewGift !== null) {
            $newGiftFunction = function (JoinClause $join) {
                $join->on('member.id', '=', 'new_gift.member_id')
                    ->where('new_gift.order_type', '=', 2)
                    ->where('new_gift.pay_status', '=', 1)
                    ->whereIn('new_gift.order_status', [0, 3])
                    ->where('new_gift.pay_status', '=', 1);
            };

            if ($isBuyNewGift == 0) {
                $model->leftJoin('vip_card_order AS new_gift', $newGiftFunction)->whereNull('new_gift.id');
            } else if ($isBuyNewGift == 1) {
                $model->join('vip_card_order AS new_gift', $newGiftFunction);
            }
        }
        $count = $model->count(Db::connection('jkc_edu')->raw('DISTINCT member.id'));
        $memberList = $model
            ->select(['member.id','member.name','member.mobile','member.created_at','member.parent_id','member.parent_mobile','member.school','member.channel'])
            ->groupBy('member.id')
            ->offset($offset)->limit($limit)
            ->get();
        $memberList = $memberList->toArray();

        foreach($memberList as $key=>$value){
            $memberId = $value['id'];

            $vipCardOrderList = VipCardOrder::query()->select(['course1','course2','course3','course1_used','course2_used','course3_used'])->where(['member_id'=>$memberId,'pay_status'=>1])->get();
            $course1Sum = 0;
            $course1UsedSum = 0;
            $course2Sum = 0;
            $course2UsedSum = 0;
            $course3Sum = 0;
            $course3UsedSum = 0;
            foreach($vipCardOrderList as $item){
                $course1Sum += $item['course1'];
                $course1UsedSum += $item['course1_used'];
                $course2Sum += $item['course2'];
                $course2UsedSum += $item['course2_used'];
                $course3Sum += $item['course3'];
                $course3UsedSum += $item['course3_used'];
            }

            $parentMemberInfo = [];
            if($value['parent_id'] != 0){
                $parentMemberInfo = Member::query()->select(['name','mobile','created_at'])->where(['id'=>$value['parent_id']])->first();
                if(!empty($parentMemberInfo)){
                    $parentMemberInfo = $parentMemberInfo->toArray();
                }
            }else{
                $memberList[$key]['parent_id'] = $memberId;
                $parentMemberInfo = ['name'=>$value['name'],'mobile'=>$value['mobile'],'created_at'=>$value['created_at']];
            }

            // 会员属性文案
            $attribute = '非会员';

            $memberList[$key]['course1'] = ['num1'=>$course1Sum,'num2'=>$course1UsedSum];
            $memberList[$key]['course2'] = ['num1'=>$course2Sum,'num2'=>$course2UsedSum];
            $memberList[$key]['course3'] = ['num1'=>$course3Sum,'num2'=>$course3UsedSum];
            $memberList[$key]['parent_name'] = $parentMemberInfo['name'] ?? '';
            $memberList[$key]['parent_mobile'] = $parentMemberInfo['mobile'] ?? '';
            $memberList[$key]['parent_created_at'] = $parentMemberInfo['created_at'] ?? '';
            $memberList[$key]['attribute'] = $attribute;
        }

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => ['list'=>$memberList,'count'=>$count]];
    }

    /**
     * 会员详情
     * @param int $id
     * @return array
     */
    public function memberDetail(int $id): array
    {
        $nowDate = date('Y-m-d H:i:s');
        $memberInfo = Member::query()
            ->select(['name','avatar','mobile','age','created_at','birthday','parent_mobile','school','channel'])
            ->where(['id'=>$id])
            ->first();
        if(empty($memberInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '', 'data' => null];
        }
        $memberInfo = $memberInfo->toArray();

        $memberTagList = MemberTag::query()
            ->select(['id','name'])
            ->where(['member_id'=>$id])
            ->get();
        $memberTagList = $memberTagList->toArray();

        //会员卡信息
        $vipCardOrderList = VipCardOrder::query()
            ->select(['id','course1','course1_used','course2','course2_used','course3','course3_used','expire_at','order_status'])
            ->where(['member_id'=>$id,'pay_status'=>1])
            ->whereIn('order_type',[1,4])
            ->get();
        $vipCardOrderList = $vipCardOrderList->toArray();
        $totalCourse1 = 0;
        $totalUsedCourse1 = 0;
        $totalCourse2 = 0;
        $totalUsedCourse2 = 0;
        $totalCourse3 = 0;
        $totalUsedCourse3 = 0;
        foreach($vipCardOrderList as $value){
            $surplusSectionCourse1 = 0;
            $surplusSectionCourse2 = 0;
            $surplusSectionCourse3 = 0;
            if($value['expire_at'] > $nowDate && $value['order_status'] == 0){
                $surplusSectionCourse1 = $value['course1']-$value['course1_used'];
                $surplusSectionCourse2 = $value['course2']-$value['course2_used'];
                $surplusSectionCourse3 = $value['course3']-$value['course3_used'];
            }
            $totalUsedCourse1 += $value['course1_used'];
            $totalUsedCourse2 += $value['course2_used'];
            $totalUsedCourse3 += $value['course3_used'];
            $totalCourse1 += $surplusSectionCourse1;
            $totalCourse2 += $surplusSectionCourse2;
            $totalCourse3 += $surplusSectionCourse3;
        }
        $memberInfo['course1'] = $totalCourse1;
        $memberInfo['course1_used'] = $totalUsedCourse1;
        $memberInfo['course2'] = $totalCourse2;
        $memberInfo['course2_used'] = $totalUsedCourse2;
        $memberInfo['course3'] = $totalCourse3;
        $memberInfo['course3_used'] = $totalUsedCourse3;
        $memberInfo['member_tag'] = $memberTagList;
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $memberInfo];
    }

    /**
     * 线上课程收藏列表
     * @param array $params
     * @return array
     */
    public function courseOnlineCollectList(array $params): array
    {
        $memberId = $params['member_id'];
        $offset = $this->offset;
        $limit = $this->limit;

        $courseOnlineCollectList = Db::connection('jkc_edu')->table('course_online_collect')
            ->leftJoin('course_online', 'course_online_collect.course_online_id', '=', 'course_online.id')
            ->select(['course_online_collect.id','course_online_collect.total_section','course_online_collect.study_section','course_online_collect.created_at','course_online.name','course_online.id as course_id','course_online.suit_age_min','course_online.suit_age_max'])
            ->where(['course_online_collect.member_id'=>$memberId])
            ->offset($offset)->limit($limit)
            ->get();
        $courseOnlineCollectList = $courseOnlineCollectList->toArray();
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $courseOnlineCollectList];
    }

    /**
     * 线上子课程收藏列表
     * @param array $params
     * @return array
     */
    public function courseOnlineChildCollectList(array $params): array
    {
        $courseOnlineCollectId = $params['course_online_collect_id'];

        $courseOnlineChildCollectList = Db::connection('jkc_edu')->table('course_online_child_collect')
            ->leftJoin('course_online_child', 'course_online_child_collect.course_online_child_id', '=', 'course_online_child.id')
            ->select(['course_online_child.name','course_online_child.id as course_child_id','course_online_child_collect.study_video_url','course_online_child_collect.study_at','course_online_child_collect.examine_at'])
            ->where(['course_online_child_collect.course_online_collect_id'=>$courseOnlineCollectId])
            ->get();
        $courseOnlineChildCollectList = $courseOnlineChildCollectList->toArray();
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $courseOnlineChildCollectList];
    }

    /**
     * 线下课程订单列表
     * @param array $params
     * @return array
     */
    public function courseOfflineOrderList(array $params): array
    {
        $classStatus = $params['class_status'];
        $memberId = $params['member_id'];
        $offset = $this->offset;
        $limit = $this->limit;
        $nowDate = date('Y-m-d H:i:');

        if($classStatus == 0){
            $courseOfflineOrderList = CourseOfflineOrder::query()
                ->leftJoin('course_offline','course_offline_order.course_offline_id','=','course_offline.id')
                ->select(['course_offline_order.course_name','course_offline_order.course_offline_id','course_offline_order.course_type','course_offline_order.start_at','course_offline_order.end_at','course_offline_order.physical_store_name','course_offline_order.classroom_name','course_offline_order.teacher_name','course_offline_order.class_status','course_offline.video_url'])
                ->where([['course_offline_order.member_id','=',$memberId],['course_offline_order.end_at','>',$nowDate],['course_offline_order.order_status','=',0]])
                //->offset($offset)->limit($limit)
                ->orderBy('course_offline_order.start_at')
                ->get();
        }else{
            $courseOfflineOrderList = CourseOfflineOrder::query()
                ->leftJoin('course_offline','course_offline_order.course_offline_id','=','course_offline.id')
                ->select(['course_offline_order.course_name','course_offline_order.course_offline_id','course_offline_order.course_type','course_offline_order.start_at','course_offline_order.end_at','course_offline_order.physical_store_name','course_offline_order.classroom_name','course_offline_order.teacher_name','course_offline_order.class_status','course_offline.video_url'])
                ->where([['course_offline_order.member_id','=',$memberId],['course_offline_order.end_at','<=',$nowDate],['course_offline_order.order_status','=',0]])
                //->offset($offset)->limit($limit)
                ->orderBy('course_offline_order.start_at','desc')
                ->get();
        }
        $courseOfflineOrderList = $courseOfflineOrderList->toArray();

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $courseOfflineOrderList];
    }

    /**
     * 教具订单列表
     * @param array $params
     * @return array
     */
    public function teachingAidsOrderList(array $params): array
    {
        $memberId = $params['member_id'];
        $offset = $this->offset;
        $limit = $this->limit;

        $orderGoodsList = OrderGoods::query()
            ->leftJoin('order_info', 'order_goods.order_info_id', '=', 'order_info.id')
            ->leftJoin('order_refund', 'order_goods.id', '=', 'order_refund.order_goods_id')
            ->select(['order_info.order_no','order_goods.goods_id','order_goods.goods_name','order_goods.goods_img','order_goods.prop_value_str','order_goods.quantity','order_goods.pay_price','order_goods.order_status','order_goods.shipping_status','order_refund.status as refund_status'])
            ->where(['order_goods.member_id'=>$memberId,'order_goods.pay_status'=>1])
            ->offset($offset)->limit($limit)
            ->orderBy('order_goods.id','desc')
            ->get();
        $orderGoodsList = $orderGoodsList->toArray();
        foreach($orderGoodsList as $key=>$value){

            //待发货
            $status = 1;
            if(!empty($value['refund_status']) && in_array($value['refund_status'],[10,15,20])){
                //售后中
                $status = 4;
            }else if($value['order_status'] == 0 && $value['shipping_status'] == 1){
                //待完成
                $status = 2;
            }else if($value['order_status'] == 0 && $value['shipping_status'] == 2){
                //已完成
                $status = 3;
            }else if($value['order_status'] != 0){
                //已关闭
                $status = 5;
            }
            $orderGoodsList[$key]['order_status'] = $status;
        }

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $orderGoodsList];
    }

    /**
     * 会员卡订单列表
     * @param array $params
     * @return array
     */
    public function vipCardOrderList(array $params): array
    {
        $memberId = $params['member_id'];
        $offset = $this->offset;
        $limit = $this->limit;

        $model = VipCardOrder::query()
            ->select(['order_title','price','expire','course1','course2','course3','created_at','expire_at','grade','course1_used','course2_used','course3_used','card_type'])
            ->where(['member_id'=>$memberId,'pay_status'=>1])
            ->whereIn('order_type',[1,4]);
        $count = $model->count();
        $vipCardOrderList = $model->orderBy('vip_card_order.id','desc')->get();
        $vipCardOrderList = $vipCardOrderList->toArray();
        foreach($vipCardOrderList as $key=>$value){
            $surplusCourse1 = $value['course1']-$value['course1_used'];
            $surplusCourse2 = $value['course2']-$value['course2_used'];
            $surplusCourse3 = $value['course3']-$value['course3_used'];
            $vipCardOrderList[$key]['surplus_course1'] = $surplusCourse1;
            $vipCardOrderList[$key]['surplus_course2'] = $surplusCourse2;
            $vipCardOrderList[$key]['surplus_course3'] = $surplusCourse3;
        }

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => ['list'=>$vipCardOrderList,'count'=>$count]];
    }

    /**
     * 创建会员卡订单
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Throwable
     */
    public function vipCardOrderCreate(array $params): array
    {
        $expire = $params['expire'];
        $cardThemeType = $params['card_theme_type'] ?? 1;
        $applicableStore = $params['applicable_store'] ?? [];
        $course1 = $params['course1'] ?? 0;
        $course2 = $params['course2'] ?? 0;
        $course3 = $params['course3'] ?? 0;
        $currencyCourse = $params['currency_course'] ?? 0;

        $orderId = IdGenerator::generate();
        $applicableStoreType = 1;
        $insertVipCardOrderPhysicalStoreData = [];
        if(!empty($applicableStore)){
            $applicableStoreType = 2;
            foreach($applicableStore as $value){
                $vipCardOrderPhysicalStoreData = [];
                $vipCardOrderPhysicalStoreData['id'] = IdGenerator::generate();
                $vipCardOrderPhysicalStoreData['vip_card_order_id'] = $orderId;
                $vipCardOrderPhysicalStoreData['physical_store_id'] = $value;
                $insertVipCardOrderPhysicalStoreData[] = $vipCardOrderPhysicalStoreData;
            }
        }
        //课单价
        $totalCourse = $course1+$course2+$course3+$currencyCourse;
        $courseUnitPrice = bcdiv((string)$params['price'],(string)$totalCourse,2);
        //推荐人信息
        $recommendPhysicalStoreId = 0;
        $memberBelongToInfo = MemberBelongTo::query()
            ->select(['physical_store_id'])
            ->where([['member_id','=',$params['member_id']]])
            ->first();
        $memberBelongToInfo = $memberBelongToInfo?->toArray();
        if(!empty($memberBelongToInfo)){
            $recommendPhysicalStoreId = $memberBelongToInfo['physical_store_id'];
        }
        $vipCardOrderCount = VipCardOrder::query()
            ->where(['member_id'=>$params['member_id'],'pay_status'=>1])
            ->whereIn('order_type',[1,2,4])
            ->count();
        $orderCounter = $vipCardOrderCount+1;

        //订单数据
        $orderNo = $this->functions->orderNo();
        $insertOrder['id'] = $orderId;
        $insertOrder['member_id'] = $params['member_id'];
        $insertOrder['order_no'] = $orderNo;
        $insertOrder['price'] = $params['price'];
        $insertOrder['order_title'] = $params['name'];
        $insertOrder['expire'] = $expire;
        $insertOrder['expire_at'] = VipCardConstant::DEFAULT_EXPIRE_AT;
        $insertOrder['course1'] = $course1;
        $insertOrder['course2'] = $course2;
        $insertOrder['course3'] = $course3;
        $insertOrder['currency_course'] = $currencyCourse;
        $insertOrder['order_type'] = 4;
        $insertOrder['pay_status'] = 1;
        $insertOrder['card_theme_type'] = $cardThemeType;
        $insertOrder['applicable_store_type'] = $applicableStoreType;
        $insertOrder['course_unit_price'] = $courseUnitPrice;
        $insertOrder['recommend_physical_store_id'] = $recommendPhysicalStoreId;
        //$insertOrder['order_counter'] = $orderCounter;

        Db::connection('jkc_edu')->transaction(function()use($insertOrder,$insertVipCardOrderPhysicalStoreData){
            VipCardOrder::query()->insert($insertOrder);
            if(!empty($insertVipCardOrderPhysicalStoreData)){
                VipCardOrderPhysicalStore::query()->insert($insertVipCardOrderPhysicalStoreData);
            }
        });
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 邀请关系树
     * @param int $id
     * @return array
     */
    public function invitationRelationTree(int $id): array
    {
        $memberInfo = Member::query()
            ->select(['parent_id'])
            ->where(['id'=>$id])
            ->first();
        $memberInfo = $memberInfo->toArray();
        $parentId = $memberInfo['parent_id'];

        //上级用户信息
        if($parentId != 0){
            $parentMemberInfo = Member::query()
                ->select(['name','mobile','created_at'])
                ->where(['id'=>$parentId])
                ->first();
            if(!empty($parentMemberInfo)){
                $parentMemberInfo = $parentMemberInfo->toArray();
                $parentMemberInfo['created_at'] = date('Y-m-d',strtotime($parentMemberInfo['created_at']));
            }
        }

        //下级用户信息
        $childMemberList = Member::query()
            ->select(['name','mobile','created_at'])
            ->where(['parent_id'=>$id])
            ->orderBy('created_at')
            ->get();
        $childMemberList = $childMemberList->toArray();
        foreach($childMemberList as $key=>$value){
            $childMemberList[$key]['created_at'] = date('Y-m-d',strtotime($value['created_at']));
        }
        $returnData = [
            'parent' => $parentMemberInfo ?? [],
            'child' => $childMemberList
        ];
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $returnData];
    }

    /**
     * 编辑会员名称
     * @param array $params
     * @return array
     */
    public function editMemberName(array $params): array
    {
        $id = $params['id'];
        $name = $params['name'];

        if(empty($name)){
            return ['code' => ErrorCode::WARNING, 'msg' => '名称不能为空', 'data' => null];
        }
        Member::query()->where(['id'=>$id])->update(['name'=>$name]);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 会员搜索列表
     * @param array $params
     * @return array
     */
    public function memberSearchList(array $params): array
    {
        $mobile = $params['mobile'] ?? '';
        $name = $params['name'] ?? '';

        $where = [];
        if(!empty($mobile)){
            $where[] = ['mobile', '=', $mobile];
        }
        if(!empty($name)){
            $where[] = ['name', 'like', "%{$name}%"];
        }
        $memberList = Member::query()
            ->select(['id', 'name', 'mobile'])
            ->where($where)
            ->get();
        $memberList = $memberList->toArray();

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $memberList];
    }

    /**
     * 新建虚拟用户
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Throwable
     */
    public function addVirtualMember(array $params): array
    {
        $mobileArray = $params['mobile'];

        if (empty($mobileArray) || !is_array($mobileArray)) {
            return ['code' => ErrorCode::WARNING, 'msg' => '请输入手机号', 'data' => null];
        }
        if (count($mobileArray) > 100) {
            return ['code' => ErrorCode::WARNING, 'msg' => '最多输入100个手机号', 'data' => null];
        }

        $repeatMemberList = Member::query()->whereIn('mobile', $mobileArray)->get(['mobile'])->toArray();
        if (!empty($repeatMemberList)) {
            $repeatStr = implode(',', array_column($repeatMemberList, 'mobile'));
            return ['code' => ErrorCode::WARNING, 'msg' => "{$repeatStr}手机号已存在， 请勿重复添加", 'data' => null];
        }

        $batchInsertMember = [];
        foreach ($mobileArray as $index => $_mobile) {
            $batchInsertMember[] = [
                'id' => IdGenerator::generate(),
                'mobile' => $_mobile,
                'name' => '虚拟用户',
                'register_type' => 2,
            ];
        }

        Member::insert($batchInsertMember);

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 会员导出
     * @param array $params
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function memberExport(array $params): array
    {
        $this->offset = 0;
        $this->limit = 50000;
        $listResult = $this->memberList($params);
        $memberList = $listResult['data']['list'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', '用户名称')
            ->setCellValue('B1', '手机号')
            ->setCellValue('C1', '常规课次数')
            ->setCellValue('D1', '活动课次数')
            ->setCellValue('E1', '专业课套数')
            ->setCellValue('F1', '注册时间')
            ->setCellValue('G1', '会员属性');
        $i=2;
        foreach($memberList as $item){
            $sheet->setCellValue('A'.$i, $item['name'])
                ->setCellValue('B'.$i, $item['mobile'])
                ->setCellValue('C'.$i, "{$item['course1']['num1']}（{$item['course1']['num2']}）")
                ->setCellValue('D'.$i, "{$item['course2']['num1']}（{$item['course1']['num2']}）")
                ->setCellValue('E'.$i, "{$item['course3']['num1']}（{$item['course1']['num2']}）")
                ->setCellValue('F'.$i, $item['created_at'])
                ->setCellValue('G'.$i, $item['attribute']);
            $i++;
        }

        $fileName = 'member_' . date('YmdHis');

        $writer = new Xlsx($spreadsheet);
        $localPath = "/tmp/{$fileName}.xlsx";
        $writer->save($localPath);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => ['path'=>$localPath]];
    }


}