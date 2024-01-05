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

use App\Service\MemberEventService;

class MemberEventController extends AbstractController
{


    /**
     * 会员事件触发动作设置列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function triggerActionSetList()
    {
        try {
            $memberEventService = new MemberEventService();
            $result = $memberEventService->triggerActionSetList();
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e, 'triggerActionSetList');
        }

        return $this->responseSuccess($data, $result['msg'], $result['code']);
    }


    /**
     * 会员事件系统处理判定准则设置列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function autoHandleJudgmentCriteriaSetList()
    {
        try {
            $memberEventService = new MemberEventService();
            $result = $memberEventService->autoHandleJudgmentCriteriaSetList();
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e, 'autoHandleJudgmentCriteriaSetList');
        }

        return $this->responseSuccess($data, $result['msg'], $result['code']);
    }


    /**
     * 新增待处理事项
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function addMemberEvent()
    {
        try {
            $params = $this->request->post();
            $memberEventService = new MemberEventService();
            $result = $memberEventService->addMemberEvent($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e, 'addMemberEvent');
        }

        return $this->responseSuccess($data, $result['msg'], $result['code']);
    }


    /**
     * 编辑待处理事项
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function editMemberEvent()
    {
        try {
            $params = $this->request->post();
            $memberEventService = new MemberEventService();
            $result = $memberEventService->editMemberEvent($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e, 'editMemberEvent');
        }

        return $this->responseSuccess($data, $result['msg'], $result['code']);
    }


    /**
     * 删除待处理事项
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function deleteMemberEvent()
    {
        try {
            $params = $this->request->post();
            $id = $params['id'];
            $memberEventService = new MemberEventService();
            $result = $memberEventService->deleteMemberEvent((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e, 'deleteMemberEvent');
        }

        return $this->responseSuccess($data, $result['msg'], $result['code']);
    }


    /**
     * 待处理事项详情
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function memberEventDetail()
    {
        try {
            $id = $this->request->query('id');

            $memberEventService = new MemberEventService();
            $result = $memberEventService->memberEventDetail((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e, 'memberEventDetail');
        }

        return $this->responseSuccess($data, $result['msg'], $result['code']);
    }


    /**
     * 待处理事项列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function memberEventList()
    {
        try {
            [$page, $pageSize, $offset] = $this->getPagingParams();
            $query = $this->request->query();

            $memberEventService = new MemberEventService();
            $memberEventService->offset = $offset;
            $memberEventService->limit = $pageSize;
            $result = $memberEventService->memberEventList($query);
            $data = [
                'list' => $result['data']['list'],
                'page' => ['page' => $page, 'page_size' => $pageSize, 'count' => $result['data']['count']],
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e, 'memberEventList');
        }

        return $this->responseSuccess($data, $result['msg'], $result['code']);
    }


    /**
     * 所有待处理事项列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function allMemberEventList()
    {
        try {
            $query = $this->request->query();

            $memberEventService = new MemberEventService();
            $result = $memberEventService->allMemberEventList($query);
        } catch (\Throwable $e) {
            return $this->responseError($e, 'allMemberEventList');
        }

        return $this->responseSuccess($result['data'], $result['msg'], $result['code']);
    }


    /**
     * 客户管理table栏列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function customerTableList()
    {
        try {
            $query = $this->request->query();

            $memberEventService = new MemberEventService();
            $result = $memberEventService->customerTableList($query);
        } catch (\Throwable $e) {
            return $this->responseError($e, 'customerTableList');
        }

        return $this->responseSuccess($result['data'], $result['msg'], $result['code']);
    }


    /**
     * 客户管理列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function customerList()
    {
        try {
            [$page, $pageSize, $offset] = $this->getPagingParams();
            $query = $this->request->query();

            $memberEventService = new MemberEventService();
            $memberEventService->offset = $offset;
            $memberEventService->limit = $pageSize;
            $result = $memberEventService->customerList($query);
            $data = [
                'list' => $result['data']['list'],
                'statistics' => $result['data']['statistics'],
                'page' => ['page' => $page, 'page_size' => $pageSize, 'count' => $result['data']['count']],
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e, 'customerList');
        }

        return $this->responseSuccess($data, $result['msg'], $result['code']);
    }

    /**
     * 会员归属分配
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function allocationMemberBelongTo()
    {
        try {
            $params = $this->request->post();
            $memberEventService = new MemberEventService();
            $result = $memberEventService->allocationMemberBelongTo($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e, 'allocationMemberBelongTo');
        }

        return $this->responseSuccess($data, $result['msg'], $result['code']);
    }

    /**
     * 完成事件跟进
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function completeEventFollowup()
    {
        try {
            $params = $this->request->post();
            $memberEventService = new MemberEventService();
            $result = $memberEventService->completeEventFollowup($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e, 'completeEventFollowup');
        }

        return $this->responseSuccess($data, $result['msg'], $result['code']);
    }

    /**
     * 更新进度
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function memberFollowupNote()
    {
        try {
            $params = $this->request->post();
            $memberEventService = new MemberEventService();
            $result = $memberEventService->memberFollowupNote($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e, 'completeEventFollowup');
        }

        return $this->responseSuccess($data, $result['msg'], $result['code']);
    }

    /**
     * 会员事件跟进开关
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function memberEventSwitch()
    {
        try {
            $params = $this->request->post();
            $memberEventService = new MemberEventService();
            $result = $memberEventService->memberEventSwitch($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e, 'memberEventSwitch');
        }

        return $this->responseSuccess($data, $result['msg'], $result['code']);
    }

    /**
     * 完成事件详情
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function memberEventCompleteDetail()
    {
        try {
            $id = $this->request->query('id');

            $memberEventService = new MemberEventService();
            $result = $memberEventService->memberEventCompleteDetail((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e, 'followupList');
        }

        return $this->responseSuccess($data, $result['msg'], $result['code']);
    }

    /**
     * 进度详情列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function followupList()
    {
        try {
            [$page, $pageSize, $offset] = $this->getPagingParams();
            $query = $this->request->query();

            $memberEventService = new MemberEventService();
            $memberEventService->offset = $offset;
            $memberEventService->limit = $pageSize;
            $result = $memberEventService->followupList($query);
            $data = [
                'list' => $result['data']['list'],
                'page' => ['page' => $page, 'page_size' => $pageSize, 'count' => $result['data']['count']],
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e, 'followupList');
        }

        return $this->responseSuccess($data, $result['msg'], $result['code']);
    }


    /**
     * 处理事项列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function memberEventCompleteList()
    {
        try {
            [$page, $pageSize, $offset] = $this->getPagingParams();
            $query = $this->request->query();

            $memberEventService = new MemberEventService();
            $memberEventService->offset = $offset;
            $memberEventService->limit = $pageSize;
            $result = $memberEventService->memberEventCompleteList($query);
            $data = [
                'list' => $result['data']['list'],
                'page' => ['page' => $page, 'page_size' => $pageSize, 'count' => $result['data']['count']],
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e, 'memberEventCompleteList');
        }

        return $this->responseSuccess($data, $result['msg'], $result['code']);
    }


    /**
     * 待报名列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function memberVipCardOrderList()
    {
        try {
            [$page, $pageSize, $offset] = $this->getPagingParams();
            $query = $this->request->query();

            $memberEventService = new MemberEventService();
            $memberEventService->offset = $offset;
            $memberEventService->limit = $pageSize;
            $result = $memberEventService->memberVipCardOrderList($query);
            $data = [
                'list' => $result['data']['list'],
                'page' => ['page' => $page, 'page_size' => $pageSize, 'count' => $result['data']['count']],
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e, 'memberVipCardOrderList');
        }

        return $this->responseSuccess($data, $result['msg'], $result['code']);
    }


    /**
     * 已报名/已结束列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function memberCourseOfflineOrderList()
    {
        try {
            [$page, $pageSize, $offset] = $this->getPagingParams();
            $query = $this->request->query();

            $memberEventService = new MemberEventService();
            $memberEventService->offset = $offset;
            $memberEventService->limit = $pageSize;
            $result = $memberEventService->memberCourseOfflineOrderList($query);
            $data = [
                'list' => $result['data']['list'],
                'page' => ['page' => $page, 'page_size' => $pageSize, 'count' => $result['data']['count']],
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e, 'memberCourseOfflineOrderList');
        }

        return $this->responseSuccess($data, $result['msg'], $result['code']);
    }

}
