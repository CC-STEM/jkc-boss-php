<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\AuthService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;

class AuthController extends AbstractController
{
    /**
     * @var ValidatorFactoryInterface
     */
    #[Inject]
    protected $validationFactory;

    /**
     * 短信验证码发送
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function smsCodeSend()
    {
        try {
            $params = $this->request->post();
            $authService = new AuthService();
            $result = $authService->smsCodeSend($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'smsCodeSend');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 手机号登录
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function mobileLogin()
    {
        try {
            $params = $this->request->post();
            $authService = new AuthService();
            $result = $authService->mobileLogin($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'mobileLogin');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 指定管理员身份
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function selectedAdminsIdentity()
    {
        try {
            $params = $this->request->post();
            $token = $this->session->get('token');

            $params['token'] = $token;
            $authService = new AuthService();
            $result = $authService->selectedAdminsIdentity($params);
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'selectedAdminsIdentity');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

    /**
     * 退出登录
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function loginOut()
    {
        try {
            $authService = new AuthService();
            $result = $authService->loginOut();
            $data = $result['data'];
        } catch (\Throwable $e) {
            return $this->responseError($e,'loginOut');
        }

        return $this->responseSuccess($data,$result['msg'],$result['code']);
    }

}


