<?php

use Carbon\CarbonImmutable;

function hiToTime($val)
{
    if (is_string($val) && preg_match('/^\d{2}:\d{2}$/', $val)) {
        return CarbonImmutable::createFromFormat('H:i', $val)->format('H:i:s');
    }

    return $val;
}

// 日付データのみ
function dateFormat($val)
{
    return CarbonImmutable::parse($val)->format('Y/m/d');
}

function dateToCarbon($val)
{
    return CarbonImmutable::parse($val);
}

function timeFormat($val)
{
    return CarbonImmutable::parse($val)->format('H:i');
}
