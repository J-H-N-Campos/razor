<?php
/**
 * PersonService
 *
 * @version    1.0
 * @date       23/08/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class PersonService
{
    public static function create($param, $fl_return_data = false, $fl_create_user = false)
    {
        $person            = null;
        $person_individual = null;
        $person_company    = null;
        
        //Se for passado person code
        if(!empty($param['code']))
        {
            $person = Person::getByCode($param['code']);
            
            if($person)
            {
                $person_individual = $person->getIndividual();
                $person_company    = $person->getCompany();
            }
            else
            {
                throw new Exception("Pessoa code {$param['code']} não existe");
            }
        } 
        elseif(!empty($param['id']))
        {
            $person = Person::where('id', '=', $param['id'])->get();

            if($person)
            {
                $person            = $person[0];
                $person_individual = $person->getIndividual();
                $person_company    = $person->getCompany();
            }
            else
            {
                throw new Exception("Pessoa id {$param['id']} não existe");
            }
        }

        //Se não tiver person, cria
        if(!$person)
        {
            $person = new Person();
        }

        //Validação para fisica
        if(!empty($param['person_individual']))
        {
            //Se não tem ainda
            if(empty($person_individual))
            {
                $person_individual = new PersonIndividual();
            }
            
            //Se informado cpf
            if(!empty($param['person_individual']['genre']))
            {
                $person_individual->genre = $param['person_individual']['genre'];
            }
            
            //Se informado cpf
            if(!empty($param['person_individual']['cpf']))
            {
                $cpf = TString::prepareStrigDocument($param['person_individual']['cpf']);

                //Valida o cpf
                $validator = new TCPFValidator();
                $validator->validate('CPF', $cpf);

                //Para edição
                if(!empty($person_individual->person_id))
                {
                    //Verifica se mudou
                    if($person_individual->cpf != $cpf)
                    {
                        //Verifica se tem outro com este cpf
                        $person_validade = PersonIndividual::where('cpf', '=', $cpf)->get();
    
                        if($person_validade)
                        {
                            throw new Exception("CPF {$cpf} já está sendo usando por outra pessoa");
                        }
                    }
                }
                //Para novo
                else
                {
                    //Verifica se tem outro com este cpf
                    $person_validade = PersonIndividual::where('cpf', '=', $cpf)->get();
                    
                    //Tem
                    if($person_validade)
                    {
                        //Verifique se a pessoa tem usuario
                        $person_individual = $person_validade[0];
                        $person            = $person_individual->getPerson();
                        $user              = $person->getUser();
                        
                        //Somente se ja tem um cpf
                        if($user)
                        {
                            throw new Exception("CPF {$cpf} já está sendo usando por outra pessoa");
                        }
                        else
                        {
                            //Deixa passar
                        }
                    }
                }
    
                //Atribui
                $person_individual->cpf = $cpf;
            }

            if(!empty($param['person_individual']['birth_date']))
            {
                //Valida a data
                if(!TDateService::validate($param['person_individual']['birth_date']))
                {
                    throw new Exception("Parâmetro 'birth_date' é invalido ou não está no formato correto");
                }
                
                $person_individual->birth_date = $param['person_individual']['birth_date'];
            }
        }

        //Validação para juridica
        if(!empty($param['person_company']))
        {
            if(empty($param['person_company']['cnpj']))
            {
                throw new Exception("Informe um CNPJ");
            }
            
            $cnpj = TString::prepareStrigDocument($param['person_company']['cnpj']);

            //Valida o cpf
            $validator = new TCNPJValidator();
            $validator->validate('cnpj', $cnpj);

            //Para edição
            if($person_company)
            {
                //Verifica se mudou
                if($person_company->cnpj != $cnpj)
                {
                    //Verifica se tem outro com este cpf
                    $person_validade = PersonCompany::where('cnpj', '=', $cnpj)->get();

                    if($person_validade)
                    {
                        throw new Exception("CNPJ {$cnpj} já está sendo usando por outra pessoa");
                    } 
                }
            }
            //Para novo
            else
            {
                //Verifica se tem outro com este cpf
                $person_validade = PersonCompany::where('cnpj', '=', $cnpj)->get();

                if($person_validade)
                {
                    throw new Exception("CNPJ {$cnpj} já está sendo usando por outra pessoa");
                }

                //Cria nova
                $person_company = new PersonCompany();
            }

            //Atribui
            $person_company->cnpj = $cnpj;

            //Validação do owner
            if(!empty($param['person_company']['owner_id']))
            {
                //Se ele ja tem
                if(!empty($person_company->owner_id))
                {
                    //Verifica se é igual
                    if(!$person->id AND $person_company->owner_id != $param['person_company']['owner_id'])
                    {
                        throw new Exception("CNPJ '{$cnpj}' já possui um dono cadastrado, portanto não pode ser usado por você");
                    }
                }

                $person_company->owner_id = $param['person_company']['owner_id'];
            }

            if(!empty($param['person_company']['name_fantasy']))
            {
                $person_company->name_fantasy = $param['person_company']['name_fantasy'];
            }
        }
            
        if(!empty($param['name']))
        {
            $person->name = $param['name'];
        }

        //Se for passado EMAIL
        if(!empty($param['email']))
        {
            //Valida email
            $validator = new TEmailValidator();
            $validator->validate('Email', $param['email']); 

            //Add
            $person->email = $param['email'];
        }
        else
        {
            throw new Exception('O e-mail é obrigatório');
        }

        //Se for passado phone
        if(!empty($param['phone']))
        {
            $person->phone = TString::preparePhone($param['phone']);
            
            //Validação somente para fisica
            if(!empty($param['person_individual']))
            {
                if(empty($person->id))
                {
                    $person_check = self::getByPhoneType($param['phone'], 'individual');
                    
                    if($person_check)
                    {
                        $person_check->name = $person->name;
                        $person             = $person_check;
                    }
                }
                
                $phone_check = null;

                if($person->id)
                {
                    //Verifica duplicação
                    $phone_check = Person::where('phone',   '=', TString::preparePhone($param['phone']))
                                             ->where('id',  '!=', $person->id)
                                             ->where('EXISTS',  '', "NOESC: (SELECT * FROM bas_person_individual WHERE bas_person_individual.person_id = bas_person.id)")
                                             ->get();
                    if($phone_check)
                    {
                        $phone_check = $phone_check[0];
                        
                        if($phone_check->id != $person->id)
                        {
                            throw new Exception("{$param['phone']} não pertence a pessoa originalmente cadastrada");
                        }
                    }
                }
                else
                {
                    //Verifica duplicação
                    $phone_check = Person::where('phone', '=', TString::preparePhone($param['phone']))
                                             ->where('EXISTS', '', "NOESC: (SELECT * FROM bas_person_individual WHERE bas_person_individual.person_id = bas_person.id)")
                                             ->get();
                }

                if($phone_check)
                {
                    throw new Exception("{$param['phone']} já está sendo usado por outra pessoa");
                }
            }
            else
            {
                if(empty($person->id))
                {
                    $person_check = self::getByPhoneType($param['phone'], 'company');

                    if($person_check)
                    {
                        $person_check->name = $person->name;
                        $person             = $person_check;
                    }
                }
            }

            //Add
            $person->phone = $param['phone'];
        }
        else
        {
            throw new Exception('O telefone é obrigatório');
        }

        //Tipo
        $person->setIndividual($person_individual);
        $person->setCompany($person_company);

        //Salva
        $person->store();
        
        //Cria o usuário
        if($fl_create_user)
        {
            $user = UserService::create(['id' => $person->id, 'password' => $param['password']]);
        }

        if($fl_return_data)
        {
            if(!empty($user))
            {
                $objUser                   = TObject::toStd($user);
                $objUser->person           = $person->toStdClass();
                $objUser->password_default = null;
                
                //Password default
                if($user->password_default)
                {
                    $objUser->password_default = $user->password_default;
                }
                
                return $objUser;
            }
            else
            {
                return $person->toStdClass();
            }
        }

        return $person;
    }
    
    public static function getByCode($code)
    {
        if($person = Person::where('code','=', $code)->get())
        {
            $personStd = new StdClass();
            
            $person = $person[0];
            
            $personStd->person = TObject::toStd($person);
            
            $individual = $person->getIndividual();
            
            $personStd->person->individual = TObject::toStd($individual);
            
            $user = new User($person->id);
            
            $personStd->user = TObject::toStd($user);
            
            return $personStd;
        }
        
        return null;
    }
    
    public static function getByCpf($cpf)
    {
        $person_individual = PersonIndividual::where('cpf','=',str_replace(['-','.'],['',''],$cpf))->get();
        $person = null;
        
        if($person_individual)
        {
            $person_individual = $person_individual[0];
            $person            = $person_individual->getPerson();
            $user              = $person->getUser();
            
            if($user)
            {
                $personStd                     = new StdClass();
                $personStd->person             = TObject::toStd($person);
                $personStd->person->individual = TObject::toStd($person_individual);
                $personStd->user               = TObject::toStd($user);
                
                return $personStd;
            }
        }
    }
    
    public static function updateByObject($person)
    {
        //transforma para array para mandar para a função create
        $params                 = (array) TObject::toStd($person);
        $individual             = $person->getIndividual();
        $params['individual']   = (array) TObject::toStd($individual);
        
        self::create($params);
    }
    
    //Preenche ou por cpf
    public static function onFillByCode($key, $type = 'cpf')
    {
        TTransaction::open('razor');

        $person = null;
        
        if($type == 'cpf')
        {
            $person_individual = PersonIndividual::getByCpf($key);
        }

        if($person_individual)
        {
            //Ja existe
            $person = $person_individual->getPerson();
            $person = TObject::merge($person, $person_individual);
            
            //Garante para edicao do formularios
            $person->person_code = $person->code;
            $person->birth_date  = TDate::date2br($person->birth_date);
        }
        
        if($type == 'cpf')
        {
            unset($person->cpf);
        }
        
        TTransaction::close();

        return $person;
    }

    public static function getByEmailType($email, $type)
    {
        $objPerson = Person::where('email', '=', strtolower(trim($email)))->get();

        //Se tiver pessoa
        if($objPerson)
        {
            foreach ($objPerson as $key => $person) 
            {
                //Se for fisica
                if($type == 'individual' AND $person->person_individual)
                {
                    return $person;
                }
                elseif($type == 'company' AND $person->person_company)
                {
                    return $person;
                }
            }
        }

        return false;
    }

    public static function getByPhoneType($phone, $type)
    {
        $phone = TString::preparePhone($phone);

        $objPerson = Person::where('phone', '=', $phone)->get();

        //Se tiver pessoa
        if($objPerson)
        {
            foreach ($objPerson as $key => $person) 
            {
                //Se for fisica
                if($type == 'individual' AND $person->person_individual)
                {
                    return $person;
                }
                elseif($type == 'company' AND $person->person_company)
                {
                    return $person;
                }
            }
        }

        return false;
    }
}
?>