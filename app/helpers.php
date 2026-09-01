<?php

use Carbon\CarbonImmutable;

function hiToTime($val)
{
    if (is_string($val) && preg_match('/^\d{2}:\d{2}$/', $val)) {
        return CarbonImmutable::createFromFormat('Hi', $val)->format('H:i:s');
    }

    return $val;
}
