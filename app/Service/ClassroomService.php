<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Classroom;
use App\Constants\ErrorCode;

class ClassroomService extends BaseService
{
    /**
     * 教室列表
     * @param array $params
     * @return array
     */
    public function classroomList(array $params): array
    {
        $physicalStoreId = $params['physical_store_id'];
        $classroomList = Classroom::query()->select(['id','name'])->where(['physical_store_id'=>$physicalStoreId])->get();
        $classroomList = $classroomList->toArray();

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $classroomList];
    }
}