<?php
declare(strict_types=1);

namespace App\Task;

use App\Lib\WeChat\MessageFactory;
use App\Logger\Log;
use App\Model\Message;
use App\Model\WeixinMessageTemplate;

class MessageTask extends BaseTask
{
    /**
     * 消息发送
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function messageSendExecute(): void
    {
        try{
            return;
            $nowDate = date('Y-m-d H:i:s');
            $mpConfig = json_decode(env('MINIPROGRAM'), true);
            $mpAppid = $mpConfig['appId'];
            $appEnv = env('APP_ENV', 'test');
            $miniprogramStateEnum = ['dev'=>'developer','test'=>'trial','prod'=>'formal'];

            $weixinMessageList = Message::query()
                ->select(['id','touser','code','data','message_type'])
                ->where([['status','=',0],['send_at','<',$nowDate]])
                ->get();
            $weixinMessageList = $weixinMessageList->toArray();
            $messageFactory = new MessageFactory();
            $mpAccessToken = $messageFactory->getMpAccessToken();
            $oaAccessToken = $messageFactory->getOaAccessToken();
            $miniprogramPage = ['1000'=>'/pages/offcourse/list/index','1001'=>'/pages/offcourse/list/index','1002'=>'/pages/my/member/index'];
            Log::get()->info('data1111111111111:'.json_encode($weixinMessageList));

            foreach($weixinMessageList as $value){
                $id = $value['id'];
                $data = json_decode($value['data'],true);
                $messageType = $value['message_type'];
                $code = $value['code'];

                $weixinMessageTemplateInfo = WeixinMessageTemplate::query()
                    ->select(['template_id'])
                    ->where(['code'=>$value['code']])
                    ->first();
                $weixinMessageTemplateInfo = $weixinMessageTemplateInfo->toArray();

                switch ($messageType){
                    case 1:
                        $body = [
                            'touser'=>$value['touser'],
                            'template_id'=>$weixinMessageTemplateInfo['template_id'],
                            'miniprogram'=>[
                                'appid'=>$mpAppid,
                                'pagepath'=>'pages/index/index'
                            ],
                            'data'=>$data
                        ];
                        $r = $messageFactory->templateMessage($body,$oaAccessToken);
                        break;
                    case 2:
                        $body = [
                            'touser'=>$value['touser'],
                            'template_id'=>$weixinMessageTemplateInfo['template_id'],
                            'page'=> $miniprogramPage[$code] ?? 'pages/index/index',
                            'miniprogram_state'=>$miniprogramStateEnum[$appEnv],
                            'miniprogram'=>[
                                'appid'=>$mpAppid,
                                'pagepath'=>'pages/index/index'
                            ],
                            'data'=>$data
                        ];
                        $r = $messageFactory->subscribeMessage($body,$mpAccessToken);
                        break;
                }


                Log::get()->info('999999999999999999999999:'.$r);
                Message::query()->where(['id'=>$id,'status'=>0])->update(['status'=>1]);
            }
        } catch(\Throwable $e){
            $error = ['tag'=>"messageSendExecute",'msg'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()];
            Log::get()->error(json_encode($error));
        }

    }
}

