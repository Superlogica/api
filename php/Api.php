<?php

class Superlogica_Api {

    /**
     * Action url
     *
     * @param string $url
     */
    protected $_url = null;
    /**
     * Conexão curl
     *
     * @var integer
     */
    protected $_curl = null;
    /**
     * Session ID
     *
     * @var string
     */
    protected $_session = '';
    
    /**
     * Armazena o nome da licença utilizada
     * @var string
     */
    protected $_licenca = '';

    /**
     * Constructor
     *
     * @param string $url
     * @return Superlogica_Api
     */
    public function __construct($url) {
        $this->_url = $url;
        return $this;
    }

    public function setSessionId($sessionId){
        session_write_close();
        $this->_session = $sessionId;
    }


    /**
     * Faz o login
     *
     * @param string $usuario
     * @param string $senha
     * @param string $licenca
     * @retun array
     */
    public function login($usuario, $senha, $licenca) {
        $params['username'] = $usuario;
        $params['password'] = $senha;
        $params['filename'] = $licenca;
        $this->_licenca = $licenca;
        $retorno = $this->action('auth/post', $params);
        if ($retorno['status'] == 202) 
            $this->_session = $retorno['session'];
       
        if ($retorno['status'] == 409) {
            //atualiza schema
            $this->action('auth/updateschema', array('filename' => $licenca ));
            //loga-se novamente
            $this->login($usuario, $senha, $licenca);
        }
        return $retorno;
    }

    
    /**
     * Faz o login usando token
     *
     * @param string $usuario
     * @param string $authtoken
     * @param string $licenca
     * @retun array
     */
    public function loginToken( $usuario, $authtoken, $licenca) {     
        $params['username'] = $usuario;
        $params['authtoken'] = $authtoken;
        $params['filename'] = $licenca;
        $retorno = $this->action('auth/post', $params);
       
        if ($retorno['status'] == 202) {
            $this->_session = $retorno['session'];
        }
        return $retorno;
    }    
    
    
    
    /**
     * Faz uma requisição
     *
     * @param string $action
     * @param array $params
     * @param boolean $upload  usado para enviar arquivos
     * @return array
     */
    public function action($action, $params = array(), $upload = false) {

        if ($this->_curl == null) {
            $this->_curl = curl_init();
            curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($this->_curl, CURLOPT_POST, 1);
            curl_setopt($this->_curl, CURLOPT_NOBODY, 1);
            curl_setopt($this->_curl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($this->_curl, CURLOPT_SSL_VERIFYHOST, 0);
        }

        $_params = array();
        $_params = $params;
        if (!$upload){
            $_params = array();
            if (!is_array($params[0])) {
                $tempParams = $params;
                $params = array();
                $params[0] = $tempParams;
            }
            $_params['json'] = json_encode(array('params' => $params));
        }
            
        curl_setopt($this->_curl, CURLOPT_URL, $this->_url . '/' . $action);
        curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $_params);
        if ($this->_session) {
            curl_setopt($this->_curl, CURLOPT_COOKIE, 'PHPSESSID=' . $this->_session);
            $_params['session'] = $this->_session;
        }
        $result = curl_exec($this->_curl);              
        if (($result[0] == '{') or ($result[0] == '[')) {
            $result = Superlogica_Utf8::encode( json_decode($result, true) );
            $result['url'] = $this->_url . '/' . $action;
            return $result;
        }

        throw new Exception("Falha na requisição para: $this->_url/$action Erro: " . curl_error($this->_curl) . $result, "500");
    }
    
    /**
     * Responsável por disparar exceptions de acordo com o json de retorno informado
     * @throw Exception
     * @param array $response
     */
    public function throwException($response){
        
        $msg = $response['msg'];
        if ( $response['data'][0]['msg'] ){
            $msg = $response['data'][0]['msg'];
            if ( count($response['data']) > 1 ){
                $msg = '';
                for ( $x=0; $x <= count($response['data']) ; $x++){
                    $msg .= $response['data'][$x]['msg'] . "\n";
                }
            }
        }
        
        throw new Exception( $msg, $response['status'] );
    }
    
    /**
     * Retorna a url do aplicativo que está sendo utilizado
     * @return string
     */
    public function getUrlApplication( $app ){
        return 'https://'.$this->_licenca.'.superlogica.net/clients/' . $app;
    }
}

