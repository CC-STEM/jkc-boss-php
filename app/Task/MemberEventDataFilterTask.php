<?php
declare(strict_types=1);

namespace App\Task;

use App\Model\AsyncTask;
use App\Model\CourseOfflineOrder;
use App\Model\MemberEventComplete;
use App\Model\MemberEventTriggerAction;
use App\Model\VipCardOrder;

class MemberEventDataFilterTask extends BaseTask
{
    /**
     * 近3天有会员卡过期
     * @return void
     */
    public function memberEventTriggerAction1003Execute(): void
    {
        $nowDate = date('Y-m-d H:i:s');
        $dayAfter3 = date('Y-m-d H:i:s',strtotime('+3 day'));
        $dayAgo3 = date('Y-m-d H:i:s',strtotime('-3 day'));

        $vipCardOrderList = VipCardOrder::query()
            ->select(['member_id','course1','course2','course3','currency_course','course1_used','course2_used','course3_used','currency_course_used'])
            ->where(['pay_status'=>1,'order_status'=>0])
            ->whereBetween('expire_at',[$nowDate,$dayAfter3])
            ->get();
        $vipCardOrderList = $vipCardOrderList->toArray();

        $insertAsyncTaskData = [];
        $triggerMemberIdArray = [];
        foreach($vipCardOrderList as $value){
            $surplusCourse = $value['course1']+$value['course2']+$value['course3']+$value['currency_course']-$value['course1_used']-$value['course2_used']-$value['course3_used']-$value['currency_course_used'];

            $memberEventCompleteExists = MemberEventComplete::query()
                ->where(['built_in_event_type'=>1003,'member_id'=>$value['member_id']])
                ->whereBetween('created_at',[$dayAgo3,$nowDate])
                ->exists();
            if($memberEventCompleteExists === false && $surplusCourse>0 && !in_array($value['member_id'],$triggerMemberIdArray)){
                $insertAsyncTaskData[] = ['type'=>10,'data'=>json_encode(['action_type'=>1003,'member_id'=>$value['member_id']])];
                $triggerMemberIdArray[] = $value['member_id'];
            }
        }
        if(!empty($insertAsyncTaskData)){
            AsyncTask::query()->insert($insertAsyncTaskData);
        }
    }

    /**
     * 近期上课
     * @return void
     */
    public function memberEventTriggerAction12Execute(): void
    {
        $nowDate = date('Y-m-d H:i:s');

        $memberEventTriggerActionExists = MemberEventTriggerAction::query()->where(['action_type'=>12,'is_deleted'=>0])->exists();
        if($memberEventTriggerActionExists === false){
            return;
        }

        $insertAsyncTaskData = [];
        $courseOfflineOrderList = CourseOfflineOrder::query()
            ->select(['member_id'])
            ->where([['order_status','=',0],['class_status','=',1],['classroom_situation_feedback_at','<>',$nowDate]])
            ->groupBy('member_id')
            ->get();
        foreach($courseOfflineOrderList as $value){
            $insertAsyncTaskData[] = ['type'=>10,'data'=>json_encode(['action_type'=>12,'member_id'=>$value['member_id']])];
        }
        if(!empty($insertAsyncTaskData)){
            AsyncTask::query()->insert($insertAsyncTaskData);
        }
    }
}

