<?php

class Superlogica_Api_Contatos extends Superlogica_Api_Abstract{
        
    /**
     * Retorna um token ao e-mail informado
     * Para logar com este e-mail basta informar 'token' em uma URL na area do cliente
     * 
     * @param string $email
     * @return string
     */
    public function loginViaToken( $email, $urlApplication ){
        $retorno = $this->_api->action('sacados/token', array( 'email' => $email ) );
        $token = $retorno['data']['token'];
        if ( !$token )
            $this->_api->throwException($retorno);    
        $urlParamSeparator = '?';
        if (strpos($urlApplication, '?') !== false )
            $urlParamSeparator = '&';
        return $urlApplication.$urlParamSeparator.'token='.$token;
    }
    
}