<?php
/**
 * Product
 *
 * @version    1.0
 * @date       23/09/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class Product extends TRecord
{
    const TABLENAME     = 'cad_product';
    const PRIMARYKEY    = 'id';
    const IDPOLICY      = 'serial'; // {max, serial}
    //const CACHECONTROL = 'TAPCache';

    public function __construct($id = NULL)
    {
        parent::__construct($id);
        parent::addAttribute('name');
        parent::addAttribute('fl_enable');
        parent::addAttribute('price');
        parent::addAttribute('fl_product');
        parent::addAttribute('qtd_time');
        parent::addAttribute('description');
        parent::addAttribute('photo');
    }

    public function store()
    {                
        $this->photo = TFileUpload::move($this->photo, 'repository/');

        parent::store();
    }
}
?>