<?php

declare(strict_types=1);

namespace App\Service;

use App\Constants\ErrorCode;
use App\Model\MemberTag;
use App\Model\MemberTagTemplate;
use App\Snowflake\IdGenerator;

class MemberTagService extends BaseService
{
    /**
     * 添加会员标签模板
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function addMemberTagTemplate(array $params): array
    {
        $insertMemberTagTemplateData['id'] = IdGenerator::generate();
        $insertMemberTagTemplateData['name'] = $params['name'];
        $insertMemberTagTemplateData['describe'] = $params['describe'] ?? '';

        MemberTagTemplate::query()->insert($insertMemberTagTemplateData);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 编辑会员标签模板
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function editMemberTagTemplate(array $params): array
    {
        $id = $params['id'];

        $updateMemberTagTemplateData['name'] = $params['name'];
        $updateMemberTagTemplateData['describe'] = $params['describe'] ?? '';

        MemberTagTemplate::query()->where(['id'=>$id])->update($updateMemberTagTemplateData);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 删除会员标签模板
     * @param int $id
     * @return array
     */
    public function deleteMemberTagTemplate(int $id): array
    {
        MemberTagTemplate::query()->where(['id'=>$id])->update(['is_deleted'=>1]);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 会员标签模板列表
     * @return array
     */
    public function memberTagTemplateList(): array
    {
        $offset = $this->offset;
        $limit = $this->limit;

        $memberTagTemplateModel = MemberTagTemplate::query()
            ->where(['is_deleted'=>0]);
        $count = $memberTagTemplateModel->count();
        $memberTagTemplateList = $memberTagTemplateModel
            ->select(['id','name','describe','created_at'])
            ->offset($offset)->limit($limit)
            ->get();
        $memberTagTemplateList = $memberTagTemplateList->toArray();

        foreach($memberTagTemplateList as $key=>$value){
            $memberTagCount = MemberTag::query()->where(['member_tag_template_id'=>$value['id']])->count();

            $memberTagTemplateList[$key]['relation_count'] = $memberTagCount;
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => ['list'=>$memberTagTemplateList,'count'=>$count]];
    }

    /**
     * 会员标签模板关联列表
     * @param int $id
     * @return array
     */
    public function memberTagTemplateRelationList(int $id): array
    {
        $offset = $this->offset;
        $limit = $this->limit;

        $memberTagModel = MemberTag::query()
            ->leftJoin('member','member_tag.member_id','=','member.id')
            ->where(['member_tag.member_tag_template_id'=>$id]);
        $count= $memberTagModel->count();
        $memberTagList = $memberTagModel
            ->select(['member.name','member.mobile','member_tag.created_at'])
            ->offset($offset)->limit($limit)
            ->get();
        $memberTagList = $memberTagList->toArray();

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => ['list'=>$memberTagList,'count'=>$count]];
    }

    /**
     * 会员标签模板
     * @return array
     */
    public function memberTagTemplate(): array
    {
        $memberTagTemplateList = MemberTagTemplate::query()
            ->select(['id','name'])
            ->where(['is_deleted'=>0])
            ->get();
        $memberTagTemplateList = $memberTagTemplateList->toArray();
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $memberTagTemplateList];
    }

    /**
     * 添加会员标签
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function addMemberTag(array $params): array
    {
        $memberTagTemplateId = $params['member_tag_template_id'];

        $memberTagTemplateInfo = MemberTagTemplate::query()
            ->select(['name'])
            ->where(['id'=>$memberTagTemplateId])
            ->first();
        $memberTagTemplateInfo = $memberTagTemplateInfo->toArray();

        $insertMemberTagData['id'] = IdGenerator::generate();
        $insertMemberTagData['name'] = $memberTagTemplateInfo['name'];
        $insertMemberTagData['member_id'] = $params['member_id'];
        $insertMemberTagData['member_tag_template_id'] = $memberTagTemplateId;

        MemberTag::query()->insert($insertMemberTagData);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 删除会员标签
     * @param int $id
     * @return array
     */
    public function deleteMemberTag(int $id): array
    {
        MemberTag::query()->where(['id'=>$id])->delete();
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }
}