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

use App\Service\DiscountTicketService;

class DiscountTicketController extends AbstractController
{


    /**
     * 减免券配置详情
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function templateDetail()
    {
        try {
            $discountTicketService = new DiscountTicketService();
            $result = $discountTicketService->templateDetail();
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e, 'discountTicketTemplateDetail');
        }

        return $this->responseSuccess($data, $result['msg'], $result['code']);
    }


    /**
     * 减免券配置
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function editTemplate()
    {
        try {
            $params = $this->request->post();
            $discountTicketService = new DiscountTicketService();
            $result = $discountTicketService->editTemplate($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e, 'editDiscountTicketTemplate');
        }

        return $this->responseSuccess($data, $result['msg'], $result['code']);
    }


    /**
     * 减免券列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function list()
    {
        try {
            [$page, $pageSize, $offset] = $this->getPagingParams();
            $query = $this->request->query();

            $discountTicketService = new DiscountTicketService();
            $discountTicketService->offset = $offset;
            $discountTicketService->limit = $pageSize;
            $result = $discountTicketService->list($query);
            $data = [
                'list' => $result['data']['list'],
                'page' => ['page' => $page, 'page_size' => $pageSize, 'count' => $result['data']['count']],
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e, 'discountTicketList');
        }

        return $this->responseSuccess($data, $result['msg'], $result['code']);
    }


    /**
     * 作废减免券
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function invalid()
    {
        try {
            $params = $this->request->post();
            $id = $params['id'];
            $discountTicketService = new DiscountTicketService();
            $result = $discountTicketService->invalid((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e, 'invalidDiscountTicket');
        }

        return $this->responseSuccess($data, $result['msg'], $result['code']);
    }


    /**
     * 减免券详情
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function detail()
    {
        try {
            $id = $this->request->query('id');

            $discountTicketService = new DiscountTicketService();
            $result = $discountTicketService->detail((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e, 'discountTicketDetail');
        }

        return $this->responseSuccess($data, $result['msg'], $result['code']);
    }


    /**
     * 减免券推荐好友详情
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function inviteFriendDetail()
    {
        try {
            $id = $this->request->query('id');

            $discountTicketService = new DiscountTicketService();
            $result = $discountTicketService->inviteFriendDetail((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e, 'discountTicketInviteFriendDetail');
        }

        return $this->responseSuccess($data, $result['msg'], $result['code']);
    }


    /**
     * 减免券发放
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function issued()
    {
        try {
            $params = $this->request->post();

            $discountTicketService = new DiscountTicketService();
            $result = $discountTicketService->issued($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e, 'issuedDiscountTicket');
        }

        return $this->responseSuccess($data, $result['msg'], $result['code']);
    }

}
