<?php

declare(strict_types=1);

namespace App\Service;

use App\Cache\AdminsCache;
use App\Model\Admins;
use App\Model\Teacher;
use App\Token\Jwt;
use App\Cache\AuthCache;
use App\Constants\ErrorCode;
use App\Lib\QCloud\Sms;
use App\Logger\Log;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Contract\SessionInterface;

class AuthService extends BaseService
{
    #[Inject]
    protected SessionInterface $session;

    /**
     * 发送短信验证码
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function smsCodeSend(array $params): array
    {
        $mobile = (int)$params['mobile'];

        $adminsInfo = Admins::query()->select(['id'])->where('mobile', $mobile)->first();
        if(empty($adminsInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '发送失败', 'data' => null];
        }
        $authCache = new AuthCache();
        $existsSmsCode = $authCache->existsSmsCode($mobile);
        if($existsSmsCode === 1){
            return ['code' => ErrorCode::WARNING, 'msg' => '发送太频繁', 'data' => null];
        }

        mt_srand();
        $code = mt_rand(10000, 99999);
        $sms = new Sms();
        $sms->mobile = [$mobile];
        $sms->templId = 'loginTemplateId';
        $result = $sms->singleSmsSend([$code,2]);
        $rsp = json_decode($result,true);
        if ($rsp['errmsg'] !== 'OK') {
            Log::get()->info("mobile[{$mobile}]:{$result}");
            return ['code' => ErrorCode::WARNING, 'msg' => '验证码发送失败，请稍后重试', 'data' => null];
        }
        $authCache->setSmsCode($mobile,$code);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 手机号登录
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function mobileLogin(array $params): array
    {
        $code = (int)$params['code'];
        $mobile = (int)$params['mobile'];

        $authCache = new AuthCache();
        $localCode = $authCache->getSmsCode($mobile);
        if($localCode !== $code){
            return ['code' => ErrorCode::WARNING, 'msg' => '验证码错误', 'data' => null];
        }

        $teacherInfo = Teacher::query()
            ->select(['id','name'])
            ->where(['mobile'=>$mobile,'is_deleted'=>0,'physical_store_id'=>0])
            ->first();
        $teacherInfo = $teacherInfo?->toArray();
        $adminsInfo = Admins::query()
            ->select(['id','name'])
            ->where('mobile', $mobile)
            ->first();
        $adminsInfo = $adminsInfo?->toArray();
        $adminsId = $adminsInfo['id'] ?? 0;
        $teacherId = $teacherInfo['id'] ?? 0;
        if($adminsId === 0 && $teacherId === 0){
            return ['code' => ErrorCode::WARNING, 'msg' => '账户不存在', 'data' => null];
        }
        $identity = 1;
        if($adminsId === 0 && $teacherId !== 0){
            $identity = 2;
        }else if($adminsId !== 0 && $teacherId !== 0){
            $identity = 3;
        }
        $adminsInfo = [
            'admins_id' => $adminsId,
            'teacher_id' => $teacherId,
            'admins_name' => $adminsInfo['name'],
            'teacher_name' => $teacherInfo['name'],
            'identity' => $identity
        ];

        $jwt = new Jwt();
        $token = $jwt->getToken([]);
        //设置登录信息
        $adminsCache = new AdminsCache();
        $adminsCache->setAdminsInfo(md5($token),$adminsInfo);
        $this->session->set('token',$token);

        $returnData = [
            'identity' => $identity
        ];
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $returnData];
    }

    /**
     * 指定管理员身份
     * @param array $params
     * @return array
     * @throws \RedisException
     */
    public function selectedAdminsIdentity(array $params): array
    {
        $identity = $params['identity'];
        $token = $params['token'];
        if(empty($token)){
            return ['code' => ErrorCode::WARNING, 'msg' => '登录失败', 'data' => null];
        }

        $adminsCache = new AdminsCache();
        $adminsCache->setAdminsInfoItem(md5($token),'identity',(string)$identity);

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 退出登录
     * @return array
     */
    public function loginOut(): array
    {
        $this->session->clear();
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

}

