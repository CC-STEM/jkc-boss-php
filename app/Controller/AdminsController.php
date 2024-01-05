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

use App\Service\AdminsService;

class AdminsController extends AbstractController
{
    /**
     * 管理员信息
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function adminsInfo()
    {
        try {
            $adminsService = new AdminsService();
            $result = $adminsService->adminsInfo();
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'adminsInfo');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

}
