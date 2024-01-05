<?php

declare(strict_types=1);

namespace App\Event;

class VipCardOrderRefundRegistered
{
    public int $memberId;

    public int $vipCardOrderRefundId;

    public function __construct(int $memberId,int $vipCardOrderRefundId)
    {
        $this->memberId = $memberId;
        $this->vipCardOrderRefundId = $vipCardOrderRefundId;
    }
}