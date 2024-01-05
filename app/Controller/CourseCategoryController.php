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

use App\Service\CourseCategoryService;

class CourseCategoryController extends AbstractController
{
    /**
     * 添加线下课程分类
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function addCourseOfflineCategory()
    {
        try {
            $params = $this->request->post();
            $courseCategoryService = new CourseCategoryService();
            $result = $courseCategoryService->addCourseOfflineCategory($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'addCourseOfflineCategory');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 删除线下课程分类
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function deleteCourseOfflineCategory()
    {
        try {
            $params = $this->request->post();
            $id = $params['id'];
            $courseCategoryService = new CourseCategoryService();
            $result = $courseCategoryService->deleteCourseOfflineCategory((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'deleteCourseOfflineCategory');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 编辑线下课程分类名称
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function editCourseOfflineCategoryName()
    {
        try {
            $params = $this->request->post();
            $courseCategoryService = new CourseCategoryService();
            $result = $courseCategoryService->editCourseOfflineCategoryName($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'editCourseOfflineCategoryName');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 线下课程分类列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function courseOfflineCategoryList()
    {
        try {
            $parentId = $this->request->query('parent_id',0);

            $params = ['parent_id'=>$parentId];
            $courseCategoryService = new CourseCategoryService();
            $result = $courseCategoryService->courseOfflineCategoryList($params);
            $data = [
                'list' => $result['data']
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'courseOfflineCategoryList');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 线上课程分类列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function courseOnlineCategoryList()
    {
        try {
            $parentId = $this->request->query('parent_id',0);

            $params = ['parent_id'=>$parentId];
            $courseCategoryService = new CourseCategoryService();
            $result = $courseCategoryService->courseOnlineCategoryList($params);
            $data = [
                'list' => $result['data']
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'courseOnlineCategoryList');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }
}
