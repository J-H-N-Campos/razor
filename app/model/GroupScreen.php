<?php
/**
 * GroupScreen
 *
 * @version    1.0
 * @date       23/08/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class GroupScreen extends TRecord
{
    const TABLENAME     = 'sys_group_screen';
    const PRIMARYKEY    = 'id';
    const IDPOLICY      = 'serial'; // {max, serial}

    private $screens;

    public function __construct($id = NULL)
    {
        parent::__construct($id);
        parent::addAttribute('screen_id');
        parent::addAttribute('group_id');
    }
}
?>