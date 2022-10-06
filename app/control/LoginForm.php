<?php
/**
 * LoginForm
 *
 * @version    1.0
 * @date       23/08/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
 
class LoginForm extends TPage
{
    protected $form; 
    private   $db;
    private   $model;
      
    /**
     * Classe construtora
     */
    function __construct()
    {
        parent::__construct();
        
        try
        {
            //Definições de conexão
            $this->db     = 'razor';
            $this->model  = 'Menu';
            $this->parent = "MenuList";

            //Se já existe login
            if(UserService::getSession())
            {
                TServer::redirect('index.php?class=Home');
            }
            else
            {                
                //Cria a form
                $this->form = new TFormStruct();
                $this->form->setAligh('center');
                
                //Entradas
                $login     = new TEntry('login');
                $password  = new TShowPassword('password');
                
                //propriedades
                $login->setProperty('placeholder', 'Seu número de celular');
                $login->setMask('(99) 9 9999-9999');;
                $password->setProperty('placeholder',   'Sua senha');

                //Monta o formulário
                $this->form->addFieldLine('Informe suas credenciais para entrar');
                $this->form->addFieldLine($login,       null,   [300, null], true);
                $this->form->addFieldLine($password,    null,   [300, null], true);
                
                //Botões de ações
                $button = new TButtonPress('Entrar', 'mdi mdi-subdirectory-arrow-right');
                $button->setAction([$this, 'onLogin']);
                $this->form->addButton($button);

                $button = new TButtonPress('mdi mdi-lock-plus', 'Esqueci minha senha');
                $button->setAction(['RecoveryForm', 'onReload']);
                $this->form->addButton($button);

                //Gera a form
                $this->form->generate();
                   
                parent::add($this->form);
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

    function onLogin()
    {
        try
        {
            TTransaction::open($this->db);

            //Validação
            $this->form->validate();
            
            //Recupera dados do Post;
            $data     = $this->form->getData();
            $redirect = null;
            
            //Autentica
            $user = UserService::authenticate($data->login, $data->password, 'admin', null, null, false);

            if(!empty($user->getGroups()))
            {
                self::createSession($user);
                TServer::redirect('index.php?class=LoginForm');
            }
            else
            {
                throw new Exception("Acesso negado");
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

    private static function createSession($user)
    {
        //Salva as telas
        $user->session_screens = $user->getScreens();
        
        //Define na sessão
        UserService::setSession($user);
    }
    
    public static function onLogout()
    {
        TSession::freeSession();
        TServer::redirect('index.php?class=LoginForm');
    }

    public static function updatePermissions()
    {
        try
        {
            TTransaction::open('razor');
            
            $user     = UserService::getSession();
            $new_user = new User($user->id);
            
            self::createSession($new_user);
            
            TTransaction::close();
            
            TServer::reload();
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