<?php

declare(strict_types=1);

namespace App\Service;

use App\Constants\ErrorCode;
use App\Logger\Log;
use App\Model\DiscountTicket;
use App\Model\DiscountTicketPhysicalStore;
use App\Model\DiscountTicketTemplate;
use App\Model\DiscountTicketTemplatePhysicalStore;
use App\Model\DiscountTicketTemplateVipCard;
use App\Model\DiscountTicketVipCard;
use App\Model\Member;
use App\Model\VipCard;
use App\Model\VipCardOrder;
use App\Model\VipCardOrderOfferInfo;
use App\Snowflake\IdGenerator;
use Hyperf\DbConnection\Db;

class DiscountTicketService extends BaseService
{


    /**
     * 减免券配置详情
     * @param
     * @return array
     */
    public function templateDetail(): array
    {
        $data = DiscountTicketTemplate::query()->select(['id', 'name', 'img_url', 'amount', 'expire', 'start_at', 'end_at', 'describe'])->first();
        if (empty($data)) {
            return ['code' => 0, 'msg' => '暂无配置', 'data' => null];
        }
        $data = $data->toArray();

        $physicalStoreList = DiscountTicketTemplatePhysicalStore::query()->select(['physical_store_id'])->where(['discount_ticket_template_id' => $data['id']])->get();
        $physicalStoreList = $physicalStoreList->toArray();
        $physicalStoreIdArray = !empty($physicalStoreList) ? array_column($physicalStoreList, 'physical_store_id') : [];
        $data['physical_store'] = $physicalStoreIdArray;

        $vipCardList = DiscountTicketTemplateVipCard::query()->select(['vip_card_id'])->where(['discount_ticket_template_id' => $data['id']])->get();
        $vipCardList = $vipCardList->toArray();
        $vipCardIdArray = !empty($vipCardList) ? array_column($vipCardList, 'vip_card_id') : [];
        $data['vip_card'] = $vipCardIdArray;

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $data];
    }


    /**
     * 减免券配置
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function editTemplate(array $params): array
    {
        $physicalStore = $params['physical_store'] ?? [];
        $vipCard = $params['vip_card'] ?? [];

        $discountTicketTemplateInfo = DiscountTicketTemplate::query()->first(['id']);

        $discountTicketTemplateData = [
            'name' => $params['name'] ?? '',
            'img_url' => $params['img_url'] ?? '',
            'amount' => $params['amount'] ?? '',
            'expire' => $params['expire'] ?? '',
            'start_at' => $params['start_at'] ?? '',
            'end_at' => $params['end_at'] ?? '',
            'describe' => $params['describe'] ?? '',
            'applicable_store_type' => 2,
            'applicable_vip_card_type' => 2,
        ];
        if (empty($discountTicketTemplateInfo)) {
            $discountTicketTemplateId = IdGenerator::generate();
            $discountTicketTemplateData['id'] = $discountTicketTemplateId;
        } else {
            $discountTicketTemplateInfo = $discountTicketTemplateInfo->toArray();
            $discountTicketTemplateId = $discountTicketTemplateInfo['id'];
        }

        $batchInsertDiscountTicketTemplatePhysicalStore = [];
        foreach ($physicalStore as $physicalStoreId) {
            $batchInsertDiscountTicketTemplatePhysicalStore[] = [
                'id' => IdGenerator::generate(),
                'discount_ticket_template_id' => $discountTicketTemplateId,
                'physical_store_id' => $physicalStoreId,
            ];
        }

        $batchInsertDiscountTicketVipCard = [];
        foreach ($vipCard as $vipCardId) {
            $batchInsertDiscountTicketVipCard[] = [
                'id' => IdGenerator::generate(),
                'discount_ticket_template_id' => $discountTicketTemplateId,
                'vip_card_id' => $vipCardId,
            ];
        }

        $db = Db::connection('jkc_edu');
        $db->beginTransaction();
        try {
            if (empty($discountTicketTemplateInfo)) {
                DiscountTicketTemplate::insert($discountTicketTemplateData);
            } else {
                DiscountTicketTemplate::query()->where('id', $discountTicketTemplateId)->update($discountTicketTemplateData);

                DiscountTicketTemplatePhysicalStore::query()->where('discount_ticket_template_id', $discountTicketTemplateId)->delete();
                DiscountTicketTemplateVipCard::query()->where('discount_ticket_template_id', $discountTicketTemplateId)->delete();
            }

            if (!empty($batchInsertDiscountTicketTemplatePhysicalStore)) {
                DiscountTicketTemplatePhysicalStore::query()->insert($batchInsertDiscountTicketTemplatePhysicalStore);
            }

            if (!empty($batchInsertDiscountTicketVipCard)) {
                DiscountTicketTemplateVipCard::query()->insert($batchInsertDiscountTicketVipCard);
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();

            Log::get()->error('editCouponTemplate error: ' . $e->getMessage());

            return ['code' => ErrorCode::WARNING, 'msg' => '请稍后重试！', 'data' => null];
        }

        return ['code' => ErrorCode::SUCCESS, 'msg' => '配置成功！', 'data' => null];
    }


    /**
     * 减免券列表
     * @param array $query
     * @return array
     */
    public function list(array $query): array
    {
        $offset = $this->offset;
        $limit = $this->limit;

        $discountTicketModel = DiscountTicket::query();
        if (!empty($query['mobile'])) {
            $memberInfo = Member::query()->where('mobile', $query['mobile'])->first(['id']);
            if (empty($memberInfo)) {
                return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => ['list' => [], 'count' => 0]];
            }
            $memberInfo = $memberInfo->toArray();

            $discountTicketModel->where('member_id', $memberInfo['id']);
        }
        if (!empty($query['member_name'])) {
            $memberList = Member::query()->where([['name', 'like', "%{$query['member_name']}%"]])->get(['id'])->toArray();
            if (empty($memberList)) {
                return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => ['list' => [], 'count' => 0]];
            }

            $discountTicketModel->whereIn('member_id', array_column($memberList, 'id'));
        }
        if (!empty($query['referrer_mobile'])) {
            $referrerMemberInfo = Member::query()->where('mobile', $query['referrer_mobile'])->first(['id']);
            if (empty($referrerMemberInfo)) {
                return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => ['list' => [], 'count' => 0]];
            }
            $referrerMemberInfo = $referrerMemberInfo->toArray();

            $memberList = Member::query()->where('parent_id', $referrerMemberInfo['id'])->get(['id'])->toArray();
            if (empty($memberList)) {
                return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => ['list' => [], 'count' => 0]];
            }

            $discountTicketModel->whereIn('member_id', array_column($memberList, 'id'))->where('source_type', 2);
        }

        $date = date('Y-m-d H:i:s');
        $status = $query['status'] ?? -1;
        if ($status == 0) {
            $discountTicketModel->where('status', 0)->where('end_at', '>', $date);
        } else if ($status == 1) {
            $discountTicketModel->where('status', 1);
        } else if ($status == 2) {
            $discountTicketModel->where('status', 2);
        } else if ($status == 3) {
            $discountTicketModel->where('status', 0)->where('end_at', '<', $date);
        }

        $count = $discountTicketModel->count();

        $discountTicketList = $discountTicketModel->select(['id', 'member_id', 'discount_ticket_template_id', 'amount', 'status', 'source_type', 'end_at', 'source_id', 'created_at'])
            ->offset($offset)
            ->limit($limit)
            ->orderByDesc('created_at')
            ->get()
            ->toArray();

        foreach ($discountTicketList as $key => &$value) {
            $memberInfo = Member::query()->where('id', $value['member_id'])->first(['mobile', 'name', 'parent_id']);
            $memberInfo && $memberInfo = $memberInfo->toArray();

            $_referrerMobile = '无';
            if ($value['source_type'] == 2) {
                $referrerMemberInfo = Member::query()->where('id', $memberInfo['parent_id'])->first(['mobile']);
                $referrerMemberInfo && $referrerMemberInfo = $referrerMemberInfo->toArray();
                $_referrerMobile = $referrerMemberInfo['mobile'];
            }

            $statusText = '未知';
            if ($value['status'] == 0) {
                $statusText = '待使用';
                if ($date >= $value['end_at']) {
                    $statusText = '已过期';
                    $value['status'] = 3;
                }
            } else if ($value['status'] == 1) {
                $statusText = '已使用';
            } else if ($value['status'] == 2) {
                $statusText = '已失效';
            }

            $value['mobile'] = $memberInfo['mobile'] ?? '-';
            $value['name'] = $memberInfo['name'] ?? '-';
            $value['source_text'] = ['平台发放', '推荐好友', '自购'][$value['source_type']];
            $value['created_at'] = date('Y.m.d H:i', strtotime($value['created_at']));
            $value['status_text'] = $statusText;
            $value['referrer_mobile'] = $_referrerMobile;
        }

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => ['list' => $discountTicketList, 'count' => $count]];
    }


    /**
     * 作废减免券
     * @param int $id
     * @return array
     */
    public function invalid(int $id): array
    {
        DiscountTicket::query()->where(['id' => $id])->update(['status' => 2, 'cancel_at' => date('Y-m-d H:i:s')]);

        return ['code' => ErrorCode::SUCCESS, 'msg' => '作废成功！', 'data' => null];
    }


    /**
     * 减免券详情
     * @param int id
     * @return array
     */
    public function detail(int $id): array
    {
        $data = DiscountTicket::query()->where('id', $id)->select(['id', 'amount', 'end_at', 'used_at', 'status', 'created_at', 'updated_at'])->first();
        if (empty($data)) {
            return ['code' => ErrorCode::WARNING, 'msg' => 'id无效', 'data' => null];
        }
        $data = $data->toArray();

        $returnData = [
            'amount' => $data['amount'],
        ];
        if ($data['status'] == 0 && date('Y-m-d H:i:s') > $data['end_at']) {
            // 已过期
            $returnData['end_at'] = date('Y.m.d H:i', strtotime($data['end_at']));
            $returnData['status'] = 3;
        } else if ($data['status'] == 1) {
            $vipCardOrderOfferInfo = VipCardOrderOfferInfo::query()->where('offer_info_id', $data['id'])->first(['vip_card_order_id', 'amount', 'type']);
            if (empty($vipCardOrderOfferInfo)) {
                return ['code' => ErrorCode::WARNING, 'msg' => '减免券优惠记录消失', 'data' => null];
            }
            $vipCardOrderOfferInfo = $vipCardOrderOfferInfo->toArray();

            $vipCardOrderInfo = VipCardOrder::query()->where('id', $vipCardOrderOfferInfo['vip_card_order_id'])->first(['vip_card_id', 'price']);
            if (empty($vipCardOrderInfo)) {
                return ['code' => ErrorCode::WARNING, 'msg' => '减免券订单消失', 'data' => null];
            }
            $vipCardOrderInfo->toArray();

            $vipCardInfo = VipCard::query()->where('id', $vipCardOrderInfo['vip_card_id'])->first(['name']);
            $vipCardInfo && $vipCardInfo->toArray();

            $couponPrice = VipCardOrderOfferInfo::query()->where('vip_card_order_id', $vipCardOrderOfferInfo['vip_card_order_id'])->where('type', 1)->sum('amount');

            $discountTicketPrice = VipCardOrderOfferInfo::query()->where('vip_card_order_id', $vipCardOrderOfferInfo['vip_card_order_id'])->where('type', 2)->sum('amount');

            $originalPrice = bcadd("{$vipCardOrderInfo['price']}", "{$discountTicketPrice}", 2);
            $originalPrice = bcadd("{$originalPrice}", "{$couponPrice}", 2);

            $returnData['used_at'] = date('Y.m.d H:i', strtotime($data['used_at']));
            $returnData['vip_card_name'] = $vipCardInfo['name'] ?? '-';
            $returnData['original_price'] = $originalPrice;
            $returnData['discount_ticket_price'] = $discountTicketPrice;
            $returnData['coupon_price'] = $couponPrice;
            $returnData['price'] = $vipCardOrderInfo['price'];
        } else if ($data['status'] == 2) {
            $returnData['updated_at'] = date('Y.m.d H:i', strtotime($data['updated_at']));
        }

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $returnData];
    }


    /**
     * 减免券推荐好友详情
     * @param int id
     * @return array
     */
    public function inviteFriendDetail(int $id): array
    {
        $data = DiscountTicket::query()->where('id', $id)->select(['member_id', 'source_id', 'created_at'])->first();
        if (empty($data)) {
            return ['code' => 0, 'msg' => 'id无效', 'data' => null];
        }
        $data = $data->toArray();

        $memberInfo = Member::query()->where('id', $data['source_id'])->first(['mobile', 'name']);
        $memberInfo && $memberInfo = $memberInfo->toArray();

        $data['created_at'] = date('Y.m.d H:i', strtotime($data['created_at']));
        $data['mobile'] = $memberInfo['mobile'] ?? '-';
        $data['name'] = $memberInfo['name'] ?? '-';

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $data];
    }


    /**
     * 减免券发放
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function issued(array $params): array
    {
        $mobile = $params['mobile'];
        if (empty($mobile) || !is_array($mobile)) {
            return ['code' => ErrorCode::WARNING, 'msg' => '请输入手机号！', 'data' => null];
        }

        $data = DiscountTicketTemplate::query()->select(['id', 'name', 'img_url', 'amount', 'expire', 'start_at', 'end_at', 'applicable_store_type', 'applicable_vip_card_type'])->first();
        if (empty($data)) {
            return ['code' => ErrorCode::WARNING, 'msg' => '减免券尚未配置完成，请先完成减免券配置', 'data' => null];
        }
        $data = $data->toArray();

        $physicalStoreList = DiscountTicketTemplatePhysicalStore::query()->where(['discount_ticket_template_id' => $data['id']])->get(['physical_store_id'])->toArray();
        $vipCardList = DiscountTicketTemplateVipCard::query()->where(['discount_ticket_template_id' => $data['id']])->get(['vip_card_id'])->toArray();

        if (empty($data['amount']) || empty($data['expire']) || empty($physicalStoreList) || empty($vipCardList)) {
            return ['code' => ErrorCode::WARNING, 'msg' => '减免券尚未配置完成，请先完成减免券配置', 'data' => null];
        }

        $memberList = Member::query()->whereIn('mobile', $mobile)->get(['id', 'mobile'])->toArray();
        if (count($memberList) != count($mobile)) {
            $diffMobile = array_diff(array_column($memberList, 'mobile'), $mobile);
            return ['code' => ErrorCode::WARNING, 'msg' => $diffMobile[0] . '手机号无效', 'data' => null];
        }

        $batchInsertDiscountTicket = [];
        $batchInsertDiscountTicketPhysicalStore = [];
        $batchInsertDiscountTicketVipCard = [];
        $endAt = date('Y-m-d H:i:s', time() + ($data['expire'] * 86400));
        foreach ($memberList as $_memberInfo) {
            $discountTicketId = IdGenerator::generate();
            $batchInsertDiscountTicket[] = [
                'id' => $discountTicketId,
                'member_id' => $_memberInfo['id'],
                'discount_ticket_template_id' => $data['id'],
                'name' => '平台购送减免券',
                'amount' => $data['amount'],
                'end_at' => $endAt,
                'source_type' => 0,
                'applicable_store_type' => $data['applicable_store_type'],
                'applicable_vip_card_type' => $data['applicable_vip_card_type'],
            ];
            foreach ($physicalStoreList as $index => $_physicalStoreInfo) {
                $batchInsertDiscountTicketPhysicalStore[] = [
                    'id' => IdGenerator::generate(),
                    'discount_ticket_id' => $discountTicketId,
                    'physical_store_id' => $_physicalStoreInfo['physical_store_id'],
                ];
            }
            foreach ($vipCardList as $index => $_vipCardInfo) {
                $batchInsertDiscountTicketVipCard[] = [
                    'id' => IdGenerator::generate(),
                    'discount_ticket_id' => $discountTicketId,
                    'vip_card_id' => $_vipCardInfo['vip_card_id'],
                ];
            }
        }
        DiscountTicket::insert($batchInsertDiscountTicket);
        DiscountTicketVipCard::insert($batchInsertDiscountTicketVipCard);
        DiscountTicketPhysicalStore::insert($batchInsertDiscountTicketPhysicalStore);

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }


}