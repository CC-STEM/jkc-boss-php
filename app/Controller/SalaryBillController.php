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

use App\Service\SalaryBillService;

class SalaryBillController extends AbstractController
{
    /**
     * 薪资账单列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function salaryBillSearchList()
    {
        try {
            $mobile = $this->request->query('mobile');
            $physicalStore = $this->request->query('physical_store');
            $month = $this->request->query('month');

            $params = [
                'mobile'=>$mobile,
                'physical_store'=>$physicalStore,
                'month'=>$month,
            ];
            $salaryBillService = new SalaryBillService();
            $result = $salaryBillService->salaryBillSearchList($params);
            $data = [
                'list' => $result['data']
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'salaryBillSearchList');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 薪资账单导出
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function salaryBillExport()
    {
        try {
            $mobile = $this->request->query('mobile');
            $physicalStore = $this->request->query('physical_store');
            $month = $this->request->query('month');

            $params = [
                'mobile'=>$mobile,
                'physical_store'=>$physicalStore,
                'month'=>$month,
            ];
            $salaryBillService = new SalaryBillService();
            $result = $salaryBillService->salaryBillExport($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'salaryBillExport');
        }
        defer(function ()use($data){
            unlink($data['path']);
        });
        return $this->download($data['path']);
    }

    /**
     * 账单详情列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function salaryBillDetailedList()
    {
        try {
            [$page, $pageSize, $offset] = $this->getPagingParams();
            $id = $this->request->query('id');
            $type = $this->request->query('type');

            $params = [
                'id'=>$id,
                'type'=>$type
            ];
            $salaryBillService = new SalaryBillService();
            $salaryBillService->offset = $offset;
            $salaryBillService->limit = $pageSize;
            $result = $salaryBillService->salaryBillDetailedList($params);
            $data = [
                'list' => $result['data']['list'],
                'page' => ['page' => $page, 'page_size' => $pageSize,'count' => $result['data']['count']],
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'salaryBillDetailedList');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 薪资账单调整
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function salaryBillAdjust()
    {
        try {
            $params = $this->request->post();
            $salaryBillService = new SalaryBillService();
            $result = $salaryBillService->salaryBillAdjust($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'salaryBillAdjust');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }
}
