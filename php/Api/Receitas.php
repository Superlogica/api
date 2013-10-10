<?php

require_once( dirname(__FILE__) . '/Abstract.php' );

class Superlogica_Api_Receitas extends Superlogica_Api_Abstract {
    
    /**
     * Array com itens que serão adicionados ao boleto
     * @var array
     */ 
    protected $_servicosBoleto = array();
    
    /**
     * Insere um novo item eventual ao cliente informado pelo identificador
     * 
     * @param mixed $identificadorDoCliente Identificador do cliente cadastrado no software
     * @param int $conta ID da conta bancária a qual este item será gerada
     * @param int $servico ID do serviço referente ao item
     * @param float $valor Valor do item
     * @param int $quantidade Utilizado para multiplicar o valor do item
     * @param string $complemento Complemento do item que será exibido na composição do boleto
     * @return int ID do item eventual inserido
     * @throws Exception
     */
    public function novaEventual( $identificadorDoCliente, $dataReferencia, $conta, $servico, $valor, $quantidade = 1, $complemento = null, $identificadorComposicao = null ){

        $dados = array(
            'ST_MESANO_COMP' => $dataReferencia,
            'ID_CONTA_CB' => $conta,
            'ST_VALOR_COMP' => $valor ,
            'ST_QTD_COMP' => $quantidade,
            'ST_COMPLEMENTO_COMP' =>$complemento
        );
        
        $dados[ self::getUtilizarIdentificador() ? 'ST_SINCROSAC_COMP' : 'ID_SACADO_COMP'] = $identificadorDoCliente;
        $dados[ self::getUtilizarIdentificador() ? 'ST_SINCRO_PRD' : 'ID_PRODUTO_PRD'] = $servico;
        
        if ( $identificadorComposicao )
            $dados['ST_SINCRO_COMP'] = $identificadorComposicao;
        
        $response = $this->_api->action("composicao/put", $dados );
        if ( $response['status'] == 200 )
            return $response['data'][0]['data']['id_composicao_comp'];

        $this->_api->throwException( $response );

    }
    
    /**
     * Insere um novo item recorrente ao cliente informado pelo identificador
     * 
     * @param mixed $identificadorDoCliente Identificador do cliente cadastrado no software
     * @param int $servico ID do serviço referente ao item
     * @param float $valorUnitario Valor do unitário do item
     * @param int $conta ID da conta bancária a qual este item será gerada     
     * @param string $dataInicio Data de inicio do item recorrente no padrão m/d/Y
     * @param int OPCIONAL $periodicidade Periodicidade do item ( 30 = mensal, 60=bimestral, 90=trimestral, 180=semestral, 365=anual )
     * @param int OPCIONAL $quantidade Quantidade do item ( será multiplicado sempre Qtd*VlUnit )
     * @param string OPCIONAL $complemento Complemento do item recorrente
     * @param string $dataFim Data de fim do item recorrente no padrão m/d/Y
     * @return int ID do item recorrente inserido
     * @throws Exception
     */
    public function novaRecorrente( $identificadorDoCliente, $servico, $valorUnitario, $conta, $dataInicio, $periodicidade = 30,  $quantidade = 1, $complemento = null, $dataFim = null){
        
        switch ( $periodicidade ){
            case 30:
                $periodicidade=0;
                break;
            case 60:
                $periodicidade=1;
                break;
            case 90:
                $periodicidade=2;
                break;
            case 180:
                $periodicidade=3;
                break;
            case 365:
                $periodicidade=4;
                break;
            default:
                throw new Exception('Períodicidade "'.$periodicidade.'" inválida. Aceita somente 30,60,90,180 e 365 dias.');
        }
        
        $dados[] = array(
            'DT_INICIO_MENS' => $dataInicio,
            'DT_FIM_MENS' => $dataFim,
            'ID_CONTA_CB' => $conta,
            'ST_VALOR_MENS' => $valorUnitario ,
            'ST_QNTD_MENS' => $quantidade,
            'ST_COMPLEMENTO_MENS' =>$complemento,
            'FL_PERIODICIDADE_MENS' => $periodicidade
        );

        $dados[0][ self::getUtilizarIdentificador() ? 'identificador' : 'ID_CLIENTE_MENS' ] = $identificadorDoCliente;
        $dados[0][ self::getUtilizarIdentificador() ? 'ST_SINCRO_PRD' : 'ID_PRODUTO_PRD' ] = $servico;
        
        $response = $this->_api->action("mensalidades/put", $dados );
        
        if ( $response['status'] == 200 )
            return $response['data'][0]['data']['id_mensalidade_mens'];

        $this->_api->throwException( $response );
        
    }
    
