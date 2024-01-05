<?php

namespace App\Listener;

use App\Event\CourseOfflinePayRegistered;
use App\Event\GoodsRefundRegistered;
use App\Event\MemberEventSwitchRegistered;
use App\Event\VipCardOrderRefundRegistered;
use App\Model\AsyncTask;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;

#[Listener]
class MemberEventListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            GoodsRefundRegistered::class,
            VipCardOrderRefundRegistered::class,
            MemberEventSwitchRegistered::class,
            CourseOfflinePayRegistered::class
        ];
    }

    public function process(object $event): void
    {
        if($event instanceof GoodsRefundRegistered){
            go(function ()use($event){
                $memberId = $event->memberId;

                $insertAsyncTaskData[] = ['type'=>10,'data'=>json_encode(['action_type'=>3,'member_id'=>$memberId])];
                AsyncTask::query()->insert($insertAsyncTaskData);
            });
        }else if($event instanceof VipCardOrderRefundRegistered){
            go(function ()use($event){
                $memberId = $event->memberId;

                $insertAsyncTaskData[] = ['type'=>10,'data'=>json_encode(['action_type'=>8,'member_id'=>$memberId])];
                $insertAsyncTaskData[] = ['type'=>10,'data'=>json_encode(['action_type'=>11,'member_id'=>$memberId])];
                AsyncTask::query()->insert($insertAsyncTaskData);
            });
        }else if($event instanceof MemberEventSwitchRegistered){
            go(function ()use($event){
                $memberId = $event->memberId;
                $isClose = $event->isClose;

                if($isClose == 0){
                    $insertAsyncTaskData[] = ['type'=>10,'data'=>json_encode(['action_type'=>1006,'member_id'=>$memberId])];
                }else{
                    $insertAsyncTaskData[] = ['type'=>10,'data'=>json_encode(['action_type'=>1005,'member_id'=>$memberId])];
                }
                AsyncTask::query()->insert($insertAsyncTaskData);
            });
        }else if($event instanceof CourseOfflinePayRegistered){
            go(function ()use($event){
                $memberId = $event->memberId;
                $isSample = $event->isSample;

                if($isSample === 0){
                    $insertAsyncTaskData[] = ['type'=>10,'data'=>json_encode(['action_type'=>9,'member_id'=>$memberId])];
                    $insertAsyncTaskData[] = ['type'=>10,'data'=>json_encode(['action_type'=>11,'member_id'=>$memberId])];
                }else{
                    $insertAsyncTaskData[] = ['type'=>10,'data'=>json_encode(['action_type'=>4,'member_id'=>$memberId])];
                    $insertAsyncTaskData[] = ['type'=>10,'data'=>json_encode(['action_type'=>5,'member_id'=>$memberId])];
                }
                $insertAsyncTaskData[] = ['type'=>10,'data'=>json_encode(['action_type'=>1004,'member_id'=>$memberId])];
                AsyncTask::query()->insert($insertAsyncTaskData);
            });
        }

    }
}