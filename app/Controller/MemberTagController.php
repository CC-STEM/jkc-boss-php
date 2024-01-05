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

use App\Service\MemberTagService;

class MemberTagController extends AbstractController
{
    /**
     * 添加会员标签模板
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function addMemberTagTemplate()
    {
        try {
            $params = $this->request->post();
            $memberTagService = new MemberTagService();
            $result = $memberTagService->addMemberTagTemplate($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'addMemberTagTemplate');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 编辑会员标签模板
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function editMemberTagTemplate()
    {
        try {
            $params = $this->request->post();
            $memberTagService = new MemberTagService();
            $result = $memberTagService->editMemberTagTemplate($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'editMemberTagTemplate');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 删除会员标签模板
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function deleteMemberTagTemplate()
    {
        try {
            $params = $this->request->post();
            $id = $params['id'];
            $memberTagService = new MemberTagService();
            $result = $memberTagService->deleteMemberTagTemplate((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'deleteMemberTagTemplate');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 会员标签模板列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function memberTagTemplateList()
    {
        try {
            [$page, $pageSize, $offset] = $this->getPagingParams();

            $memberTagService = new MemberTagService();
            $memberTagService->offset = $offset;
            $memberTagService->limit = $pageSize;
            $result = $memberTagService->memberTagTemplateList();
            $data = [
                'list' => $result['data']['list'],
                'page' => ['page' => $page, 'page_size' => $pageSize,'count' => $result['data']['count']],
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'memberTagTemplateList');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 会员标签模板关联列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function memberTagTemplateRelationList()
    {
        try {
            [$page, $pageSize, $offset] = $this->getPagingParams();
            $id = $this->request->query('id');

            $memberTagService = new MemberTagService();
            $memberTagService->offset = $offset;
            $memberTagService->limit = $pageSize;
            $result = $memberTagService->memberTagTemplateRelationList((int)$id);
            $data = [
                'list' => $result['data']['list'],
                'page' => ['page' => $page, 'page_size' => $pageSize,'count' => $result['data']['count']],
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'memberTagTemplateRelationList');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 会员标签模板
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function memberTagTemplate()
    {
        try {
            $memberTagService = new MemberTagService();
            $result = $memberTagService->memberTagTemplate();
            $data = [
                'list' => $result['data']
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'memberTagTemplate');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 添加会员标签
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function addMemberTag()
    {
        try {
            $params = $this->request->post();
            $memberTagService = new MemberTagService();
            $result = $memberTagService->addMemberTag($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'addMemberTag');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 删除会员标签
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function deleteMemberTag()
    {
        try {
            $params = $this->request->post();
            $id = $params['id'];
            $memberTagService = new MemberTagService();
            $result = $memberTagService->deleteMemberTag((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'deleteMemberTag');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }
}
