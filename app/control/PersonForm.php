<?php
/**
 * PersonForm
 *
 * @version    1.0
 * @date       23/08/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
 
class PersonForm extends TCurtain
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
            
            TTransaction::open('razor');
            
            //Definições de conexão
            $this->db     = 'razor';
            $this->model  = 'Person';
            $this->parent = "PersonList";

            parent::setExpanded("70%");
            
            if(!empty($param['return']))
            {
                if(!empty($param['return_params']))
                {
                    parent::setActionClose([$param['return'], null, $param['return_params']]);
                }
                else
                {
                    parent::setActionClose([$param['return']]);
                }
            }
            else
            {
                parent::setActionClose([$this->parent]);
            }

            //Cria a form
            $this->form  = new TFormStruct('person_form');

            $criteria    = new TCriteria();
            $criteria->add(new TFilter('id', 'IN', "NOESC: (SELECT bas_person_individual.person_id FROM bas_person_individual WHERE bas_person_individual.person_id = bas_person.id )"));
            
            //Entradas
            //Pessoa Física
            $id             = new THidden('id');
            $code           = new THidden('code');
            $name           = new TEntry('name');
            $birth_date     = new TEntry('birth_date');
            $cpf            = new TEntry('cpf');
            $email          = new TEntry('email');
            $phone          = new TEntry('phone');
            $genre          = new TCombo('genre');

            //Pessoa Jurídica
            $name_fantasy   = new TEntry('name_fantasy');
            $cnpj           = new TEntry('cnpj');
            $owner_id       = new TDBUniqueSearch('owner_id', $this->db, 'Person', 'id', 'name', 'name', $criteria);

            //atributos
            $owner_id->setMinLength(0);
            $owner_id->setOperator('ilike');
            $owner_id->setMask('{id} - {name} {aux}');
            $cpf->setMask('999.999.999-99');
            $cnpj->setMask('99.999.999/9999-99');
            $birth_date->setMask('99/99/9999');
            $phone->setMask('(99)99999-9999');
            $genre->addItems(['F' => 'Feminino', 'M' => 'Maiusculo', 'O' => 'Outros']);

            //$cpf->setExitAction(new TAction([$this, 'onCpf']));
            $email->setExitAction(new TAction([$this, 'onCheckEmail']));
            $phone->setExitAction(new TAction([$this, 'onCheckPhone']));

            //Monta o formulário
            $this->form->addTab('Pessoa Física', 'mdi mdi-human-male');
            $this->form->addFieldLine(TInterface::getHelp('Evite duplicação de cadastro quando não informar o CPF, consulte antes para ver se a pessoa ja existe', 'mdi mdi-information-outline'));
            $this->form->addFieldLine($cpf,         'CPF',                  [150, null], false, null, 1);
            $this->form->addFieldLine($birth_date,  'Data de nascimento',   [150, null], false, null, 1);
            $this->form->addFieldLine($name,        'Nome',                 [400, null]);
            $this->form->addFieldLine($genre,       'Gênero',               [150, null]);
            $this->form->addFieldLine($code);
            $this->form->addFieldLine($id);

            //pessoa jurídica
            $this->form->addTab('Pessoa Jurídica', 'mdi mdi-domain');
            $this->form->addFieldLine($owner_id,        'Dono',             [700, null]);
            $this->form->addFieldLine($name_fantasy,    'Nome fantasia',    [450, null]);
            $this->form->addFieldLine($cnpj,            'CNPJ',             [200, null]);

            //contato
            $this->form->addTab('Contato', 'mdi mdi-card-account-mail');
            $this->form->addFieldLine($phone,   'Telefone', [250, null]);
            $this->form->addFieldLine($email,   'E-mail',   [400, null]);
            
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
            
            $button = new TButtonPress('Novo', 'mdi mdi-plus');
            $button->setAction([$this, 'onEdit']);
            $this->form->addButton($button);

            //Gera a form
            $this->form->generate();
            
            TTransaction::close();
            
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

    public static function onCheckEmail($param)
    {
        try
        {
            TTransaction::open('razor');

            $id = null;

            if(isset($param['id']) AND !empty($param['id']))
            {
                $id = $param['id'];
            }

            //Desconsidera se for uma pessoa juridica
            if($param['cnpj'] OR $param['name_fantasy'] OR $param['owner_id'])
            {
                return true;
            }
            elseif(isset($param['email']) AND !empty($param['email']))
            {
                $email = strtolower($param['email']);

                if($id)
                {
                    $objCheck = Person::where('email', '=',  $email)
                                         ->where('id', '!=', $id)
                                         ->where('id', 'IN', "NOESC: (SELECT bas_person_individual.person_id FROM bas_person_individual WHERE bas_person_individual.person_id = bas_person.id)")
                                         ->get();

                    if($objCheck)
                    {
                        //Verifica somente para a fisica
                        $objCheck = $objCheck[0];
                        
                        $notify = new TNotify("Este E-MAIL já está sendo usado por outra pessoa ({$objCheck->id} - {$objCheck->name}). Você não pode usá-lo para este cadastro");
                        $notify->setIcon('mdi mdi-close');
                        $notify->show();

                        TScript::create("$('[name=email]').val('')");
                    }
                }
                else
                {
                    $objCheck = Person::where('email', '=', $email)
                                                ->where('id', 'IN', "NOESC: (SELECT bas_person_individual.person_id FROM bas_person_individual WHERE bas_person_individual.person_id = bas_person.id)")
                                                ->get();
                    if($objCheck)
                    {
                        $objCheck = $objCheck[0];

                        $notify = new TNotify("Este E-MAIL já está sendo usado por outra pessoa ({$objCheck->id} - {$objCheck->name}). Se você gravar este formulário o sistema atualizará a pessoa que já existe e não criará uma nova.");
                        $notify->setIcon('mdi mdi-close');
                        $notify->show();
                    }
                }
            }

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

    public static function onCheckPhone($param)
    {
        try
        {
            TTransaction::open('razor');

            $id = null;

            if(isset($param['id']) AND !empty($param['id']))
            {
                $id = $param['id'];
            }

            //Desconsidera se for uma pessoa juridica
            if($param['cnpj'] OR $param['name_fantasy'] OR $param['owner_id'])
            {
                return true;
            }
            elseif(isset($param['phone']) AND !empty($param['phone']))
            {
                $phone = TString::preparePhone($param['phone']);
                
                if($id)
                {
                    $objCheck = Person::where('phone', '=',  $phone)
                                         ->where('id', '!=', $id)
                                         ->where('id', 'IN', "NOESC: (SELECT bas_person_individual.person_id FROM bas_person_individual WHERE bas_person_individual.person_id = bas_person.id)")
                                         ->get();
                    if($objCheck)
                    {
                        //Verifica somente para a fisica
                        $objCheck = $objCheck[0];
                        
                        $notify = new TNotify("Este telefone já está sendo usado por outra pessoa ({$objCheck->id} - {$objCheck->name}). Você não pode usá-lo para este cadastro");
                        $notify->setIcon('mdi mdi-close');
                        $notify->show();

                        TScript::create("$('[name=phone]').val('')");
                    }
                }
                else
                {
                    $objCheck = Person::where('phone', '=', $phone)
                                                ->where('id', 'IN', "NOESC: (SELECT bas_person_individual.person_id FROM bas_person_individual WHERE bas_person_individual.person_id = bas_person.id)")
                                                ->get();
                    if($objCheck)
                    {
                        $objCheck = $objCheck[0];

                        $notify = new TNotify("Este Telefone já está sendo usado por outra pessoa ({$objCheck->id} - {$objCheck->name}). Se você gravar este formulário o sistema atualizará a pessoa que já existe e não criará uma nova.");
                        $notify->setIcon('mdi mdi-close');
                        $notify->show();
                    }
                }
            }

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
    
    public static function clearFields($type)
    {
        if($type == 'person_individual')
        {
            TField::clearField('person_form', 'name');
            TField::clearField('person_form', 'cpf');
            TField::clearField('person_form', 'birth_date');
            TField::clearField('person_form', 'genre');
        }
        
        if($type == 'person_company')
        {
            TField::clearField('person_form', 'name');
            TField::clearField('person_form', 'cnpj');
            TField::clearField('person_form', 'name_fantasy');
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
            
            $user  = UserService::getSession();

            //Validação
            $this->form->validate();
            
            //Recupera dados do Post;
            $data = $this->form->getData($this->model);

            $param          = [];
            $param['id']    = $data->id; 
            $param['code']  = $data->code; 
            $param['name']  = $data->name; 
            $param['phone'] = $data->phone; 
            $param['email'] = $data->email;

            //Para pessoa fisica
            if($data->birth_date OR $data->cpf OR $data->genre)
            {
                $param['person_individual']['cpf']         = $data->cpf; 
                $param['person_individual']['birth_date']  = TDate::date2us($data->birth_date);
                $param['person_individual']['genre']       = $data->genre; 
            }

            //Para empresa
            if($data->name_fantasy OR $data->cnpj)
            {
                $param['person_company']['cnpj']            = $data->cnpj;
                $param['name']                              = $data->name_fantasy;
                $param['person_company']['name_fantasy']    = $data->name_fantasy;
                $param['person_company']['owner_id']        = $data->owner_id;
            }

            //Apenas cria a pessoa
            $person = PersonService::create($param);

            TTransaction::close();

            //Volta os dados para o form
            $this->form->setData($data);
           
            $notify = new TNotify('Sucesso', 'Operação foi realizada');
            $notify->enableNote();
            $notify->show();
            
            if(!empty($param['return']))
            {
                parent::closeWindow(['person_id' => $person->id, 'return' => $param['return']]);
            }
            else
            {
                parent::closeWindow(['person_id' => $person->id]);
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
    
    /**
     * Método onEdit()
     * 
     */
    function onEdit($param)
    {
        try
        {
            if(isset($param['code']))
            {
                TTransaction::open($this->db);

                $person             = Person::getByCode($param['code']);
                $person_individual  = $person->getIndividual();
                $person_company     = $person->getCompany();

                if($person_individual)
                {
                    $person->birth_date = TDate::date2br($person_individual->birth_date);
                    $person->cpf        = $person_individual->cpf;
                    $person->genre      = $person_individual->genre;

                    //$this->form->removeTab('Pessoa Juridica');
                }

                if($person_company)
                {
                    $person->name_fantasy   = $person_company->name_fantasy;
                    $person->cnpj           = $person_company->cnpj;
                    $person->owner_id       = $person_company->owner_id;
                    $person->name           = null;
                    
                    //$this->form->removeTab('Pessoa Física');
                }

                $this->form->setData($person);
                
                TTransaction::close();
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