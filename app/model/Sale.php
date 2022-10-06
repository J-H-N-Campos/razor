<?php
/**
 * Sale
 *
 * @version    1.0
 * @date       23/09/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class Sale extends TRecord
{
    const TABLENAME     = 'fin_sale';
    const PRIMARYKEY    = 'id';
    const IDPOLICY      = 'serial'; // {max, serial}
    //const CACHECONTROL = 'TAPCache';

    public function __construct($id = NULL)
    {
        parent::__construct($id);
        parent::addAttribute('dt_register_sale');
        parent::addAttribute('dt_service');
        parent::addAttribute('product_id');
        parent::addAttribute('person_id');
        parent::addAttribute('operator_id');
    }

    public function getPerson()
    {
        return new Person($this->person_id);
    }

    public function getOperator()
    {
        return new Operator($this->operator_id);
    }

    public function getProduct()
    {
        return new Product($this->product_id);
    }
}
?>