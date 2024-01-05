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

use App\Service\CourseService;

class CourseController extends AbstractController
{
    /**
     * 添加线下课程
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function addCourseOffline()
    {
        try {
            $params = $this->request->post();
            $courseService = new CourseService();
            $result = $courseService->addCourseOffline($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'addCourseOffline');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 编辑线下课程
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function editCourseOffline()
    {
        try {
            $params = $this->request->post();
            $courseService = new CourseService();
            $result = $courseService->editCourseOffline($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'editCourseOffline');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 编辑线下课程
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function deleteCourseOffline()
    {
        try {
            $params = $this->request->post();
            $id = $params['id'];
            $courseService = new CourseService();
            $result = $courseService->deleteCourseOffline((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'deleteCourseOffline');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 线下课程列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function courseOfflineList()
    {
        try {
            $courseCategoryId = $this->request->query('course_category_id');

            $params = ['course_category_id'=>$courseCategoryId];
            $courseService = new CourseService();
            $result = $courseService->courseOfflineList($params);
            $data = [
                'list' => $result['data']
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'courseOfflineList');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 线下课程详情
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function courseOfflineDetail()
    {
        try {
            $id = $this->request->query('id');

            $courseService = new CourseService();
            $result = $courseService->courseOfflineDetail((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'courseOfflineDetail');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 添加线下课程排课
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function addCourseOfflinePlan()
    {
        try {
            $params = $this->request->post();
            $courseService = new CourseService();
            $result = $courseService->addCourseOfflinePlan($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'addCourseOfflinePlan');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 编辑线上课程排课
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function editCourseOfflinePlan()
    {
        try {
            $params = $this->request->post();
            $courseService = new CourseService();
            $result = $courseService->editCourseOfflinePlan($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'editCourseOfflinePlan');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 删除线下课程排课
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function deleteCourseOfflinePlan()
    {
        try {
            $params = $this->request->post();
            $id = $params['id'];
            $courseService = new CourseService();
            $result = $courseService->deleteCourseOfflinePlan((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'deleteCourseOfflinePlan');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 线下课程排课列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function courseOfflinePlanList()
    {
        try {
            [$page, $pageSize, $offset] = $this->getPagingParams();
            $classStatus = $this->request->query('class_status');
            $physicalStoreId = $this->request->query('physical_store_id');
            $classroomId = $this->request->query('classroom_id');
            $teacherId = $this->request->query('teacher_id');
            $suitAgeMin = $this->request->query('suit_age_min');
            $suitAgeMax = $this->request->query('suit_age_max');
            $classStartDateMin = $this->request->query('class_start_date_min');
            $classStartDateMax = $this->request->query('class_start_date_max');

            $params = [
                'class_status'=>$classStatus,
                'physical_store_id'=>$physicalStoreId,
                'classroom_id'=>$classroomId,
                'teacher_id'=>$teacherId,
                'suit_age_min'=>$suitAgeMin,
                'suit_age_max'=>$suitAgeMax,
                'class_start_date_min'=>$classStartDateMin,
                'class_start_date_max'=>$classStartDateMax,
            ];
            $courseService = new CourseService();
            $courseService->offset = $offset;
            $courseService->limit = $pageSize;
            $result = $courseService->courseOfflinePlanList($params);
            $data = [
                'list' => $result['data']['list'],
                'page' => ['page' => $page, 'page_size' => $pageSize,'count'=>$result['data']['count']],
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'courseOfflinePlanList');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 线下课程排课详情
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function courseOfflinePlanDetail()
    {
        try {
            $id = $this->request->query('id');

            $courseService = new CourseService();
            $result = $courseService->courseOfflinePlanDetail((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'courseOfflinePlanDetail');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 排课报名学生
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function courseOfflinePlanSignUpStudent()
    {
        try {
            $id = $this->request->query('id');

            $courseService = new CourseService();
            $result = $courseService->courseOfflinePlanSignUpStudent((int)$id);
            $data = [
                'list' => $result['data']
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'courseOfflinePlanSignUpStudent');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 排课实到学生
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function courseOfflinePlanArriveStudent()
    {
        try {
            $id = $this->request->query('id');

            $courseService = new CourseService();
            $result = $courseService->courseOfflinePlanArriveStudent((int)$id);
            $data = [
                'list' => $result['data']
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'courseOfflinePlanArriveStudent');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 排课课堂情况
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function courseOfflinePlanClassroomSituation()
    {
        try {
            $id = $this->request->query('id');

            $courseService = new CourseService();
            $result = $courseService->courseOfflinePlanClassroomSituation((int)$id);
            $data = [
                'list' => $result['data']
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'courseOfflinePlanClassroomSituation');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 添加线下课程年龄标签
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function addCourseOfflineAgeTag()
    {
        try {
            $params = $this->request->post();
            $courseService = new CourseService();
            $result = $courseService->addCourseOfflineAgeTag($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'addCourseOfflineAgeTag');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 编辑线下课程年龄标签
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function editCourseOfflineAgeTag()
    {
        try {
            $params = $this->request->post();
            $courseService = new CourseService();
            $result = $courseService->editCourseOfflineAgeTag($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'editCourseOfflineAgeTag');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 删除线下课程年龄标签
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function deleteCourseOfflineAgeTag()
    {
        try {
            $params = $this->request->post();
            $id = $params['id'];
            $courseService = new CourseService();
            $result = $courseService->deleteCourseOfflineAgeTag((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'deleteCourseOfflineAgeTag');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 线下课程年龄标签
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function courseOfflineAgeTag()
    {
        try {
            $courseService = new CourseService();
            $result = $courseService->courseOfflineAgeTag();
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'courseOfflineAgeTag');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 添加线上课程
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function addCourseOnline()
    {
        try {
            $params = $this->request->post();
            $courseService = new CourseService();
            $result = $courseService->addCourseOnline($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'addCourseOnline');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 编辑线上课程
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function editCourseOnline()
    {
        try {
            $params = $this->request->post();
            $courseService = new CourseService();
            $result = $courseService->editCourseOnline($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'editCourseOnline');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 线上课程列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function courseOnlineList()
    {
        try {
            [$page, $pageSize, $offset] = $this->getPagingParams();
            $courseCategoryId = $this->request->query('course_category_id');
            $name = $this->request->query('name');
            $courseId = $this->request->query('course_id');

            $params = ['course_category_id'=>$courseCategoryId,'name'=>$name,'course_id'=>$courseId];
            $courseService = new CourseService();
            $courseService->offset = $offset;
            $courseService->limit = $pageSize;
            $result = $courseService->courseOnlineList($params);
            $data = $result['data'];
            $data = [
                'list' => $data,
                'page' => ['page' => $page, 'page_size' => $pageSize],
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'courseOnlineList');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 线上课程详情
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function courseOnlineDetail()
    {
        try {
            $id = $this->request->query('id');

            $courseService = new CourseService();
            $result = $courseService->courseOnlineDetail((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'courseOnlineDetail');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 删除线上课程
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function deleteCourseOnline()
    {
        try {
            $params = $this->request->post();
            $id = $params['id'];
            $courseService = new CourseService();
            $result = $courseService->deleteCourseOnline((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'deleteCourseOnline');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 添加线上子课程
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function addCourseOnlineChild()
    {
        try {
            $params = $this->request->post();
            $courseService = new CourseService();
            $result = $courseService->addCourseOnlineChild($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'addCourseOnlineChild');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 编辑线上子课程
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function editCourseOnlineChild()
    {
        try {
            $params = $this->request->post();
            $courseService = new CourseService();
            $result = $courseService->editCourseOnlineChild($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'editCourseOnlineChild');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 线上子课程列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function courseOnlineChildList()
    {
        try {
            $courseOnlineId = $this->request->query('course_online_id');
            $name = $this->request->query('name');
            $type = $this->request->query('type');
            $courseCategoryId = $this->request->query('course_category_id');

            $params = ['course_online_id'=>$courseOnlineId,'name'=>$name,'type'=>$type,'course_category_id'=>$courseCategoryId];
            $courseService = new CourseService();
            $result = $courseService->courseOnlineChildList($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'courseOnlineChildList');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 线上子课程详情
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function courseOnlineChildDetail()
    {
        try {
            $id = $this->request->query('id');

            $courseService = new CourseService();
            $result = $courseService->courseOnlineChildDetail((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'courseOnlineChildDetail');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 删除线上子课程
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function deleteCourseOnlineChild()
    {
        try {
            $params = $this->request->post();
            $id = $params['id'];
            $courseService = new CourseService();
            $result = $courseService->deleteCourseOnlineChild((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'deleteCourseOnlineChild');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 线上子课程收藏列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function courseOnlineChildCollectList()
    {
        try {
            [$page, $pageSize, $offset] = $this->getPagingParams();
            $courseId = $this->request->query('course_id');
            $courseName = $this->request->query('course_name');
            $mobile = $this->request->query('mobile');
            $status = $this->request->query('status',1);

            $params = ['course_id'=>$courseId,'course_name'=>$courseName,'mobile'=>$mobile,'status'=>$status];
            $courseService = new CourseService();
            $courseService->offset = $offset;
            $courseService->limit = $pageSize;
            $result = $courseService->courseOnlineChildCollectList($params);
            $data = [
                'list' => $result['data']['list'],
                'page' => ['page' => $page, 'page_size' => $pageSize,'count'=>$result['data']['count']],
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'courseOnlineChildCollectList');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 线上子课程收藏详情
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function courseOnlineChildCollectDetail()
    {
        try {
            $id = $this->request->query('id');

            $courseService = new CourseService();
            $result = $courseService->courseOnlineChildCollectDetail((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'courseOnlineChildCollectDetail');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 处理线上子课程收藏审核
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function handleCourseOnlineChildCollect()
    {
        try {
            $params = $this->request->post();
            $courseService = new CourseService();
            $result = $courseService->handleCourseOnlineChildCollect($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'handleCourseOnlineChildCollect');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }


}
