<?php

use Adianti\Widget\Form\TUniqueSearch;

/**
 * SaleAdminList
 *
 * @version    1.0
 * @date       30/09/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
 
class SaleAdminList extends TPage
{
    private $loaded;
    private $datagrid;
    private $db;
    private $model;
    private $page_navigation;
    private $form;
    
    /**
     * Classe contrutora
     * 
     */
    public function __construct()
    {
        try
        {
            parent::__construct();
            
            //Definições de conexão
            $this->db     = 'razor';
            $this->model  = 'Sale';
            
            //Busca - Cria a form
            $this->form = new TFormStruct();
            $this->form->enablePostSession($this->model);

            //Busca - Entradas
            $product_id     = new TDBUniqueSearch('product_id',     $this->db, 'Product',   'id', 'name');
            $person_id      = new TDBUniqueSearch('person_id',      $this->db, 'Person',    'id', 'name');
            $operator_id    = new TUniqueSearch('operator_id');
            $dt_service     = new TDate('dt_service');

            $array_operators = Operator::getArrayOperators();
            $operator_id->addItems($array_operators);
            $operator_id->setMinLength(0);
            $product_id->setMinLength(0);
            $person_id->setMinLength(0);
            $dt_service->setMask('dd/mm/yyyy');

            //Busca - Formulário
            $this->form->addTab('Dados', 'mdi mdi-chart-donut');
            $this->form->addFieldLine($product_id,  'Produto',          [300, null], false, false, 1);
            $this->form->addFieldLine($person_id,   'Pessoa',           [300, null], false, false, 1);
            $this->form->addFieldLine($operator_id, 'Operador',         [300, null], false, false, 1);
            $this->form->addFieldLine($dt_service,  'Data do Serviço',  [120, null], false, false, 2);

            //Busca - Ações
            $button = new TButtonPress('Filtrar', 'mdi mdi-filter');
            $button->setAction([$this, 'onSearch']);
            $this->form->addButton($button);

            //Busca - Gera a forma
            $this->form->generate();
            
            //Cria datagrid
            $this->datagrid = new TDataGridResponsive;
            $this->datagrid->setConfig(false);
            $this->datagrid->setDb($this->db);

            //Colunas
            $this->datagrid->addColumnReduced('dt_service', 'mdi mdi-calendar-check', ['TDateService', 'timeStampToBr'], 'Data do serviço realizado');

            $this->datagrid->addColumn('id',            'Id');
            $this->datagrid->addColumn('product_id',    'Produto');
            $this->datagrid->addColumn('person_id',     'Pessoa');
            $this->datagrid->addColumn('operator_id',   'Operador');

            //Nevegação
            $this->page_navigation = new TPageNavigation;
            $this->page_navigation->setAction(new TAction([$this, 'onReload']));
            $this->page_navigation->setWidth($this->datagrid->getWidth());
            $this->datagrid->setPageNavigation($this->page_navigation);
            
            //Estrutura da pagina
            $page = new TPageContainer();
            $page_box = $page->createBox(false);
            $page_box->add(ScreenHelper::getHeader(__CLASS__));
            $page_box->add($this->form);
            $page_box->add($this->datagrid);
            $page_box->add($this->page_navigation, 'false');
            
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
     * Method onSearch()
     * Registra uma busca na sessão
     */
    function onSearch()
    {
        $data           = $this->form->getData();
        $session_name   = $this->form->getPostSessionName();
        $filters        = [];

        if($data->dt_service)
        {
            $filters[]  = new TFilter('dt_service::date', '=', $data->dt_service);
        }

        if($data->operator_id)
        {
            $filters[]  = new TFilter('operator_id', '=', $data->operator_id);
        }
        
        if($data->person_id)
        {
            $filters[]  = new TFilter('person_id', '=', $data->person_id);
        }

        if($data->product_id)
        {
            $filters[]  = new TFilter('product_id', '=', $data->product_id);
        }

        //Registra o filtro na sessão
        TSession::setValue("filters_{$session_name}", $filters);

        //Recarrega a página
        $this->form->setData($data);
        $this->onReload(['offset' => 0, 'first_page' => 1]);
    }
    
    /**
     * Method onReload()
     * Carrega dados para a tela
     */
    function onReload($param = NULL)
    {
        try
        {
            TTransaction::open($this->db);

            //Cria filtros
            $criteria = new TCriteria;
            $limit    = 15;

            // default order
            if (empty($param['order']))
            {
                $param['order']     = 'id';
                $param['direction'] = 'desc';
            }
    
            //Define ordenação e limite da pagina
            $criteria->setProperties($param);
            $criteria->setProperty('limit', $limit);
                
            //Sessão de filtros da form
            $session_name = $this->form->getPostSessionName();
    
            //Se tiver filtros, aplica
            if ($filters = TSession::getValue("filters_{$session_name}"))
            {
                foreach ($filters as $filter)
                {
                    $criteria->add($filter);
                }
            }

            //Carrega os objetos
            $repository = new TRepository($this->model);
            $objects    = $repository->load($criteria, true);
            $this->datagrid->clear();

            if($objects)
            {
                //Percorre os resultados
                foreach ($objects as $object)
                {
                    $product            = $object->getProduct();
                    $person             = $object->getPerson();
                    $operator           = $object->getOperator();
                    $person_operator    = $operator->getPersonOperator();

                    $object->product_id     = $product->name;
                    $object->person_id      = $person->name;
                    $object->operator_id    = $person_operator->name;
                    
                    $this->datagrid->addItem($object);
                }
            }

            $criteria->resetProperties();
            $this->page_navigation->setCount($repository->count($criteria));
            $this->page_navigation->setProperties($param);
            $this->page_navigation->setLimit($limit);
            $this->loaded = true;
            
            TTransaction::close();
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
    
    function show()
    {
        if (!$this->loaded)
        {
            $this->onReload( func_get_arg(0) );
        }
        
        parent::show();
    }
}
?>