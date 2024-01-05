<?php

declare(strict_types=1);
namespace App\Aspect;

use App\Constants\ErrorCode;
use App\Model\AsyncTask;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;

#[Aspect]
class GoodsRefundSalaryCalculateAspect extends AbstractAspect
{
    public $classes = [
        'App\Service\OrderService::handleTeachingAidsRefundOrder',
    ];

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $params = $proceedingJoinPoint->getArguments();
        $params = $params[0];
        $orderRefundId = $params['id'];
        $scanAt = date('Y-m-d H:i:s',strtotime('+1 minute'));
        $data = ['order_refund_id'=>$orderRefundId];
        $insertAsyncTaskData = ['data'=>json_encode($data),'type'=>7,'scan_at'=>$scanAt,'status'=>-1];
        $asyncTaskId = AsyncTask::query()->insertGetId($insertAsyncTaskData);

        $result = $proceedingJoinPoint->process();

        if($result['code'] === ErrorCode::SUCCESS){
            //消息确认通知
            go(function ()use($asyncTaskId){
                AsyncTask::query()->where(['id'=>$asyncTaskId,'status'=>-1])->update(['status'=>0]);
            });
        }
        return $result;
    }
}