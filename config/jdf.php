<?php
/**
 * -------------------------In the name of ALLAH-----------------------------
 * --------------------------------------------------------------------------
 * Programmer:  Amid Ahadi
 * Email:       Amid-ahadi@gmail.com
 * Website:     amid-ahadi.ir
 * Copyright:   All rights reserved for Amid Ahadi
 * --------------------------------------------------------------------------
 * Coded for Karaj Emam Hospital with love ❤️
 * Created:     2026-06-20
 */
function jdate($format, $timestamp = '', $none = '', $time_zone = 'Asia/Tehran', $tr_num = 'fa') {
    $T_N = ($tr_num == 'fa') ? array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9') : array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
    if ($timestamp === '') $timestamp = time();
    $date = explode('_', date('H_i_s_Y_m_d_w', $timestamp));
    list($j_y, $j_m, $j_d) = gregorian_to_jalali($date[3], $date[4], $date[5]);
    $res = "";
    $format_chars = str_split($format);
    foreach ($format_chars as $char) {
        switch ($char) {
            case 'Y': $res .= $j_y; break;
            case 'm': $res .= str_pad($j_m, 2, '0', STR_PAD_LEFT); break;
            case 'd': $res .= str_pad($j_d, 2, '0', STR_PAD_LEFT); break;
            case 'H': $res .= $date[0]; break;
            case 'i': $res .= $date[1]; break;
            case 's': $res .= $date[2]; break;
            default: $res .= $char; break;
        }
    }
    return ($tr_num == 'fa') ? str_replace(array('0','1','2','3','4','5','6','7','8','9'), $T_N, $res) : $res;
}

function gregorian_to_jalali($gy, $gm, $gd) {
    $g_d_m = array(0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334);
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = 355666 + (365 * $gy) + ((int)(($gy2 + 3) / 4)) - ((int)(($gy2 + 99) / 100)) + ((int)(($gy2 + 399) / 400)) + $gd + $g_d_m[$gm - 1];
    $jy = -1595 + (33 * ((int)($days / 12053)));
    $days %= 12053;
    $jy += 4 * ((int)($days / 1461));
    $days %= 1461;
    if ($days > 365) {
        $jy += (int)(($days - 1) / 365);
        $days = ($days - 1) % 365;
    }
    $jm = ($days < 186) ? 1 + (int)($days / 31) : 7 + (int)(($days - 186) / 30);
    $jd = 1 + (($days < 186) ? ($days % 31) : (($days - 186) % 30));
    return array($jy, $jm, $jd);
}
?>
