<?php

require_once( dirname(__FILE__) . '/Abstract.php' );

class Superlogica_Api_Clientes extends Superlogica_Api_Abstract {
    
    /**
     * Função responsável por inserir o sacado informado
     * 
     * @param string|int $identificador Identificador do sacado
     * @param array $dados Informações do sacado
     * @return bool
     * @throws Exception
     */
    public function novo( $identificador, $dados ){
        
        $dados['ST_SINCRO_SAC'] = $identificador;
        

        $response = $this->_api->action('sacados/put', $dados );
        if ( $response['status'] == 200 )
            return true;

        $this->_api->throwException( $response );
       
    }
    
    /**
     * Função responsável por alterar os dados do sacado informado pelo identificador
     * 
     * @param string|int $identificador Identificador do sacado
     * @param array $dados Informações do sacado
     * @return bool
     * @throws Exception
     */
    public function alterar( $identificador, $dados ){
        
        $dados[ self::getUtilizarIdentificador() ? 'identificador' : 'ID_SACADO_SAC' ] = $identificador;

        $response = $this->_api->action('sacados/post', $dados );
        if ( $response['status'] == 200 )
            return true;

        $this->_api->throwException( $response );
       
    }
    
    /**
     * Atrela um plano a um cliente
     * @param int|string $identificador
     * @param int $idPlano
     * @param string $data Data no padrão m/d/Y
     * @param string $identificadorContrato Identificador desta contratação
     * @param boolean $notificarClientes Envia notificação de contratação ao cliente caso esteja configurada no plano
     * @param int $parcelasAdesao Número de parcelas da adesão ( limite configurado no plano é respeitado )
     * @return boolean
     */
    public function contratar($identificador, $idPlano, $data = null, $identificadorContrato = null, $notificarClientes = false, $parcelasAdesao = null ){
        
        $params = array(
            'ID_PLANO_PLA' => $idPlano,
            'DT_CONTRATO_PLC' => $data,
            'ST_IDENTIFICADOR_PLC' => $identificadorContrato,
            'FL_NOTIFICARCLIENTE' => $notificarClientes ? 1 : 0,
            'QUANT_PARCELAS_ADESAO' => $parcelasAdesao
        );
        $params[ self::getUtilizarIdentificador() ? 'identificador' : 'ID_SACADO_SAC'] = $identificador;
        $this->_api->action('planosclientes/put', array("PLANOS" => array($params) ) );
        
        if ( $this->_api->getStatus() == 200 ){
            $resposta = $this->_api->getData();
            return $resposta['st_identificador_plc'];
        }
        
        $this->_api->throwException( $response );
        
    }
    
    /**
     * Retorna se o cliente informado está inadimplente ou não 
     *  
     * @param int|string $identificador
     * @param int $diasTolerancia Dias de tolerancia para ser considerado inadimplente
     * @return boolean
     */
    public function inadimplente( $identificador, $diasTolerancia = 0 ){
        
        $dados[ self::getUtilizarIdentificador() ? 'identificador' : 'CLIENTES[0]'] = $identificador;
        
        $diasTolerancia = $diasTolerancia;
        $timestampVencimentoFim = strtotime('-'. ($diasTolerancia).' day', mktime(0,0,0) );        
        $dados['posicaoEm'] = date('m/d/Y', $timestampVencimentoFim );
        
        $retorno = $this->_api->action('inadimplencia/index', $dados );

        if ( $retorno['status'] != 200 )
            $this->_api->throwException( $retorno );
        
        if ( count($retorno['data']) > 0 )
            return true;
        
        return false;
        
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
    
    /**
     * Retorna a url para alteração da forma de pagamento do cliente
     * 
     * @param string $callback URL utilizada pelo aplicativo para redirecionar após o cliente escolher a forma de pagamento
     * @param string $application Aplicativo a ser aberta a escolha da forma de pagamento
     * @return string
     */
    public function getUrlFormaDePagamento( $callback, $application = 'areadocliente' ){
        return $this->_api->getUrlApplication($application) . '/clientes/formadepagamento?menu=0&alterar=1&callback=' . urlencode($callback);
    }
    
}