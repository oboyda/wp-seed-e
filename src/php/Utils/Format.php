<?php

namespace WPSEEDE\Utils;

class Format
{
    static function formatPrice($price)
    {
        return function_exists('wc_price') ? wc_price($price) : $price;
    }

    static function formatPhoneSys($phone)
    {
        return str_replace([' ', '-', '.'], '', $phone);
    }
}