    /**
     * Adiciona um novo item a cobrança que será inserida
     * 
     * @param int $servico Id do serviço
     * @param int $quantidade
     * @param string $complemento
     * @return void
     */
    public function nova( $servico, $valorUnitario, $quantidade = 1, $complemento = null, $idMensalidade= null ){
        $dados = array(
            'VL_UNITARIO_PRD' => $valorUnitario,
            'NM_QUANTIDADE_PRD' => $quantidade,
            'ST_COMPLEMENTO_COMP' => $complemento,
            'ID_MENSALIDADE_COMP' => $idMensalidade
        );
        $dados[ self::getUtilizarIdentificador() ? 'ST_SINCRO_PRD' : 'ID_PRODUTO_PRD'] = $servico;
        
        $this->_servicosBoleto[] = $dados;
    }
    
    /**
     * Gera a cobrança de acordo com os itens adicionados anteriomente pelas chamadas a função 'nova'
     * 
     * @param string $identificadorDoCliente
     * @param string $vencimento Data no formato m/d/Y
     * @param int $conta
     * @param float $juros
     * @param float $multa
     * @param float $desconto
     * @param string $obsInterna
     * @param string $obsParaCliente
     * @return array Informações da cobrança gerada incluindo link de segunda via
     */
    public function gerar( $identificadorDoCliente, $vencimento, $conta, $juros=null, $multa=null, $desconto = 0, $obsInterna = '', $obsParaCliente = '' ){        
        $dados = $this->_getDadosCobranca( $identificadorDoCliente, $vencimento, $conta, $juros, $multa, $desconto , $obsInterna , $obsParaCliente );                
        return $this->_cobrancaPut($dados);        
    }
    
    /**
     * Gera uma cobrança a ser cobrada pelo cartão do cliente
     * 
     * @param string $identificadorDoCliente
     * @param string $vencimento Data no formato m/d/Y
     * @param int $conta
     * @param float $juros
     * @param float $multa
     * @param float $desconto
     * @param string $obsInterna
     * @param string $obsParaCliente
     * @return int Retorna o id da cobrança gerada
     */
    public function processarPagamento( $identificadorDoCliente, $vencimento, $conta, $juros=null, $multa=null, $desconto = 0, $obsInterna = '', $obsParaCliente = '' ){        
        $dados = $this->_getDadosCobranca( $identificadorDoCliente, $vencimento, $conta, $juros, $multa, $desconto , $obsInterna , $obsParaCliente, 1 );        
        return $this->_cobrancaPut($dados);        
    }
    
    /**
     * Executa a requisição em cobrança put
     * @param array $dados
     * @return int ID da cobrança inserida  
     */
    protected function _cobrancaPut( $dados ){
        $response = $this->_api->action('cobranca/put', $dados );
        
        if ( $response['status'] == 200 )
            return $response['data'][0]['data'];
        
        $this->_api->throwException($response);
    }
    
    /**
     * Retorna um array com todos campos necessários para inserir uma cobrança
     * 
     * @param string $identificadorDoCliente
     * @param string $vencimento Data no formato m/d/Y
     * @param int $conta
     * @param float $juros
     * @param float $multa
     * @param float $desconto
     * @param string $obsInterna
     * @param string $obsParaCliente
     * @return array
     */
    protected function _getDadosCobranca( $identificadorDoCliente, $vencimento, $conta, $juros=null, $multa=null, $desconto = 0, $obsInterna = '', $obsParaCliente = '', $forcarCartao = 0 ){
        $dados = array(
            'DT_VENCIMENTO_RECB' => $vencimento,
            'ID_CONTA_CB' => $conta,
            'ST_OBSERVACAOINTERNA_RECB' => $obsInterna,
            'ST_OBSERVACAOEXTERNA_RECB' => $obsParaCliente,
            'COMPO_RECEBIMENTO' => $this->_servicosBoleto,
            'VL_TXMULTA_RECB' => $multa,
            'VL_TXJUROS_RECB' => $juros,
            'VL_TXDESCONTO_RECB' => $desconto,
            "FL_CIELOFORCARPAGAMENTO_RECB" => $forcarCartao
        );
        $dados[ self::getUtilizarIdentificador() ? 'identificador' : 'ID_SACADO_SAC'] = $identificadorDoCliente;
        return $dados;
    }
}