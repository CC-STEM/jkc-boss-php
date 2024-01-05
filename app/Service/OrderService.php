<?php

declare(strict_types=1);

namespace App\Service;

use App\Event\GoodsRefundRegistered;
use App\Model\OrderInfo;
use App\Model\OrderGoods;
use App\Model\OrderRefund;
use App\Model\OrderPackage;
use App\Model\PayApply;
use App\Model\Region;
use App\Model\Teacher;
use App\Snowflake\IdGenerator;
use App\Constants\ErrorCode;
use App\Lib\WeChat\WeChatPayFactory;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\EventDispatcher\EventDispatcherInterface;

class OrderService extends BaseService
{
    #[Inject]
    private EventDispatcherInterface $eventDispatcher;

    /**
     * 教具订单延长收货
     * @param array $params
     * @return array
     */
    public function teachingAidsOrderExtendReceipt(array $params): array
    {
        $orderGoodsId = $params['id'];
        $extendDays = $params['extend_days'];

        OrderGoods::query()->where(['id'=>$orderGoodsId])->update(['extend_days'=>$extendDays]);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 教具订单发货
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function teachingAidsOrderShipment(array $params): array
    {
        $date = date('Y-m-d H:i:s');
        $orderGoodsId = $params['id'];
        $logisName = $params['logis_name'];
        $expressNumber = $params['express_number'];

        $orderGoodsAffected = OrderGoods::query()->where(['id'=>$orderGoodsId,'pay_status'=>1,'order_status'=>0,'shipping_status'=>0])->update(['shipping_status'=>1,'shipment_at'=>$date]);
        if($orderGoodsAffected){
            $insertOrderPackageData['id'] = IdGenerator::generate();
            $insertOrderPackageData['order_goods_id'] = $orderGoodsId;
            $insertOrderPackageData['logis_name'] = $logisName;
            $insertOrderPackageData['express_number'] = $expressNumber;
            OrderPackage::query()->insert($insertOrderPackageData);
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 教具订单列表
     * @param array $params
     * @return array
     */
    public function teachingAidsOrderList(array $params): array
    {
        $orderNo = $params['order_no'];
        $goodsName = $params['goods_name'];
        $goodsId = $params['goods_id'];
        $mobile = $params['mobile'];
        $searchStatus = $params['status'];
        $payAtMin = $params['pay_at_min'];
        $payAtMax = $params['pay_at_max'];
        $provinceName = $params['province_name'];
        $cityName = $params['city_name'];
        $districtName = $params['district_name'];
        $memberName = $params['member_name'];
        $offset = $this->offset;
        $limit = $this->limit;

        $model = OrderGoods::query()
            ->leftJoin('order_info', 'order_goods.order_info_id', '=', 'order_info.id')
            ->leftJoin('member', 'order_goods.member_id', '=', 'member.id')
            ->leftJoin('order_refund', 'order_goods.id', '=', 'order_refund.order_goods_id');
        $where[] = ['order_goods.pay_status','=',1];
        if($orderNo !== null){
            $where[] = ['order_info.order_no','=',$orderNo];
        }
        if($goodsName !== null){
            $where[] = ['order_goods.goods_name','=',$goodsName];
        }
        if($goodsId !== null){
            $where[] = ['order_goods.goods_id','=',$goodsId];
        }
        if($mobile !== null){
            $where[] = ['order_info.mobile','=',$mobile];
        }
        if($payAtMin !== null && $payAtMax !== null){
            $model->whereBetween('order_goods.pay_at',[$payAtMin,$payAtMax]);
        }
        if($provinceName !== null){
            $regionInfo = Region::query()->select(['name'])->where(['id'=>$provinceName])->first();
            $regionInfo = $regionInfo->toArray();
            $provinceName = $regionInfo['name'];
            $where[] = ['order_info.province_name','=',$provinceName];
        }
        if($cityName !== null){
            $regionInfo = Region::query()->select(['name'])->where(['id'=>$cityName])->first();
            $regionInfo = $regionInfo->toArray();
            $cityName = $regionInfo['name'];
            $where[] = ['order_info.city_name','=',$cityName];
        }
        if($districtName !== null){
            $regionInfo = Region::query()->select(['name'])->where(['id'=>$districtName])->first();
            $regionInfo = $regionInfo->toArray();
            $districtName = $regionInfo['name'];
            $where[] = ['order_info.district_name','=',$districtName];
        }
        if($memberName !== null){
            $where[] = ['member.name','like',"%{$memberName}%"];
        }
        if($searchStatus !== null){
            if($searchStatus == 1){
                //待发货
                $where[] = ['order_goods.order_status','=',0];
                $where[] = ['order_goods.shipping_status','=',0];
                $model->whereNotIn('order_refund.status',[10,15,20,24]);
            }else if($searchStatus == 2){
                //待完成
                $where[] = ['order_goods.order_status','=',0];
                $where[] = ['order_goods.shipping_status','=',1];
                $model->whereNotIn('order_refund.status',[10,15,20,24]);
            }else if($searchStatus == 3){
                //已完成
                $where[] = ['order_goods.order_status','=',0];
                $where[] = ['order_goods.shipping_status','=',2];
                $model->whereNotIn('order_refund.status',[10,15,20,24]);
            }else if($searchStatus == 4){
                //售后中
                $model->whereIn('order_refund.status',[10,15,20,24]);
                $where[] = ['order_goods.order_status','=',0];
            }else if($searchStatus == 5){
                //已关闭
                $where[] = ['order_goods.order_status','=',3];
            }
        }
        $count = $model->where($where)->count('order_goods.id');
        $orderGoodsList = $model
            ->select(['order_info.order_no','order_info.province_name','order_info.city_name','order_info.district_name','order_info.address','order_info.recommend_teacher_id','order_goods.id','order_goods.goods_name','order_goods.goods_id','order_goods.goods_img','order_goods.prop_value_str','order_goods.quantity','order_goods.pay_price','order_goods.order_status','order_goods.shipping_status','order_goods.extend_days','order_goods.shipment_at','order_goods.commission_rate','order_refund.status as refund_status','member.name as member_name','member.mobile as member_mobile'])
            ->where($where)
            ->orderBy('order_goods.id','desc')
            ->offset($offset)->limit($limit)
            ->get();
        $orderGoodsList = $orderGoodsList->toArray();

        foreach($orderGoodsList as $key=>$value){
            $parentMobile = '无';
            $commission = '无';
            if(!empty($value['recommend_teacher_id'])){
                $parentMemberInfo = Teacher::query()->select(['mobile'])->where(['id'=>$value['recommend_teacher_id']])->first();
                $parentMemberInfo = $parentMemberInfo->toArray();
                $parentMobile = $parentMemberInfo['mobile'];
            }
            //待发货
            $status = 1;
            if(!empty($value['refund_status']) && in_array($value['refund_status'],[10,15,20,24])){
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
            $finishedAt = '';
            if($value['shipping_status'] == 1){
                $finishedAt = date('Y-m-d H:i:s',strtotime($value['shipment_at'])+(3600*24*(14+$value['extend_days'])));
            }
            $totalAmount = bcmul($value['pay_price'],(string)$value['quantity'],2);
            if($value['recommend_teacher_id'] != 0){
                $commissionRate = bcdiv($value['commission_rate'],'100',4);
                $commission = bcmul($totalAmount,$commissionRate,2);
            }
            unset($orderGoodsList[$key]['refund_status']);
            unset($orderGoodsList[$key]['shipping_status']);
            unset($orderGoodsList[$key]['order_status']);

            $orderGoodsList[$key]['pay_price'] = $totalAmount;
            $orderGoodsList[$key]['commission'] = $commission;
            $orderGoodsList[$key]['parent_mobile'] = $parentMobile;
            $orderGoodsList[$key]['status'] = $status;
            $orderGoodsList[$key]['finished_at'] = $finishedAt;
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => ['list'=>$orderGoodsList,'count'=>$count]];
    }

    /**
     * 教具订单详情
     * @param int $id
     * @return array
     */
    public function teachingAidsOrderDetail(int $id): array
    {
        $orderGoodsInfo = OrderGoods::query()
            ->select(['order_info_id','goods_name','goods_img','quantity','pay_price','prop_value_str','order_status','shipping_status','pay_at','shipment_at','receipt_at','closed_at','price'])
            ->where(['id'=>$id])
            ->first();
        if(empty($orderGoodsInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '订单信息错误', 'data' => null];
        }
        $orderGoodsInfo = $orderGoodsInfo->toArray();
        $orderInfoId = $orderGoodsInfo['order_info_id'];

        $orderInfo = OrderInfo::query()
            ->select(['order_no','consignee','mobile','province_name','city_name','district_name','address','member_remark'])
            ->where(['id'=>$orderInfoId])
            ->first();
        $orderInfo = $orderInfo->toArray();

        $orderPackageList = OrderPackage::query()->select(['logis_name','express_number'])->where(['resource_id'=>$id,'type'=>1])->get();
        $orderPackageList = $orderPackageList->toArray();

        $refundAt = '';
        $orderRefundInfo = OrderRefund::query()->select(['created_at'])->where(['order_goods_id'=>$id])->whereIn('status',[10,15,20,24])->first();
        if(!empty($orderRefundInfo)){
            $orderRefundInfo = $orderRefundInfo->toArray();
            $refundAt = $orderRefundInfo['created_at'];
        }

        //待发货
        $orderStatus = 1;
        if(!empty($orderRefundInfo)){
            //售后中
            $orderStatus = 4;
        }else if($orderGoodsInfo['order_status'] != 0){
            //已关闭
            $orderStatus = 5;
        }else if($orderGoodsInfo['order_status'] == 0 && $orderGoodsInfo['shipping_status'] == 1){
            //待完成
            $orderStatus = 2;
        }else if($orderGoodsInfo['order_status'] == 0 && $orderGoodsInfo['shipping_status'] == 2){
            //已完成
            $orderStatus = 3;
        }
        $returnData['refund_at'] = $refundAt;
        $returnData['closed_at'] = $orderGoodsInfo['closed_at'];
        $returnData['pay_at'] = $orderGoodsInfo['pay_at'];
        $returnData['shipment_at'] = $orderGoodsInfo['shipment_at'];
        $returnData['receipt_at'] = $orderGoodsInfo['receipt_at'];
        $returnData['order_no'] = $orderInfo['order_no'];
        $returnData['member_remark'] = $orderInfo['member_remark'];
        $returnData['status'] = $orderStatus;
        $goods = [
            'goods_name' => $orderGoodsInfo['goods_name'],
            'goods_img' => $orderGoodsInfo['goods_img'],
            'quantity' => $orderGoodsInfo['quantity'],
            'pay_price' => $orderGoodsInfo['price'],
            'prop_value_str' => $orderGoodsInfo['prop_value_str'],
            'amount' => $orderGoodsInfo['pay_price']
        ];
        $returnData['goods'] = $goods;
        $address = [
            'consignee' => $orderInfo['consignee'],
            'mobile' => $orderInfo['mobile'],
            'province_name' => $orderInfo['province_name'],
            'city_name' => $orderInfo['city_name'],
            'district_name' => $orderInfo['district_name'],
            'address' => $orderInfo['address'],
        ];
        $returnData['consignee'] = $address;
        $returnData['package1'] = $orderPackageList;
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $returnData];
    }

    /**
     * 教具订单售后列表
     * @param array $params
     * @return array
     */
    public function teachingAidsRefundOrderList(array $params): array
    {
        $orderNo = $params['order_no'];
        $goodsName = $params['goods_name'];
        $goodsId = $params['goods_id'];
        $mobile = $params['mobile'];
        $status = $params['status'];
        $createdAtMin = $params['created_at_min'];
        $createdAtMax = $params['created_at_max'];
        $provinceName = $params['province_name'];
        $cityName = $params['city_name'];
        $districtName = $params['district_name'];
        $memberName = $params['member_name'];
        $offset = $this->offset;
        $limit = $this->limit;

        $model = OrderRefund::query()
            ->leftJoin('order_goods', 'order_refund.order_goods_id', '=', 'order_goods.id')
            ->leftJoin('member', 'order_refund.member_id', '=', 'member.id')
            ->leftJoin('order_info', 'order_goods.order_info_id', '=', 'order_info.id');
        $where = [];
        if($orderNo !== null){
            $where[] = ['order_info.order_no','=',$orderNo];
        }
        if($goodsName !== null){
            $where[] = ['order_goods.goods_name','=',$goodsName];
        }
        if($goodsId !== null){
            $where[] = ['order_goods.goods_id','=',$goodsId];
        }
        if($mobile !== null){
            $where[] = ['order_info.mobile','=',$mobile];
        }
        if($status !== null){
            if($status == 25){
                $model->whereIn('order_refund.status',[24,25]);
            }else{
                $where[] = ['order_refund.status','=',$status];
            }
        }
        if($createdAtMin !== null && $createdAtMax !== null){
            $model->whereBetween('order_refund.created_at',[$createdAtMin,$createdAtMax]);
        }
        if($provinceName !== null){
            $regionInfo = Region::query()->select(['name'])->where(['id'=>$provinceName])->first();
            $regionInfo = $regionInfo->toArray();
            $provinceName = $regionInfo['name'];
            $where[] = ['order_info.province_name','=',$provinceName];
        }
        if($cityName !== null){
            $regionInfo = Region::query()->select(['name'])->where(['id'=>$cityName])->first();
            $regionInfo = $regionInfo->toArray();
            $cityName = $regionInfo['name'];
            $where[] = ['order_info.city_name','=',$cityName];
        }
        if($districtName !== null){
            $regionInfo = Region::query()->select(['name'])->where(['id'=>$districtName])->first();
            $regionInfo = $regionInfo->toArray();
            $districtName = $regionInfo['name'];
            $where[] = ['order_info.district_name','=',$districtName];
        }
        if($memberName !== null){
            $where[] = ['member.name','like',"%{$memberName}%"];
        }
        $count = $model->where($where)->count();
        $orderRefundList = $model->select(['order_info.order_no','order_info.province_name','order_info.city_name','order_info.district_name','order_info.address','order_goods.goods_name','order_goods.prop_value_str','order_goods.quantity','order_goods.pay_price','order_refund.id','order_refund.status','order_refund.created_at','member.name as member_name','member.mobile as member_mobile'])
            ->where($where)
            ->orderBy('order_refund.id','desc')
            ->offset($offset)->limit($limit)
            ->get();
        $orderRefundList = $orderRefundList->toArray();

        foreach($orderRefundList as $key=>$value){
            $totalAmount = bcmul($value['pay_price'],(string)$value['quantity'],2);

            $orderRefundList[$key]['pay_price'] = $totalAmount;
            $orderRefundList[$key]['status'] = $value['status'] == 24 ? 25 : $value['status'];
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => ['list'=>$orderRefundList,'count'=>$count]];
    }

    /**
     * 教具订单售后详情
     * @param int $id
     * @return array
     */
    public function teachingAidsRefundOrderDetail(int $id): array
    {
        $orderRefundInfo = OrderRefund::query()->select(['order_goods_id','reason','memo','status','img_url','created_at'])->where(['id'=>$id])->first();
        if(empty($orderRefundInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '订单信息错误', 'data' => null];
        }
        $orderRefundInfo = $orderRefundInfo->toArray();
        $orderGoodsId = $orderRefundInfo['order_goods_id'];

        $orderGoodsInfo = OrderGoods::query()
            ->select(['order_info_id','goods_img','pay_price','quantity','shipment_at','goods_name','prop_value_str','closed_at','shipping_status','amount','price'])
            ->where(['id'=>$orderGoodsId])
            ->first();
        if(empty($orderGoodsInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '订单信息错误', 'data' => null];
        }
        $orderGoodsInfo = $orderGoodsInfo->toArray();
        $orderInfoId = $orderGoodsInfo['order_info_id'];

        $orderInfo = OrderInfo::query()->select(['order_no','consignee','mobile','province_name','city_name','district_name','address'])->where(['id'=>$orderInfoId])->first();
        if(empty($orderInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '订单信息错误', 'data' => null];
        }
        $orderInfo = $orderInfo->toArray();

        $orderPackage1List = OrderPackage::query()->select(['logis_name','express_number'])->where(['resource_id'=>$orderGoodsId,'type'=>1])->get();
        $orderPackage1List = $orderPackage1List->toArray();
        $orderPackage2List = OrderPackage::query()->select(['logis_name','express_number'])->where(['resource_id'=>$id,'type'=>2])->get();
        $orderPackage2List = $orderPackage2List->toArray();

        $totalAmount = $orderGoodsInfo['amount'];
        $returnData['order_no'] = $orderInfo['order_no'];
        $returnData['shipment_at'] = $orderGoodsInfo['shipment_at'];
        $returnData['created_at'] = $orderRefundInfo['created_at'];
        $returnData['closed_at'] = $orderGoodsInfo['closed_at'];
        $returnData['reason'] = $orderRefundInfo['reason'];
        $returnData['memo'] = $orderRefundInfo['memo'];
        $returnData['img_url'] = !empty($orderRefundInfo['img_url']) ? json_decode($orderRefundInfo['img_url'],true) : [];
        $returnData['status'] = $orderRefundInfo['status'];
        $goods = [
            'amount'=>$totalAmount,
            'goods_name' => $orderGoodsInfo['goods_name'],
            'goods_img' => $orderGoodsInfo['goods_img'],
            'quantity' => $orderGoodsInfo['quantity'],
            'pay_price' => $orderGoodsInfo['pay_price'],
            'prop_value_str' => $orderGoodsInfo['prop_value_str'],
            'price' => $orderGoodsInfo['price'],
        ];
        $returnData['goods'] = $goods;
        $address = [
            'consignee' => $orderInfo['consignee'],
            'mobile' => $orderInfo['mobile'],
            'province_name' => $orderInfo['province_name'],
            'city_name' => $orderInfo['city_name'],
            'district_name' => $orderInfo['district_name'],
            'address' => $orderInfo['address'],
        ];
        $returnData['consignee'] = $address;
        $returnData['package1'] = $orderPackage1List;
        $returnData['package2'] = $orderPackage2List;
        $returnData['shipping_status'] = $orderGoodsInfo['shipping_status'];
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $returnData];
    }

    /**
     * 教具订单售后处理
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function handleTeachingAidsRefundOrder(array $params): array
    {
        $id = $params['id'];
        $status = $params['status'];
        $date = date('Y-m-d H:i:s');

        if(!in_array($status,[15,20,25,30])){
            return ['code' => ErrorCode::WARNING, 'msg' => '参数错误', 'data' => null];
        }
        //售后订单信息
        $orderRefundInfo = OrderRefund::query()
            ->select(['order_goods_id','member_id','amount'])
            ->where(['id'=>$id])
            ->first();
        if(empty($orderRefundInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '订单信息错误', 'data' => null];
        }
        $orderRefundInfo = $orderRefundInfo->toArray();
        $orderGoodsId = $orderRefundInfo['order_goods_id'];
        //订单商品信息
        $orderGoodsInfo = OrderGoods::query()->select(['order_info_id','pay_price','quantity','shipping_status'])->where(['id'=>$orderGoodsId])->first();
        if(empty($orderGoodsInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '订单信息错误', 'data' => null];
        }
        $orderGoodsInfo = $orderGoodsInfo->toArray();
        $orderInfoId = $orderGoodsInfo['order_info_id'];

        if($status == 15 && $orderGoodsInfo['shipping_status'] == 1){
            OrderRefund::query()->where(['id'=>$id,'status'=>10])->update(['status'=>15,'operated_at'=>$date]);
            return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
        }else if($status == 20){
            OrderRefund::query()->where(['id'=>$id,'status'=>15])->update(['status'=>20,'operated_at'=>$date]);
            return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
        }else if($status == 30){
            $orderRefundAffected = OrderRefund::query()->where([['id','=',$id],['status','<>',30]])->update(['status'=>30,'operated_at'=>$date]);
            if($orderRefundAffected){
                OrderGoods::query()->where(['id'=>$orderGoodsId,'is_refund'=>1])->update(['is_refund'=>0]);
            }
            return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
        }

        //订单信息
        $orderInfo = OrderInfo::query()
            ->select(['order_no'])
            ->where(['id'=>$orderInfoId])
            ->first();
        if(empty($orderInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '支付信息缺失', 'data' => null];
        }
        $orderInfo = $orderInfo->toArray();

        //支付信息
        $payApplyInfo = PayApply::query()
            ->select(['out_trade_no','pay_code'])
            ->where(['order_no'=>$orderInfo['order_no'],'status'=>1,'order_type'=>3])
            ->first();
        if(empty($payApplyInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '支付信息缺失', 'data' => null];
        }
        $payApplyInfo = $payApplyInfo->toArray();
        $payCode = $payApplyInfo['pay_code'];

        //总金额
        $totalAmount = $orderRefundInfo['amount'];
        $outRefundNo = $this->functions->outTradeNo();

        //退款申请数据
        $insertRefundApplyData['id'] = IdGenerator::generate();
        $insertRefundApplyData['order_refund_id'] = $id;
        $insertRefundApplyData['out_refund_no'] = $outRefundNo;
        $insertRefundApplyData['pay_code'] = $payCode;
        $insertRefundApplyData['order_type'] = 3;

        Db::connection('jkc_edu')->beginTransaction();
        try{
            if($orderGoodsInfo['shipping_status'] == 0){
                $orderRefundAffected = Db::connection('jkc_edu')->table('order_refund')->where(['id'=>$id,'status'=>10])->update(['status'=>24,'operated_at'=>$date]);
            }else{
                $orderRefundAffected = Db::connection('jkc_edu')->table('order_refund')->where(['id'=>$id,'status'=>20])->update(['status'=>24,'operated_at'=>$date]);
            }
            if(!$orderRefundAffected){
                Db::connection('jkc_edu')->rollBack();
                return ['code' => ErrorCode::FAILURE, 'msg' => '售后订单操作异常', 'data' => null];
            }
            Db::connection('jkc_edu')->table('refund_apply')->insert($insertRefundApplyData);
            Db::connection('jkc_edu')->commit();
        } catch(\Throwable $e){
            Db::connection('jkc_edu')->rollBack();
            throw new \Exception($e->getMessage(), 1);
        }

        switch ($payCode){
            case 'WXPAY':
                $weChatPayFactory = new WeChatPayFactory();
                $weChatPayFactory->amount = ['total'=>(int)bcmul($totalAmount,"100"),'refund'=>(int)bcmul($totalAmount,"100"),'currency'=>'CNY'];
                $weChatPayFactory->timeExpire = date("c", strtotime("+15 minutes"));
                $weChatPayFactory->outRefundNo = $outRefundNo;
                $weChatPayFactory->outTradeNo = $payApplyInfo['out_trade_no'];
                $weChatPayFactory->notifyUrl = env('APP_DOMAIN').'/api/pay/callback/wx/goods_refund';
                $result = $weChatPayFactory->refunds();
                $returnData = $result['data'];
                break;
            case 'ZERO':
                $payService = new PayService();
                $result = $payService->goodsRefundCallback(['out_refund_no'=>$outRefundNo,'refund_status'=>'SUCCESS']);
                if($result['code'] === ErrorCode::FAILURE){
                    return ['code' => ErrorCode::WARNING, 'msg' => '退款失败', 'data' => null];
                }
                $returnData['body'] = 'zero';
                break;
            default:
                return ['code' => ErrorCode::WARNING, 'msg' => '退款失败:支付方式错误', 'data' => null];
        }
        $this->eventDispatcher->dispatch(new GoodsRefundRegistered((int)$orderRefundInfo['member_id'],(int)$id));
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $returnData];
    }

    /**
     * 教具订单导出
     * @param array $params
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function teachingAidsOrderExport(array $params): array
    {
        $orderNo = $params['order_no'];
        $goodsName = $params['goods_name'];
        $goodsId = $params['goods_id'];
        $mobile = $params['mobile'];
        $searchStatus = $params['status'];
        $payAtMin = $params['pay_at_min'];
        $payAtMax = $params['pay_at_max'];
        $provinceName = $params['province_name'];
        $cityName = $params['city_name'];
        $districtName = $params['district_name'];
        $fileName = 'go'.date('YmdHis');

        $model = OrderGoods::query()
            ->leftJoin('order_info', 'order_goods.order_info_id', '=', 'order_info.id')
            ->leftJoin('member', 'order_goods.member_id', '=', 'member.id')
            ->leftJoin('order_refund', 'order_goods.id', '=', 'order_refund.order_goods_id');
        $where[] = ['order_goods.pay_status','=',1];
        if($orderNo !== null){
            $where[] = ['order_info.order_no','=',$orderNo];
        }
        if($goodsName !== null){
            $where[] = ['order_goods.goods_name','=',$goodsName];
        }
        if($goodsId !== null){
            $where[] = ['order_goods.goods_id','=',$goodsId];
        }
        if($mobile !== null){
            $where[] = ['order_info.mobile','=',$mobile];
        }
        if($payAtMin !== null && $payAtMax !== null){
            $model->whereBetween('order_goods.pay_at',[$payAtMin,$payAtMax]);
        }
        if($provinceName !== null){
            $regionInfo = Region::query()->select(['name'])->where(['id'=>$provinceName])->first();
            $regionInfo = $regionInfo->toArray();
            $provinceName = $regionInfo['name'];
            $where[] = ['order_info.province_name','=',$provinceName];
        }
        if($cityName !== null){
            $regionInfo = Region::query()->select(['name'])->where(['id'=>$cityName])->first();
            $regionInfo = $regionInfo->toArray();
            $cityName = $regionInfo['name'];
            $where[] = ['order_info.city_name','=',$cityName];
        }
        if($districtName !== null){
            $regionInfo = Region::query()->select(['name'])->where(['id'=>$districtName])->first();
            $regionInfo = $regionInfo->toArray();
            $districtName = $regionInfo['name'];
            $where[] = ['order_info.district_name','=',$districtName];
        }
        if($searchStatus !== null){
            if($searchStatus == 1){
                //待发货
                $where[] = ['order_goods.order_status','=',0];
                $where[] = ['order_goods.shipping_status','=',0];
                $model->whereNotIn('order_refund.status',[10,15,20,24]);
            }else if($searchStatus == 2){
                //待完成
                $where[] = ['order_goods.order_status','=',0];
                $where[] = ['order_goods.shipping_status','=',1];
                $model->whereNotIn('order_refund.status',[10,15,20,24]);
            }else if($searchStatus == 3){
                //已完成
                $where[] = ['order_goods.order_status','=',0];
                $where[] = ['order_goods.shipping_status','=',2];
                $model->whereNotIn('order_refund.status',[10,15,20,24]);
            }else if($searchStatus == 4){
                //售后中
                $model->whereIn('order_refund.status',[10,15,20,24]);
                $where[] = ['order_goods.order_status','=',0];
            }else if($searchStatus == 5){
                //已关闭
                $where[] = ['order_goods.order_status','=',3];
            }
        }
        $orderGoodsList = $model
            ->select(['order_info.order_no','order_info.province_name','order_info.city_name','order_info.district_name','order_info.address','order_goods.goods_name','order_goods.goods_id','order_goods.prop_value_str','order_goods.quantity','order_goods.pay_price','order_goods.order_status','order_goods.shipping_status','order_refund.status as refund_status','member.name as member_name','member.mobile as member_mobile'])
            ->where($where)
            ->orderBy('order_goods.id','desc')
            ->get();
        $orderGoodsList = $orderGoodsList->toArray();
        foreach($orderGoodsList as $key=>$value){
            //待发货
            $status = '待发货';
            if(!empty($value['refund_status']) && in_array($value['refund_status'],[10,15,20,24])){
                //售后中
                $status = '售后中';
            }else if($value['order_status'] == 0 && $value['shipping_status'] == 1){
                //待完成
                $status = '待完成';
            }else if($value['order_status'] == 0 && $value['shipping_status'] == 2){
                //已完成
                $status = '已完成';
            }else if($value['order_status'] != 0){
                //已关闭
                $status = '已关闭';
            }
            $orderGoodsList[$key]['status'] = $status;
            $orderGoodsList[$key]['address'] = $value['province_name'].$value['city_name'].$value['district_name'].$value['address'];
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', '订单编号')
            ->setCellValue('B1', '商品名称')
            ->setCellValue('C1', '规格')
            ->setCellValue('D1', '数量')
            ->setCellValue('E1', '商品ID')
            ->setCellValue('F1', '订单状态')
            ->setCellValue('G1', '实付价格')
            ->setCellValue('H1', '注册用户名')
            ->setCellValue('I1', '注册手机号')
            ->setCellValue('J1', '收件地址');
        $i=2;
        foreach($orderGoodsList as $item){
            $sheet->setCellValueExplicit('A'.$i, $item['order_no'],DataType::TYPE_STRING)
                ->setCellValue('B'.$i, $item['goods_name'])
                ->setCellValue('C'.$i, $item['prop_value_str'])
                ->setCellValue('D'.$i, $item['quantity'])
                ->setCellValueExplicit('E'.$i, $item['goods_id'],DataType::TYPE_STRING)
                ->setCellValue('F'.$i, $item['status'])
                ->setCellValue('G'.$i, $item['pay_price'])
                ->setCellValue('H'.$i, $item['member_name'])
                ->setCellValueExplicit('I'.$i, $item['member_mobile'],DataType::TYPE_STRING)
                ->setCellValue('J'.$i, $item['address']);
            $i++;
        }

        $writer = new Xlsx($spreadsheet);
        $localPath = "/tmp/{$fileName}.xlsx";
        $writer->save($localPath);

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => ['path'=>$localPath]];
    }

    /**
     * 物流公司
     * @return array
     */
    public function logisticsList(): array
    {
        $logisticsList = [
            '顺丰快递',
            '圆通快递',
            '申通快递',
            '极兔快递',
            'EMS',
            '韵达快递',
            '中通快递',
            '百世快递',
            '德邦快递',
            '中国邮政',
        ];
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $logisticsList];
    }

}