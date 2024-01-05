<?php
declare(strict_types=1);

namespace App\Task;

use App\Model\Teacher;
use App\Model\TeacherSalaryRankPresets;
use Hyperf\DbConnection\Db;

class TeacherSalaryRankTask extends BaseTask
{
    /**
     * 老师薪资等级设置
     * @return void
     * @throws \Exception
     */
    public function teacherSalaryRankSetExecute(): void
    {
        $presetsDate = date('Ymd');
        $nowDate = date('Y-m-d H:i:s');

        $teacherSalaryRankPresetsList = TeacherSalaryRankPresets::query()
            ->select(['id','teacher_id','salary_template_id','rank_level','rank_status','salary_template_set_at'])
            ->where(['presets_date'=>$presetsDate,'status'=>0])
            ->get();
        $teacherSalaryRankPresetsList = $teacherSalaryRankPresetsList->toArray();

        foreach($teacherSalaryRankPresetsList as $value){
            $updateTeacherData['salary_template_id'] = $value['salary_template_id'];
            $updateTeacherData['rank_level'] = $value['rank_level'];
            $updateTeacherData['rank_status'] = $value['rank_status'];
            $updateTeacherData['salary_template_set_at'] = $value['salary_template_set_at'];
            $updateTeacherData['salary_template_use_at'] = $nowDate;

            Db::connection('jkc_edu')->beginTransaction();
            try{
                $teacherSalaryRankPresetsAffected = TeacherSalaryRankPresets::query()->where(['id'=>$value['id'],'status'=>0])->update(['status'=>1]);
                if(!$teacherSalaryRankPresetsAffected){
                    Db::connection('jkc_edu')->rollBack();
                    return;
                }
                Teacher::query()->where(['id'=>$value['teacher_id']])->update($updateTeacherData);

                Db::connection('jkc_edu')->commit();
            } catch(\Throwable $e){
                Db::connection('jkc_edu')->rollBack();
                throw new \Exception($e->getMessage(), 1);
            }
        }
    }
}

