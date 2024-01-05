<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\CourseOfflineAgeTag;
use App\Model\CourseOfflineCategory;
use App\Model\CourseOfflineClassroomSituation;
use App\Model\CourseOfflineOutline;
use App\Model\CourseOnlineChildCollect;
use App\Model\Classroom;
use App\Model\CourseOfflinePlan;
use App\Model\Goods;
use App\Model\PhysicalStore;
use App\Model\Teacher;
use App\Model\CourseOffline;
use App\Model\CourseOfflineOrder;
use App\Model\CourseOnline;
use App\Model\CourseOnlineChild;
use App\Constants\ErrorCode;
use App\Snowflake\IdGenerator;
use Hyperf\DbConnection\Db;

class CourseService extends BaseService
{
    /**
     * 添加线下课程
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function addCourseOffline(array $params): array
    {
        $courseCategoryId = $params['course_category_id'];

        $courseOfflineCategoryInfo = CourseOfflineCategory::query()->select(['type','theme_type'])->where(['id'=>$courseCategoryId])->first();
        if(empty($courseOfflineCategoryInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '信息错误', 'data' => null];
        }
        $courseOfflineCategoryInfo = $courseOfflineCategoryInfo->toArray();

        $courseOfflineId = IdGenerator::generate();
        $outline = $params['outline'];
        $insertCourseOfflineOutlineData = [];
        foreach($outline as $value){
            $courseOfflineOutlineData = [];
            $courseOfflineOutlineData['id'] = IdGenerator::generate();
            $courseOfflineOutlineData['course_offline_id'] = $courseOfflineId;
            $courseOfflineOutlineData['content'] = $value;
            $insertCourseOfflineOutlineData[] = $courseOfflineOutlineData;
        }

        $insertCourseData['id'] = $courseOfflineId;
        $insertCourseData['course_category_id'] = $courseCategoryId;
        $insertCourseData['name'] = $params['name'];
        $insertCourseData['price'] = $params['price'];
        $insertCourseData['duration'] = $params['duration'];
        $insertCourseData['img_url'] = $params['img_url'];
        $insertCourseData['video_url'] = $params['video_url'] ?? '';
        $insertCourseData['student_video_url'] = $params['student_video_url'] ?? '';
        $insertCourseData['type'] = $courseOfflineCategoryInfo['type'];
        $insertCourseData['suit_age_min'] = $params['suit_age_min'];
        $insertCourseData['describe'] = $params['describe'] ?? '';
        $insertCourseData['phase'] = $params['phase'] ?? '';
        $insertCourseData['theme_type'] = $courseOfflineCategoryInfo['theme_type'];

        CourseOffline::query()->insert($insertCourseData);
        CourseOfflineOutline::query()->insert($insertCourseOfflineOutlineData);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 编辑线下课程
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function editCourseOffline(array $params): array
    {
        $id = $params['id'];

        $outline = $params['outline'];
        $insertCourseOfflineOutlineData = [];
        foreach($outline as $value){
            $courseOfflineOutlineData = [];
            $courseOfflineOutlineData['id'] = IdGenerator::generate();
            $courseOfflineOutlineData['course_offline_id'] = $id;
            $courseOfflineOutlineData['content'] = $value;
            $insertCourseOfflineOutlineData[] = $courseOfflineOutlineData;
        }

        $updateCourseData['name'] = $params['name'];
        $updateCourseData['price'] = $params['price'];
        $updateCourseData['duration'] = $params['duration'];
        $updateCourseData['img_url'] = $params['img_url'];
        $updateCourseData['video_url'] = $params['video_url'] ?? '';
        $updateCourseData['student_video_url'] = $params['student_video_url'] ?? '';
        $updateCourseData['suit_age_min'] = $params['suit_age_min'];
        $updateCourseData['describe'] = $params['describe'] ?? '';
        $updateCourseData['phase'] = $params['phase'] ?? '';

        CourseOfflineOutline::query()->where(['course_offline_id'=>$id])->delete();
        CourseOffline::query()->where(['id'=>$id])->update($updateCourseData);
        CourseOfflineOutline::query()->insert($insertCourseOfflineOutlineData);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 删除线下课程
     * @param int $id
     * @return array
     */
    public function deleteCourseOffline(int $id): array
    {
        CourseOffline::query()->where(['id'=>$id])->update(['is_deleted'=>1]);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 线下课程列表
     * @param array $params
     * @return array
     */
    public function courseOfflineList(array $params): array
    {
        $courseCategoryId = $params['course_category_id'];

        $courseOfflineList = CourseOffline::query()
            ->select(['id','name','img_url','suit_age_min','suit_age_max','type','sign_up_num','duration'])
            ->where(['course_category_id'=>$courseCategoryId,'is_deleted'=>0])
            ->get();
        $courseOfflineList = $courseOfflineList->toArray();
        foreach($courseOfflineList as $key=>$value){
            $studyNum = CourseOfflineOrder::query()->where(['course_offline_id'=>$value['id'],'class_status'=>1])->count('id');
            $courseOfflineList[$key]['study_num'] = $studyNum;
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $courseOfflineList];
    }

    /**
     * 线下课程详情
     * @param int $id
     * @return array
     */
    public function courseOfflineDetail(int $id): array
    {
        $courseOfflineInfo = CourseOffline::query()
            ->select(['id','name','price','duration','img_url','video_url','student_video_url','type','suit_age_min','suit_age_max','describe','phase'])
            ->where(['id'=>$id,'is_deleted'=>0])
            ->first();
        if(empty($courseOfflineInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '信息异常', 'data' => null];
        }
        $courseOfflineInfo = $courseOfflineInfo->toArray();

        $courseOfflineOutlineList = CourseOfflineOutline::query()->select(['content'])->where(['course_offline_id'=>$id])->get();
        $courseOfflineOutlineList = $courseOfflineOutlineList->toArray();
        if(!empty($courseOfflineOutlineList)){
            $courseOfflineOutlineList = array_column($courseOfflineOutlineList,'content');
        }
        $courseOfflineInfo['outline'] = $courseOfflineOutlineList;
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $courseOfflineInfo];
    }

