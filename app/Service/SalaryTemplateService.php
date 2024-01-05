<?php

declare(strict_types=1);

namespace App\Service;

use App\Constants\ErrorCode;
use App\Logger\Log;
use App\Model\SalaryTemplate;
use App\Model\SalaryTemplateLevel;
use App\Model\Teacher;
use App\Snowflake\IdGenerator;
use Hyperf\DbConnection\Db;

class SalaryTemplateService extends BaseService
{
    /**
     * 添加薪资模板
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Throwable
     */
    public function addSalaryTemplate(array $params): array
    {
        $templateLevel = $params['template_level'];

        if(empty($templateLevel)){
            return ['code' => ErrorCode::WARNING, 'msg' => '模板信息不能为空', 'data' => null];
        }
        $salaryTemplateId = IdGenerator::generate();
        $insertSalaryTemplateData['id'] = $salaryTemplateId;
        $insertSalaryTemplateData['name'] = $params['name'];

        $insertSalaryTemplateLevelData = [];
        foreach($templateLevel as $key=>$value){
            $level = $key+1;
            $salaryTemplateLevelData['id'] = IdGenerator::generate();
            $salaryTemplateLevelData['salary_template_id'] = $salaryTemplateId;
            $salaryTemplateLevelData['protected_period_salary'] = $value['protected_period_salary'];
            $salaryTemplateLevelData['formal_period_salary'] = $value['formal_period_salary'];
            $salaryTemplateLevelData['course_theme_type1'] = $value['course_theme_type1'];
            $salaryTemplateLevelData['course_theme_type2'] = $value['course_theme_type2'];
            $salaryTemplateLevelData['course_theme_type3'] = $value['course_theme_type3'];
            $salaryTemplateLevelData['level'] = $level;
            $insertSalaryTemplateLevelData[] = $salaryTemplateLevelData;
        }

        Db::connection('jkc_edu')->transaction(function()use($insertSalaryTemplateData,$insertSalaryTemplateLevelData){
            SalaryTemplate::query()->insert($insertSalaryTemplateData);
            SalaryTemplateLevel::query()->insert($insertSalaryTemplateLevelData);
        });
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 编辑薪资模板
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function editSalaryTemplate(array $params): array
    {
        $id = $params['id'];
        $name = $params['name'];
        $templateLevel = $params['template_level'];
        $month = date('Ym');

        if(empty($templateLevel)){
            return ['code' => ErrorCode::WARNING, 'msg' => '模板信息不能为空', 'data' => null];
        }
        $teacherList = Teacher::query()
            ->leftJoin('teacher_salary_bill','teacher.id','=','teacher_salary_bill.teacher_id')
            ->select(['teacher.id','teacher.rank_level','teacher.rank_status','teacher_salary_bill.id as teacher_salary_bill_id','teacher_salary_bill.basic_salary'])
            ->where([['teacher.salary_template_id','=',$id],['teacher.rank_status','<>',0],['teacher_salary_bill.month','=',$month]])
            ->get();
        $teacherList = $teacherList->toArray();
        $teacherBasicSalaryUpdateData = [];
        foreach($teacherList as $value){
            $rankLevel = $value['rank_level'];
            $salaryTemplateLevel = [];
            foreach($templateLevel as $item){
                if($rankLevel == $item['level']){
                    $salaryTemplateLevel = $item;
                    break;
                }
            }
            if(empty($salaryTemplateLevel)){
                return ['code' => ErrorCode::WARNING, 'msg' => '修改失败:'.$value['id'], 'data' => null];
            }
            $basicSalary = $value['rank_status'] == 1 ? $salaryTemplateLevel['protected_period_salary'] : $salaryTemplateLevel['formal_period_salary'];
            if($value['basic_salary'] != $basicSalary){
                $teacherBasicSalaryUpdateData[$basicSalary][] = $value['teacher_salary_bill_id'];
            }
        }

        $insertSalaryTemplateLevelData = [];
        foreach($templateLevel as $value){
            $salaryTemplateLevelData['id'] = IdGenerator::generate();
            $salaryTemplateLevelData['salary_template_id'] = $id;
            $salaryTemplateLevelData['protected_period_salary'] = $value['protected_period_salary'];
            $salaryTemplateLevelData['formal_period_salary'] = $value['formal_period_salary'];
            $salaryTemplateLevelData['course_theme_type1'] = $value['course_theme_type1'];
            $salaryTemplateLevelData['course_theme_type2'] = $value['course_theme_type2'];
            $salaryTemplateLevelData['course_theme_type3'] = $value['course_theme_type3'];
            $salaryTemplateLevelData['level'] = $value['level'];
            $insertSalaryTemplateLevelData[] = $salaryTemplateLevelData;
        }

        Db::connection('jkc_edu')->beginTransaction();
        try{
            Db::connection('jkc_edu')->table('salary_template_level')->where(['salary_template_id'=>$id])->delete();
            Db::connection('jkc_edu')->table('salary_template')->where(['id'=>$id])->update(['name'=>$name]);
            Db::connection('jkc_edu')->table('salary_template_level')->insert($insertSalaryTemplateLevelData);

            foreach($teacherBasicSalaryUpdateData as $key=>$value){
                Db::connection('jkc_edu')->table('teacher_salary_bill')->whereIn('id',$value)->update(['basic_salary'=>$key]);
            }
            Db::connection('jkc_edu')->commit();
        } catch(\Throwable $e){
            Db::connection('jkc_edu')->rollBack();
            throw new \Exception($e->getMessage(), 1);
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 删除薪资模板
     * @param int $id
     * @return array
     */
    public function deleteSalaryTemplate(int $id): array
    {
        $checkResult = Teacher::query()->where(['salary_template_id'=>$id])->exists();
        if($checkResult === true){
            return ['code' => ErrorCode::WARNING, 'msg' => '该模板正在使用中,暂时无法删除', 'data' => null];
        }

        SalaryTemplate::query()->where(['id'=>$id])->update(['is_deleted'=>1]);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 薪资模板列表
     * @return array
     */
    public function salaryTemplateList(): array
    {
        $salaryTemplateList = SalaryTemplate::query()
            ->select(['id','name','created_at'])
            ->where(['is_deleted'=>0])
            ->get();
        $salaryTemplateList = $salaryTemplateList->toArray();

        foreach($salaryTemplateList as $key=>$value){
            $usedQuantity = Teacher::query()->where(['salary_template_id'=>$value['id']])->count();
            $templateLevelList = SalaryTemplateLevel::query()
                ->select(['protected_period_salary','formal_period_salary','course_theme_type1','course_theme_type2','course_theme_type3','level'])
                ->where(['salary_template_id'=>$value['id']])
                ->get();
            $templateLevelList = $templateLevelList->toArray();

            $salaryTemplateList[$key]['template_level'] = $templateLevelList;
            $salaryTemplateList[$key]['used_quantity'] = $usedQuantity;
        }

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $salaryTemplateList];
    }

    /**
     * 薪资模板使用列表
     * @param array $params
     * @return array
     */
    public function salaryTemplateUsedList(array $params): array
    {
        $id = $params['id'];

        $rankLevelEnum = ['1'=>'1星','2'=>'2星','3'=>'3星','4'=>'4星','5'=>'5星'];
        $teacherList = Teacher::query()
            ->leftJoin('physical_store','teacher.physical_store_id','=','physical_store.id')
            ->select(['teacher.name','teacher.mobile','teacher.rank_level','teacher.rank_status','teacher.salary_template_set_at as salary_template_use_at','physical_store.name as physical_store_name'])
            ->where(['teacher.salary_template_id'=>$id])
            ->get();
        $teacherList = $teacherList->toArray();

        foreach($teacherList as $key=>$value){
            $teacherList[$key]['rank_status'] = $value['rank_status']==1 ? '保护期' : '正式期';
            $teacherList[$key]['rank_level'] = $rankLevelEnum[$value['rank_level']];
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $teacherList];
    }

}