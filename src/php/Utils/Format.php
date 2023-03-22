<?php

namespace WPSEEDE\Utils;

class Format
{
    static function formatPrice($price)
    {
        return function_exists('wc_price') ? wc_price($price) : $price;
    }

    static function formatPhoneSys($phone, $replace_plus=false)
    {
        if($replace_plus && strpos($phone, '+') === 0){
            $phone = '00' . substr($phone, 1);
        }

        // return str_replace([' ', '-', '.'], '', $phone);
        // return preg_replace('/\d/', '', $phone);
        // return preg_replace('/[^0-9]/', '', $phone);

        $phone = filter_var($phone, FILTER_SANITIZE_NUMBER_INT);
        $phone = str_replace([' ', '-', '.'], '', $phone);

        return $phone;
    }
}