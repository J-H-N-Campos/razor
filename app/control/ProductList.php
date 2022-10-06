<?php

/**
 * ProductList
 *
 * @version    1.0
 * @date       23/09/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
 
class ProductList extends TPage
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
            $this->model  = 'Product';
            $this->parent = 'ProductForm';
            
            //Busca - Cria a form
            $this->form = new TFormStruct();
            $this->form->enablePostSession($this->model);
            
            //Busca - Entradas
            $Id     = new TEntry('id');
            $name   = new TEntry('name');

            //Busca - Formulário
            $this->form->addTab('Dados',    'mdi mdi-chart-donut');
            $this->form->addFieldLine($Id,      'Id',   [80,  null], false, false, 1);
            $this->form->addFieldLine($name,    'Nome', [300, null], false, false, 1);

            //Busca - Ações
            $button = new TButtonPress('Filtrar', 'mdi mdi-filter');
            $button->setAction([$this, 'onSearch']);
            $this->form->addButton($button);

            $button = new TButtonPress('Novo', 'mdi mdi-plus');
            $button->setAction([$this->parent, 'onEdit']);
            $this->form->addButton($button);

            //Busca - Gera a forma
            $this->form->generate();
            
            //Cria datagrid
            $this->datagrid = new TDataGridResponsive;
            $this->datagrid->setConfig(false);
            $this->datagrid->setDb($this->db);

            //Colunas
            $this->datagrid->addColumn('id',        'Id');
            $this->datagrid->addColumn('name',      'Nome');
            $this->datagrid->addColumn('price',     'Preço', ['TCoin', 'Tobr']);
            $this->datagrid->addColumn('product',   'Produto/Serviço');

            //Ações
            $this->datagrid->addGroupAction('mdi mdi-dots-vertical');
            $this->datagrid->addGroupActionButton('Editar',     'mdi mdi-pencil',   [$this->parent, 'onEdit']);
            $this->datagrid->addGroupActionButton('Deletar',    'mdi mdi-delete',   [$this, 'onDelete']);

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

        if($data->id)
        {
            $filters[]  = new TFilter('id', '=', $data->id);
        }

        if($data->name)
        {
            $filters[]  = new TFilter('name', 'ILIKE', "%$data->name%");
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

            if ($objects)
            {
                //Percorre os resultados
                foreach ($objects as $object)
                {
                    if($object->fl_product)
                    {
                        $object->product = TInterface::getBoxStatus('Produto', '#ffa500');
                    }
                    else
                    {
                        $object->product = TInterface::getBoxStatus('Serviço', '#808080');
                    }

                    if($object->fl_enable)
                    {
                        $this->datagrid->addItem($object);
                    }
                    else
                    { 
                        $this->datagrid->addItem($object, "background-color: #ffffff; opacity: 0.3;");
                    }
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
    
    /**
     * Method onDelete()
     * Executa uma confirmação se tem ou não certeza antes de deletar
     * 
     */
    function onDelete($param)
    {
        //Ação de delete
        $action = new TAction([$this, 'delete']);
        $action->setParameters($param);
        
        //Pergunta
        $notify = new TNotify('Apagar registro', 'Você tem certeza que quer apagar este(s) registro(s)?');
        $notify->setIcon('mdi mdi-help-circle-outline');
        $notify->addButton('Sim', $action);
        $notify->addButton('Não', null);
        $notify->show();
    }
    
    /**
     * Method Delete()
     * Deleta o cadastro
     * 
     */
    function delete($param)
    {
        try
        {
            //Abre transação
            TTransaction::open($this->db);
            
            $object = new $this->model($param['key']);
            $object->delete();  
            
            TTransaction::close();

            //Avisa que foi excluido
            $notify = new TNotify('Sucesso', 'Operação foi realizada');
            $notify->enableNote();
            $notify->setAutoRedirect([$this, 'onReload']);
            $notify->show();
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
     * Method show()
     * Exibe conteúdos pertencentes a tela criada
     * 
     */
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