<?php

use Adianti\Widget\Form\TCombo;

/**
 * ProductForm
 *
 * @version    1.0
 * @date       23/09/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
 
class ProductForm extends TCurtain
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
            $this->model    = 'Product';
            $this->parent   = "ProductList";

            //Cria a form
            $this->form = new TFormStruct('product_form');

            //Ação do close
            parent::setActionClose([$this->parent]);

            //Entradas
            $id             = new TEntry('id');
            $name           = new TEntry('name');
            $price          = new TNumeric('price', 2, ',', '.',  true);
            $fl_product     = new TSwitch('fl_product');
            $fl_enable      = new TSwitch('fl_enable');
            $qtd_time       = new TCombo('qtd_time');
            $description    = new TText('description');
            $photo          = new TFileUpload('photo');

            //Propriedades das entradas
            $id->setEditable(false);
            $qtd_time->addItems(['15' => '15 Minutos', '30' => '30 Minutos', '45' => '45 Minutos', '60' => '60 Minutos']);

            //Monta o formulário
            $this->form->addTab('Formulário', 'mdi mdi-database');
            $this->form->addFieldLine($id,          'Id',           [80,  null]);
            $this->form->addFieldLine($name,        'Nome',         [250, null], true,  false, 1);
            $this->form->addFieldLine($price,       'Preço',        [100, null], true,  false, 1);
            $this->form->addFieldLine($description, 'Descrição',    [450, 150],  false, false, 2);
            $this->form->addFieldLine($photo,       'Foto',         [250, null], false, false, 3);
            $this->form->addFieldLine($fl_product,  'É produto?', null, true, 'Se não marcado, será considerado um serviço');
            $this->form->addFieldLine($fl_enable,   'Está habilitado para o cliente?', null, true, 'Se não marcado, não será mostrado para o cliente');

            //serviços
            $this->form->addTab('Serviços', 'mdi mdi-toolbox-outline');
            $this->form->addFieldLine($qtd_time, 'Qtd. Tempo', [200, null], false, 'Defina a quantidade de tempo para o seu serviço aqui');

            //Botões de ações
            $button = new TButtonPress('Gravar', 'mdi mdi-content-save-settings');
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

            //Validação
            $this->form->validate();
            
            //Recupera dados do Post;
            $data = $this->form->getData($this->model);

            //se não tiver fl_product, então é um serviço e precisa ter qtd_time
            if($data->fl_product == 'f' AND !$data->qtd_time)
            {
                throw new Exception("Não existe quantidade tempo para o serviço, por favor preencher o campo que define o tempo de serviço");
            }

            if($data->fl_product == 't'  AND $data->qtd_time)
            {
                throw new Exception("Não é possível adicionar tempo para o produto");
            }

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
    
    /**
     * Método onEdit()
     * 
     */
    function onEdit($param)
    {
        try
        {
            if (isset($param['key']))
            {
                $key = $param['key'];
                
                TTransaction::open($this->db);
                
                $object = new $this->model($key);

                $this->form->setData($object);
                
                TTransaction::close();
            }
            else
            {
                $this->form->clear();
            }
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