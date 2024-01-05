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

use App\Constants\ErrorCode;
use App\Service\GoodsService;

class GoodsController extends AbstractController
{
    /**
     * 添加教具商品
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function addTeachingAidsGoods()
    {
        try {
            $params = $this->request->post();
            $goodsService = new GoodsService();
            $result = $goodsService->addTeachingAidsGoods($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'addTeachingAidsGoods');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 编辑教具商品
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function editTeachingAidsGoods()
    {
        try {
            $params = $this->request->post();
            $goodsService = new GoodsService();
            $result = $goodsService->editTeachingAidsGoods($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'editTeachingAidsGoods');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 上架教具商品
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function onlineTeachingAidsGoods()
    {
        try {
            $params = $this->request->post();
            $goodsId = $params['id'];
            $goodsService = new GoodsService();
            $result = $goodsService->onlineTeachingAidsGoods((int)$goodsId);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'onlineTeachingAidsGoods');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 下架教具商品
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function offlineTeachingAidsGoods()
    {
        try {
            $params = $this->request->post();
            $goodsId = $params['id'];
            $goodsService = new GoodsService();
            $result = $goodsService->offlineTeachingAidsGoods((int)$goodsId);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'offlineTeachingAidsGoods');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 删除教具商品
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function deleteTeachingAidsGoods()
    {
        try {
            $params = $this->request->post();
            $id = $params['id'];
            $goodsService = new GoodsService();
            $result = $goodsService->deleteTeachingAidsGoods((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'deleteTeachingAidsGoods');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 置顶教具商品
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function toppingTeachingAidsGoods()
    {
        try {
            $params = $this->request->post();
            $id = $params['id'];
            $goodsService = new GoodsService();
            $result = $goodsService->toppingTeachingAidsGoods((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'toppingTeachingAidsGoods');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 教具商品列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function teachingAidsGoodsList()
    {
        try {
            [$page, $pageSize, $offset] = $this->getPagingParams();
            $id = $this->request->query('id');
            $category = $this->request->query('category');
            $name = $this->request->query('name');
            $online = $this->request->query('online');

            $params = ['id'=>$id,'category'=>$category,'name'=>$name,'online'=>$online];
            $goodsService = new GoodsService();
            $goodsService->offset = $offset;
            $goodsService->limit = $pageSize;
            $result = $goodsService->teachingAidsGoodsList($params);
            $data = [
                'list' => $result['data']['list'],
                'page' => ['page' => $page, 'page_size' => $pageSize,'count' => $result['data']['count']],
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'teachingAidsGoodsList');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 教具商品详情
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function teachingAidsGoodsDetail()
    {
        try {
            $id = $this->request->query('id');
            $goodsService = new GoodsService();
            $result = $goodsService->teachingAidsGoodsDetail((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'teachingAidsGoodsDetail');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 添加商品规格名称
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function addPropName()
    {
        try {
            $params = $this->request->post();
            $goodsService = new GoodsService();
            $result = $goodsService->addPropName($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'addPropName');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 商品规格名称列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function propNameList()
    {
        try {
            $goodsService = new GoodsService();
            $result = $goodsService->propNameList();
            $data = [
                'list' => $result['data']
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'propNameList');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 添加商品规格名称
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function addPropValue()
    {
        try {
            $params = $this->request->post();
            $goodsService = new GoodsService();
            $result = $goodsService->addPropValue($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'addPropValue');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 商品规格值列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function propValueList()
    {
        try {
            $parentId = $this->request->query('parent_id');

            $params = ['parent_id'=>$parentId];
            $goodsService = new GoodsService();
            $result = $goodsService->propValueList($params);
            $data = [
                'list' => $result['data']
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'propValueList');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 更改虚拟销量
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function updateShamCsale()
    {
        try {
            $params = $this->request->post();

            if (empty($params['id']) || empty($params['sham_csale'])) {
                return $this->responseSuccess(null, '参数有误！', ErrorCode::WARNING);
            }
            if ($params['sham_csale'] < 0) {
                return $this->responseSuccess(null, '不能小于0！', ErrorCode::WARNING);
            }

            $goodsService = new GoodsService();
            $result = $goodsService->updateShamCsale($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'updateShamCSale');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

}
