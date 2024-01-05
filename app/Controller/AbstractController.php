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

use App\Logger\Log;
use App\Constants\ErrorCode;
use Hyperf\Contract\SessionInterface;
use Hyperf\Utils\Context;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Container\ContainerInterface;

abstract class AbstractController
{
    /**
     * @var ContainerInterface
     */
    #[Inject]
    protected ContainerInterface $container;

    /**
     * @var RequestInterface
     */
    #[Inject]
    protected RequestInterface $request;

    /**
     * @var ResponseInterface
     */
    #[Inject]
    protected ResponseInterface $response;

    /**
     * @var SessionInterface
     */
    #[Inject]
    protected SessionInterface $session;

    /**
     * 返回成功响应信息
     * @param array|null $data
     * @param int $code
     * @param string $msg
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function responseSuccess(?array $data = null, string $msg = 'SUCCESS', int $code = ErrorCode::SUCCESS): \Psr\Http\Message\ResponseInterface
    {
        $responseData['data'] = $data;
        $responseData['code'] = $code;
        $responseData['msg']  = $msg;
        $response = $this->_setHeaders();
        return $response->json($responseData);
    }

    /**
     * 返回系统失败响应信息
     * @param \Throwable $throwable
     * @param string $tag
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function responseError(\Throwable $throwable, string $tag = ''): \Psr\Http\Message\ResponseInterface
    {
        $responseData['data'] = null;
        $responseData['code'] = ErrorCode::FAILURE;
        $responseData['msg']  = ErrorCode::getMessage(ErrorCode::SERVER_ERROR);
        $error = $tag.':'.$throwable->getMessage().', file:'.$throwable->getFile().', line:'.$throwable->getLine();
        Log::get()->error($error);
        $response = $this->_setHeaders();
        return $response->withStatus(ErrorCode::SERVER_ERROR)->json($responseData);
    }

    /**
     * 自定义响应信息
     * @param array $data
     * @param int $code
     * @param null|\Throwable $throwable
     * @param string $tag
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function response(array $data = [], int $code = 200, ?\Throwable $throwable = null, string $tag = ''): \Psr\Http\Message\ResponseInterface
    {
        if($throwable !== null){
            $error = $tag.':'.$throwable->getMessage();
            Log::get()->error($error);
        }
        $response = $this->_setHeaders();
        return $response->withStatus($code)->json($data);
    }

    /**
     * 文件下载
     * @param string $file
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function download(string $file): \Psr\Http\Message\ResponseInterface
    {
        $response = $this->_setHeaders();
        return $response->download($file);
    }

    /**
     * @return ResponseInterface
     */
    private function _setHeaders(): ResponseInterface
    {
        $default = ['Access-Control-Max-Age'=>86400];
        $response = $this->response;
        $headers = Context::get('headers', []);
        $headers = array_merge($headers,$default);
        foreach($headers as $key=>$value){
            $response = $response->withHeader($key, $value);
        }
        return $response;
    }

    /**
     * @return int[]
     */
    protected function getPagingParams(): array
    {
        $page = $this->request->query('page',1);
        $pageSize = $this->request->query('page_size',10);
        $pageSize = $pageSize > 20 ? 20 : $pageSize;
        $offset = ($page - 1) * $pageSize;
        return [(int)$page, (int)$pageSize, (int)$offset];
    }

}
