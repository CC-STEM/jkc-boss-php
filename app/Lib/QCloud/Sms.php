<?php

declare(strict_types=1);

namespace App\Lib\QCloud;

use Qcloud\Sms\SmsSingleSender;

class Sms
{
    /**
     * @var string
     */
    private string $sign = '';
    /**
     * @var SmsSingleSender
     */
    private SmsSingleSender $ssender;
    /**
     * @var array
     */
    public array $mobile = [];
    /**
     * @var string
     */
    public string $templId = '';

    /**
     * SmsService constructor.
     */
    public function __construct()
    {
        $env = json_decode(getenv('SMS'), true);
        $this->sign = $env['sign'];
        $this->ssender = new SmsSingleSender($env['appId'], $env['appKey']);
    }

    /**
     * 单条短信发送
     * @param array $params
     * @param string
     * @return string
     */
    public function singleSmsSend(array $params): string
    {
        $env = json_decode(getenv('SMS_TEMPL'), true);
        $templId = $env[$this->templId];
        $result = $this->ssender->sendWithParam('86', $this->mobile[0], $templId, $params, $this->sign);

        return $result;
    }
}