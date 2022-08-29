<?php
/**
 * RecoveryForm 
 *
 * @version    1.0
 * @date       23/08/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
 
class RecoveryForm  extends TPage
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
            
            //Definições de conexão
            $this->db     = 'razor';
            $this->model  = 'Menu';

            //Cria a form
            $this->form = new TFormStruct();
            $this->form->setAligh('center');
            
            //Entradas
            $phone = new TEntry('phone');

            $phone->setMask('(99) 99999-9999');
            
            //Monta o formulário
            $this->form->addFieldLine($phone, 'Seu Telefone',  [300, null], true);

            $content_buttons = new TElement('div');
            $content_buttons->class = "content-buttons";

            //Botões de ações
            $button = new TButtonPress('Recuperar', 'mdi mdi-content-save-settings');
            $button->setAction([$this, 'onRecover']);
            $content_buttons->add($button);

            $button = new TButtonPress('Voltar', 'mdi mdi-keyboard-return');
            $button->setAction(['LoginForm', 'onEdit']);
            $content_buttons->add($button);

            //Gera a form
            $this->form->generate();
            
            //Estrutura da pagina
            $page = new TPageContainer();
            $page_box = $page->createBox(false);
            $page_box->add("<br/>");
            $page_box->add($this->form);
            $page_box->add($content_buttons);

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

    function onRecover()
    {
        try
        {
            TTransaction::open($this->db);

            //Validação
            $this->form->validate();
            
            //Recupera dados do Post;
            $data = $this->form->getData();
            
            UserService::recover($data->phone);

            TTransaction::close();
            
            $notify = new TNotify('Certo', 'Verifique seu e-mail e seu telefone, enviamos as instruções para recuperação da sua senha');
            $notify->setIcon('mdi mdi-help-circle-outline');
            $notify->addButton('Ok', ['LoginForm']);
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
   
    public static function onLogout()
    {
        TSession::freeSession();
        TServer::redirect('index.php?class=LoginForm');
    }
}
?>