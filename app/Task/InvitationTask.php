<?php
declare(strict_types=1);

namespace App\Task;

use App\Constants\ErrorCode;
use App\Logger\Log;
use App\Model\AsyncTask;
use App\Model\CourseOfflineOrder;
use App\Model\InvitationRelation;
use App\Model\Member;
use App\Model\VipCardOrder;
use App\Snowflake\IdGenerator;
use Hyperf\DbConnection\Db;

class InvitationTask extends BaseTask
{
    /**
     * 邀请奖励发放
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function invitationRewardSendExecute(): void
    {
        try{
            $nowDate = date('Y-m-d H:i:s');
            $asyncTaskList = AsyncTask::query()
                ->select(['id','data'])
                ->where([['status','=',0],['type','=',1],['scan_at','<=',$nowDate]])
                ->offset(0)->limit(50)
                ->get();
            $asyncTaskList = $asyncTaskList->toArray();

            foreach($asyncTaskList as $value){
                $data = json_decode($value['data'],true);
                $courseOfflineOrderInfo = CourseOfflineOrder::query()
                    ->select(['class_status'])
                    ->where(['id'=>$data['course_offline_order_id']])
                    ->first();
                $courseOfflineOrderInfo = $courseOfflineOrderInfo->toArray();

                if($courseOfflineOrderInfo['class_status'] == 0){
                    AsyncTask::query()->where(['id'=>$value['id']])->update(['status'=>1]);
                }else{
                    $result = $this->invitationRewardSend($data);
                    if($result['code'] === ErrorCode::SUCCESS){
                        AsyncTask::query()->where(['id'=>$value['id']])->update(['status'=>1]);
                    }
                }
            }
        } catch (\Throwable $e) {
            $error = ['tag'=>'invitationRewardSendExecute','msg'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()];
            Log::get()->error(json_encode($error));
        }
    }

    /**
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function invitationRewardSend(array $params): array
    {
        $memberId = $params['member_id'];

        $memberInfo = Member::query()
            ->select(['parent_id'])
            ->where(['id' => $memberId])
            ->first();
        if (empty($memberInfo)) {
            return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
        }
        $memberInfo = $memberInfo->toArray();
        $parentId = $memberInfo['parent_id'];
        if ($parentId == 0) {
            return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
        }
        $invitationRelationInfo = InvitationRelation::query()
            ->select(['id'])
            ->where(['member_id' => $memberId])
            ->first();
        if (!empty($invitationRelationInfo)) {
            return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
        }
        $vipCardOrderCount = VipCardOrder::query()->where(['member_id'=>$parentId,'order_type'=>3])->count();
        if($vipCardOrderCount>=4){
            return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
        }

        $insertInvitationRelationData['id'] = IdGenerator::generate();
        $insertInvitationRelationData['member_id'] = $memberId;
        $insertInvitationRelationData['parent_member_id'] = $memberInfo['parent_id'];

        $orderNo = $this->functions->orderNo();
        $orderId = IdGenerator::generate();
        $insertVipCardOrder['id'] = $orderId;
        $insertVipCardOrder['member_id'] = $parentId;
        $insertVipCardOrder['order_no'] = $orderNo;
        $insertVipCardOrder['price'] = 0;
        $insertVipCardOrder['order_title'] = '推荐有礼';
        $insertVipCardOrder['vip_card_id'] = 0;
        $insertVipCardOrder['expire'] = 60;
        $insertVipCardOrder['expire_at'] = date("Y-m-d H:i:s", strtotime("+60 day"));
        $insertVipCardOrder['currency_course'] = 1;
        $insertVipCardOrder['order_type'] = 3;
        $insertVipCardOrder['pay_status'] = 1;

        Db::connection('jkc_edu')->beginTransaction();
        try {
            InvitationRelation::query()->insert($insertInvitationRelationData);
            VipCardOrder::query()->insert($insertVipCardOrder);
            Db::connection('jkc_edu')->commit();
        } catch (\Throwable $e) {
            Db::connection('jkc_edu')->rollBack();
            throw new \Exception($e->getMessage(), 1);
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }


}

