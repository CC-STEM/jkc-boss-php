<?php
declare(strict_types=1);

namespace App\Task;

use App\Model\OrderGoods;
use App\Model\OrderInfo;

class TmpTask extends BaseTask
{
    public function orderGoodsAmountExecute(): void
    {
        $orderInfoList = OrderInfo::query()
            ->select(['id','amount'])
            ->get();

        foreach ($orderInfoList as $value){
            $id = $value['id'];

            OrderGoods::query()->where([['order_info_id','=',$id],['amount','=',0]])->update(['amount'=>$value['amount']]);
        }
    }

}

