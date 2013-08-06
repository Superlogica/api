<?php

class Superlogica_Api_Planos extends Superlogica_Api_Abstract {

    /**
     * Utilizado para alterar dados da contratação
     * 
     * @param string $identificadorContrato Identificador do contrato
     * @param array $dados Dados a serem alterados
     * @return boolean
     * @throws  Exception
     */
    public function alterar( $identificadorContrato, $dados ){
        
        $dados['identificadorContrato'] = $identificadorContrato;
        $retorno = $this->_api->action("planosclientes/post", $dados );
        if ( $retorno['status'] == 200 )
            return true;
        
        $this->_api->throwException($retorno);
        
    }
    
    /**
     * Verifica se uma mensalidade está contratado por um cliente
     * 
     * @param string|int $identificador Identificador do cliente
     * @param string $identificadorServico Identificador do serviço
     * @return boolean
     */
    public function contratado( $identificador, $identificadorServico ){
        
        $dados[ self::getUtilizarIdentificador() ? 'identificador' : 'ID_SACADO_SAC'] = $identificador;
        $dados[ self::getUtilizarIdentificador() ? 'identificadorServico' : 'ID_PRODUTO_PRD'] = $identificadorServico;
        
        $retorno = $this->_api->action("mensalidades/contratada", $dados);
        if ( $retorno['status'] != 200 )
            $this->_api->throwException($retorno);
        
        return count($retorno['data'][0]['data']) ? true : false;

    }
    
}