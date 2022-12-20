<?php 

namespace WPSEEDE\Utils;

class Date 
{
    const DATE_FORMAT_SYS = 'Y-m-d';
    const TIME_FORMAT_SYS = 'H:i:s';
    const DATETIME_FORMAT_SYS = 'Y-m-d H:i:s';

    static function getDateFormat()
    {
        $format_opt = get_option('date_format');
        return $format_opt ? $format_opt : self::DATE_FORMAT_SYS;
    }

    static function getTimeFormat()
    {
        $format_opt = get_option('time_format');
        return $format_opt ? $format_opt : self::TIME_FORMAT_SYS;
    }

    static function getDateTimeFormat()
    {
        $format = self::getDateFormat() . ' ' . self::getTimeFormat();
        return trim($format);
    }

    static function getTimezone()
    {
        return new \DateTimeZone(wp_timezone_string());
    }

    static function getDate($timestamp='now', $format=null, $set_timezone=false)
    {
        if(is_int($timestamp))
        {
            $timestamp = gmdate(DATETIME_FORMAT_SYS, $timestamp);
        }

        $date = new \DateTime($timestamp);
        if($set_timezone)
        {
            $date->setTimezone(self::getTimezone());
        }

        return isset($format) ? $date->format($format) : $date;
    }

    static function formatDate($timestamp='now', $format=null, $set_timezone=true)
    {
        if(!isset($format))
        {
            $format = self::getDateFormat();
        }
        return self::getDate($timestamp, $format, $set_timezone);
    }

    static function formatDateTime($timestamp='now', $format=null, $set_timezone=true)
    {
        if(!isset($format))
        {
            $format = self::getDateTimeFormat();
        }
        return self::getDate($timestamp, $format, $set_timezone);
    }

    static function getDateFormatted($timestamp='now', $set_timezone=true)
    {
        return self::formatDate($timestamp, null, $set_timezone);
    }

    static function getDateTimeFormatted($timestamp='now', $set_timezone=true)
    {
        return self::formatDateTime($timestamp, null, $set_timezone);
    }

    static function getNowDate($format=null, $set_timezone=false)
    {
        return self::getDate('now', $format, $set_timezone);
    }

    static function getNowDateFormatted($set_timezone=true)
    {
        return self::getDateFormatted('now', $set_timezone);
    }

    static function getNowDateTimeFormatted($set_timezone=true)
    {
        return self::getDateTimeFormatted('now', $set_timezone);
    }

    static function getSysDateTime($timestamp='now')
    {
        return self::getDate($timestamp, self::DATETIME_FORMAT_SYS, false);
    }

    static function getSysDate($timestamp='now')
    {
        return self::getDate($timestamp, self::DATE_FORMAT_SYS, false);
    }

    static function parseAcfDateMeta($meta)
    {
        $date = [];
        $meta = strval($meta);
        if(strlen($meta) === 8)
        {
            $date[] = substr($meta, 0, 4);
            $date[] = substr($meta, 4, 2);
            $date[] = substr($meta, 6, 2);
        }
        return implode('-', $date);
    }
}
