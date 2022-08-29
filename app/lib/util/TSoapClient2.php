<?php
class TSoapClient2 extends SoapClient
{
    private $key;
    
    public function __construct($params)
    {
        if(empty($params['location']))
        {
            throw new Exception("Parametro 'location' não informado");
        }
        
        if(empty($params['uri']))
        {
            throw new Exception("Parametro 'uri' não informado");
        }

        if(empty($params['key']))
        {
            throw new Exception("Parametro 'key' não informado");
        }
        
        $this->key = $params['key'];
        
        if(!empty($params['password']))
        {
            parent::__construct(null, array('location' => "{$params['location']}",'login'=>$params['login'],'password'=>$params['password'], 'uri' => "{$params['uri']}", 'trace' => 1, 'encoding' => "UTF-8"));            
        }
        else
        {
            parent::__construct(null, array('location' => "{$params['location']}", 'uri' => "{$params['uri']}", 'trace' => 1, 'encoding' => "UTF-8"));    
        }
        
        
    } 

    
    public function __call($name, $arguments)
    {
        $arguments[] = $this->key;
                
        $result = parent::__soapCall($name, $arguments);
        $result = base64_decode($result);
        $result = unserialize($result);

        
        if ( $result instanceof Exception )
        {
            $result = utf8_encode($result->getMessage());
            
            throw new Exception($result);
        }
        else
        {
            if(is_array($result))
            {
                $array = [];
                
                foreach($result as $key => $data)
                {
                    $array[$key] = utf8_encode($data);
                }
                
                return $array;
            }
            
            return $result;
        }
    } 
}
?>