    /**
     * 添加线下课程排课
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function addCourseOfflinePlan(array $params): array
    {
        $physicalStoreId = $params['physical_store_id'];
        $coursePlan = $params['course_plan'];

        $courseOfflineIdArray = array_column($coursePlan,'course_offline_id');
        $classroomIdArray = array_column($coursePlan,'classroom_id');
        $teacherIdArray = array_column($coursePlan,'teacher_id');

        $courseOfflineList = CourseOffline::query()->select(['id','duration','course_category_id'])->whereIn('id',$courseOfflineIdArray)->get();
        $courseOfflineList = $courseOfflineList->toArray();
        $combineCourseOfflineKey = array_column($courseOfflineList,'id');
        $courseOfflineList = array_combine($combineCourseOfflineKey,$courseOfflineList);

        $classroomList = Classroom::query()->select(['id','name','capacity'])->whereIn('id',$classroomIdArray)->get();
        $classroomList = $classroomList->toArray();
        $combineClassroomKey = array_column($classroomList,'id');
        $classroomList = array_combine($combineClassroomKey,$classroomList);

        $teacherList = Teacher::query()->select(['id','name'])->whereIn('id',$teacherIdArray)->get();
        $teacherList = $teacherList->toArray();
        $combineTeacherKey = array_column($teacherList,'id');
        $teacherList = array_combine($combineTeacherKey,$teacherList);

        $batchNo = IdGenerator::generate();
        $insertCourseOfflinePlanData = [];
        foreach($coursePlan as $value){
            $courseOfflinePlanData = [];
            $courseOfflineId = $value['course_offline_id'];
            $classroomId = $value['classroom_id'];
            $teacherId = $value['teacher_id'];
            $classStartTime = strtotime($value['class_start_time']);
            $courseOfflineInfo = $courseOfflineList[$courseOfflineId];
            $classroomInfo = $classroomList[$classroomId];
            $teacherInfo = $teacherList[$teacherId];
            $classEndTime = $classStartTime + ($courseOfflineInfo['duration']*60);
            $classDate = strtotime(date('Y-m-d',$classStartTime));

            $planTimeIntervalMin = $classStartTime-3600;
            $planTimeIntervalMax = $classStartTime+3600;
            $planCheckInfo = CourseOfflinePlan::query()
                ->select(['id'])
                ->where(['classroom_id'=>$classroomId,'teacher_id'=>$teacherId,'is_deleted'=>0])
                ->whereBetween('class_start_time',[$planTimeIntervalMin,$planTimeIntervalMax])
                ->first();
            if(!empty($planCheckInfo)){
                return ['code' => ErrorCode::WARNING, 'msg' => '同一老师教室排课间隔不足1小时，无法排课', 'data' => null];
            }

            $courseOfflinePlanData['id'] = IdGenerator::generate();
            $courseOfflinePlanData['batch_no'] = $batchNo;
            $courseOfflinePlanData['course_category_id'] = $courseOfflineInfo['course_category_id'];
            $courseOfflinePlanData['course_offline_id'] = $courseOfflineId;
            $courseOfflinePlanData['physical_store_id'] = $physicalStoreId;
            $courseOfflinePlanData['classroom_id'] = $classroomId;
            $courseOfflinePlanData['teacher_id'] = $teacherId;
            $courseOfflinePlanData['classroom_name'] = $classroomInfo['name'];
            $courseOfflinePlanData['teacher_name'] = $teacherInfo['name'];
            $courseOfflinePlanData['class_start_time'] = $classStartTime;
            $courseOfflinePlanData['class_end_time'] = $classEndTime;
            $courseOfflinePlanData['class_date'] = $classDate;
            $courseOfflinePlanData['classroom_capacity'] = $classroomInfo['capacity'];
            $insertCourseOfflinePlanData[] = $courseOfflinePlanData;
        }
        array_multisort(array_column($insertCourseOfflinePlanData,'class_start_time'), SORT_ASC, $insertCourseOfflinePlanData);

        foreach($insertCourseOfflinePlanData as $key=>$value){
            $insertCourseOfflinePlanData[$key]['section_no'] = $key+1;
        }
        CourseOfflinePlan::query()->insert($insertCourseOfflinePlanData);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 编辑线上课程排课
     * @param array $params
     * @return array
     */
    public function editCourseOfflinePlan(array $params): array
    {
        $courseOfflinePlanId = $params['id'];
        $courseOfflineId = $params['course_offline_id'];
        $classroomId = $params['classroom_id'];
        $teacherId = $params['teacher_id'];
        $classStartTime = strtotime($params['class_start_time']);

        $courseOfflinePlanInfo = CourseOfflinePlan::query()
            ->select(['batch_no'])
            ->where(['id'=>$courseOfflinePlanId])
            ->first();
        if(empty($courseOfflinePlanInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '排课信息错误', 'data' => null];
        }
        $courseOfflinePlanInfo = $courseOfflinePlanInfo->toArray();
        $batchNo = $courseOfflinePlanInfo['batch_no'];

        $courseOfflineInfo = CourseOffline::query()->select(['id','duration'])->where(['id'=>$courseOfflineId])->first();
        $courseOfflineInfo = $courseOfflineInfo->toArray();
        $classEndTime = $classStartTime + ($courseOfflineInfo['duration']*60);
        $classDate = strtotime(date('Y-m-d',$classStartTime));

        $classroomInfo = Classroom::query()->select(['id','name','capacity'])->where(['id'=>$classroomId])->first();
        $classroomInfo = $classroomInfo->toArray();

        $teacherInfo = Teacher::query()->select(['id','name'])->where(['id'=>$teacherId])->first();
        $teacherInfo = $teacherInfo->toArray();

        $updateCourseOfflinePlanData['classroom_id'] = $classroomId;
        $updateCourseOfflinePlanData['teacher_id'] = $teacherId;
        $updateCourseOfflinePlanData['classroom_name'] = $classroomInfo['name'];
        $updateCourseOfflinePlanData['teacher_name'] = $teacherInfo['name'];
        $updateCourseOfflinePlanData['class_start_time'] = $classStartTime;
        $updateCourseOfflinePlanData['class_end_time'] = $classEndTime;
        $updateCourseOfflinePlanData['class_date'] = $classDate;
        $updateCourseOfflinePlanData['classroom_capacity'] = $classroomInfo['capacity'];
        CourseOfflinePlan::query()->where(['id'=>$courseOfflinePlanId])->update($updateCourseOfflinePlanData);

        $courseOfflinePlanList = CourseOfflinePlan::query()
            ->select(['id'])
            ->where(['batch_no'=>$batchNo])
            ->orderBy('class_start_time')
            ->get();
        $courseOfflinePlanList = $courseOfflinePlanList->toArray();
        foreach($courseOfflinePlanList as $key=>$value){
            CourseOfflinePlan::query()->where(['id'=>$value['id']])->update(['section_no'=>$key+1]);
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 删除线下课程排课
     * @param int $id
     * @return array
     */
    public function deleteCourseOfflinePlan(int $id): array
    {
        CourseOfflinePlan::query()->where(['id'=>$id])->update(['is_deleted'=>1]);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 线下课程排课列表
     * @param array $params
     * @return array
     */
    public function courseOfflinePlanList(array $params): array
    {
        $classStatus = $params['class_status'];
        $physicalStoreId = $params['physical_store_id'];
        $classroomId = $params['classroom_id'];
        $teacherId = $params['teacher_id'];
        $suitAgeMin = $params['suit_age_min'];
        $suitAgeMax = $params['suit_age_max'];
        $classStartDateMin = $params['class_start_date_min'];
        $classStartDateMax = $params['class_start_date_max'];
        $nowTime = time();
        $offset = $this->offset;
        $limit = $this->limit;

        $model = CourseOfflinePlan::query()
            ->leftJoin('course_offline', 'course_offline_plan.course_offline_id', '=', 'course_offline.id')
            ->leftJoin('physical_store', 'course_offline_plan.physical_store_id', '=', 'physical_store.id');
        $where = [['course_offline_plan.is_deleted','=',0]];
        if($classStatus == 0){
            $where[] = ['course_offline_plan.class_end_time','>',$nowTime];
        }else{
            $where[] = ['course_offline_plan.class_end_time','<=',$nowTime];
        }
        if($physicalStoreId !== null){
            $where[] = ['course_offline_plan.physical_store_id','=',$physicalStoreId];
        }
        if($classroomId !== null){
            $where[] = ['course_offline_plan.classroom_id','=',$classroomId];
        }
        if($teacherId !== null){
            $where[] = ['course_offline_plan.teacher_id','=',$teacherId];
        }
        if($suitAgeMin !== null){
            $model->whereBetween('course_offline.suit_age_min',[$suitAgeMin,$suitAgeMax]);
        }
        if($classStartDateMin !== null && $classStartDateMax !== null){
            $model->whereBetween('course_offline_plan.class_start_time',[strtotime($classStartDateMin),strtotime($classStartDateMax)]);
        }
        $count = $model->where($where)->count();
        $courseOfflinePlanList = $model
            ->select(['course_offline.name','course_offline.video_url','course_offline.type','course_offline.suit_age_min','course_offline.suit_age_max','course_offline_plan.id','course_offline_plan.course_offline_id','course_offline_plan.classroom_name','course_offline_plan.teacher_name','course_offline_plan.classroom_id','course_offline_plan.teacher_id','course_offline_plan.physical_store_id','course_offline_plan.class_start_time','course_offline_plan.class_end_time','course_offline_plan.classroom_capacity','course_offline_plan.sign_up_num','physical_store.name as physical_store_name'])
            ->where($where)
            ->orderBy('id','desc')
            ->offset($offset)->limit($limit)
            ->get();
        $courseOfflinePlanList = $courseOfflinePlanList->toArray();

        foreach($courseOfflinePlanList as $key=>$value){
            $courseOfflinePlanId = $value['id'];
            $arriveNum = CourseOfflineOrder::query()->where(['course_offline_plan_id'=>$courseOfflinePlanId,'pay_status'=>1,'class_status'=>1])->count();

            $courseOfflinePlanList[$key]['class_start_time'] = date('Y-m-d H:i',$value['class_start_time']);
            $courseOfflinePlanList[$key]['class_end_time'] = date('Y-m-d H:i',$value['class_end_time']);
            $courseOfflinePlanList[$key]['arrive_num'] = $arriveNum;
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => ['list'=>$courseOfflinePlanList,'count'=>$count]];
    }

    /**
     * 线下课程排课详情
     * @param int $id
     * @return array
     */
    public function courseOfflinePlanDetail(int $id): array
    {
        $courseOfflinePlanInfo = CourseOfflinePlan::query()
            ->select(['id','course_offline_id','classroom_id','teacher_id','class_start_time','physical_store_id'])
            ->where(['id'=>$id])
            ->first();
        if(empty($courseOfflinePlanInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '信息异常', 'data' => null];
        }
        $courseOfflinePlanInfo = $courseOfflinePlanInfo->toArray();
        $courseOfflineId = $courseOfflinePlanInfo['course_offline_id'];
        $physicalStoreId = $courseOfflinePlanInfo['physical_store_id'];

        $courseOfflineInfo = CourseOffline::query()->select(['duration','name','suit_age_min','suit_age_max','video_url'])->where(['id'=>$courseOfflineId])->first();
        $courseOfflineInfo = $courseOfflineInfo->toArray();

        $physicalStoreInfo = PhysicalStore::query()->select(['name'])->where(['id'=>$physicalStoreId])->first();
        $physicalStoreInfo = $physicalStoreInfo->toArray();

        $courseOfflinePlanInfo['class_start_time'] = date('Y-m-d H:i',$courseOfflinePlanInfo['class_start_time']);
        $courseOfflinePlanInfo['name'] = $courseOfflineInfo['name'];
        $courseOfflinePlanInfo['duration'] = $courseOfflineInfo['duration'];
        $courseOfflinePlanInfo['suit_age_min'] = $courseOfflineInfo['suit_age_min'];
        $courseOfflinePlanInfo['suit_age_max'] = $courseOfflineInfo['suit_age_max'];
        $courseOfflinePlanInfo['video_url'] = $courseOfflineInfo['video_url'];
        $courseOfflinePlanInfo['physical_store_name'] = $physicalStoreInfo['name'];
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $courseOfflinePlanInfo];
    }

    /**
     * 排课报名学生
     * @param int $id
     * @return array
     */
    public function courseOfflinePlanSignUpStudent(int $id): array
    {
        $courseOfflineOrderList = CourseOfflineOrder::query()
            ->leftJoin('member','course_offline_order.member_id','=','member.id')
            ->select(['member.name','member.mobile','course_offline_order.created_at'])
            ->where(['course_offline_order.course_offline_plan_id'=>$id,'course_offline_order.pay_status'=>1,'course_offline_order.order_status'=>0])
            ->get();
        $courseOfflineOrderList = $courseOfflineOrderList->toArray();
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $courseOfflineOrderList];
    }

    /**
     * 排课实到学生
     * @param int $id
     * @return array
     */
    public function courseOfflinePlanArriveStudent(int $id): array
    {
        $courseOfflineOrderList = CourseOfflineOrder::query()
            ->leftJoin('member','course_offline_order.member_id','=','member.id')
            ->select(['member.name','member.mobile','course_offline_order.created_at'])
            ->where(['course_offline_order.course_offline_plan_id'=>$id,'course_offline_order.pay_status'=>1,'course_offline_order.class_status'=>1])
            ->get();
        $courseOfflineOrderList = $courseOfflineOrderList->toArray();
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $courseOfflineOrderList];
    }

