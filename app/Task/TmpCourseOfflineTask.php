<?php
declare(strict_types=1);

namespace App\Task;

use App\Model\CourseOfflineClassroomSituation;
use App\Model\CourseOfflineOrder;
use App\Model\CourseOfflinePlan;

class TmpCourseOfflineTask extends BaseTask
{
    public function classroomSituationExecute(): void
    {
        $courseOfflineClassroomSituationList = CourseOfflineClassroomSituation::query()
            ->select(['course_offline_plan_id','created_at'])
            ->groupBy('course_offline_plan_id')
            ->get();
        $courseOfflineClassroomSituationList = $courseOfflineClassroomSituationList->toArray();

        foreach($courseOfflineClassroomSituationList as $value){
            CourseOfflineOrder::query()->where(['course_offline_plan_id'=>$value['course_offline_plan_id'],'classroom_situation_feedback_at'=>'0000-00-00 00:00:00'])->update(['classroom_situation_feedback_at'=>$value['created_at']]);
        }
    }


}

