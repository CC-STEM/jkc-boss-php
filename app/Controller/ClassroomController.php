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

use App\Service\ClassroomService;

class ClassroomController extends AbstractController
{
    /**
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function classroomList()
    {
        try {
            $physicalStoreId = $this->request->query('physical_store_id');

            $params = ['physical_store_id'=>$physicalStoreId];
            $classroomService = new ClassroomService();
            $result = $classroomService->classroomList($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'classroomList');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }
}
