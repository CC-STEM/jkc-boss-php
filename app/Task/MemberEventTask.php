<?php

namespace App\Task;

use App\Logger\Log;
use App\Model\AsyncTask;
use App\Model\CourseOfflineOrder;
use App\Model\MemberBelongTo;
use App\Model\MemberEvent;
use App\Model\MemberEventAutoHandleJudgmentCriteria;
use App\Model\MemberEventComplete;
use App\Model\MemberEventCompleteAutoHandleJudgmentCriteria;
use App\Model\MemberEventCompleteFeedbackDefine;
use App\Model\MemberEventCompleteFollowup;
use App\Model\MemberEventFeedbackDefine;
use App\Model\MemberEventTriggerAction;
use App\Model\OrderGoods;
use App\Model\OrderInfo;
use App\Model\OrderRefund;
use App\Model\VipCardOrder;
use App\Snowflake\IdGenerator;
use Hyperf\DbConnection\Db;

class MemberEventTask extends BaseTask
{

    /**
     * 完成事件自动反馈
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function memberEventCompleteAutoHandleExecute(): void
    {
        $nowDate = date('Y-m-d H:i:s');

        $memberEventCompleteList = MemberEventComplete::query()
            ->select(['id','member_event_id','member_id','qualified_deadline_at','auto_handle_judgment_type','created_at'])
            ->where([['handle_status','=',0],['is_built_in','=',0],['handle_type','=',1]])
            ->offset(0)->limit(100)
            ->get();
        $memberEventCompleteList = $memberEventCompleteList->toArray();

        foreach($memberEventCompleteList as $value){
            $memberEventCompleteId = $value['id'];
            $memberEventId = $value['member_event_id'];
            $memberId = $value['member_id'];
            $autoHandleJudgmentType = $value['auto_handle_judgment_type'];
            $minDate = $value['created_at'];
            $maxDate = $value['qualified_deadline_at'];

            $memberEventCompleteAutoHandleJudgmentCriteriaList = MemberEventCompleteAutoHandleJudgmentCriteria::query()
                ->select(['id','criteria_type'])
                ->where(['member_event_complete_id'=>$memberEventCompleteId])
                ->get();
            $memberEventCompleteAutoHandleJudgmentCriteriaList = $memberEventCompleteAutoHandleJudgmentCriteriaList->toArray();

            $eventAutoHandleJudgmentCriteriaCount = count($memberEventCompleteAutoHandleJudgmentCriteriaList);
            $completeEventAutoHandleJudgmentCriteriaCount = 0;
            $memberEventCompleteAutoHandleJudgmentCriteriaIdArray = [];
            foreach($memberEventCompleteAutoHandleJudgmentCriteriaList as $item){
                $criteriaType = $item['criteria_type'];
                switch ($criteriaType){
                    case 1:
                        $completeResult = CourseOfflineOrder::query()->where(['member_id'=>$memberId])->whereBetween('created_at',[$minDate,$maxDate])->exists();
                        if($completeResult === true){
                            $completeEventAutoHandleJudgmentCriteriaCount++;
                            $memberEventCompleteAutoHandleJudgmentCriteriaIdArray[] = $item['id'];
                        }
                        break;
                    case 2:
                        $completeResult = CourseOfflineOrder::query()->where(['member_id'=>$memberId,'class_status'=>1])->whereBetween('classroom_situation_feedback_at',[$minDate,$maxDate])->exists();
                        if($completeResult === true){
                            $completeEventAutoHandleJudgmentCriteriaCount++;
                            $memberEventCompleteAutoHandleJudgmentCriteriaIdArray[] = $item['id'];
                        }
                        break;
                    case 3:
                        $completeResult = VipCardOrder::query()->where(['member_id'=>$memberId,'pay_status'=>1,'order_type'=>1])->whereBetween('created_at',[$minDate,$maxDate])->exists();
                        if($completeResult === true){
                            $completeEventAutoHandleJudgmentCriteriaCount++;
                            $memberEventCompleteAutoHandleJudgmentCriteriaIdArray[] = $item['id'];
                        }
                        break;
                    case 4:
                        $completeResult = VipCardOrder::query()->where(['member_id'=>$memberId,'pay_status'=>1,'order_type'=>2])->whereBetween('created_at',[$minDate,$maxDate])->exists();
                        if($completeResult === true){
                            $completeEventAutoHandleJudgmentCriteriaCount++;
                            $memberEventCompleteAutoHandleJudgmentCriteriaIdArray[] = $item['id'];
                        }
                        break;
                    case 5:
                        $completeResult = CourseOfflineOrder::query()->where(['member_id'=>$memberId,'is_sample'=>1])->whereBetween('created_at',[$minDate,$maxDate])->exists();
                        if($completeResult === true){
                            $completeEventAutoHandleJudgmentCriteriaCount++;
                            $memberEventCompleteAutoHandleJudgmentCriteriaIdArray[] = $item['id'];
                        }
                        break;
                    case 6:
                        $completeResult = CourseOfflineOrder::query()->where(['member_id'=>$memberId,'is_sample'=>0])->whereBetween('created_at',[$minDate,$maxDate])->exists();
                        if($completeResult === true){
                            $completeEventAutoHandleJudgmentCriteriaCount++;
                            $memberEventCompleteAutoHandleJudgmentCriteriaIdArray[] = $item['id'];
                        }
                        break;
                    case 7:
                        $completeResult = OrderInfo::query()->where(['member_id'=>$memberId,'pay_status'=>1])->whereBetween('created_at',[$minDate,$maxDate])->exists();
                        if($completeResult === true){
                            $completeEventAutoHandleJudgmentCriteriaCount++;
                            $memberEventCompleteAutoHandleJudgmentCriteriaIdArray[] = $item['id'];
                        }
                        break;
                }
            }
            $isQualified = 0;
            if(($autoHandleJudgmentType==1 && $eventAutoHandleJudgmentCriteriaCount==$completeEventAutoHandleJudgmentCriteriaCount) || ($autoHandleJudgmentType==2 && $completeEventAutoHandleJudgmentCriteriaCount>0)){
                $isQualified = 1;
            }
            if($nowDate<$maxDate && $isQualified === 0){
                continue;
            }
            $insertMemberEventCompleteFollowupData['id'] = IdGenerator::generate();
            $insertMemberEventCompleteFollowupData['member_event_complete_id'] = $memberEventCompleteId;
            $insertMemberEventCompleteFollowupData['result'] = $isQualified;

            Db::connection('jkc_edu')->beginTransaction();
            try{
                $memberEventCompleteAffected = MemberEventComplete::query()->where(['id'=>$memberEventCompleteId,'handle_status'=>0])->update(['handle_status'=>1]);
                if(!$memberEventCompleteAffected){
                    Db::connection('jkc_edu')->rollBack();
                    continue;
                }
                if($isQualified === 1){
                    Db::connection('jkc_edu')->update("UPDATE member_event SET qualified_quantity=qualified_quantity+?,handle_quantity=handle_quantity+? WHERE id = ?", [1,1,$memberEventId]);
                }else{
                    Db::connection('jkc_edu')->update("UPDATE member_event SET unqualified_quantity=unqualified_quantity+?,handle_quantity=handle_quantity+? WHERE id = ?", [1,1,$memberEventId]);
                }
                if(!empty($memberEventCompleteAutoHandleJudgmentCriteriaIdArray)){
                    MemberEventCompleteAutoHandleJudgmentCriteria::query()->whereIn('id',$memberEventCompleteAutoHandleJudgmentCriteriaIdArray)->update(['is_complete'=>1]);
                }
                MemberEventCompleteFollowup::query()->insert($insertMemberEventCompleteFollowupData);
                Db::connection('jkc_edu')->commit();
            } catch(\Throwable $e){
                Db::connection('jkc_edu')->rollBack();
                $error = ['tag'=>"memberEventCompleteAutoHandleExecute",'msg'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()];
                Log::get()->error(json_encode($error));
            }
        }
    }

    /**
     * 完成事件人为反馈过期处理
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function memberEventCompleteExpireHandleExecute(): void
    {
        $nowDate = date('Y-m-d H:i:s');

        $memberEventCompleteList = MemberEventComplete::query()
            ->select(['id','teacher_id','physical_store_id','member_event_id'])
            ->where([['handle_status','=',0],['is_built_in','=',0],['handle_type','=',2],['qualified_deadline_at','<=',$nowDate]])
            ->offset(0)->limit(100)
            ->get();
        $memberEventCompleteList = $memberEventCompleteList->toArray();

        foreach($memberEventCompleteList as $value){
            $memberEventCompleteId = $value['id'];
            $memberEventId = $value['member_event_id'];

            $memberEventCompleteFeedbackDefineInfo = MemberEventCompleteFeedbackDefine::query()
                ->select(['name','describe'])
                ->where(['member_event_complete_id'=>$memberEventCompleteId,'result'=>0])
                ->first();
            if(empty($memberEventCompleteFeedbackDefineInfo)){
                continue;
            }
            $memberEventCompleteFeedbackDefineInfo = $memberEventCompleteFeedbackDefineInfo->toArray();

            $insertMemberEventCompleteFollowupData['id'] = IdGenerator::generate();
            $insertMemberEventCompleteFollowupData['member_event_complete_id'] = $memberEventCompleteId;
            $insertMemberEventCompleteFollowupData['teacher_id'] = $value['teacher_id'];
            $insertMemberEventCompleteFollowupData['physical_store_id'] = $value['physical_store_id'];
            $insertMemberEventCompleteFollowupData['name'] = $memberEventCompleteFeedbackDefineInfo['name'];
            $insertMemberEventCompleteFollowupData['result'] = 0;
            $insertMemberEventCompleteFollowupData['describe'] = $memberEventCompleteFeedbackDefineInfo['describe'];

            Db::connection('jkc_edu')->beginTransaction();
            try{
                $memberEventCompleteAffected = MemberEventComplete::query()->where(['id'=>$memberEventCompleteId,'handle_status'=>0])->update(['handle_status'=>1]);
                if(!$memberEventCompleteAffected){
                    Db::connection('jkc_edu')->rollBack();
                    continue;
                }
                Db::connection('jkc_edu')->update("UPDATE member_event SET unqualified_quantity=unqualified_quantity+?,handle_quantity=handle_quantity+? WHERE id = ?", [1,1,$memberEventId]);
                MemberEventCompleteFollowup::query()->insert($insertMemberEventCompleteFollowupData);
                Db::connection('jkc_edu')->commit();
            } catch(\Throwable $e){
                Db::connection('jkc_edu')->rollBack();
                $error = ['tag'=>"memberEventCompleteExpireHandleExecute",'msg'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()];
                Log::get()->error(json_encode($error));
            }
        }
    }

    public function memberEventExecute()
    {
        try{
            $nowDate = date('Y-m-d H:i:s');

            $asyncTaskList = AsyncTask::query()
                ->select(['id','data'])
                ->where([['status','=',0],['type','=',10],['scan_at','<=',$nowDate]])
                ->offset(0)->limit(50)
                ->get();
            $asyncTaskList = $asyncTaskList->toArray();

            foreach($asyncTaskList as $value){
                $data = json_decode($value['data'],true);
                $actionType = $data['action_type'];
                $memberId = $data['member_id'];

                if(in_array($actionType,[1000,1001,1002,1003,1004,1005,1006])){
                    $this->builtInEventHandle(['action_type'=>$actionType,'member_id'=>$memberId]);
                }else{
                    $memberEventTriggerActionList = MemberEventTriggerAction::query()
                        ->select(['member_event_id'])
                        ->where(['action_type'=>$actionType,'is_deleted'=>0])
                        ->get();
                    $memberEventTriggerActionList = $memberEventTriggerActionList->toArray();
                    $memberEventIdArray = array_column($memberEventTriggerActionList,'member_event_id');

                    $this->eventHandle(['event_ids'=>$memberEventIdArray,'member_id'=>$memberId]);
                }
                AsyncTask::query()->where(['id'=>$value['id']])->update(['status'=>1]);
            }
        } catch(\Throwable $e){
            $error = ['tag'=>"memberEventExecute",'msg'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()];
            Log::get()->error(json_encode($error));
        }

    }

    private function builtInEventHandle(array $params): void
    {
        $memberId = $params['member_id'];
        $actionType = $params['action_type'];
        $nowDate = date('Y-m-d H:i:s');

        if($actionType === 1003){
            $memberEventCompleteExists = MemberEventComplete::query()->where([['built_in_event_type','=',$actionType],['member_id','=',$memberId],['lose_efficacy_at','>',$nowDate]])->exists();
            if($memberEventCompleteExists === true){
                return;
            }
        }
        $operationResult = $this->eventTriggerActionOperation($memberId,'',$actionType,0,'0000-00-00 00:00:00','0000-00-00 00:00:00');
        if($operationResult === true){
            $builtInEventName = [1000=>'注册会员',1001=>'购买会员卡',1002=>'购买新人礼包',1003=>'3天内有过期次数',1004=>'报名次数用完',1005=>'会员放弃跟进',1006=>'会员重新跟进'];
            $memberBelongToInfo = MemberBelongTo::query()
                ->select(['teacher_id','physical_store_id'])
                ->where(['member_id'=>$memberId])
                ->first();
            $memberBelongToInfo = $memberBelongToInfo?->toArray();
            $teacherId = $memberBelongToInfo['teacher_id'] ?? 0;
            $physicalStoreId = $memberBelongToInfo['physical_store_id'] ?? 0;
            $loseEfficacyAt = $actionType === 1003 ? date('Y-m-d H:i:s',strtotime('+3 day')) : '0000-00-00 00:00:00';

            $insertMemberEventCompleteData['id'] = IdGenerator::generate();
            $insertMemberEventCompleteData['member_id'] = $memberId;
            $insertMemberEventCompleteData['name'] = $builtInEventName[$actionType];
            $insertMemberEventCompleteData['is_built_in'] = 1;
            $insertMemberEventCompleteData['built_in_event_type'] = $actionType;
            $insertMemberEventCompleteData['teacher_id'] = $teacherId;
            $insertMemberEventCompleteData['physical_store_id'] = $physicalStoreId;
            $insertMemberEventCompleteData['handle_status'] = 1;
            $insertMemberEventCompleteData['lose_efficacy_at'] = $loseEfficacyAt;
            MemberEventComplete::query()->insert($insertMemberEventCompleteData);
        }
    }

    private function eventHandle(array $params): void
    {
        $memberEventIdArray = $params['event_ids'];
        $memberId = $params['member_id'];

        $memberEventList = MemberEvent::query()
            ->select(['id','name','is_need_feedback','handle_type','complete_judgment_type','qualified_expire','auto_handle_judgment_type','describe'])
            ->whereIn('id',$memberEventIdArray)
            ->get();
        $memberEventList = $memberEventList->toArray();

        foreach($memberEventList as $value){
            $memberEventId = $value['id'];
            $qualifiedExpire = $value['qualified_expire'];

            $memberEventCompleteExists = MemberEventComplete::query()->where(['member_event_id'=>$memberEventId,'member_id'=>$memberId,'handle_status'=>0])->exists();
            if($memberEventCompleteExists === true){
                continue;
            }
            $memberEventAutoHandleJudgmentCriteriaList = MemberEventAutoHandleJudgmentCriteria::query()
                ->select(['name','criteria_type'])
                ->where(['member_event_id'=>$memberEventId])
                ->get();
            $memberEventAutoHandleJudgmentCriteriaList = $memberEventAutoHandleJudgmentCriteriaList->toArray();
            $memberEventFeedbackDefineList = MemberEventFeedbackDefine::query()
                ->select(['name','result','describe'])
                ->where(['member_event_id'=>$memberEventId,'is_deleted'=>0])
                ->get();
            $memberEventFeedbackDefineList = $memberEventFeedbackDefineList->toArray();

            $memberEventTriggerActionData = MemberEventTriggerAction::query()
                ->select(['action_type','operator','value','start_at','end_at'])
                ->where(['member_event_id'=>$memberEventId,'is_deleted'=>0])
                ->get();
            $memberEventTriggerActionData = $memberEventTriggerActionData->toArray();
            $eventTriggerActionCount = count($memberEventTriggerActionData);
            $completeEventTriggerActionCount = 0;

            foreach($memberEventTriggerActionData as $item){
                $actionType = $item['action_type'];
                $operator = $item['operator'];
                $judgmentValue = $item['value'];
                $minDate = $item['start_at'];
                $maxDate = $item['end_at'];
                $operationResult = $this->eventTriggerActionOperation($memberId,$operator,$actionType,$judgmentValue,$minDate,$maxDate);
                if($operationResult === true){
                    $completeEventTriggerActionCount++;
                }
            }
            if($eventTriggerActionCount === $completeEventTriggerActionCount){
                $memberBelongToInfo = MemberBelongTo::query()
                    ->select(['teacher_id','physical_store_id'])
                    ->where(['member_id'=>$memberId])
                    ->first();
                $memberBelongToInfo = $memberBelongToInfo?->toArray();
                $teacherId = $memberBelongToInfo['teacher_id'] ?? 0;
                $physicalStoreId = $memberBelongToInfo['physical_store_id'] ?? 0;

                $memberEventCompleteId = IdGenerator::generate();
                $insertMemberEventCompleteData['id'] = $memberEventCompleteId;
                $insertMemberEventCompleteData['member_event_id'] = $memberEventId;
                $insertMemberEventCompleteData['member_id'] = $memberId;
                $insertMemberEventCompleteData['name'] = $value['name'];
                $insertMemberEventCompleteData['is_need_feedback'] = $value['is_need_feedback'];
                $insertMemberEventCompleteData['handle_type'] = $value['handle_type'];
                $insertMemberEventCompleteData['qualified_expire'] = $qualifiedExpire;
                $insertMemberEventCompleteData['qualified_deadline_at'] = date('Y-m-d H:i:s',strtotime("+$qualifiedExpire day"));
                $insertMemberEventCompleteData['auto_handle_judgment_type'] = $value['auto_handle_judgment_type'];
                $insertMemberEventCompleteData['describe'] = $value['describe'];
                $insertMemberEventCompleteData['teacher_id'] = $teacherId;
                $insertMemberEventCompleteData['physical_store_id'] = $physicalStoreId;

                $insertMemberEventCompleteAutoHandleJudgmentCriteriaData = [];
                foreach($memberEventAutoHandleJudgmentCriteriaList as $item){
                    $memberEventCompleteAutoHandleJudgmentCriteriaData['id'] = IdGenerator::generate();
                    $memberEventCompleteAutoHandleJudgmentCriteriaData['member_event_complete_id'] = $memberEventCompleteId;
                    $memberEventCompleteAutoHandleJudgmentCriteriaData['name'] = $item['name'];
                    $memberEventCompleteAutoHandleJudgmentCriteriaData['criteria_type'] = $item['criteria_type'];
                    $insertMemberEventCompleteAutoHandleJudgmentCriteriaData[] = $memberEventCompleteAutoHandleJudgmentCriteriaData;
                }
                $insertMemberEventCompleteFeedbackDefineData = [];
                foreach($memberEventFeedbackDefineList as $item){
                    $memberEventCompleteFeedbackDefineData['id'] = IdGenerator::generate();
                    $memberEventCompleteFeedbackDefineData['member_event_complete_id'] = $memberEventCompleteId;
                    $memberEventCompleteFeedbackDefineData['name'] = $item['name'];
                    $memberEventCompleteFeedbackDefineData['result'] = $item['result'];
                    $memberEventCompleteFeedbackDefineData['describe'] = $item['describe'];
                    $insertMemberEventCompleteFeedbackDefineData[] = $memberEventCompleteFeedbackDefineData;
                }

                MemberEventComplete::query()->insert($insertMemberEventCompleteData);
                if(!empty($insertMemberEventCompleteAutoHandleJudgmentCriteriaData)){
                    MemberEventCompleteAutoHandleJudgmentCriteria::query()->insert($insertMemberEventCompleteAutoHandleJudgmentCriteriaData);
                }
                if(!empty($insertMemberEventCompleteFeedbackDefineData)){
                    MemberEventCompleteFeedbackDefine::query()->insert($insertMemberEventCompleteFeedbackDefineData);
                }
                Db::connection('jkc_edu')->update("UPDATE member_event SET trigger_quantity=trigger_quantity+? WHERE id = ?", [1,$memberEventId]);
            }
        }
    }

    private function eventTriggerActionOperation(int $memberId, string $operator,int $actionType, int $judgmentValue, string $minDate, string $maxDate): bool
    {
        $nowDate = date('Y-m-d H:i:s');
        switch ($actionType){
            case 1:
                if($minDate === '0000-00-00 00:00:00' && $maxDate === '0000-00-00 00:00:00'){
                    $operationValue = OrderGoods::query()->where(['member_id'=>$memberId,'order_status'=>0,'pay_status'=>1])->sum(DB::connection('jkc_edu')->raw('pay_price*quantity'));
                }else{
                    $operationValue = OrderGoods::query()->where(['member_id'=>$memberId,'order_status'=>0,'pay_status'=>1])->whereBetween('pay_at',[$minDate,$maxDate])->sum(DB::connection('jkc_edu')->raw('pay_price*quantity'));
                }
                $operationResult = $this->compare($operator,(string)$operationValue,(string)$judgmentValue);
                break;
            case 2:
                if($minDate === '0000-00-00 00:00:00' && $maxDate === '0000-00-00 00:00:00'){
                    $operationValue = OrderGoods::query()->where(['member_id'=>$memberId,'pay_status'=>1])->count();
                }else{
                    $operationValue = OrderGoods::query()->where(['member_id'=>$memberId,'pay_status'=>1])->whereBetween('pay_at',[$minDate,$maxDate])->count();
                }
                $operationResult = $this->compare($operator,(string)$operationValue,(string)$judgmentValue);
                break;
            case 3:
                if($minDate === '0000-00-00 00:00:00' && $maxDate === '0000-00-00 00:00:00'){
                    $operationValue = OrderRefund::query()->where(['member_id'=>$memberId,'status'=>25])->count();
                }else{
                    $operationValue = OrderRefund::query()->where(['member_id'=>$memberId,'status'=>25])->whereBetween('operated_at',[$minDate,$maxDate])->count();
                }
                $operationResult = $this->compare($operator,(string)$operationValue,(string)$judgmentValue);
                break;
            case 4:
                if($minDate === '0000-00-00 00:00:00' && $maxDate === '0000-00-00 00:00:00'){
                    $operationValue = VipCardOrder::query()->where([['member_id','=',$memberId],['pay_status','=',1],['order_status','=',0],['expire_at','>=',$nowDate]])->whereIn('order_type',[2,3])->sum(DB::connection('jkc_edu')->raw('currency_course-currency_course_used'));
                }else{
                    $operationValue = VipCardOrder::query()->where([['member_id','=',$memberId],['pay_status','=',1],['order_status','=',0],['expire_at','>=',$nowDate]])->whereIn('order_type',[2,3])->whereBetween('created_at',[$minDate,$maxDate])->sum(DB::connection('jkc_edu')->raw('currency_course-currency_course_used'));
                }
                $operationResult = $this->compare($operator,(string)$operationValue,(string)$judgmentValue);
                break;
            case 5:
                if($minDate === '0000-00-00 00:00:00' && $maxDate === '0000-00-00 00:00:00'){
                    $operationValue = CourseOfflineOrder::query()->where(['member_id'=>$memberId,'pay_status'=>1,'order_status'=>0,'is_sample'=>1])->count();
                }else{
                    $operationValue = CourseOfflineOrder::query()->where(['member_id'=>$memberId,'pay_status'=>1,'order_status'=>0,'is_sample'=>1])->whereBetween('created_at',[$minDate,$maxDate])->count();
                }
                $operationResult = $this->compare($operator,(string)$operationValue,(string)$judgmentValue);
                break;
            case 6:
                if($minDate === '0000-00-00 00:00:00' && $maxDate === '0000-00-00 00:00:00'){
                    $operationValue = CourseOfflineOrder::query()->where(['member_id'=>$memberId,'pay_status'=>1,'order_status'=>2,'is_sample'=>1])->count();
                }else{
                    $operationValue = CourseOfflineOrder::query()->where(['member_id'=>$memberId,'pay_status'=>1,'order_status'=>2,'is_sample'=>1])->whereBetween('created_at',[$minDate,$maxDate])->count();
                }
                $operationResult = $this->compare($operator,(string)$operationValue,(string)$judgmentValue);
                break;
            case 7:
                if($minDate === '0000-00-00 00:00:00' && $maxDate === '0000-00-00 00:00:00'){
                    $operationValue = VipCardOrder::query()->where(['member_id'=>$memberId,'pay_status'=>1,'order_type'=>1])->count();
                }else{
                    $operationValue = VipCardOrder::query()->where(['member_id'=>$memberId,'pay_status'=>1,'order_type'=>1])->whereBetween('created_at',[$minDate,$maxDate])->count();
                }
                $operationResult = $this->compare($operator,(string)$operationValue,(string)$judgmentValue);
                break;
            case 8:
                if($minDate === '0000-00-00 00:00:00' && $maxDate === '0000-00-00 00:00:00'){
                    $operationValue = VipCardOrder::query()->where(['member_id'=>$memberId,'pay_status'=>1,'order_status'=>3,'order_type'=>1])->count();
                }else{
                    $operationValue = VipCardOrder::query()->where(['member_id'=>$memberId,'pay_status'=>1,'order_status'=>3,'order_type'=>1])->whereBetween('created_at',[$minDate,$maxDate])->count();
                }
                $operationResult = $this->compare($operator,(string)$operationValue,(string)$judgmentValue);
                break;
            case 9:
                if($minDate === '0000-00-00 00:00:00' && $maxDate === '0000-00-00 00:00:00'){
                    $operationValue = CourseOfflineOrder::query()->where(['member_id'=>$memberId,'pay_status'=>1,'order_status'=>0,'is_sample'=>0])->count();
                }else{
                    $operationValue = CourseOfflineOrder::query()->where(['member_id'=>$memberId,'pay_status'=>1,'order_status'=>0,'is_sample'=>0])->whereBetween('created_at',[$minDate,$maxDate])->count();
                }
                $operationResult = $this->compare($operator,(string)$operationValue,(string)$judgmentValue);
                break;
            case 10:
                if($minDate === '0000-00-00 00:00:00' && $maxDate === '0000-00-00 00:00:00'){
                    $operationValue = CourseOfflineOrder::query()->where(['member_id'=>$memberId,'pay_status'=>1,'order_status'=>2,'is_sample'=>0])->count();
                }else{
                    $operationValue = CourseOfflineOrder::query()->where(['member_id'=>$memberId,'pay_status'=>1,'order_status'=>2,'is_sample'=>0])->whereBetween('created_at',[$minDate,$maxDate])->count();
                }
                $operationResult = $this->compare($operator,(string)$operationValue,(string)$judgmentValue);
                break;
            case 11:
                if($minDate === '0000-00-00 00:00:00' && $maxDate === '0000-00-00 00:00:00'){
                    $operationValue = VipCardOrder::query()->where([['member_id','=',$memberId],['pay_status','=',1],['order_status','=',0],['expire_at','>=',$nowDate],['order_type','=',1]])->sum(DB::connection('jkc_edu')->raw('course1+course2+course3-course1_used-course2_used-course3_used'));
                }else{
                    $operationValue = VipCardOrder::query()->where([['member_id','=',$memberId],['pay_status','=',1],['order_status','=',0],['expire_at','>=',$nowDate],['order_type','=',1]])->whereBetween('created_at',[$minDate,$maxDate])->sum(DB::connection('jkc_edu')->raw('course1+course2+course3-course1_used-course2_used-course3_used'));
                }
                $operationResult = $this->compare($operator,(string)$operationValue,(string)$judgmentValue);
                break;
            case 12:
                $courseOfflineOrderInfo = CourseOfflineOrder::query()
                    ->select(['classroom_situation_feedback_at'])
                    ->where([['member_id','=',$memberId],['class_status','=',1],['classroom_situation_feedback_at','<>','0000-00-00 00:00:00']])
                    ->orderBy('classroom_situation_feedback_at','desc')
                    ->first();
                $operationResult = false;
                if($courseOfflineOrderInfo !== null){
                    $courseOfflineOrderInfo = $courseOfflineOrderInfo->toArray();
                    $classroomSituationFeedbackAt = $courseOfflineOrderInfo['classroom_situation_feedback_at'];
                    $operationValue = bcdiv((string)((int)strtotime($nowDate)-(int)strtotime($classroomSituationFeedbackAt)),'86400');
                    $operationResult = $this->compare($operator,$operationValue,(string)$judgmentValue);
                }

                break;
            case 13:
                if($minDate === '0000-00-00 00:00:00' && $maxDate === '0000-00-00 00:00:00'){
                    $operationValue = CourseOfflineOrder::query()->where(['member_id'=>$memberId,'pay_status'=>1,'class_status'=>1])->count();
                }else{
                    $operationValue = CourseOfflineOrder::query()->where(['member_id'=>$memberId,'pay_status'=>1,'class_status'=>1])->whereBetween('created_at',[$minDate,$maxDate])->count();
                }
                $operationResult = $this->compare($operator,(string)$operationValue,(string)$judgmentValue);
                break;
            case 1000:
            case 1001:
            case 1002:
            case 1003:
            case 1005:
            case 1006:
                $operationResult =  true;
                break;
            case 1004:
                $operationValue = VipCardOrder::query()->where([['member_id','=',$memberId],['pay_status','=',1],['order_status','=',0],['expire_at','>',$nowDate]])->sum(DB::connection('jkc_edu')->raw('course1+course2+course3+currency_course-course1_used-course2_used-course3_used-currency_course_used'));
                $operationResult = $this->compare('le',(string)$operationValue,'0');
                break;
            default:
                $operationResult = false;
        }
        return $operationResult;
    }

    private function compare(string $operator, string $left, string $right): bool
    {
        return match ($operator) {
            'ge' => $left >= $right,
            'gt' => $left > $right,
            'le' => $left <= $right,
            'lt' => $left < $right,
            'eq' => $left == $right
        };
    }

}