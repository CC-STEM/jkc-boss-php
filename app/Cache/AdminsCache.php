<?php

declare(strict_types=1);

namespace App\Cache;

use Hyperf\Redis\Redis;
use Hyperf\Utils\ApplicationContext;

class AdminsCache
{
    /**
     * @var Redis|mixed
     */
    public $redis;

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __construct()
    {
        $container = ApplicationContext::getContainer();
        $this->redis = $container->get(Redis::class);
    }

    /**
     * 设置管理员信息
     * @param string $token
     * @param array $data
     * @return void
     * @throws \RedisException
     */
    public function setAdminsInfo(string $token, array $data): void
    {
        $key = 'bos_admins_info:'.$token;
        $this->redis->hMSet($key,$data);
        $this->redis->expire($key,48*3600);
    }

    /**
     * 设置管理员信息元素
     * @param string $token
     * @param string $hashKey
     * @param string $value
     * @return void
     * @throws \RedisException
     */
    public function setAdminsInfoItem(string $token, string $hashKey, string $value): void
    {
        $key = 'bos_admins_info:'.$token;
        $this->redis->hSet($key,$hashKey,$value);
    }

    /**
     * 获取管理员信息
     * @param string $token
     * @return array
     * @throws \RedisException
     */
    public function getAdminsInfo(string $token): array
    {
        $key = 'bos_admins_info:'.$token;
        return $this->redis->hGetAll($key);
    }
}