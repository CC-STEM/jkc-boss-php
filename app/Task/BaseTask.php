<?php
declare(strict_types=1);

namespace App\Task;

use App\Common\Functions;
use Hyperf\Di\Annotation\Inject;

class BaseTask
{
    /**
     * @var Functions
     */
    #[Inject]
    protected Functions $functions;
}