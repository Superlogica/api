<?php

require_once( dirname(__FILE__) . '/Abstract.php' );

class Superlogica_Api_Contatos extends Superlogica_Api_Abstract{
        
    /**
     * Retorna um token ao e-mail informado
     * Para logar com este e-mail basta informar 'token' em uma URL na area do cliente
     * 
     * @param string $email
     * @return string
     */
    public function loginViaToken( $email, $urlApplication = null ){
        if ( !$urlApplication )
            $urlApplication = $this->_api->getUrlApplication('areadocliente');
        $token = $this->getToken( $email );
        $urlParamSeparator = '?';
        if (strpos($urlApplication, '?') !== false )
            $urlParamSeparator = '&';
        return $urlApplication.$urlParamSeparator.'token='.$token;
    }
    
    /**
     * Gera um token de acesso ao e-mail informado
     * @param string $email
     * @return string
     * @throw Exception
     */
    public function getToken( $email ){
        $retorno = $this->_api->action('sacados/token', array( 'email' => $email ) );
        $token = $retorno['data']['token'];
        if ( !$token )
            $this->_api->throwException($retorno);
        return $token;
    }
}