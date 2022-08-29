<?php
/**
 * UserService
 *
 * @version    1.0
 * @date       23/08/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class UserService
{
    public static function create($param)
    {
        if(empty($param['id']))
        {
            throw new Exception("Parâmetro 'id' não informado");
        }

        $person       = Person::where('id', '=', $param['id'])->get();
        $new_password = null;

        if($person)
        {
            $person            = $person[0];
            $person_individual = $person->getIndividual();
            $person_company    = $person->getCompany();

            //Nao pode criar para pessoa juridica
            if($person_company)
            {
                return false;
            }
            
            $fl_new = false;

            //Verifica se já tem usuario
            $user = $person->getUser();
            
            //Para novos usuários
            if(!$user)
            {
                $user               = new User();
                $user->id           = $person->id;
                $user->code         = TString::getCode();
                $user->dt_register  = date('Y-m-d H:i:s');

                if(!empty($param['lat']) AND $param['long'])
                {
                    $user->lat  = $param['lat'];
                    $user->long = $param['long'];
                }
                
                //Gera a nova senha
                if(!empty($param['password']))
                {
                    $user->password = TString::encrypt($param['password']);
                }
                else
                {
                    $new_password   = TString::generatePassword();
                    $user->password = TString::encrypt($new_password);
                }
            }            

            //Define os grupos
            if(!empty($param['groups']))
            {
                $user->clearGroups();

                foreach ($param['groups'] as $key => $ref_group)
                {
                    $user->addGroup(new Group($ref_group));
                }
            }

            //Se não tem pip, cria
            if(!$user->pip_code)
            {
                $user->pip_code = TApiRestClient::post('pipme', 'subaccount', 'create', [$person->name]);
            }

            //Salva no Razor
            $user->store();
            
            if($new_password)
            {
                UserService::sendNotification('USUARIO_CADASTRO', ['email'], $user, ['tmp_login' => $person->phone, 'tmp_password' => $new_password]);
            }

            return $user;
        }
        else
        {
            throw new Exception("Pessoa não existe");
        }
    }

    public static function setSession($user)
    {
        TSession::setValue('user', $user);
    }

    public static function getSession()
    {
        $user = TSession::getValue('user');

        return $user;
    }
    
    public static function authenticate($login, $password, $platform = null, $application_token = null, $version = null, $fl_std_class = true)
    {
        $person = Person::getByPhone($login);
        
        if($person)
        {
            $user = $person->getUser();

            if($user)
            {
                //Compara a senha
                if($user->password == TString::encrypt($password))
                {
                    //Registra o acesso
                    $user_access                = new UserAccess();
                    $user_access->dt_register   = date('Y-m-d H:i:s');
                    $user_access->user_id       = $user->id;
                    $user_access->session_token = TString::getCode();
                    $user_access->platform      = $platform;
                    $user_access->version       = $version;
                    $user_access->store();
                    
                    if($fl_std_class)
                    {
                        $objUser         = TObject::toStd($user);
                        $objUser->person = $person->toStdClass();
                        
                        return $objUser;
                    }

                    return $user;
                }
                else
                {
                    throw new Exception("Senha incorreta");
                }
            }
            else
            {
                throw new Exception("Você não é um usuário do sistema");
            }
        }
        else
        {
            throw new Exception("Você não é um usuário do sistema");
        }
    }

    public static function isSession()
    {
        $user = TSession::getValue('user');

        if($user)
        {
            return true;
        }

        return false;
    }

    public static function sendNotification($key_template, $methods, $user, $params = null, $push_options = null)
    {
        $person     = $user->getPerson();
        $ini        = parse_ini_file('app/config/application.ini', true);
        
        //Replaces do template
        $replaces              = [];
        $replaces['main_name'] = $person->first_name;
        $replaces['url_razor'] = $ini['general']['url'];
        
        if($params)
        {
            $replaces = $replaces + $params;
        }

        //Faz o envio
        $senders = PipmeService::send($key_template, $methods, $person, $replaces, $push_options);
    }

    public static function updatePassword($id, $pass_new, $pass_new_confirm)
    {
        if($id AND $pass_new AND $pass_new_confirm)
        {
            //Verifica se a chave existe
            $user = User::where('id', '=', $id)->get();

            if($user)
            {
                $user = $user[0];

                if($pass_new == $pass_new_confirm)
                {
                    //Somente se tiver senha
                    if($user->password)
                    {
                        //Valida a senha
                        Person::validatePassword($pass_new);
            
                        $user->password = TString::encrypt($pass_new);
                        $user->store();
    
                        //Atualiza o login
                        self::setSession($user);
                    }
                    else
                    {
                        throw new Exception("Não é possível trocar a senha, sua conta esta vinculada a um login externo");
                    }
                }
                else
                {
                    throw new Exception("Senha nova não confere com a da confirmação");
                }
            }
            else
            {
                throw new Exception("User inválido");
            }
        }
    }

    public static function recover($phone)
    {
        $person = Person::getByPhone($phone);
        
        if($person)
        {
            $user = $person->getUser();
            
            if($user)
            {
                $user->recover();
            }
            else
            {
                throw new Exception("Usuário não existe");
            }
        }
        else
        {
            throw new Exception("Pessoa não encontrada");
        }
    }
}
?>