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

    public function store()
    {
        $dt_register_sale   = explode(' ', $this->dt_register_sale);
        $dt_register_sale   = $dt_register_sale[1];
        $dt_register_sale   = explode(':',  $dt_register_sale[1]);
        $dt_register_sale   = $dt_register_sale[0];
        $dt_register_sale   = intval($dt_register_sale);

        if($dt_register_sale > 8 && $dt_register_sale < 18)
        {
            parent::store();
        }
        else
        {
            throw new Exception("Serviço não está sendo realizado nesse horário");
        }
    }

    public function delete($id = NULL)
    {  
        $id = isset($id) ? $id : $this->id;

        if($this->dt_service)
        {
            throw new Exception("Esse serviço já foi realizado");
        }

        $dt_now = explode(' ', date("Y-m-d H:i:s"));
        $dt_now = explode(':',  $dt_now[1]);
        $dt_now = $dt_now[0];

        $dt_register_sale   = explode(' ', $this->dt_register_sale);
        $dt_register_sale   = $dt_register_sale[1];
        $dt_register_sale   = explode(':',  $dt_register_sale[1]);
        $dt_register_sale   = $dt_register_sale[0];

        $dt_validate = intval($dt_now) - intval($dt_register_sale); 

        if($dt_validate < 2)
        {
            throw new Exception("Esse serviço não pode ser cancelado 2 horas antes");
        }

        parent::delete($id);   
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

    public static function serviceCompleted($sale_id, $dt_service)
    {
        if($sale_id)
        {
            $sale = self::where('id', '=', $sale_id)->get();

            if($sale)
            {
                $sale = $sale[0];
                $sale->dt_service = $dt_service;
                $sale->store();
            }
            else
            {
                throw new Exception("Venda não existe");
            }
        }
        else
        {
            throw new Exception("Venda não encontrada");
        }
    }
}
?>