<?php

declare(strict_types=1);

namespace App\Cache;

use Hyperf\Redis\Redis;
use Hyperf\Utils\ApplicationContext;

class AuthCache
{
    /**
     * @var Redis|mixed|null
     */
    public $redis = null;

    /**
     * MemberCache constructor.
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function __construct()
    {
        $container = ApplicationContext::getContainer();
        $this->redis = $container->get(Redis::class);
    }

    /**
     * 设置短信验证码
     * @param int $mobile
     * @param int $code
     * @return bool
     */
    public function setSmsCode(int $mobile, int $code): bool
    {
        $key = 'boss_login_mobile_verify:'.$mobile;
        $result = $this->redis->setex($key,120,$code);
        return $result;
    }

    /**
     * 检查短信验证码
     * @param int $mobile
     * @return int
     */
    public function existsSmsCode(int $mobile): int
    {
        $key = 'boss_login_mobile_verify:'.$mobile;
        $result = $this->redis->exists($key);
        return $result;
    }

    /**
     * 获取短信验证码
     * @param int $mobile
     * @return int
     */
    public function getSmsCode(int $mobile): int
    {
        $key = 'boss_login_mobile_verify:'.$mobile;
        $data = $this->redis->get($key);
        if($data === false){
            return 0;
        }
        return (int)$data;
    }
}