<?php
/**
 * OperatorForm
 *
 * @version    1.0
 * @date       26/09/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
 
class OperatorForm extends TCurtain
{
    protected $form; 
    private   $db;
    private   $model;
      
    /**
     * Classe construtora
     */
    function __construct($param = null)
    {
        try
        {
            parent::__construct($param);
            
            //Definições de conexão
            $this->db     = 'razor';
            $this->model  = 'Operator';
            $this->parent = 'OperatorList';

            if(!empty($param['return']))
            {
                parent::setActionClose([$param['return']]);
            }
            else
            {
                parent::setActionClose(['PersonList']);
            }

            TTransaction::open('razor');

            //Cria a form
            $this->form  = new TFormStruct('form_operator');

            //Entradas
            $person_name = new TEntry('person');
            $person_individual_id = new TEntry('person_individual_id');
     
            //Funções das entradas
            $person_individual_id->setEditable(false);
            $person_name->setEditable(false);

            if(!empty($param['key']))
            {
                $person = Person::where('id', '=', $param['key'])->get();
                $person = $person[0];
                $person_name->setValue($person->name);
                $person_individual_id->setValue($param['key']);
            }
            
            //Monta o formulário
            $this->form->addTab('Formulário', 'mdi mdi-database');
            $this->form->addFieldLine($person_individual_id, 'Id', [450, null]);
            $this->form->addFieldLine($person_name, 'Pessoa', [450, null]);

            //Botões de ações
            $button = new TButtonPress('Gravar', 'mdi mdi-content-save-settings');

            if(!empty($param['return']))
            {
                $button->setAction([$this, 'onSave', ['effect' => false, 'return' => $param['return']]]);
            }
            else
            {
                $button->setAction([$this, 'onSave', ['effect' => false]]);
            }
            
            $this->form->addButton($button);

            //Gera a form
            $this->form->generate();
            
            //Estrutura da pagina
            $page = new TPageContainer();
            $page_box = $page->createBox(false);
            $page_box->add(ScreenHelper::getHeader(__CLASS__));
            $page_box->add($this->form);
            
            parent::add($page);
            
            TTransaction::close();
        }
        catch (Exception $e) 
        {
            ErrorService::send($e);

            $notify = new TNotify('Ops! Algo deu errado!', $e->getMessage());
            $notify->setIcon('mdi mdi-close');
            $notify->show();
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

            $person = Person::where('id', '=', $data->person_individual_id)->get();
            $person = $person [0];
            $user   = $person->getUser();

            if($user)
            {
                $user_group = UserGroup::where('user_id', '=', $user->id)->get();
                $user_group = $user_group[0];

                $new_user_group = new UserGroup($user_group->id);
                $new_user_group->user_id = $user->id;
                $new_user_group->group_id = 1;
                $new_user_group->store();
            }
            else
            {
                throw new Exception("Para criar o operador para essa pessoa, ela primeiramente precisa ser um usuário do sistema.");
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
                TTransaction::open($this->db);

                $object = new $this->model($param['key']);

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