<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
return [
    'enable' => true,
    'crontab' => [
        //邀请奖励发放
        (new Hyperf\Crontab\Crontab())->setName('invitationRewardSendExecute')->setRule("* * * * *")->setCallback([\App\Task\InvitationTask::class, 'invitationRewardSendExecute'])->setMemo("invitationRewardSendExecute"),

        //会员卡过期激活预处理
        (new Hyperf\Crontab\Crontab())->setName('vipCardOrderExpirePrepareExecute')->setRule("* * * * *")->setCallback([\App\Task\VipCardOrderTask::class, 'vipCardOrderExpirePrepareExecute'])->setMemo("vipCardOrderExpirePrepareExecute"),
        //会员卡过期激活
        (new Hyperf\Crontab\Crontab())->setName('vipCardOrderExpireExecute')->setRule("* * * * *")->setCallback([\App\Task\VipCardOrderTask::class, 'vipCardOrderExpireExecute'])->setMemo("vipCardOrderExpireExecute"),
        //会员卡关闭
        (new Hyperf\Crontab\Crontab())->setName('vipCardOrderNotPayExecute')->setRule("* * * * *")->setCallback([\App\Task\VipCardOrderTask::class, 'vipCardOrderNotPayExecute'])->setMemo("vipCardOrderNotPayExecute"),
        //会员卡订单月度清算
        (new Hyperf\Crontab\Crontab())->setName('vipCardOrderMonthlyStatisticsExecute')->setRule("0 0 1 * *")->setCallback([\App\Task\VipCardOrderTask::class, 'vipCardOrderMonthlyStatisticsExecute'])->setMemo("vipCardOrderMonthlyStatisticsExecute"),

        //支付宝退款查询
        (new Hyperf\Crontab\Crontab())->setName('alipayRefundNotifyExecute')->setRule("* * * * *")->setCallback([\App\Task\PayTask::class, 'alipayRefundNotifyExecute'])->setMemo("alipayRefundNotifyExecute"),

        //老师职称等级设置
        (new Hyperf\Crontab\Crontab())->setName('teacherSalaryRankSetExecute')->setRule("*/10 * * * *")->setCallback([\App\Task\TeacherSalaryRankTask::class, 'teacherSalaryRankSetExecute'])->setMemo("teacherSalaryRankSetExecute"),
        //老师薪资月度初始化
        (new Hyperf\Crontab\Crontab())->setName('salaryBillMonthlyInitExecute')->setRule("*/20 * * * *")->setCallback([\App\Task\TeacherSalaryBillTask::class, 'salaryBillMonthlyInitExecute'])->setMemo("salaryBillMonthlyInitExecute"),
        //老师线下课程薪资计算
        (new Hyperf\Crontab\Crontab())->setName('salaryBill3SalaryCalculateExecute')->setRule("* * * * *")->setCallback([\App\Task\TeacherSalaryBillTask::class, 'salaryBill3SalaryCalculateExecute'])->setMemo("salaryBill3SalaryCalculateExecute"),
        //老师商品订单薪资计算
        (new Hyperf\Crontab\Crontab())->setName('salaryBill4SalaryCalculateExecute')->setRule("* * * * *")->setCallback([\App\Task\TeacherSalaryBillTask::class, 'salaryBill4SalaryCalculateExecute'])->setMemo("salaryBill4SalaryCalculateExecute"),
        //老师会员卡订单薪资计算
        (new Hyperf\Crontab\Crontab())->setName('salaryBill5SalaryCalculateExecute')->setRule("* * * * *")->setCallback([\App\Task\TeacherSalaryBillTask::class, 'salaryBill5SalaryCalculateExecute'])->setMemo("salaryBill5SalaryCalculateExecute"),
        //老师商品订单退款薪资计算
        (new Hyperf\Crontab\Crontab())->setName('salaryBill7SalaryCalculateExecute')->setRule("* * * * *")->setCallback([\App\Task\TeacherSalaryBillTask::class, 'salaryBill7SalaryCalculateExecute'])->setMemo("salaryBill7SalaryCalculateExecute"),
        //老师会员卡订单退款薪资计算
        (new Hyperf\Crontab\Crontab())->setName('salaryBill8SalaryCalculateExecute')->setRule("* * * * *")->setCallback([\App\Task\TeacherSalaryBillTask::class, 'salaryBill8SalaryCalculateExecute'])->setMemo("salaryBill8SalaryCalculateExecute"),

        //购卡获赠减免券
        (new Hyperf\Crontab\Crontab())->setName('payVipCardToDiscountTicketExecute')->setRule("* * * * *")->setCallback([\App\Task\DiscountTicketTask::class, 'payVipCardToDiscountTicketExecute'])->setMemo("payVipCardToDiscountTicketExecute"),

        //会员事件
        (new Hyperf\Crontab\Crontab())->setName('memberEventExecute')->setRule("*/2 * * * *")->setCallback([\App\Task\MemberEventTask::class, 'memberEventExecute'])->setMemo("memberEventExecute"),
        (new Hyperf\Crontab\Crontab())->setName('memberEventCompleteAutoHandleExecute')->setRule("*/5 * * * *")->setCallback([\App\Task\MemberEventTask::class, 'memberEventCompleteAutoHandleExecute'])->setMemo("memberEventCompleteAutoHandleExecute"),
        (new Hyperf\Crontab\Crontab())->setName('memberEventCompleteExpireHandleExecute')->setRule("*/5 * * * *")->setCallback([\App\Task\MemberEventTask::class, 'memberEventCompleteExpireHandleExecute'])->setMemo("memberEventCompleteExpireHandleExecute"),
        (new Hyperf\Crontab\Crontab())->setName('memberEventTriggerAction1003Execute')->setRule("*0 1 * * *")->setCallback([\App\Task\MemberEventDataFilterTask::class, 'memberEventTriggerAction1003Execute'])->setMemo("memberEventTriggerAction1003Execute"),
        (new Hyperf\Crontab\Crontab())->setName('memberEventTriggerAction12Execute')->setRule("*0 2 * * *")->setCallback([\App\Task\MemberEventDataFilterTask::class, 'memberEventTriggerAction12Execute'])->setMemo("memberEventTriggerAction12Execute"),

        //会员归属分配
        (new Hyperf\Crontab\Crontab())->setName('memberBelongToAllocationExecute')->setRule("*/5 * * * *")->setCallback([\App\Task\MemberBelongToTask::class, 'memberBelongToAllocationExecute'])->setMemo("memberBelongToAllocationExecute"),

        //消息下发
        (new Hyperf\Crontab\Crontab())->setName('messageSendExecute')->setRule("* * * * *")->setCallback([\App\Task\MessageTask::class, 'messageSendExecute'])->setMemo("messageSendExecute"),
        (new Hyperf\Crontab\Crontab())->setName('message1004Execute')->setRule("*/30 * * * *")->setCallback([\App\Task\MessageDataFilterTask::class, 'message1004Execute'])->setMemo("message1004Execute"),

        //tmp脚本
        //(new Hyperf\Crontab\Crontab())->setName('memberInviteCodeInitExecute')->setRule("* * * * *")->setCallback([\App\Task\TmpInviteCodeInitTask::class, 'memberInviteCodeInitExecute'])->setMemo("memberInviteCodeInitExecute"),
        //(new Hyperf\Crontab\Crontab())->setName('goodsInviteCodeInitExecute')->setRule("* * * * *")->setCallback([\App\Task\TmpInviteCodeInitTask::class, 'goodsInviteCodeInitExecute'])->setMemo("goodsInviteCodeInitExecute"),
        //(new Hyperf\Crontab\Crontab())->setName('classroomSituationExecute')->setRule("* * * * *")->setCallback([\App\Task\TmpCourseOfflineTask::class, 'classroomSituationExecute'])->setMemo("classroomSituationExecute"),
        //(new Hyperf\Crontab\Crontab())->setName('recommendTeacherExecute')->setRule("* * * * *")->setCallback([\App\Task\TmpRecommendTeacherTask::class, 'recommendTeacherExecute'])->setMemo("recommendTeacherExecute"),
        //(new Hyperf\Crontab\Crontab())->setName('vipCardOrderRefundExecute')->setRule("* * * * *")->setCallback([\App\Task\TmpRecommendTeacherTask::class, 'vipCardOrderRefundExecute'])->setMemo("vipCardOrderRefundExecute"),
        //(new Hyperf\Crontab\Crontab())->setName('vipCardOrderCourseUnitPriceExecute')->setRule("* * * * *")->setCallback([\App\Task\TmpRecommendTeacherTask::class, 'vipCardOrderCourseUnitPriceExecute'])->setMemo("vipCardOrderCourseUnitPriceExecute"),
        //(new Hyperf\Crontab\Crontab())->setName('orderRefundExecute')->setRule("* * * * *")->setCallback([\App\Task\TmpRecommendTeacherTask::class, 'orderRefundExecute'])->setMemo("orderRefundExecute"),
        //(new Hyperf\Crontab\Crontab())->setName('memberBelongToExecute')->setRule("*/2 * * * *")->setCallback([\App\Task\TmpRecommendTeacherTask::class, 'memberBelongToExecute'])->setMemo("memberBelongToExecute"),
        //(new Hyperf\Crontab\Crontab())->setName('orderGoodsAmountExecute')->setRule("*/2 * * * *")->setCallback([\App\Task\TmpTask::class, 'orderGoodsAmountExecute'])->setMemo("orderGoodsAmountExecute"),

    ],
];
