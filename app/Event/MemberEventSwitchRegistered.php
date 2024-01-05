<?php

declare(strict_types=1);

namespace App\Event;

class MemberEventSwitchRegistered
{
    public int $memberId;

    public int $isClose;

    public function __construct(int $memberId, int $isClose)
    {
        $this->memberId = $memberId;
        $this->isClose = $isClose;
    }
}