<?php


class Superlogica_Api_Configuracoes extends Superlogica_Api_Abstract {
    
    /**
     * Armazena as configurações temporariamente
     * @var array
     */
    protected $_configs = array();
    
    /**
     * Seta o valor de uma configuração
     * @param string $nome
     * @param string $valor
     */
    public function __set( $nome, $valor ){
        $this->_api->action('configuracoes/put',array(
            $nome => $valor
        ));
        if ( $this->_api->getStatus() != 200 ){
            $this->_api->throwException();
        }
        $this->_config[strtolower($nome)] = $valor;
    }
    
    /**
     * Retorna o valor de uma configuração
     * @param string $nome
     * @return string
     */
    public function __get( $nome ){        
        $nome = strtolower($nome);        
        if ( isset($this->_configs[$nome]) )
            return $this->_configs[$nome];
        
        $retorno = $this->_api->action('configuracoes/index');
        if ( $this->_api->getStatus() != 200 ){
            $this->_api->throwException();
        }
        $this->_configs = $retorno['data'];
        return $this->_configs[$nome];
    }
    
}
