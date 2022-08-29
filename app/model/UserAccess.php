<?php
/**
 * UserAccess
 *
 * @version    1.0
 * @date       23/08/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class UserAccess extends TRecord
{
    const TABLENAME     = 'sys_user_access';
    const PRIMARYKEY    = 'id';
    const IDPOLICY      = 'serial'; // {max, serial}
    
    public function __construct($id = NULL)
    {
        parent::__construct($id);
        parent::addAttribute('dt_register');
        parent::addAttribute('user_id');
        parent::addAttribute('session_token');
        parent::addAttribute('platform');
        parent::addAttribute('version');
        parent::addAttribute('ip');
        parent::addAttribute('status');
    }
}
?>