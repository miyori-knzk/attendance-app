<?php

use Carbon\CarbonImmutable;

/**
 * 文字列の時間をHH:MM:SSをHHH:MMに変換
 * HH:MM:SSでない場合はそのまま返す
 *
 * @return string|mixed
 */
function hisToHi(mixed $val): mixed
{
    if (is_string($val) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $val)) {
        return CarbonImmutable::parse($val)->format('H:i');
    }

    return $val;
}

/**
 * 文字列を CarbonImmutable に変換
 *
 * @param  string|null  $val  Y-m-d 形式 または null
 * @return CarbonImmutable|null 成功時は CarbonImmutable オブジェクト、失敗時は null
 */
function dateFormat(?string $val): ?CarbonImmutable
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

/**
 * 日付をY/m/dにして返す
 *
 * @param  mixed  $val  日付
 * @return string|null 変換できなかった場合はNULLで返す
 */
function dateFormat2(mixed $val): ?string
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

/**
 * 時間の文字列をCarbonImmutableに変換
 *
 * @param  string|null  $val  HH:MM:SS形式
 * @return CarbonImmutable|null 変換に成功すればCarbonImmutable
 */
function timeFormat(?string $val): ?CarbonImmutable
{
    if (empty($val)) {
        return null;
    }

    try {
        return CarbonImmutable::createFromFormat('H:i:s', $val);
    } catch (Exception $e) {
        return null;
    }
}

/**
 * 日本語の曜日を返す
 *
 * @param  int  $val  0~6の数字
 */
function jpWeekday(int $val): string
{
    $tmp = null;
    $dayOfWeekArr = ['日', '月', '火', '水', '木', '金', '土'];

    if (in_array($val, array_keys($dayOfWeekArr))) {
        $tmp = $dayOfWeekArr[$val];
    }

    return $tmp;

}

/**
 * CarbonImmutableをHH:MMに変換
 */
function dateTimeToHi(?CarbonImmutable $val): ?string
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

/**
 * CarbonImmutableから月初を取得
 */
function getFirstOfMonth(CarbonImmutable $val): ?CarbonImmutable
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

/**
 * CarbonImmutableから月初を取得
 */
function getEndOfMonth(CarbonImmutable $val): ?CarbonImmutable
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

/**
 * Y-m-d形式の日付をY年m月d日に変換
 *
 * @param string Y-m-d形式
 */
function jpDateFormat(string $val): array
{
    $data = [];
    $spritDate = explode('-', $val);
    $data['year'] = $spritDate[0] . '年';
    $data['date'] = $spritDate[1] . '月' . $spritDate[2] . '日';

    return $data;
}
