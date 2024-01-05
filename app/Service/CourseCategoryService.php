<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\CourseOffline;
use App\Model\CourseOnlineCategory;
use App\Snowflake\IdGenerator;
use App\Model\CourseOfflineCategory;
use App\Constants\ErrorCode;

class CourseCategoryService extends BaseService
{
    /**
     * 添加线下课程分类
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function addCourseOfflineCategory(array $params): array
    {
        $type = $params['type'] ?? 0;
        $parentId = $params['parent_id'] ?? 0;
        $themeType = $params['theme_type'] ?? 0;
        if($parentId == 0){
            $type = 0;
        }else{
            $parentCourseOfflineCategoryInfo = CourseOfflineCategory::query()
                ->select(['theme_type'])
                ->where(['id'=>$parentId])
                ->first();
            $parentCourseOfflineCategoryInfo = $parentCourseOfflineCategoryInfo->toArray();
            $themeType = $parentCourseOfflineCategoryInfo['theme_type'];
        }
        if(empty($themeType)){
            return ['code' => ErrorCode::WARNING, 'msg' => '课程分类不能为空', 'data' => null];
        }

        $insertCourseOfflineCategoryData['id'] = IdGenerator::generate();
        $insertCourseOfflineCategoryData['name'] = $params['name'];
        $insertCourseOfflineCategoryData['parent_id'] = $parentId;
        $insertCourseOfflineCategoryData['type'] = $type;
        $insertCourseOfflineCategoryData['theme_type'] = $themeType;

        CourseOfflineCategory::query()->insert($insertCourseOfflineCategoryData);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 删除线下课程分类
     * @param int $id
     * @return array
     */
    public function deleteCourseOfflineCategory(int $id): array
    {
        $checkInfo = CourseOfflineCategory::query()->select(['id'])->where(['parent_id'=>$id])->first();
        if(!empty($checkInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '该分类还有子分类无法删除', 'data' => null];
        }
        $checkInfo = CourseOffline::query()->select(['id'])->where(['course_category_id'=>$id,'is_deleted'=>0])->first();
        if(!empty($checkInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '该分类还有子课程无法删除', 'data' => null];
        }

        CourseOfflineCategory::query()->where(['id'=>$id])->delete();
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 编辑线下课程分类名称
     * @param array $params
     * @return array
     */
    public function editCourseOfflineCategoryName(array $params): array
    {
        $id = $params['id'];
        $name = $params['name'];

        if(empty($name)){
            return ['code' => ErrorCode::WARNING, 'msg' => '名称不能为空', 'data' => null];
        }
        CourseOfflineCategory::query()->where(['id'=>$id])->update(['name'=>$name]);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 线下课程分类列表
     * @param array $params
     * @return array
     */
    public function courseOfflineCategoryList(array $params): array
    {
        $parentId = $params['parent_id'];

        $courseOfflineCategoryList = CourseOfflineCategory::query()->select(['id','name','type','theme_type'])->where(['parent_id'=>$parentId])->get();
        $courseOfflineCategoryList = $courseOfflineCategoryList->toArray();
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $courseOfflineCategoryList];
    }

    /**
     * 线上课程分类列表
     * @param array $params
     * @return array
     */
    public function courseOnlineCategoryList(array $params): array
    {
        $parentId = $params['parent_id'];

        $courseOnlineCategoryList = CourseOnlineCategory::query()->select(['id','name'])->where(['parent_id'=>$parentId])->get();
        $courseOnlineCategoryList = $courseOnlineCategoryList->toArray();
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $courseOnlineCategoryList];
    }
}