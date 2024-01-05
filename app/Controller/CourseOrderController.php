<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace App\Controller;

use App\Service\CourseOrderService;

class CourseOrderController extends AbstractController
{
    /**
     * 约课筛选列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function courseOfflineOrderCreateScreenList()
    {
        try {
            [$page, $pageSize, $offset] = $this->getPagingParams();
            $courseType = $this->request->query('course_type',1);
            $physicalStoreId = $this->request->query('physical_store_id');
            $teacherId = $this->request->query('teacher_id');
            $classStartDateMin = $this->request->query('class_start_date_min');
            $classStartDateMax = $this->request->query('class_start_date_max');

            $params = [
                'course_type'=>$courseType,
                'physical_store_id'=>$physicalStoreId,
                'class_start_date_min'=>$classStartDateMin,
                'class_start_date_max'=>$classStartDateMax,
                'teacher_id'=>$teacherId,
            ];
            $courseOrderService = new CourseOrderService();
            $courseOrderService->offset = $offset;
            $courseOrderService->limit = $pageSize;
            $result = $courseOrderService->courseOfflineOrderCreateScreenList($params);
            $data = [
                'list' => $result['data']['list'],
                'page' => ['page' => $page, 'page_size' => $pageSize,'count'=>$result['data']['count']],
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'courseOfflineOrderCreateScreenList');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 约课会员信息
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function courseOfflineOrderCreateMemberInfo()
    {
        try {
            $mobile = $this->request->query('mobile');
            $memberId = $this->request->query('member_id');

            $params = [
                'mobile' => $mobile,
                'member_id' => $memberId,
            ];
            $courseOrderService = new CourseOrderService();
            $result = $courseOrderService->courseOfflineOrderCreateMemberInfo($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'courseOfflineOrderCreateMemberInfo');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 创建线下课程订单
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function courseOfflineCreateOrder()
    {
        try {
            $params = $this->request->post();

            $courseOrderService = new CourseOrderService();
            $result = $courseOrderService->courseOfflineCreateOrder($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'courseOfflineCreateOrder');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 调课筛选列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function courseOfflineOrderReadjustScreenList()
    {
        try {
            $courseOfflineOrderId = $this->request->query('course_offline_order_id');
            $classStartDateMin = $this->request->query('class_start_date_min');
            $classStartDateMax = $this->request->query('class_start_date_max');

            $params = [
                'course_offline_order_id'=>$courseOfflineOrderId,
                'class_start_date_min'=>$classStartDateMin,
                'class_start_date_max'=>$classStartDateMax,
            ];
            $courseOrderService = new CourseOrderService();
            $result = $courseOrderService->courseOfflineOrderReadjustScreenList($params);
            $data = [
                'list' => $result['data']
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'courseOfflineOrderReadjustScreenList');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 调课
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function courseOfflineOrderReadjust()
    {
        try {
            $params = $this->request->post();
            $courseOrderService = new CourseOrderService();
            $result = $courseOrderService->courseOfflineOrderReadjust($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'courseOfflineOrderReadjust');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 线下课程订单列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function courseOfflineOrderList()
    {
        try {
            [$page, $pageSize, $offset] = $this->getPagingParams();
            $classStatus = $this->request->query('class_status');
            $physicalStoreId = $this->request->query('physical_store_id');
            $classroomId = $this->request->query('classroom_id');
            $teacherId = $this->request->query('teacher_id');
            $classStartDateMin = $this->request->query('class_start_date_min');
            $classStartDateMax = $this->request->query('class_start_date_max');
            $memberName = $this->request->query('member_name');
            $mobile = $this->request->query('mobile');

            $params = [
                'class_status'=>$classStatus,
                'physical_store_id'=>$physicalStoreId,
                'classroom_id'=>$classroomId,
                'teacher_id'=>$teacherId,
                'class_start_date_min'=>$classStartDateMin,
                'class_start_date_max'=>$classStartDateMax,
                'member_name'=>$memberName,
                'mobile'=>$mobile,
            ];
            $courseOrderService = new CourseOrderService();
            $courseOrderService->offset = $offset;
            $courseOrderService->limit = $pageSize;
            $result = $courseOrderService->courseOfflineOrderList($params);
            $data = [
                'list' => $result['data']['list'],
                'page' => ['page' => $page, 'page_size' => $pageSize,'count'=>$result['data']['count']],
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'courseOfflineOrderList');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 线下课程订单导出
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function courseOfflineOrderExport()
    {
        try {
            $classStatus = $this->request->query('class_status');
            $physicalStoreId = $this->request->query('physical_store_id');
            $classroomId = $this->request->query('classroom_id');
            $teacherId = $this->request->query('teacher_id');
            $classStartDateMin = $this->request->query('class_start_date_min');
            $classStartDateMax = $this->request->query('class_start_date_max');
            $memberName = $this->request->query('member_name');
            $mobile = $this->request->query('mobile');

            $params = [
                'class_status'=>$classStatus,
                'physical_store_id'=>$physicalStoreId,
                'classroom_id'=>$classroomId,
                'teacher_id'=>$teacherId,
                'class_start_date_min'=>$classStartDateMin,
                'class_start_date_max'=>$classStartDateMax,
                'member_name'=>$memberName,
                'mobile'=>$mobile,
            ];
            $courseOrderService = new CourseOrderService();
            $result = $courseOrderService->courseOfflineOrderExport($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'courseOfflineOrderExport');
        }
        defer(function ()use($data){
            unlink($data['path']);
        });
        return $this->download($data['path']);
    }

    /**
     * 线下课程调课订单列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function courseOfflineOrderReadjustList()
    {
        try {
            [$page, $pageSize, $offset] = $this->getPagingParams();

            $courseOrderService = new CourseOrderService();
            $courseOrderService->offset = $offset;
            $courseOrderService->limit = $pageSize;
            $result = $courseOrderService->courseOfflineOrderReadjustList();
            $data = [
                'list' => $result['data'],
                'page' => ['page' => $page, 'page_size' => $pageSize],
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'courseOfflineOrderReadjustList');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 线下课程调课订单详情
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function courseOfflineOrderReadjustDetail()
    {
        try {
            $id = $this->request->query('id');
            $courseOrderService = new CourseOrderService();
            $result = $courseOrderService->courseOfflineOrderReadjustDetail((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'courseOfflineOrderReadjustDetail');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 线下课程调课订单处理
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function handleCourseOfflineOrderReadjust()
    {
        try {
            $params = $this->request->post();
            $courseOrderService = new CourseOrderService();
            $result = $courseOrderService->handleCourseOfflineOrderReadjust($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'handleCourseOfflineOrderReadjust');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 线下课程订单取消
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function courseOfflineOrderCancel()
    {
        try {
            $params = $this->request->post();
            $id = $params['id'];
            $courseOrderService = new CourseOrderService();
            $result = $courseOrderService->courseOfflineOrderCancel((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'courseOfflineOrderCancel');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 线下课程订单取消(特殊场景手工入口)
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function courseOfflineOrderCancelBackDoor()
    {
        try {
            $params = $this->request->post();
            $id = $params['id'];
            $courseOrderService = new CourseOrderService();
            $result = $courseOrderService->courseOfflineOrderCancelBackDoor((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'courseOfflineOrderCancel');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 评价管理列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function courseOfflineOrderEvaluationList()
    {
        try {
            [$page, $pageSize, $offset] = $this->getPagingParams();
            $memberName = $this->request->query('member_name');
            $mobile = $this->request->query('mobile');
            $classStartDateMin = $this->request->query('class_start_date_min');
            $classStartDateMax = $this->request->query('class_start_date_max');
            $createdAtStart = $this->request->query('created_at_start');
            $createdAtEnd = $this->request->query('created_at_end');
            $physicalStoreId = $this->request->query('physical_store_id');
            $teacherId = $this->request->query('teacher_id');
            $remark = $this->request->query('remark');
            $grade = $this->request->query('grade');

            $params = [
                'physical_store_id'=>$physicalStoreId,
                'class_start_date_min'=>$classStartDateMin,
                'class_start_date_max'=>$classStartDateMax,
                'teacher_id'=>$teacherId,
                'member_name'=>$memberName,
                'mobile'=>$mobile,
                'created_at_start'=>$createdAtStart,
                'created_at_end'=>$createdAtEnd,
                'remark'=>$remark,
                'grade'=>$grade,
            ];
            $courseOrderService = new CourseOrderService();
            $courseOrderService->offset = $offset;
            $courseOrderService->limit = $pageSize;
            $result = $courseOrderService->courseOfflineOrderEvaluationList($params);
            $data = [
                'list' => $result['data']['list'],
                'page' => ['page' => $page, 'page_size' => $pageSize,'count'=>$result['data']['count']],
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'courseOfflineOrderEvaluationList');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

}
