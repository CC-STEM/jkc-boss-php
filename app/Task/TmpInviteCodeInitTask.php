<?php
declare(strict_types=1);

namespace App\Task;

use App\Model\Goods;
use App\Model\Member;

class TmpInviteCodeInitTask extends BaseTask
{
    public function memberInviteCodeInitExecute(): void
    {
        $memberList = Member::query()
            ->select(['id'])
            ->where(['invite_code'=>''])
            ->offset(0)->limit(100)
            ->get();
        $memberList = $memberList->toArray();

        foreach($memberList as $value){
            $inviteCode = $this->functions->randomCode();
            $memberExists = Member::query()->where(['invite_code'=>$inviteCode])->exists();
            if($memberExists === false){
                Member::query()->where(['id'=>$value['id'],'invite_code'=>''])->update(['invite_code'=>$inviteCode]);
            }
        }
    }

    public function goodsInviteCodeInitExecute(): void
    {
        $goodsList = Goods::query()
            ->select(['id'])
            ->where(['invite_code'=>''])
            ->offset(0)->limit(100)
            ->get();
        $goodsList = $goodsList->toArray();

        foreach($goodsList as $value){
            $inviteCode = $this->functions->randomCode();
            $goodsExists = Goods::query()->where(['invite_code'=>$inviteCode])->exists();
            if($goodsExists === false){
                Goods::query()->where(['id'=>$value['id'],'invite_code'=>''])->update(['invite_code'=>$inviteCode]);
            }
        }
    }

}

