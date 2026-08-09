<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Analytics;

use Orchid\Screen\Layouts\Chart;

class TrafficSourceChart extends Chart
{
    protected $type = self::TYPE_PIE;

    protected $height = 320;
}