    /**
     * 排课课堂情况
     * @param int $id
     * @return array
     */
    public function courseOfflinePlanClassroomSituation(int $id): array
    {
        $courseOfflineClassroomSituationList = CourseOfflineClassroomSituation::query()
            ->select(['img_url'])
            ->where(['course_offline_plan_id'=>$id])
            ->get();
        $courseOfflineClassroomSituationList = $courseOfflineClassroomSituationList->toArray();
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $courseOfflineClassroomSituationList];
    }

    /**
     * 添加线下课程年龄标签
     * @param array $params
     * @return array
     */
    public function addCourseOfflineAgeTag(array $params): array
    {
        $themeType1 = $params[1] ?? [];
        $themeType2 = $params[2] ?? [];
        $themeType3 = $params[3] ?? [];

        $insertCourseOfflineAgeTagData = [];
        foreach($themeType1 as $value){
            $courseOfflineAgeTagData['theme_type'] = 1;
            $courseOfflineAgeTagData['age_min'] = $value['age_min'];
            $courseOfflineAgeTagData['age_max'] = $value['age_max'];
            $courseOfflineAgeTagData['describe'] = $value['describe'];
            $courseOfflineAgeTagData['suit_age'] = $value['age_min'].'|'.$value['age_max'];
            $insertCourseOfflineAgeTagData[] = $courseOfflineAgeTagData;
        }
        foreach($themeType2 as $value){
            $courseOfflineAgeTagData['theme_type'] = 2;
            $courseOfflineAgeTagData['age_min'] = $value['age_min'];
            $courseOfflineAgeTagData['age_max'] = $value['age_max'];
            $courseOfflineAgeTagData['describe'] = $value['describe'];
            $courseOfflineAgeTagData['suit_age'] = $value['age_min'].'|'.$value['age_max'];
            $insertCourseOfflineAgeTagData[] = $courseOfflineAgeTagData;
        }
        foreach($themeType3 as $value){
            $courseOfflineAgeTagData['theme_type'] = 3;
            $courseOfflineAgeTagData['age_min'] = $value['age_min'];
            $courseOfflineAgeTagData['age_max'] = $value['age_max'];
            $courseOfflineAgeTagData['describe'] = $value['describe'];
            $courseOfflineAgeTagData['suit_age'] = $value['age_min'].'|'.$value['age_max'];
            $insertCourseOfflineAgeTagData[] = $courseOfflineAgeTagData;
        }
        CourseOfflineAgeTag::query()->delete();
        CourseOfflineAgeTag::query()->insert($insertCourseOfflineAgeTagData);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 编辑线下课程年龄标签
     * @param array $params
     * @return array
     */
    public function editCourseOfflineAgeTag(array $params): array
    {
        $id = $params['id'];

        $updateCourseOfflineAgeTagData['age_min'] = $params['age_min'];
        $updateCourseOfflineAgeTagData['age_max'] = $params['age_max'];
        $updateCourseOfflineAgeTagData['describe'] = $params['describe'];
        $updateCourseOfflineAgeTagData['suit_age'] = $params['age_min'].'|'.$params['age_max'];

        CourseOfflineAgeTag::query()->where(['id'=>$id])->update($updateCourseOfflineAgeTagData);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 删除线下课程年龄标签
     * @param int $id
     * @return array
     */
    public function deleteCourseOfflineAgeTag(int $id): array
    {
        CourseOfflineAgeTag::query()->where(['id'=>$id])->delete();
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 线下课程年龄标签数据
     * @return array
     */
    public function courseOfflineAgeTag(): array
    {
        $courseOfflineAgeTagList = CourseOfflineAgeTag::query()
            ->select(['id','theme_type','age_min','age_max','describe'])
            ->get();
        $courseOfflineAgeTagList = $courseOfflineAgeTagList->toArray();
        $courseOfflineAgeTagList = $this->functions->arrayGroupBy($courseOfflineAgeTagList,'theme_type');

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $courseOfflineAgeTagList];
    }

    /**
     * 添加线上课程
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function addCourseOnline(array $params): array
    {
        $insertCourseOnlineData['id'] = IdGenerator::generate();
        $insertCourseOnlineData['course_category_id'] = $params['course_category_id'] ?? 0;
        $insertCourseOnlineData['name'] = $params['name'];
        $insertCourseOnlineData['author'] = '官方';
        $insertCourseOnlineData['img_url'] = $params['img_url'];
        $insertCourseOnlineData['suit_age_min'] = $params['suit_age_min'];
        $insertCourseOnlineData['suit_age_max'] = $params['suit_age_max'];

        CourseOnline::query()->insert($insertCourseOnlineData);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 编辑线上课程
     * @param array $params
     * @return array
     * @throws \TypeError
     */
    public function editCourseOnline(array $params): array
    {
        $id = $params['id'];

        $updateCourseOnlineData['course_category_id'] = $params['course_category_id'];
        $updateCourseOnlineData['name'] = $params['name'];
        $updateCourseOnlineData['img_url'] = $params['img_url'];
        $updateCourseOnlineData['suit_age_min'] = $params['suit_age_min'];
        $updateCourseOnlineData['suit_age_max'] = $params['suit_age_max'];

        CourseOnline::query()->where(['id'=>$id])->update($updateCourseOnlineData);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 线上课程列表
     * @param array $params
     * @return array
     */
    public function courseOnlineList(array $params): array
    {
        $courseCategoryId = $params['course_category_id'];
        $name = $params['name'];
        $courseId = $params['course_id'];
        $offset = $this->offset;
        $limit = $this->limit;

        $where[] = ['is_deleted','=',0];
        if($courseCategoryId !== null){
            $where[] = ['course_category_id','=',$courseCategoryId];
        }
        if($name !== null){
            $where[] = ['name','like',"%{$name}%"];
        }
        if($courseId !== null){
            $where[] = ['id','=',$courseId];
        }

        $courseOnlineList = CourseOnline::query()
            ->select(['id','name','img_url','suit_age_min','suit_age_max','total_collect','course_category_id'])
            ->where($where)
            ->offset($offset)->limit($limit)
            ->get();
        $courseOnlineList = $courseOnlineList->toArray();

        foreach($courseOnlineList as $key=>$value){
            $courseOnlineId = $value['id'];

            $courseOnlineCollectCount1 = Db::connection('jkc_edu')->table('course_online_collect')->whereRaw('course_online_id = ? AND total_section <= study_section',[$courseOnlineId])->count('id');
            $courseOnlineCollectCount2 = Db::connection('jkc_edu')->table('course_online_collect')->whereRaw('course_online_id = ? AND total_section > study_section',[$courseOnlineId])->count('id');
            $courseOnlineList[$key]['all_study'] = $courseOnlineCollectCount1;
            $courseOnlineList[$key]['part_study'] = $courseOnlineCollectCount2;
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $courseOnlineList];
    }

    /**
     * 线上课程详情
     * @param int $id
     * @return array
     */
    public function courseOnlineDetail(int $id): array
    {
        $courseOnlineInfo = CourseOnline::query()
            ->select(['id','name','img_url','suit_age_min','suit_age_max','course_category_id'])
            ->where(['id'=>$id])
            ->first();
        if(empty($courseOnlineInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '数据错误', 'data' => null];
        }
        $courseOnlineInfo = $courseOnlineInfo->toArray();
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $courseOnlineInfo];
    }

    /**
     * 删除线上课程
     * @param int $id
     * @return array
     */
    public function deleteCourseOnline(int $id): array
    {
        CourseOnline::query()->where(['id'=>$id])->update(['is_deleted'=>1]);
        CourseOnlineChild::query()->where(['course_online_id'=>$id])->update(['is_deleted'=>1]);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 添加线上子课程
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function addCourseOnlineChild(array $params): array
    {
        $courseOnlineId = $params['course_online_id'];
        $goods = $params['goods'];
        if(!empty($goods)){
            $goodsIdArray = array_column($goods,'id');
        }
        $goodsId = !empty($goodsIdArray) ? json_encode($goodsIdArray,JSON_UNESCAPED_UNICODE) : '';
        $courseOnlineChildCount = CourseOnlineChild::query()->where(['course_online_id'=>$courseOnlineId,'is_deleted'=>0])->count('id');
        $courseOnlineChildCount += 1;

        $courseOnlineChildId = IdGenerator::generate();
        $insertCourseOnlineChildData['id'] = $courseOnlineChildId;
        $insertCourseOnlineChildData['course_online_id'] = $courseOnlineId;
        $insertCourseOnlineChildData['name'] = $params['name'];
        $insertCourseOnlineChildData['img_url'] = $params['img_url'];
        $insertCourseOnlineChildData['video_url'] = $params['video_url'];
        $insertCourseOnlineChildData['describe'] = $params['describe'] ?? '';
        $insertCourseOnlineChildData['goods_id'] = $goodsId;
        CourseOnlineChild::query()->insert($insertCourseOnlineChildData);
        CourseOnline::query()->where(['id'=>$courseOnlineId])->update(['total_section'=>$courseOnlineChildCount]);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 编辑线上子课程
     * @param array $params
     * @return array
     * @throws \TypeError
     */
    public function editCourseOnlineChild(array $params): array
    {
        $id = $params['id'];
        $goods = $params['goods'];
        if(!empty($goods)){
            $goodsIdArray = array_column($goods,'id');
        }
        $goodsId = !empty($goodsIdArray) ? json_encode($goodsIdArray,JSON_UNESCAPED_UNICODE) : '';

        $updateCourseOnlineChildData['name'] = $params['name'];
        $updateCourseOnlineChildData['img_url'] = $params['img_url'];
        $updateCourseOnlineChildData['video_url'] = $params['video_url'];
        $updateCourseOnlineChildData['describe'] = $params['describe'] ?? '';
        $updateCourseOnlineChildData['goods_id'] = $goodsId;
        CourseOnlineChild::query()->where(['id'=>$id])->update($updateCourseOnlineChildData);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 删除线上子课程
     * @param int $id
     * @return array
     */
    public function deleteCourseOnlineChild(int $id): array
    {
        $courseOnlineChildInfo = CourseOnlineChild::query()->select(['course_online_id'])->where(['id'=>$id])->first();
        $courseOnlineChildInfo = $courseOnlineChildInfo->toArray();
        $courseOnlineId = $courseOnlineChildInfo['course_online_id'];

        $courseOnlineChildCount = CourseOnlineChild::query()->where(['course_online_id'=>$courseOnlineId,'is_deleted'=>0])->count('id');
        if($courseOnlineChildCount>0){
            $courseOnlineChildCount -= 1;
        }

        CourseOnlineChild::query()->where(['id'=>$id])->update(['is_deleted'=>1]);
        CourseOnline::query()->where(['id'=>$courseOnlineId])->update(['total_section'=>$courseOnlineChildCount]);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 线上子课程列表
     * @param array $params
     * @return array
     */
    public function courseOnlineChildList(array $params): array
    {
        $courseOnlineId = $params['course_online_id'];
        $courseCategoryId = $params['course_category_id'];
        $name = $params['name'];
        $type = $params['type'];

        $where = [['course_online_child.is_deleted','=',0]];
        if($type !== null){
            $where[] = ['course_online.type','=',$type];
        }
        if($courseOnlineId !== null){
            $where[] = ['course_online_child.course_online_id','=',$courseOnlineId];
        }
        if($name !== null){
            $where[] = ['course_online_child.name','like',"%{$name}%"];
        }
        if($courseCategoryId !== null){
            $where[] = ['course_online.course_category_id','=',$courseCategoryId];
        }
        $courseOnlineChildList = CourseOnlineChild::query()
            ->leftJoin('course_online','course_online_child.course_online_id','=','course_online.id')
            ->select(['course_online_child.id','course_online_child.course_online_id','course_online_child.name','course_online_child.img_url','course_online_child.video_url','course_online_child.goods_id','course_online_child.video_url','course_online.type','course_online.suit_age_min','course_online.suit_age_max'])
            ->where($where)
            ->get();
        $courseOnlineChildList = $courseOnlineChildList->toArray();

        foreach($courseOnlineChildList as $key=>$value){
            $isReachGoods = 0;
            if(!empty($value['goods_id'])){
                $isReachGoods = 1;
            }
            $courseOnlineChildList[$key]['is_reach_goods'] = $isReachGoods;
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $courseOnlineChildList];
    }

    /**
     * 线上子课程详情
     * @param int $id
     * @return array
     */
    public function courseOnlineChildDetail(int $id): array
    {
        $courseOnlineChildInfo = CourseOnlineChild::query()
            ->select(['id','course_online_id','name','img_url','video_url','describe','goods_id'])
            ->where(['id'=>$id,'is_deleted'=>0])
            ->first();
        if(empty($courseOnlineChildInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '数据错误', 'data' => null];
        }
        $courseOnlineChildInfo = $courseOnlineChildInfo->toArray();

        $goodsList = [];
        $goodsIdArray = !empty($courseOnlineChildInfo['goods_id']) ? json_decode($courseOnlineChildInfo['goods_id'],true) : [];
        if(!empty($goodsIdArray)){
            $goodsList = Goods::query()->select(['id','name'])->whereIn('id',$goodsIdArray)->get();
            $goodsList = $goodsList->toArray();
        }

        $courseOnlineChildInfo['goods'] = $goodsList;
        unset($courseOnlineChildInfo['goods_id']);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $courseOnlineChildInfo];
    }

    /**
     * 线上子课程收藏列表
     * @param array $params
     * @return array
     */
    public function courseOnlineChildCollectList(array $params): array
    {
        $courseId = $params['course_id'];
        $courseName = $params['course_name'];
        $mobile = $params['mobile'];
        $status = $params['status'];
        $offset = $this->offset;
        $limit = $this->limit;

        $where[] = ['course_online_child_collect.status','=',$status];
        if($mobile !== null){
            $where[] = ['member.mobile','=',$mobile];
        }
        if($courseId !== null){
            $where[] = ['course_online.id','=',$courseId];
        }
        if($courseName !== null){
            $where[] = ['course_online_child_collect.name','=',$courseName];
        }

        $model = CourseOnlineChildCollect::query()
            ->leftJoin('course_online_child', 'course_online_child_collect.course_online_child_id', '=', 'course_online_child.id')
            ->leftJoin('course_online', 'course_online_child_collect.course_online_id', '=', 'course_online.id')
            ->leftJoin('member', 'course_online_child_collect.member_id', '=', 'member.id');

        $count = $model->where($where)->count();
        $courseOnlineChildCollectList = $model
            ->select(['course_online_child_collect.id','course_online_child_collect.status','member.name as member_name','member.mobile','course_online_child_collect.name','course_online_child.id as course_child_id','course_online_child_collect.study_at','course_online_child_collect.examine_at','course_online.suit_age_min','course_online.suit_age_max'])
            ->where($where)
            ->offset($offset)->limit($limit)
            ->get();
        $courseOnlineChildCollectList = $courseOnlineChildCollectList->toArray();

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => ['list'=>$courseOnlineChildCollectList,'count'=>$count]];
    }

    /**
     * 线上子课程收藏详情
     * @param int $id
     * @return array
     */
    public function courseOnlineChildCollectDetail(int $id): array
    {
        $courseOnlineChildCollectInfo = CourseOnlineChildCollect::query()
            ->leftJoin('course_online_child', 'course_online_child_collect.course_online_child_id', '=', 'course_online_child.id')
            ->leftJoin('course_online', 'course_online_child_collect.course_online_id', '=', 'course_online.id')
            ->select(['course_online.suit_age_min','course_online.suit_age_max','course_online_child_collect.id','course_online_child.name','course_online_child_collect.member_explain','course_online_child_collect.examine_explain','course_online_child_collect.study_video_url'])
            ->where(['course_online_child_collect.id'=>$id])
            ->first();
        if(empty($courseOnlineChildCollectInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '信息异常', 'data' => null];
        }
        $courseOnlineChildCollectInfo = $courseOnlineChildCollectInfo->toArray();

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $courseOnlineChildCollectInfo];
    }

    /**
     * 处理线上子课程收藏审核
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function handleCourseOnlineChildCollect(array $params): array
    {
        $courseOnlineChildCollectId = $params['id'];
        $status = $params['status'];
        $date = date('Y-m-d H:i:s');

        $courseOnlineChildCollectInfo = CourseOnlineChildCollect::query()->select(['course_online_collect_id','course_online_id','course_online_child_id'])->where(['id'=>$courseOnlineChildCollectId])->first();
        $courseOnlineChildCollectInfo = $courseOnlineChildCollectInfo->toArray();
        $courseOnlineCollectId = $courseOnlineChildCollectInfo['course_online_collect_id'];
        $courseOnlineId = $courseOnlineChildCollectInfo['course_online_id'];
        $courseOnlineChildId = $courseOnlineChildCollectInfo['course_online_child_id'];

        $updateCourseOnlineChildCollectData['status'] = $status;
        $updateCourseOnlineChildCollectData['examine_explain'] = $params['examine_explain'];
        $updateCourseOnlineChildCollectData['examine_at'] = $date;

        if($status == 2){
            Db::connection('jkc_edu')->beginTransaction();
            try{
                $courseOnlineChildCollectAffected = CourseOnlineChildCollect::query()->where(['id'=>$courseOnlineChildCollectId,'status'=>1])->update($updateCourseOnlineChildCollectData);
                if(!$courseOnlineChildCollectAffected){
                    Db::connection('jkc_edu')->rollBack();
                }
                Db::connection('jkc_edu')->update('UPDATE course_online_collect SET study_section = study_section + ? WHERE id = ?', [1, $courseOnlineCollectId]);
                Db::connection('jkc_edu')->update('UPDATE course_online SET total_study = total_study + ? WHERE id = ?', [1, $courseOnlineId]);
                Db::connection('jkc_edu')->update('UPDATE course_online_child SET total_study = total_study + ? WHERE id = ?', [1, $courseOnlineChildId]);
                Db::connection('jkc_edu')->commit();
            } catch(\Throwable $e){
                Db::connection('jkc_edu')->rollBack();
                throw new \Exception($e->getMessage(), 1);
            }
        }else if($status == 3){
            CourseOnlineChildCollect::query()->where(['id'=>$courseOnlineChildCollectId,'status'=>1])->update($updateCourseOnlineChildCollectData);
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

}