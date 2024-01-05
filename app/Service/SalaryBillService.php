<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\CourseOfflineOrder;
use App\Constants\ErrorCode;
use App\Model\TeacherSalaryBill;
use App\Model\TeacherSalaryBillDetailed;
use App\Snowflake\IdGenerator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SalaryBillService extends BaseService
{
    /**
     * 薪资账单列表
     * @param array $params
     * @return array
     */
    public function salaryBillSearchList(array $params): array
    {
        $mobile = $params['mobile'];
        $physicalStore = $params['physical_store'];
        $month = $params['month'] ?? date('Y-m');
        $month = date('Ym',strtotime($month));

        $where['teacher_salary_bill.month'] = $month;
        if($mobile !== null){
            $where['teacher.mobile'] = $mobile;
        }
        if($physicalStore !== null){
            $where['teacher.physical_store_id'] = $physicalStore;
        }
        $teacherSalaryBillList = TeacherSalaryBill::query()
            ->leftJoin('teacher','teacher_salary_bill.teacher_id','=','teacher.id')
            ->leftJoin('physical_store','teacher_salary_bill.physical_store_id','=','physical_store.id')
            ->select(['teacher_salary_bill.id','teacher_salary_bill.teacher_id','teacher_salary_bill.basic_salary','teacher_salary_bill.created_at','teacher.rank_level','teacher.rank_status','teacher.name as teacher_name','teacher.mobile','physical_store.name as physical_store_name'])
            ->where($where)
            ->get();
        $teacherSalaryBillList = $teacherSalaryBillList->toArray();
        $rankLevelEnum = ['1'=>'1星','2'=>'2星','3'=>'3星','4'=>'4星','5'=>'5星'];

        foreach($teacherSalaryBillList as $key=>$value){
            //常规班报名人数
            $courseOfflineTheme1Count1 = CourseOfflineOrder::query()
                ->whereRaw('teacher_id=? AND DATE_FORMAT(start_at, "%Y%m")=? AND order_status=0 AND pay_status=1 AND theme_type=1',[$value['teacher_id'],$month])
                ->count();
            //常规班实到人数
            $courseOfflineTheme1Count2 = CourseOfflineOrder::query()
                ->whereRaw('teacher_id=? AND DATE_FORMAT(start_at, "%Y%m")=? AND order_status=0 AND pay_status=1 AND class_status=1 AND theme_type=1',[$value['teacher_id'],$month])
                ->count();

            //精品小班报名人数
            $courseOfflineTheme2Count1 = CourseOfflineOrder::query()
                ->whereRaw('teacher_id=? AND DATE_FORMAT(start_at, "%Y%m")=? AND order_status=0 AND pay_status=1 AND theme_type=2',[$value['teacher_id'],$month])
                ->count();
            //精品小班实到人数
            $courseOfflineTheme2Count2 = CourseOfflineOrder::query()
                ->whereRaw('teacher_id=? AND DATE_FORMAT(start_at, "%Y%m")=? AND order_status=0 AND pay_status=1 AND class_status=1 AND theme_type=2',[$value['teacher_id'],$month])
                ->count();

            //竞赛班报名人数
            $courseOfflineTheme3Count1 = CourseOfflineOrder::query()
                ->whereRaw('teacher_id=? AND DATE_FORMAT(start_at, "%Y%m")=? AND order_status=0 AND pay_status=1 AND theme_type=3',[$value['teacher_id'],$month])
                ->count();
            //竞赛班实到人数
            $courseOfflineTheme3Count2 = CourseOfflineOrder::query()
                ->whereRaw('teacher_id=? AND DATE_FORMAT(start_at, "%Y%m")=? AND order_status=0 AND pay_status=1 AND class_status=1 AND theme_type=3',[$value['teacher_id'],$month])
                ->count();

            //薪资账单清单数据
            $teacherSalaryBillDetailedData = TeacherSalaryBillDetailed::query()
                ->selectRaw('sum(commission) as sum_commission,type')
                ->where(['teacher_salary_bill_id'=>$value['id'],'status'=>1])
                ->groupBy('type')
                ->get();
            $teacherSalaryBillDetailedData = $teacherSalaryBillDetailedData->toArray();
            $teacherSalaryBillDetailedData = $this->functions->arrayGroupBy($teacherSalaryBillDetailedData,'type');
            $commission1 = $teacherSalaryBillDetailedData['1'][0]['sum_commission'] ?? 0;
            $commission2 = $teacherSalaryBillDetailedData['2'][0]['sum_commission'] ?? 0;
            $commission3 = $teacherSalaryBillDetailedData['3'][0]['sum_commission'] ?? 0;
            $commission4 = $teacherSalaryBillDetailedData['4'][0]['sum_commission'] ?? 0;
            $commission5 = $teacherSalaryBillDetailedData['5'][0]['sum_commission'] ?? 0;
            $commission6 = $teacherSalaryBillDetailedData['6'][0]['sum_commission'] ?? 0;
            //薪资总计
            $totalCommission = $commission1+$commission2+$commission3+$commission4+$commission5+$commission6+$value['basic_salary'];

            $teacherSalaryBillList[$key]['month'] = date('Y.m',strtotime($value['created_at']));
            $teacherSalaryBillList[$key]['rank_status'] = $value['rank_status']==1 ? '保护期' : '正式期';
            $teacherSalaryBillList[$key]['rank_level'] = $rankLevelEnum[$value['rank_level']];
            $teacherSalaryBillList[$key]['course_offline_theme1_count1'] = $courseOfflineTheme1Count1;
            $teacherSalaryBillList[$key]['course_offline_theme1_count2'] = $courseOfflineTheme1Count2;
            $teacherSalaryBillList[$key]['course_offline_theme2_count1'] = $courseOfflineTheme2Count1;
            $teacherSalaryBillList[$key]['course_offline_theme2_count2'] = $courseOfflineTheme2Count2;
            $teacherSalaryBillList[$key]['course_offline_theme3_count1'] = $courseOfflineTheme3Count1;
            $teacherSalaryBillList[$key]['course_offline_theme3_count2'] = $courseOfflineTheme3Count2;
            $teacherSalaryBillList[$key]['commission1'] = $commission1;
            $teacherSalaryBillList[$key]['commission2'] = $commission2;
            $teacherSalaryBillList[$key]['commission3'] = $commission3;
            $teacherSalaryBillList[$key]['commission4'] = $commission4;
            $teacherSalaryBillList[$key]['commission5'] = $commission5;
            $teacherSalaryBillList[$key]['commission6'] = $commission6;
            $teacherSalaryBillList[$key]['total_commission'] = (string)$totalCommission;
        }

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $teacherSalaryBillList];
    }

    /**
     * 薪资账单导出
     * @param array $params
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function salaryBillExport(array $params): array
    {
        $mobile = $params['mobile'];
        $physicalStore = $params['physical_store'];
        $month = $params['month'] ?? date('Y-m');
        $month = date('Ym',strtotime($month));
        $fileName = 'tsl'.date('YmdHis');

        $where['teacher_salary_bill.month'] = $month;
        if($mobile !== null){
            $where['teacher.mobile'] = $mobile;
        }
        if($physicalStore !== null){
            $where['teacher.physical_store_id'] = $physicalStore;
        }
        $teacherSalaryBillList = TeacherSalaryBill::query()
            ->leftJoin('teacher','teacher_salary_bill.teacher_id','=','teacher.id')
            ->leftJoin('physical_store','teacher_salary_bill.physical_store_id','=','physical_store.id')
            ->select(['teacher_salary_bill.id','teacher_salary_bill.teacher_id','teacher_salary_bill.basic_salary','teacher_salary_bill.created_at','teacher.rank_level','teacher.rank_status','teacher.name as teacher_name','teacher.mobile','physical_store.name as physical_store_name'])
            ->where($where)
            ->get();
        $teacherSalaryBillList = $teacherSalaryBillList->toArray();
        $rankLevelEnum = ['1'=>'1星','2'=>'2星','3'=>'3星','4'=>'4星','5'=>'5星'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', '老师名称')
            ->setCellValue('B1', '手机号')
            ->setCellValue('C1', '所属门店')
            ->setCellValue('D1', '星级')
            ->setCellValue('E1', '当前状态')
            ->setCellValue('F1', '月份')
            ->setCellValue('G1', '保底薪资')
            ->setCellValue('H1', '常规课人数 实到/报名')
            ->setCellValue('I1', '常规课课时费')
            ->setCellValue('J1', '精品课人数 实到/报名')
            ->setCellValue('K1', '精品课课时费')
            ->setCellValue('L1', '竞赛课人数 实到/报名')
            ->setCellValue('M1', '竞赛课课时费')
            ->setCellValue('N1', '教具提成')
            ->setCellValue('O1', '会员卡提成')
            ->setCellValue('P1', '特殊奖励')
            ->setCellValue('Q1', '总计');
        $i=2;
        foreach($teacherSalaryBillList as $value){
            //常规班报名人数
            $courseOfflineTheme1Count1 = CourseOfflineOrder::query()
                ->whereRaw('teacher_id=? AND DATE_FORMAT(start_at, "%Y%m")=? AND order_status=0 AND pay_status=1 AND theme_type=1',[$value['teacher_id'],$month])
                ->count();
            //常规班实到人数
            $courseOfflineTheme1Count2 = CourseOfflineOrder::query()
                ->whereRaw('teacher_id=? AND DATE_FORMAT(start_at, "%Y%m")=? AND order_status=0 AND pay_status=1 AND class_status=1 AND theme_type=1',[$value['teacher_id'],$month])
                ->count();

            //精品小班报名人数
            $courseOfflineTheme2Count1 = CourseOfflineOrder::query()
                ->whereRaw('teacher_id=? AND DATE_FORMAT(start_at, "%Y%m")=? AND order_status=0 AND pay_status=1 AND theme_type=2',[$value['teacher_id'],$month])
                ->count();
            //精品小班实到人数
            $courseOfflineTheme2Count2 = CourseOfflineOrder::query()
                ->whereRaw('teacher_id=? AND DATE_FORMAT(start_at, "%Y%m")=? AND order_status=0 AND pay_status=1 AND class_status=1 AND theme_type=2',[$value['teacher_id'],$month])
                ->count();

            //竞赛班报名人数
            $courseOfflineTheme3Count1 = CourseOfflineOrder::query()
                ->whereRaw('teacher_id=? AND DATE_FORMAT(start_at, "%Y%m")=? AND order_status=0 AND pay_status=1 AND theme_type=3',[$value['teacher_id'],$month])
                ->count();
            //竞赛班实到人数
            $courseOfflineTheme3Count2 = CourseOfflineOrder::query()
                ->whereRaw('teacher_id=? AND DATE_FORMAT(start_at, "%Y%m")=? AND order_status=0 AND pay_status=1 AND class_status=1 AND theme_type=3',[$value['teacher_id'],$month])
                ->count();

            //薪资账单清单数据
            $teacherSalaryBillDetailedData = TeacherSalaryBillDetailed::query()
                ->selectRaw('sum(commission) as sum_commission,type')
                ->where(['teacher_salary_bill_id'=>$value['id'],'status'=>1])
                ->groupBy('type')
                ->get();
            $teacherSalaryBillDetailedData = $teacherSalaryBillDetailedData->toArray();
            $teacherSalaryBillDetailedData = $this->functions->arrayGroupBy($teacherSalaryBillDetailedData,'type');
            $commission1 = $teacherSalaryBillDetailedData['1'][0]['sum_commission'] ?? 0;
            $commission2 = $teacherSalaryBillDetailedData['2'][0]['sum_commission'] ?? 0;
            $commission3 = $teacherSalaryBillDetailedData['3'][0]['sum_commission'] ?? 0;
            $commission4 = $teacherSalaryBillDetailedData['4'][0]['sum_commission'] ?? 0;
            $commission5 = $teacherSalaryBillDetailedData['5'][0]['sum_commission'] ?? 0;
            $commission6 = $teacherSalaryBillDetailedData['6'][0]['sum_commission'] ?? 0;
            //薪资总计
            $totalCommission = $commission1+$commission2+$commission3+$commission4+$commission5+$commission6+$value['basic_salary'];
            $rankStatus = $value['rank_status']==1 ? '保护期' : '正式期';

            $sheet->setCellValue('A'.$i, $value['teacher_name'])
                ->setCellValue('B'.$i, $value['mobile'])
                ->setCellValue('C'.$i, $value['physical_store_name'])
                ->setCellValue('D'.$i, $rankLevelEnum[$value['rank_level']])
                ->setCellValue('E'.$i, $rankStatus)
                ->setCellValue('F'.$i, date('Y.m',strtotime($value['created_at'])))
                ->setCellValue('G'.$i, $value['basic_salary'])
                ->setCellValue('H'.$i, $courseOfflineTheme1Count2.'/'.$courseOfflineTheme1Count1)
                ->setCellValue('I'.$i, $commission1)
                ->setCellValue('J'.$i, $courseOfflineTheme2Count2.'/'.$courseOfflineTheme2Count1)
                ->setCellValue('K'.$i, $commission2)
                ->setCellValue('L'.$i, $courseOfflineTheme3Count2.'/'.$courseOfflineTheme3Count1)
                ->setCellValue('M'.$i, $commission3)
                ->setCellValue('N'.$i, $commission4)
                ->setCellValue('O'.$i, $commission5)
                ->setCellValue('P'.$i, $commission6)
                ->setCellValue('Q'.$i, $totalCommission);
            $i++;
        }

        $writer = new Xlsx($spreadsheet);
        $localPath = "/tmp/{$fileName}.xlsx";
        $writer->save($localPath);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => ['path'=>$localPath]];
    }

    /**
     * 账单详情列表
     * @param array $params
     * @return array
     */
    public function salaryBillDetailedList(array $params): array
    {
        $id = $params['id'];
        $type = $params['type'];
        $offset = $this->offset;
        $limit = $this->limit;

        switch ($type){
            case 1:
            case 2:
            case 3:
                $model = TeacherSalaryBillDetailed::query()
                    ->leftJoin('member','teacher_salary_bill_detailed.member_id','=','member.id')
                    ->select(['member.name as member_name','member.mobile','teacher_salary_bill_detailed.amount','teacher_salary_bill_detailed.commission','teacher_salary_bill_detailed.commission_rate','teacher_salary_bill_detailed.status','teacher_salary_bill_detailed.created_at'])
                    ->where(['teacher_salary_bill_detailed.teacher_salary_bill_id'=>$id,'teacher_salary_bill_detailed.type'=>$type]);
                $count = $model->count();
                $teacherSalaryBillDetailedList = $model->offset($offset)->limit($limit)->get();
                $teacherSalaryBillDetailedList = $teacherSalaryBillDetailedList->toArray();
                break;
            case 4:
                $model = TeacherSalaryBillDetailed::query()
                    ->leftJoin('order_info','teacher_salary_bill_detailed.outer_parent_id','=','order_info.id')
                    ->leftJoin('member','teacher_salary_bill_detailed.member_id','=','member.id')
                    ->select(['order_info.order_no','member.name as member_name','member.mobile','teacher_salary_bill_detailed.amount','teacher_salary_bill_detailed.commission','teacher_salary_bill_detailed.commission_rate','teacher_salary_bill_detailed.status','teacher_salary_bill_detailed.created_at'])
                    ->where(['teacher_salary_bill_detailed.teacher_salary_bill_id'=>$id,'teacher_salary_bill_detailed.type'=>$type]);
                $count = $model->count();
                $teacherSalaryBillDetailedList = $model->offset($offset)->limit($limit)->get();
                $teacherSalaryBillDetailedList = $teacherSalaryBillDetailedList->toArray();
                break;
            case 5:
                $model = TeacherSalaryBillDetailed::query()
                    ->leftJoin('vip_card_order','teacher_salary_bill_detailed.outer_id','=','vip_card_order.id')
                    ->leftJoin('member','teacher_salary_bill_detailed.member_id','=','member.id')
                    ->select(['member.name as member_name','member.mobile','vip_card_order.order_title','teacher_salary_bill_detailed.amount','teacher_salary_bill_detailed.commission','teacher_salary_bill_detailed.commission_rate','teacher_salary_bill_detailed.status','teacher_salary_bill_detailed.created_at'])
                    ->where(['teacher_salary_bill_detailed.teacher_salary_bill_id'=>$id,'teacher_salary_bill_detailed.type'=>$type]);
                $count = $model->count();
                $teacherSalaryBillDetailedList = $model->offset($offset)->limit($limit)->get();
                $teacherSalaryBillDetailedList = $teacherSalaryBillDetailedList->toArray();
                break;
            case 6:
                $model = TeacherSalaryBillDetailed::query()
                    ->select(['commission','notes','created_at'])
                    ->where(['teacher_salary_bill_id'=>$id,'type'=>$type]);
                $count = $model->count();
                $teacherSalaryBillDetailedList = $model->offset($offset)->limit($limit)->get();
                $teacherSalaryBillDetailedList = $teacherSalaryBillDetailedList->toArray();
                break;
            default:
                return ['code' => ErrorCode::WARNING, 'msg' => '参数错误', 'data' => ['list'=>[],'count'=>0]];
        }

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => ['list'=>$teacherSalaryBillDetailedList,'count'=>$count]];
    }

    /**
     * 薪资账单调整
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function salaryBillAdjust(array $params): array
    {
        $amount = $params['amount'];
        $teacherId = $params['teacher_id'];
        $notes = $params['desc'] ?? '';
        $month = date('Ym');

        //薪资账单数据
        $teacherSalaryBillInfo = TeacherSalaryBill::query()
            ->select(['id'])
            ->where(['month'=>$month,'teacher_id'=>$teacherId])
            ->first();
        if(empty($teacherSalaryBillInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '薪资异常', 'data' => null];
        }
        $teacherSalaryBillInfo = $teacherSalaryBillInfo->toArray();
        $teacherSalaryBillId = $teacherSalaryBillInfo['id'];
        //薪资账单清单数据
        $insertTeacherSalaryBillDetailedData['id'] = IdGenerator::generate();
        $insertTeacherSalaryBillDetailedData['teacher_salary_bill_id'] = $teacherSalaryBillId;
        $insertTeacherSalaryBillDetailedData['teacher_id'] = $teacherId;
        $insertTeacherSalaryBillDetailedData['outer_id'] = time();
        $insertTeacherSalaryBillDetailedData['commission'] = $amount;
        $insertTeacherSalaryBillDetailedData['type'] = 6;
        $insertTeacherSalaryBillDetailedData['notes'] = $notes;

        TeacherSalaryBillDetailed::query()->insert($insertTeacherSalaryBillDetailedData);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }
}