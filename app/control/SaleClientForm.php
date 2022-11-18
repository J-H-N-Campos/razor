<?php

use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\THidden;

/**
 * SaleClientForm
 *
 * @version    1.0
 * @date       27/10/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
 
class SaleClientForm extends TCurtain
{
    protected $form; 
    private   $db;
    private   $model;
      
    /**
     * Classe construtora
     */
    function __construct()
    {
        try
        {
            parent::__construct();
            
            //Definições d  e conexão
            $this->db       = 'razor';
            $this->model    = 'Sale';
            $this->parent   = "SaleClientList";

            //Cria a form
            $this->form = new TFormStruct('SaleClientForm');

            //Ação do close
            parent::setActionClose([$this->parent]);

            //Entradas
            $product_id         = new TUniqueSearch('product_id',   $this->db, 'Product',   'id', 'name');
            $person_id          = new THidden('person_id');
            $operator_id        = new TUniqueSearch('operator_id');
            $dt_register_sale   = new TDateTime('dt_register_sale');

            $array_operators = Operator::getArrayOperators();
            $array_products  = Product::getArrayProducts();

            $operator_id->addItems($array_operators);
            $operator_id->setMinLength(0);
            $product_id->addItems($array_products);
            $product_id->setMinLength(0);
            $dt_register_sale->setMask('dd/mm/yyyy hh:ii');

            //Busca - Formulário
            $this->form->addTab('Dados', 'mdi mdi-chart-donut');
            $this->form->addFieldLine($product_id,          'Serviço',                  [300, null], false, false, 1);
            $this->form->addFieldLine($operator_id,         'Operador',                 [300, null], false, false, 1);
            $this->form->addFieldLine($dt_register_sale,    'Data do Serviço Marcado',  [200, null], false, false, 2);
            $this->form->addFieldLine($person_id);

            //Botões de ações
            $button = new TButtonPress('Marcar', 'mdi mdi-content-save-settings');
            $button->setAction([$this, 'onSave', ['effect' => false]]);
            $this->form->addButton($button);

            //Gera a form
            $this->form->generate();
            
            //Estrutura da pagina
            $page = new TPageContainer();
            $page_box = $page->createBox(false);
            $page_box->add(ScreenHelper::getHeader(__CLASS__));
            $page_box->add($this->form);
            
            parent::add($page);
        }
        catch (Exception $e) 
        {
            ErrorService::send($e);

            $notify = new TNotify('Ops! Algo deu errado!', $e->getMessage());
            $notify->setIcon('mdi mdi-close');
            $notify->show();
            
            TTransaction::rollback();
        } 
    }

    /**
     * Método onSave()
     * 
     */
    function onSave()
    {
        try
        {
            TTransaction::open($this->db); 

            $user = UserService::getSession();

            //Validação
            $this->form->validate();
            
            //Recupera dados do Post;
            $data = $this->form->getData($this->model);

            $data->person_id = $user->id;

            $operator           = new Operator($data->operator_id);
            $person_individual  = $operator->getPersonIndividual();
            $person             = new Person($person_individual->person_id);
            $client             = new Person($data->person_id);
            $date               = TDateService::timeStampToBr($data->dt_register_sale);

            // if()
            // {
                //Faz o envio
                PipmeService::send('MARCACAO', ['email'], $person, ['main_name' => $person->first_name, 'name_client' => $client->first_name, 'date_hours' => $date]);
            // }
           
            //Grava
            $data->store();

            TTransaction::close();
           
            //Volta os dados para o form
            $this->form->setData($data);
            
            $notify = new TNotify('Sucesso', 'Operação foi realizada');
            $notify->enableNote();
            $notify->show();
            
            parent::closeWindow();
        }
        catch (Exception $e)
        {
            ErrorService::send($e);

            $notify = new TNotify('Ops! Algo deu errado!', $e->getMessage());
            $notify->setIcon('mdi mdi-close');
            $notify->show();
            
            TTransaction::rollback();
        }
    }
}
?>