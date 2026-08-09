<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Analytics;

use Orchid\Screen\Layouts\Chart;

class NewReturningChart extends Chart
{
    protected $type = self::TYPE_BAR;

    protected $height = 300;
}
