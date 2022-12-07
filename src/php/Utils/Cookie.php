<?php

namespace WPSEEDE\Utils;

class Cookie
{
    protected $args;

    public function __construct($args=[])
    {
        $this->args = wp_parse_args($args, [
            'prefix' => 'wpseede_',
            'path' => '/',
            'time' => time() + (60*60*24*30),
            'del_time' => time() - 86400
        ]);
    }

    public function setCookie($key, $val)
    {
        if(is_array($val))
        {
            $val = serialize($val);
        }
        return setcookie($this->args['prefix'] . $key, $val, $this->args['time'], $this->args['path']);
    }

    public function getCookie($key, $default=null, $unserialize=false)
    {
        $cookie = isset($_COOKIE[$this->args['prefix'] . $key]) ? $_COOKIE[$this->args['prefix'] . $key] : $default;

        if($unserialize && !empty($cookie))
        {
            $cookie = unserialize(stripslashes($cookie));
        }

        return $cookie;
    }

    public function delCookie($key)
    {
        return setcookie($this->args['prefix'] . $key, $val, $this->args['del_time'], $this->args['path']);
    }

    public function setCookieGroupItem($group, $key, $val)
    {
        $data = $this->getCookie($group);
        $data_u = $data ? unserialize(stripslashes($data)) : [];

        $data_u[$key] = $val;

        return $this->setCookie($group, serialize($data_u));
    }

    public function getCookieGroupItem($group, $key=null, $default=null)
    {
        $data = $this->getCookie($group);
        $data_u = $data ? unserialize(stripslashes($data)) : [];

        if(isset($key))
        {
            return (empty($data_u[$key]) && isset($default)) ? $default : $data_u[$key];
        }

        return (empty($data_u) && isset($default)) ? $default : $data_u;
    }

    public function delCookieGroupItem($group, $key)
    {
        return $this->setCookieGroupItem($group, $key, null);
    }
}
