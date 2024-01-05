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

use App\Service\SalaryTemplateService;

class SalaryTemplateController extends AbstractController
{
    /**
     * 添加薪资模板
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function addSalaryTemplate()
    {
        try {
            $params = $this->request->post();
            $salaryTemplateService = new SalaryTemplateService();
            $result = $salaryTemplateService->addSalaryTemplate($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'addSalaryTemplate');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 编辑薪资模板
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function editSalaryTemplate()
    {
        try {
            $params = $this->request->post();
            $salaryTemplateService = new SalaryTemplateService();
            $result = $salaryTemplateService->editSalaryTemplate($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'editSalaryTemplate');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 删除薪资模板
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function deleteSalaryTemplate()
    {
        try {
            $params = $this->request->post();
            $id = $params['id'];
            $salaryTemplateService = new SalaryTemplateService();
            $result = $salaryTemplateService->deleteSalaryTemplate((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'deleteSalaryTemplate');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 薪资模板列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function salaryTemplateList()
    {
        try {
            $salaryTemplateService = new SalaryTemplateService();
            $result = $salaryTemplateService->salaryTemplateList();
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'salaryTemplateList');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 薪资模板使用列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function salaryTemplateUsedList()
    {
        try {
            $id = $this->request->query('id');

            $params = [
                'id'=>$id
            ];
            $salaryTemplateService = new SalaryTemplateService();
            $result = $salaryTemplateService->salaryTemplateUsedList($params);
            $data = [
                'list' => $result['data']
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'salaryTemplateUsedList');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }
}
