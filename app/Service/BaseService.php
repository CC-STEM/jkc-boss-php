<?php

declare(strict_types=1);

namespace App\Service;

use App\Common\Functions;
use Hyperf\Di\Annotation\Inject;

class BaseService
{
    /**
     * @var array
     */
    public array $adminsInfo = [];
    /**
     * @var Functions
     */
    #[Inject]
    protected Functions $functions;
    /**
     * @var int
     */
    public int $offset = 0;
    /**
     * @var int
     */
    public int $limit = 1;

}