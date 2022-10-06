<?php

use Adianti\Database\TTransaction;

/**
 * Operator
 *
 * @version    1.0
 * @date       23/09/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class Operator extends TRecord
{
    const TABLENAME     = 'cad_operator';
    const PRIMARYKEY    = 'id';
    const IDPOLICY      = 'serial'; // {max, serial}
    //const CACHECONTROL = 'TAPCache';

    public function __construct($id = NULL)
    {
        parent::__construct($id);
        parent::addAttribute('person_individual_id');
    }

    public function getPersonIndividual()
    {
        return new PersonIndividual($this->person_individual_id);
    }

    public function getPersonOperator()
    {
        $person_individual = $this->getPersonIndividual();

        if($person_individual)
        {
            $person_operator = $person_individual->getPerson();

            return $person_operator;
        }
    }

    public static function getArrayOperators()
    {
        TTransaction::open('razor');

        $operators = Operator::get();

        TTransaction::close();

        $array_operators = [];

        if($operators)
        {
            foreach($operators as $operator)
            {
                $person_individual  = new Person($operator->person_individual_id);
                $array_operators[$operator->id] = $person_individual->name;
            }
        }

        return $array_operators;
    }
}
?>