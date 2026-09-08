<?php

use Carbon\CarbonImmutable;

function hiToTime($val)
{
    if (is_string($val) && preg_match('/^\d{2}:\d{2}$/', $val)) {
        return CarbonImmutable::createFromFormat('H:i', $val)->format('H:i:s');
    }

    return $val;
}

function dateFormat($val)
{
    if (empty($val)) {
        return null;
    }

    $carbon = CarbonImmutable::createFromFormat('Y-m-d', $val);

    if ($carbon === false) {
        return null;
    }

    return $carbon;
}

function dateFormat2($val)
{
    if (empty($val)) {
        return null;
    }

    if ($val instanceof CarbonImmutable || $val instanceof Carbon\Carbon) {
        $carbon = $val->format('Y/m/d');
    } else {
        $carbon = dateFormat($val)->format('Y/m/d');
    }

    if ($carbon === false) {
        return null;
    }

    return $carbon;
}

function timeFormat($val)
{
    if (empty($val)) {
        return null;
    }

    try {
        return CarbonImmutable::createFromFormat('H:i', $val);
    } catch (Exception $e) {
        return null;
    }
}

function jpWeekday($val)
{
    $tmp = null;
    $dayOfWeekArr = ['日', '月', '火', '水', '木', '金', '土'];

    if (in_array($val, array_keys($dayOfWeekArr))) {
        $tmp = $dayOfWeekArr[$val];
    }

    return $tmp;

}

function dateTimeToHi($val)
{
    if (empty($val)) {
        return null;
    }

    try {
        return $val->format('H:i');
    } catch (Exception $e) {
        return null;
    }
}

function getFirstOfMonth($val)
{
    if (empty($val)) {
        return null;
    }

    try {
        return $val->firstOfMonth();
    } catch (Exception $e) {
        return null;
    }
}

function getEndOfMonth($val)
{
    if (empty($val)) {
        return null;
    }

    try {
        return $val->endOfMonth();
    } catch (Exception $e) {
        return null;
    }
}

function jpDateFormat($val)
{
    $data = [];
    $spritDate = explode('-', $val);
    $data['year'] = $spritDate[0] . '年';
    $data['date'] = $spritDate[1] . '月' . $spritDate[2] . '日';

    return $data;
}
