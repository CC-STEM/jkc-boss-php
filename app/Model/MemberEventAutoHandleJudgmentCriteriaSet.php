<?php

declare (strict_types=1);
namespace App\Model;

use Hyperf\DbConnection\Model\Model;

class MemberEventAutoHandleJudgmentCriteriaSet extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'member_event_auto_handle_judgment_criteria_set';
    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'jkc_edu';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];
    /**
     * @var string[]
     */
    protected $casts = ['id' => 'string'];
}