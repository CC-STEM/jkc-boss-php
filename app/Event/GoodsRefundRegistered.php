<?php

declare(strict_types=1);

namespace App\Event;

class GoodsRefundRegistered
{
    public int $memberId;

    public int $orderRefundId;

    public function __construct(int $memberId,int $orderRefundId)
    {
        $this->memberId = $memberId;
        $this->orderRefundId = $orderRefundId;
    }
}