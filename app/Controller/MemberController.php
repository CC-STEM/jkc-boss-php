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

use App\Service\MemberService;

class MemberController extends AbstractController
{
    /**
     * 会员列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function memberList()
    {
        try {
            [$page, $pageSize, $offset] = $this->getPagingParams();
            $mobile = $this->request->query('mobile');
            $name = $this->request->query('member_name');
            $registerType = $this->request->query('register_type');
            $isBuyVipCard = $this->request->query('is_buy_vip_card');
            $isBuyNewGift = $this->request->query('is_buy_new_gift');
            $memberAttribute = $this->request->query('member_attribute');

            $params = [
                'mobile'=>$mobile,
                'name'=>$name,
                'register_type' => $registerType,
                'is_buy_vip_card' => $isBuyVipCard,
                'is_buy_new_gift' => $isBuyNewGift,
                'member_attribute' => $memberAttribute,
            ];
            $memberService = new MemberService();
            $memberService->offset = $offset;
            $memberService->limit = $pageSize;
            $result = $memberService->memberList($params);
            $data = [
                'list' => $result['data']['list'],
                'page' => ['page' => $page, 'page_size' => $pageSize,'count'=>$result['data']['count']],
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'memberList');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 会员详情
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function memberDetail()
    {
        try {
            $id = $this->request->query('id');
            $memberService = new MemberService();
            $result = $memberService->memberDetail((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'memberDetail');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 线上课程收藏列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function courseOnlineCollectList()
    {
        try {
            [$page, $pageSize, $offset] = $this->getPagingParams();
            $memberId = $this->request->query('member_id');

            $params = ['member_id'=>$memberId];
            $memberService = new MemberService();
            $memberService->offset = $offset;
            $memberService->limit = $pageSize;
            $result = $memberService->courseOnlineCollectList($params);
            $data = [
                'list' => $result['data'],
                'page' => ['page' => $page, 'page_size' => $pageSize],
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'courseOnlineCollectList');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 线上子课程收藏列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function courseOnlineChildCollectList()
    {
        try {
            $courseOnlineCollectId = $this->request->query('course_online_collect_id');

            $params = ['course_online_collect_id'=>$courseOnlineCollectId];
            $memberService = new MemberService();
            $result = $memberService->courseOnlineChildCollectList($params);
            $data = [
                'list' => $result['data']
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'courseOnlineChildCollectList');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 线下课程订单列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function courseOfflineOrderList()
    {
        try {
            [$page, $pageSize, $offset] = $this->getPagingParams();
            $classStatus = $this->request->query('class_status');
            $memberId = $this->request->query('member_id');

            $params = ['member_id'=>$memberId,'class_status'=>$classStatus];
            $memberService = new MemberService();
            $memberService->offset = $offset;
            $memberService->limit = $pageSize;
            $result = $memberService->courseOfflineOrderList($params);
            $data = [
                'list' => $result['data'],
                'page' => ['page' => $page, 'page_size' => $pageSize],
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'courseOfflineOrderList');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 教具订单列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function teachingAidsOrderList()
    {
        try {
            [$page, $pageSize, $offset] = $this->getPagingParams();
            $memberId = $this->request->query('member_id');

            $params = ['member_id'=>$memberId];
            $memberService = new MemberService();
            $memberService->offset = $offset;
            $memberService->limit = $pageSize;
            $result = $memberService->teachingAidsOrderList($params);
            $data = [
                'list' => $result['data'],
                'page' => ['page' => $page, 'page_size' => $pageSize],
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'teachingAidsOrderList');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 会员卡订单列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function vipCardOrderList()
    {
        try {
            [$page, $pageSize, $offset] = $this->getPagingParams();
            $memberId = $this->request->query('member_id');

            $params = ['member_id'=>$memberId];
            $memberService = new MemberService();
            $memberService->offset = $offset;
            $memberService->limit = $pageSize;
            $result = $memberService->vipCardOrderList($params);
            $data = [
                'list' => $result['data']['list'],
                'page' => ['page' => $page, 'page_size' => $pageSize],
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'vipCardOrderList');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 创建会员卡订单
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function vipCardOrderCreate()
    {
        try {
            $params = $this->request->post();
            $memberService = new MemberService();
            $result = $memberService->vipCardOrderCreate($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'vipCardOrderCreate');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 邀请关系树
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function invitationRelationTree()
    {
        try {
            $id = $this->request->query('id');

            $memberService = new MemberService();
            $result = $memberService->invitationRelationTree((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'invitationRelationTree');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 编辑会员名称
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function editMemberName()
    {
        try {
            $params = $this->request->post();
            $memberService = new MemberService();
            $result = $memberService->editMemberName($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'editMemberName');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 会员搜索列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function memberSearchList()
    {
        try {
            $query = $this->request->query();

            $memberService = new MemberService();
            $result = $memberService->memberSearchList($query);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'memberList');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }


    /**
     * 新建虚拟用户
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function addVirtualMember()
    {
        try {
            $params = $this->request->post();
            $memberService = new MemberService();
            $result = $memberService->addVirtualMember($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e, 'addVirtualMember');
        }

        return $this->responseSuccess($data, $result['msg'], $result['code']);
    }

    /**
     * 会员导出
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function memberExport()
    {
        try {
            $mobile = $this->request->query('mobile');
            $name = $this->request->query('name');
            $registerType = $this->request->query('register_type');
            $isBuyVipCard = $this->request->query('is_buy_vip_card');
            $isBuyNewGift = $this->request->query('is_buy_new_gift');
            $memberAttribute = $this->request->query('member_attribute');

            $params = [
                'mobile'=>$mobile,
                'name'=>$name,
                'register_type' => $registerType,
                'is_buy_vip_card' => $isBuyVipCard,
                'is_buy_new_gift' => $isBuyNewGift,
                'member_attribute' => $memberAttribute,
            ];
            $memberService = new MemberService();
            $result = $memberService->memberExport($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'memberExport');
        }
        defer(function ()use($data){
            unlink($data['path']);
        });
        return $this->download($data['path']);
    }

}
