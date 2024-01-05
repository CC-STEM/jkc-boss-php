<?php
declare(strict_types=1);

namespace App\Task;

use App\Logger\Log;
use App\Model\AsyncTask;
use App\Model\DiscountTicket;
use App\Model\DiscountTicketPhysicalStore;
use App\Model\DiscountTicketTemplate;
use App\Model\DiscountTicketTemplatePhysicalStore;
use App\Model\DiscountTicketTemplateVipCard;
use App\Model\DiscountTicketVipCard;
use App\Model\Member;
use App\Model\PayApply;
use App\Model\VipCardOrder;
use App\Snowflake\IdGenerator;
use Hyperf\DbConnection\Db;

class DiscountTicketTask extends BaseTask
{
    /**
     * 购卡获赠减免券
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \RedisException
     */
    public function payVipCardToDiscountTicketExecute(): void
    {
        $nowDate = date('Y-m-d H:i:s');

        $asyncTaskList = AsyncTask::query()
            ->select(['id','data'])
            ->where([['status','=',0],['type','=',9],['scan_at','<=',$nowDate]])
            ->offset(0)->limit(50)
            ->get();
        $asyncTaskList = $asyncTaskList->toArray();

        foreach($asyncTaskList as $value){
            $data = json_decode($value['data'],true);
            $outTradeNo = $data['out_trade_no'];

            $payApplyInfo = PayApply::query()->select(['order_no'])->where(['out_trade_no'=>$outTradeNo])->first();
            $payApplyInfo = $payApplyInfo->toArray();
            $orderNo = $payApplyInfo['order_no'];
            $vipCardOrderInfo = VipCardOrder::query()
                ->select(['id','member_id','created_at'])
                ->where(['order_no'=>$orderNo,'pay_status'=>1])
                ->first();
            if(empty($vipCardOrderInfo)){
                Log::get()->info("payVipCardToDiscountTicketExecute[会员卡订单数据缺失]:".$orderNo);
                continue;
            }
            $vipCardOrderInfo = $vipCardOrderInfo->toArray();
            $createdAt = $vipCardOrderInfo['created_at'];
            $memberId = $vipCardOrderInfo['member_id'];
            $atomLockKey = "payVipCardToDiscountTicket:$memberId";
            if($this->functions->existsAtomLock($atomLockKey) === true){
                Log::get()->info("payVipCardToDiscountTicketExecute[会员处理中]:".$memberId);
                continue;
            }
            $discountTicketExists = VipCardOrder::query()->where([['member_id','=',$memberId],['id','<>',$vipCardOrderInfo['id']],['pay_status','=',1]])->exists();
            if($discountTicketExists === true){
                AsyncTask::query()->where(['id'=>$value['id']])->update(['status'=>1]);
                continue;
            }
            $discountTicketTemplateInfo = DiscountTicketTemplate::query()
                ->select(['id','name','amount','expire','applicable_store_type','applicable_vip_card_type'])
                ->where([['start_at','<=',$createdAt],['end_at','>',$createdAt],['is_deleted','=',0]])
                ->first();
            if(empty($discountTicketTemplateInfo)){
                AsyncTask::query()->where(['id'=>$value['id']])->update(['status'=>1]);
                continue;
            }
            $discountTicketTemplateInfo = $discountTicketTemplateInfo->toArray();
            $discountTicketTemplateVipCardList = DiscountTicketTemplateVipCard::query()
                ->select(['vip_card_id'])
                ->where(['discount_ticket_template_id'=>$discountTicketTemplateInfo['id']])
                ->get();
            $discountTicketTemplateVipCardList = $discountTicketTemplateVipCardList->toArray();
            $discountTicketTemplatePhysicalStoreList = DiscountTicketTemplatePhysicalStore::query()
                ->select(['physical_store_id'])
                ->where(['discount_ticket_template_id'=>$discountTicketTemplateInfo['id']])
                ->get();
            $discountTicketTemplatePhysicalStoreList = $discountTicketTemplatePhysicalStoreList->toArray();

            $memberInfo = Member::query()
                ->select(['parent_id'])
                ->where(['id'=>$memberId])
                ->first();
            if(empty($memberInfo)){
                Log::get()->info("payVipCardToDiscountTicketExecute[会员数据缺失]:".$memberId.'#'.$orderNo);
                continue;
            }
            $memberInfo = $memberInfo->toArray();
            $parentId = $memberInfo['parent_id'];
            $expire = $discountTicketTemplateInfo['expire'];
            $endAt = date('Y-m-d H:i:s',strtotime("+$expire day"));
            if($parentId == 0){
                AsyncTask::query()->where(['id'=>$value['id']])->update(['status'=>1]);
                continue;
            }

            $selfDiscountTicketId = IdGenerator::generate();
            $insertDiscountTicketData[0] = [
                'id' => $selfDiscountTicketId,
                'member_id' => $memberId,
                'discount_ticket_template_id' => $discountTicketTemplateInfo['id'],
                'name' => $discountTicketTemplateInfo['name'],
                'amount' => $discountTicketTemplateInfo['amount'],
                'end_at' => $endAt,
                'source_type' => 2,
                'source_id' => $memberId,
                'applicable_store_type' => $discountTicketTemplateInfo['applicable_store_type'],
                'applicable_vip_card_type' => $discountTicketTemplateInfo['applicable_vip_card_type']
            ];
            $insertDiscountTicketVipCardData = [];
            foreach($discountTicketTemplateVipCardList as $item){
                $discountTicketVipCardData['id'] = IdGenerator::generate();
                $discountTicketVipCardData['discount_ticket_id'] = $selfDiscountTicketId;
                $discountTicketVipCardData['vip_card_id'] = $item['vip_card_id'];
                $insertDiscountTicketVipCardData[] = $discountTicketVipCardData;
            }
            $insertDiscountTicketPhysicalStoreData = [];
            foreach($discountTicketTemplatePhysicalStoreList as $item){
                $discountTicketPhysicalStoreData['id'] = IdGenerator::generate();
                $discountTicketPhysicalStoreData['discount_ticket_id'] = $selfDiscountTicketId;
                $discountTicketPhysicalStoreData['physical_store_id'] = $item['physical_store_id'];
                $insertDiscountTicketPhysicalStoreData[] = $discountTicketPhysicalStoreData;
            }

            $parentDiscountTicketId = IdGenerator::generate();
            $insertDiscountTicketData[1] = [
                'id' => $parentDiscountTicketId,
                'member_id' => $parentId,
                'discount_ticket_template_id' => $discountTicketTemplateInfo['id'],
                'name' => $discountTicketTemplateInfo['name'],
                'amount' => $discountTicketTemplateInfo['amount'],
                'end_at' => $endAt,
                'source_type' => 1,
                'source_id' => $memberId,
                'applicable_store_type' => $discountTicketTemplateInfo['applicable_store_type'],
                'applicable_vip_card_type' => $discountTicketTemplateInfo['applicable_vip_card_type']
            ];
            foreach($discountTicketTemplateVipCardList as $item){
                $discountTicketVipCardData['id'] = IdGenerator::generate();
                $discountTicketVipCardData['discount_ticket_id'] = $parentDiscountTicketId;
                $discountTicketVipCardData['vip_card_id'] = $item['vip_card_id'];
                $insertDiscountTicketVipCardData[] = $discountTicketVipCardData;
            }
            foreach($discountTicketTemplatePhysicalStoreList as $item){
                $discountTicketPhysicalStoreData['id'] = IdGenerator::generate();
                $discountTicketPhysicalStoreData['discount_ticket_id'] = $parentDiscountTicketId;
                $discountTicketPhysicalStoreData['physical_store_id'] = $item['physical_store_id'];
                $insertDiscountTicketPhysicalStoreData[] = $discountTicketPhysicalStoreData;
            }

            Db::connection('jkc_edu')->beginTransaction();
            try{
                $asyncTaskAffected = AsyncTask::query()->where(['id'=>$value['id'],'status'=>0])->update(['status'=>1]);
                if(!$asyncTaskAffected){
                    Db::connection('jkc_edu')->rollBack();
                    Log::get()->info("payVipCardToDiscountTicketExecute[数据修改失败]:".$value['id']);
                    continue;
                }
                DiscountTicket::query()->insert($insertDiscountTicketData);
                DiscountTicketVipCard::query()->insert($insertDiscountTicketVipCardData);
                DiscountTicketPhysicalStore::query()->insert($insertDiscountTicketPhysicalStoreData);
                Db::connection('jkc_edu')->commit();
                $this->functions->atomLock($atomLockKey,60);
            } catch(\Throwable $e){
                Db::connection('jkc_edu')->rollBack();
                $error = ['tag'=>"salaryBill5SalaryCalculateExecute",'msg'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()];
                Log::get()->error(json_encode($error));
            }
        }
    }
}

