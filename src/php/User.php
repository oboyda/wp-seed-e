<?php 

namespace WPSEEDE;

class User extends \WPSEED\User
{
    public function __construct($user=null)
    {
        parent::__construct($user, self::_get_props_config());
    }

    static function _get_props_config()
    {
        return [
            'email_verified' => [
                'type' => 'meta'
            ]
        ];
    }

    /* ------------------------- */

    public function getId()
    {
        return $this->get_id();
    }

    public function getRole()
    {
        return $this->get_role();
    }

    public function getLogin()
    {
        return $this->get_data('user_login', '');
    }

    public function getEmail()
    {
        return $this->get_data('user_email', '');
    }

    public function getFirstName()
    {
        return $this->get_meta('first_name', true, '');
    }

    public function getLastName()
    {
        return $this->get_meta('last_name', true, '');
    }

    public function getFullName()
    {
        return trim($this->getFirstName() . ' ' . $this->getLastName());
    }

    public function isEmailVerified()
    {
        if(in_array($this->getRole(), ['administrator']))
        {
            return true;
        }

        return (bool)$this->getMeta('email_verified', true);
    }
}
