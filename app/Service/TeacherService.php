<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\MemberBelongTo;
use App\Model\Teacher;
use App\Constants\ErrorCode;
use App\Model\TeacherSalaryRankPresets;
use App\Snowflake\IdGenerator;

class TeacherService extends BaseService
{
    /**
     * 添加老师
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function addTeacher(array $params): array
    {
        $mobile = $params['mobile'];

        $teacherExists = Teacher::query()->where(['mobile'=>$mobile,'is_deleted'=>0])->exists();
        if($teacherExists === true){
            return ['code' => ErrorCode::WARNING, 'msg' => '手机号已存在', 'data' => null];
        }
        $insertTeacherData['id'] = IdGenerator::generate();
        $insertTeacherData['name'] = $params['name'];
        $insertTeacherData['mobile'] = $mobile;
        $insertTeacherData['identity'] = 2;

        Teacher::query()->insert($insertTeacherData);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 编辑老师
     * @param array $params
     * @return array
     */
    public function editTeacher(array $params): array
    {
        $id = $params['id'];
        $mobile = $params['mobile'];

        $teacherExists = Teacher::query()->where([['mobile','=',$mobile],['id','<>',$id],['is_deleted','=',0]])->exists();
        if($teacherExists === true){
            return ['code' => ErrorCode::WARNING, 'msg' => '手机号已存在', 'data' => null];
        }
        $updateTeacherData['name'] = $params['name'];
        $updateTeacherData['mobile'] = $mobile;

        Teacher::query()->where(['id'=>$id])->update($updateTeacherData);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 编辑老师职级
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function editTeacherRank(array $params): array
    {
        $id = $params['id'];
        $rankLevel = $params['rank_level'];
        $rankStatus = $params['rank_status'];
        $salaryTemplateId = $params['salary_template_id'];
        $presetsDate = date("Ym01",strtotime(date('Y-m-01 00:00:00')." +1 month"));
        $nowDate = date('Y-m-d H:i:s');

        if(empty($rankLevel) || empty($rankStatus) || empty($salaryTemplateId)){
            return ['code' => ErrorCode::WARNING, 'msg' => '参数不能为空', 'data' => null];
        }
        $teacherSalaryRankPresetsInfo = TeacherSalaryRankPresets::query()
            ->select(['salary_template_id'])
            ->where(['teacher_id'=>$id,'presets_date'=>$presetsDate])
            ->first();
        if(empty($teacherSalaryRankPresetsInfo)){
            $insertTeacherSalaryRankPresetsData['id'] = IdGenerator::generate();
            $insertTeacherSalaryRankPresetsData['teacher_id'] = $id;
            $insertTeacherSalaryRankPresetsData['salary_template_id'] = $salaryTemplateId;
            $insertTeacherSalaryRankPresetsData['rank_level'] = $rankLevel;
            $insertTeacherSalaryRankPresetsData['rank_status'] = $rankStatus;
            $insertTeacherSalaryRankPresetsData['presets_date'] = $presetsDate;
            $insertTeacherSalaryRankPresetsData['salary_template_set_at'] = $nowDate;
            TeacherSalaryRankPresets::query()->insert($insertTeacherSalaryRankPresetsData);
        }else{
            $teacherSalaryRankPresetsInfo = $teacherSalaryRankPresetsInfo->toArray();
            if($teacherSalaryRankPresetsInfo['salary_template_id'] != $salaryTemplateId){
                $updateTeacherSalaryRankPresetsData['salary_template_set_at'] = $nowDate;
            }
            $updateTeacherSalaryRankPresetsData['salary_template_id'] = $salaryTemplateId;
            $updateTeacherSalaryRankPresetsData['rank_level'] = $rankLevel;
            $updateTeacherSalaryRankPresetsData['rank_status'] = $rankStatus;
            TeacherSalaryRankPresets::query()->where(['teacher_id'=>$id,'presets_date'=>$presetsDate])->update($updateTeacherSalaryRankPresetsData);
        }

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 删除老师
     * @param int $id
     * @return array
     */
    public function deleteTeacher(int $id): array
    {
        $memberBelongToExists = MemberBelongTo::query()->where(['teacher_id'=>$id])->exists();
        if($memberBelongToExists === true){
            return ['code' => ErrorCode::WARNING, 'msg' => '老师暂时无法删除', 'data' => null];
        }

        Teacher::query()->where(['id'=>$id])->update(['is_deleted'=>1]);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 老师列表
     * @param array $params
     * @return array
     */
    public function teacherList(array $params): array
    {
        $physicalStoreId = $params['physical_store_id'];
        $teacherList = Teacher::query()
            ->select(['id','name'])
            ->where(['physical_store_id'=>$physicalStoreId,'identity'=>1,'is_deleted'=>0])
            ->get();
        $teacherList = $teacherList->toArray();
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $teacherList];
    }

    /**
     * 老师搜索列表
     * @param array $params
     * @return array
     */
    public function teacherSearchList(array $params): array
    {
        $type = $params['type'] ?? 0;
        $mobile = $params['mobile'];
        $physicalStoreId = $params['physical_store_id'];

        $where = [['teacher.is_deleted','=',0]];
        if($mobile !== null){
            $where[] = ['teacher.mobile','=',$mobile];
        }
        if($type == 0){
            if($physicalStoreId !== null){
                $where[] = ['teacher.physical_store_id','=',$physicalStoreId];
            }else{
                $where[] = ['teacher.physical_store_id','<>',0];
            }
        }
        $teacherList = Teacher::query()
            ->leftJoin('physical_store','teacher.physical_store_id','=','physical_store.id')
            ->leftJoin('salary_template','teacher.salary_template_id','=','salary_template.id')
            ->select(['teacher.id','teacher.name','teacher.mobile','teacher.rank_level','teacher.rank_status','teacher.created_at','physical_store.name as physical_store_name','salary_template.name as salary_template_name'])
            ->where($where)
            ->get();
        $teacherList = $teacherList->toArray();
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $teacherList];
    }

    /**
     * 平台老师搜索列表
     * @param array $params
     * @return array
     */
    public function platformTeacherSearchList(array $params): array
    {
        $mobile = $params['mobile'];

        $where = ['teacher.identity'=>2,'teacher.physical_store_id'=>0,'teacher.is_deleted'=>0];
        if($mobile !== null){
            $where['teacher.mobile'] = $mobile;
        }
        $teacherList = Teacher::query()
            ->leftJoin('physical_store','teacher.physical_store_id','=','physical_store.id')
            ->leftJoin('salary_template','teacher.salary_template_id','=','salary_template.id')
            ->select(['teacher.id','teacher.name','teacher.mobile','teacher.rank_level','teacher.rank_status','teacher.created_at','salary_template.name as salary_template_name'])
            ->where($where)
            ->get();
        $teacherList = $teacherList->toArray();
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $teacherList];
    }

    /**
     * 老师职级数据
     * @param int $id
     * @return array
     */
    public function teacherRankData(int $id): array
    {
        $presetsDate = date("Ym01",strtotime(date('Y-m-01 00:00:00')." +1 month"));

        $teacherSalaryRankInfo = TeacherSalaryRankPresets::query()
            ->leftJoin('teacher','teacher_salary_rank_presets.teacher_id','=','teacher.id')
            ->select(['teacher.name','teacher_salary_rank_presets.salary_template_id','teacher_salary_rank_presets.rank_level','teacher_salary_rank_presets.rank_status'])
            ->where(['teacher_salary_rank_presets.teacher_id'=>$id,'teacher_salary_rank_presets.presets_date'=>$presetsDate])
            ->first();
        if(empty($teacherSalaryRankInfo)){
            $teacherSalaryRankInfo = Teacher::query()
                ->select(['name','salary_template_id','rank_level','rank_status'])
                ->where(['id'=>$id])
                ->first();
        }
        $teacherSalaryRankInfo = $teacherSalaryRankInfo->toArray();
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $teacherSalaryRankInfo];
    }
}