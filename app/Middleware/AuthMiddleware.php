<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Cache\AdminsCache;
use App\Token\Jwt;
use App\Constants\ErrorCode;
use Hyperf\Contract\SessionInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Hyperf\HttpServer\Router\Dispatched;
use Hyperf\Utils\Context;
use Hyperf\Di\Annotation\Inject;

class AuthMiddleware implements MiddlewareInterface
{
    #[Inject]
    protected SessionInterface $session;

    /**
     * @var ContainerInterface
     */
    protected ContainerInterface $container;

    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @var HttpResponse
     */
    protected HttpResponse $response;

    /**
     * AuthMiddleware constructor.
     * @param ContainerInterface $container
     * @param HttpResponse $response
     * @param RequestInterface $request
     */
    public function __construct(ContainerInterface $container, HttpResponse $response, RequestInterface $request)
    {
        $this->container = $container;
        $this->response = $response;
        $this->request = $request;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws \Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        //此行代码用于联调
        //Context::set('AdminsInfo', ['admins_id'=>'1','name'=>'admin']);
        //return $handler->handle($request);

        if(empty($this->request->getAttribute(Dispatched::class)->handler)){
            $response = ['code' => ErrorCode::NOT_FOUND, 'data' => null, 'msg' => ErrorCode::getMessage(ErrorCode::NOT_FOUND)];
            return $this->response->withStatus(ErrorCode::NOT_FOUND)->json($response);
        }
        $response = ['code' => ErrorCode::UNAUTHORIZED, 'data' => null, 'msg' => ErrorCode::getMessage(ErrorCode::UNAUTHORIZED)];
        //$token = $this->request->getHeaderLine('authorization');
        //Context::set('Authorization', $token);
        $token = $this->session->get('token');
        if(empty($token)){
            return $this->response->withStatus(ErrorCode::SUCCESS)->json($response);
        }
        $jwt = new Jwt();
        $checkTokenResult = $jwt->checkToken($token);
        if($checkTokenResult['code'] === ErrorCode::FAILURE){
            return $this->response->withStatus(ErrorCode::SUCCESS)->json($response);
        }
        $adminsCache = new AdminsCache();
        $adminsData = $adminsCache->getAdminsInfo(md5($token));
        if(empty($adminsData)){
            return $this->response->withStatus(ErrorCode::SUCCESS)->json($response);
        }
        $identity = (int)$adminsData['identity'];
        if(!in_array($identity,[1,2])){
            return $this->response->withStatus(ErrorCode::SUCCESS)->json($response);
        }
        $claimValue['identity'] = $identity;
        if($identity === 1){
            $claimValue['admins_id'] = $adminsData['admins_id'];
            $claimValue['name'] = $adminsData['admins_name'];
            $claimValue['store_id'] = $adminsData['store_id'];
        }else{
            $claimValue['admins_id'] = $adminsData['teacher_id'];
            $claimValue['name'] = $adminsData['teacher_name'];
        }
        Context::set('AdminsInfo', $claimValue);
        return $handler->handle($request);
    }
}


