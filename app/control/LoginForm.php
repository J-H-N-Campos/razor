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
        try
        {
            parent::__construct();
            
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
                TTransaction::open('razor');
                
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
                
                $content_buttons = new TElement('div');
                $content_buttons->class = "content-buttons";

                //Botões de ações
                $button = new TButtonPress('Entrar', 'mdi mdi-subdirectory-arrow-right');
                $button->setAction([$this, 'onLogin']);
                $content_buttons->add($button);

                $button = new TButtonPress('mdi mdi-lock-plus', 'Esqueci minha senha');
                $button->setAction(['RecoveryForm',   'onReload']);
                $content_buttons->add($button);

                //Gera a form
                $this->form->generate();
                
                //Estrutura da pagina
                $page = new TPageContainer();
                $page_box = $page->createBox(false);
                $page_box->add(ScreenHelper::getHeader(__CLASS__));
                $page_box->add("<br/>");
                $page_box->add($this->form);
                $page_box->add($content_buttons);
                $page_box->add("<div style='text-align: center;margin-top: 8px;'>by Razor Tecnologia</br>© 2021</div>");
                
                TTransaction::close();
                
                parent::add($page);
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