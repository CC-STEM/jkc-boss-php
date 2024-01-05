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

use App\Service\TeacherService;

class TeacherController extends AbstractController
{
    /**
     * 编辑老师
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function addTeacher()
    {
        try {
            $params = $this->request->post();
            $teacherService = new TeacherService();
            $result = $teacherService->addTeacher($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'addTeacher');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 编辑老师
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function editTeacher()
    {
        try {
            $params = $this->request->post();
            $teacherService = new TeacherService();
            $result = $teacherService->editTeacher($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'editTeacher');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 编辑老师薪资等级
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function editTeacherRank()
    {
        try {
            $params = $this->request->post();
            $teacherService = new TeacherService();
            $result = $teacherService->editTeacherRank($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'editTeacherRank');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 删除老师
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function deleteTeacher()
    {
        try {
            $params = $this->request->post();
            $id = $params['id'];
            $teacherService = new TeacherService();
            $result = $teacherService->deleteTeacher((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'deleteTeacher');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 老师列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function teacherList()
    {
        try {
            $physicalStoreId = $this->request->query('physical_store_id');

            $params = ['physical_store_id'=>$physicalStoreId];
            $teacherService = new TeacherService();
            $result = $teacherService->teacherList($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'teacherList');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 老师搜索列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function teacherSearchList()
    {
        try {
            $mobile = $this->request->query('mobile');
            $physicalStoreId = $this->request->query('physical_store_id');
            $type = $this->request->query('type');

            $params = [
                'mobile'=>$mobile,
                'physical_store_id'=>$physicalStoreId,
                'type'=>$type
            ];
            $teacherService = new TeacherService();
            $result = $teacherService->teacherSearchList($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'teacherSearchList');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 平台老师搜索列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function platformTeacherSearchList()
    {
        try {
            $mobile = $this->request->query('mobile');

            $params = [
                'mobile'=>$mobile
            ];
            $teacherService = new TeacherService();
            $result = $teacherService->platformTeacherSearchList($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'platformTeacherSearchList');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 老师职级数据
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function teacherRankData()
    {
        try {
            $id = $this->request->query('id');

            $teacherService = new TeacherService();
            $result = $teacherService->teacherRankData((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'teacherRankData');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }
}
