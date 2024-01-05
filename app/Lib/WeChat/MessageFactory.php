<?php

declare(strict_types=1);

namespace App\Lib\WeChat;

use App\Logger\Log;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;

class MessageFactory
{
    #[Inject]
    private ClientFactory $guzzleClientFactory;

    /**
     * 获取公众号token
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getOaAccessToken(): string
    {
        $config = json_decode(env('OFFICIALACCOUNTS'), true);
        $appId = $config['appId'];
        $appSecret = $config['appSecret'];
        $client = $this->guzzleClientFactory->create();
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appId}&secret={$appSecret}";
        $response = $client->request('GET', $url);
        $r = $response->getBody()->getContents();
        Log::get()->info('data2222222222222222222:'.$r);
        $data = json_decode($r,true);
        return $data['access_token'];
    }

    /**
     * 获取小程序token
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getMpAccessToken(): string
    {
        $config = json_decode(env('MINIPROGRAM'), true);
        $appId = $config['appId'];
        $appSecret = $config['appSecret'];
        $client = $this->guzzleClientFactory->create();
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appId}&secret={$appSecret}";
        $response = $client->request('GET', $url);
        $r = $response->getBody()->getContents();
        $data = json_decode($r,true);
        return $data['access_token'];
    }

    /**
     * 公众号模板消息
     * @param array $body
     * @param string $accessToken
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function templateMessage(array $body,string $accessToken): string
    {
        $client = $this->guzzleClientFactory->create();
        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$accessToken}";
        $response = $client->request('POST', $url,[
            'json' => $body
        ]);
        return $response->getBody()->getContents();
    }

    /**
     * 小程序订阅消息
     * @param array $body
     * @param string $accessToken
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function subscribeMessage(array $body,string $accessToken): string
    {
        $client = $this->guzzleClientFactory->create();
        $url = "https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token={$accessToken}";
        $response = $client->request('POST', $url,[
            'json' => $body
        ]);
        return $response->getBody()->getContents();
    }
}