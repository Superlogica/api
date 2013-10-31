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
     * Armazena o retorna da ultima requisição
     * Utilizado nas outras funções como getData, getMsg e getStatus
     * @var array
     */
    protected $_retorno = null;
    
    /**
     * Arquivo de debug
     * @var string
     */
    protected $_debugFile = null;
    
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

    /**
     * Seta o arquivo de debug
     * @param type $file
     */
    public function setDebugFile( $file ){
        $this->_debugFile = $file;
    }
    
    /**
     * Seta o id da sessão
     * @param string $sessionId
     */
    public function setSessionId($sessionId){
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
        $this->_retorno = null;
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
            $_params['json'] = json_encode(array('params' => $params ));
        }
        
        if ( $this->_debugFile ){
            curl_setopt($this->_curl, CURLOPT_VERBOSE, true);
            $verbose = fopen( $this->_debugFile, 'a+' );
            $paramsDebug = $params;
            unset($paramsDebug[0]['password']);
            fwrite($verbose, "\n\n\n<--------------->\n\n\nURL: ".($this->_url . '/' . $action)."\nParams: " . print_r($paramsDebug,true) . "\n\n" );
            curl_setopt($this->_curl, CURLOPT_STDERR,  $verbose );
        }
        
        curl_setopt($this->_curl, CURLOPT_URL, $this->_url . '/' . $action);
        curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $_params);
        if ($this->_session) {
            curl_setopt($this->_curl, CURLOPT_COOKIE, 'PHPSESSID=' . $this->_session);
            $_params['session'] = $this->_session;
        }
        $result = curl_exec($this->_curl);    
        
        if ( $this->_debugFile ){
            fclose($verbose);
        }
        
        if (($result[0] == '{') or ($result[0] == '[')) {
            $result = json_decode($result, true);
            $result['url'] = $this->_url . '/' . $action;
            $this->_retorno = $result;
            return $result;
        }

        throw new Exception("Falha na requisição para: $this->_url/$action Erro: " . curl_error($this->_curl) . $result, "500");
    }
    
    /**
     * Responsável por disparar exceptions de acordo com o json de retorno informado
     * @throw Exception
     * @param array $response
     */
    public function throwException( $response = null ){
        
        if ( $response === null )
            $response = $this->_retorno;
        
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
    
    /**
     * Retorna o data do result passado
     * Utilizado no caso de multiple response e é ncessário apenas o primeiro item da requisição
     * 
     * FUNCIONANDO COMO NO JS_REQUEST 
     * 
     * @param array|int $retornoOuIndice um array de retorno ou o índice do data a ser retornado
     * @return array
     */
    public function getData( $retornoOuIndice = '0' ){   
        
        // Mantendo compatibilidade com código anterior
        if ( is_array($retornoOuIndice) ){
            if ( is_array( $retornoOuIndice['data'] ) && $retornoOuIndice['data'][0]['data'] )
                return $retornoOuIndice['data'][0]['data'];
            return $retornoOuIndice['data'];
        }
        
        $dados = array();
        if ( $retornoOuIndice == -1 ){
        	
            $dados = $this->_retorno['data'];

        }else if ( ( is_array($this->_retorno['data']) ) && ( is_array($this->_retorno['data'][$retornoOuIndice]) ) && ( $this->_retorno['data'][$retornoOuIndice]["data"] ) ){
        	
            $dados = $this->_retorno['data'][$retornoOuIndice]["data"];

        }else if ( is_array($this->_retorno['data'][$retornoOuIndice]) ){
        	
            $dados = $this->_retorno['data'][$retornoOuIndice];
            
        }
        
        return $dados;
        
        
    }
    
    /**
     * Retorna o status da requisição
     * @return int
     */
    public function getStatus(){
        
        $status = 500;
        if ( is_array($this->_retorno) ){
            $status = $this->_retorno['status'];
            if ( count($this->_retorno['data']) == 1 && $this->_retorno['data'][0]['status']){
                $status = $this->_retorno['data'][0]['status'];
            }
        }
        return $status;

    }
    
    /**
     * Retorna a msg da requisição
     * @return string
     */
    public function getMsg(){
        $msg = $this->_retorno;
        if ( is_array($this->_retorno) ){
            $msg = $this->_retorno['msg'];
            if ( count($this->_retorno['data']) == 1 && $this->_retorno['data'][0]['msg']){
                $msg = $this->_retorno['data'][0]['msg'];
            }
        }
        return $msg;
    }
    
    /**
     * Verifica se a requisição é válida
     * @return bool
     */
    public function isValid(){
        return ($this->_retorno) && ( $this->_retorno['status'] < 299 ) && ($this->_retorno['status'] != 0);
    }

}

