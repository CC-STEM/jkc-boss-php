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

use App\Service\FitUpService;

class FitUpController extends AbstractController
{
    /**
     * 添加首页广告
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function addHomeAd()
    {
        try {
            $params = $this->request->post();
            $fitUpService = new FitUpService();
            $result = $fitUpService->addHomeAd($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'addHomeAd');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 首页广告列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function homeAdList()
    {
        try {
            $fitUpService = new FitUpService();
            $result = $fitUpService->homeAdList();
            $data = [
                'list' => $result['data']
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'homeAdList');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 删除首页广告
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function deleteHomeAd()
    {
        try {
            $params = $this->request->post();
            $id = $params['id'];
            $fitUpService = new FitUpService();
            $result = $fitUpService->deleteHomeAd((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'deleteHomeAd');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 添加首页推荐课程
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function addHomeBoutiqueCourse()
    {
        try {
            $params = $this->request->post();
            $fitUpService = new FitUpService();
            $result = $fitUpService->addHomeBoutiqueCourse($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'addHomeBoutiqueCourse');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 首页推荐课程列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function homeBoutiqueCourseList()
    {
        try {
            $fitUpService = new FitUpService();
            $result = $fitUpService->homeBoutiqueCourseList();
            $data = [
                'list' => $result['data']
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'homeBoutiqueCourseList');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 删除首页推荐课程
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function deleteHomeBoutiqueCourse()
    {
        try {
            $params = $this->request->post();
            $id = $params['id'];
            $fitUpService = new FitUpService();
            $result = $fitUpService->deleteHomeBoutiqueCourse((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'deleteHomeBoutiqueCourse');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 添加营销信息
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function addMarketInfo()
    {
        try {
            $params = $this->request->post();
            $fitUpService = new FitUpService();
            $result = $fitUpService->addMarketInfo($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'addMarketInfo');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 编辑营销信息
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function editMarketInfo()
    {
        try {
            $params = $this->request->post();
            $fitUpService = new FitUpService();
            $result = $fitUpService->editMarketInfo($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'editMarketInfo');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 营销信息列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function marketInfoList()
    {
        try {
            [$page, $pageSize, $offset] = $this->getPagingParams();
            $name = $this->request->query('name');

            $params = ['name'=>$name];
            $fitUpService = new FitUpService();
            $fitUpService->offset = $offset;
            $fitUpService->limit = $pageSize;
            $result = $fitUpService->marketInfoList($params);
            $data = [
                'list' => $result['data'],
                'page' => ['page' => $page, 'page_size' => $pageSize],
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'marketInfoList');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 营销信息详情
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function marketInfoDetail()
    {
        try {
            $id = $this->request->query('id');
            $fitUpService = new FitUpService();
            $result = $fitUpService->marketInfoDetail((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'marketInfoDetail');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 删除营销信息
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function deleteMarketInfo()
    {
        try {
            $params = $this->request->post();
            $id = $params['id'];
            $fitUpService = new FitUpService();
            $result = $fitUpService->deleteMarketInfo((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'deleteMarketInfo');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 添加文章主题
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function addArticleTheme()
    {
        try {
            $params = $this->request->post();
            $fitUpService = new FitUpService();
            $result = $fitUpService->addArticleTheme($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'addArticleTheme');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 文章主题列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function articleThemeList()
    {
        try {
            $type = $this->request->query('type');

            $params = ['type'=>$type];
            $fitUpService = new FitUpService();
            $result = $fitUpService->articleThemeList($params);
            $data = [
                'list' => $result['data']
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'articleThemeList');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 删除文章主题
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function deleteArticleTheme()
    {
        try {
            $params = $this->request->post();
            $id = $params['id'];
            $fitUpService = new FitUpService();
            $result = $fitUpService->deleteArticleTheme((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'deleteArticleTheme');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 添加文章
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function addArticle()
    {
        try {
            $params = $this->request->post();
            $fitUpService = new FitUpService();
            $result = $fitUpService->addArticle($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'addArticle');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 编辑文章
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function editArticle()
    {
        try {
            $params = $this->request->post();
            $fitUpService = new FitUpService();
            $result = $fitUpService->editArticle($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'editArticle');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 文章列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function articleList()
    {
        try {
            [$page, $pageSize, $offset] = $this->getPagingParams();
            $articleThemeId = $this->request->query('article_theme_id');
            $type = $this->request->query('type');

            $params = ['article_theme_id'=>$articleThemeId,'type'=>$type];
            $fitUpService = new FitUpService();
            $fitUpService->offset = $offset;
            $fitUpService->limit = $pageSize;
            $result = $fitUpService->articleList($params);
            $data = [
                'list' => $result['data'],
                'page' => ['page' => $page, 'page_size' => $pageSize],
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'articleList');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 文章详情
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function articleDetail()
    {
        try {
            $id = $this->request->query('id');
            $fitUpService = new FitUpService();
            $result = $fitUpService->articleDetail((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'articleDetail');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 删除文章
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function deleteArticle()
    {
        try {
            $params = $this->request->post();
            $id = $params['id'];
            $fitUpService = new FitUpService();
            $result = $fitUpService->deleteArticle((int)$id);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'deleteArticle');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 添加课程详情配置
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function addCourseDetailSetUp()
    {
        try {
            $params = $this->request->post();
            $fitUpService = new FitUpService();
            $result = $fitUpService->addCourseDetailSetUp($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'addCourseDetailSetUp');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 课程详情配置列表
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function courseDetailSetUpList()
    {
        try {
            $fitUpService = new FitUpService();
            $result = $fitUpService->courseDetailSetUpList();
            $data = [
                'list' => $result['data']
            ];
        } catch (\Throwable $e) {
            return $this->responseError($e,'courseDetailSetUpList');
        }
        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

}
