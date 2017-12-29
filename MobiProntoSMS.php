<?php

/*
 * @author Thyago Henrique Pacher - thyago.pacher@gmail.com
 */
ini_set('default_socket_timeout', 0);
/**
 * Description of MobiPrintoSMS
 *
 * @author ThyagoHenrique
 */
class MobiProntoSMS {

    private $credencial = '';
    private $token_gateway = '';
    private $token_mobi_pronto_api = '';
    public $msg;
    public $numero;

    /**
     * @param $numero é para o telefone da pessoa
     */
    function __construct($numero = NULL, $msg = NULL) {
        if(isset($numero) && $numero != NULL && $numero != ""){
            $this->numero = $numero;
            $this->msg = $msg;
        }
    }

    function __destruct() {
        unset($this->conexao);
    }

    public function enviaSMS() {
        $this->numero = str_replace(' ', '', str_replace('(', '', str_replace(')', '', str_replace('-', '', $this->numero))));
        $credencial = URLEncode($this->credencial); //**Credencial da Conta 40 caracteres
        $token = URLEncode($this->token_gateway); //**Token da Conta 6 caracteres
        $principal = URLEncode("aaa");  //* SEU CODIGO PARA CONTROLE, não colocar e-mail
        $auxuser = URLEncode("AUX_USER"); //* SEU CODIGO PARA CONTROLE, não colocar e-mail
        $mobile = URLEncode("55" . $this->numero); //* Numero do telefone  FORMATO: PAÍS+DDD(DOIS DÍGITOS)+NÚMERO
        $sendproj = URLEncode("N"); //* S = Envia o SenderId antes da mensagem , N = Não envia o SenderId
        $msg = mb_convert_encoding($this->msg, "UTF-8"); // Converte a mensagem para não ocorrer erros com caracteres semi-gráficos
        $msg = URLEncode($msg);
        
        $url = "http://www.mpgateway.com/v_3_00/sms/smspush/enviasms.aspx?CREDENCIAL=" . $credencial ."&PRINCIPAL_USER=".$principal."&AUX_USER=".$auxuser. "&TOKEN=" . $token . "&MOBILE=" . $mobile . "&SEND_PROJECT=" . $sendproj . "&MESSAGE=" . $msg;
        $response = $this->AbreSite($url);
        $status_code = $response;
        $msg_retorno = $this->converteCodigo($status_code);
        return array('mensagem' => $msg_retorno, 'codigo' => $status_code);
    }

    /**
     * para verificar créditos disponiveis na plataforma
     */
    public function verificaCreditos() {
        return file_get_contents("http://www.mpgateway.com/v_3_00/sms/smscredits/credits.aspx?Credencial={$this->credencial}&Token=" . $this->token_gateway);
    }

    /**
     * para verificar o status da msg enviado
     */
    public function verificaStatusMsg($codigo) {
        return file_get_contents("http://www.mpgateway.com/v_3_00/sms/smsstatus/status.aspx?Credencial={$this->credencial}&ID={$codigo}&Token=$this->token_gateway");
    }

    public function converteCodigo($codigo) {
        $msg = '';
        
        if ($codigo >= 800 && $codigo <= 899) {
            $msg = 'Falha no Gateway';
        } elseif ($codigo >= 901 && $codigo <= 999) {
            $msg = 'Erro no acesso as operadoras';
        } elseif (strstr($codigo, '000') != FALSE) {
            $msg = 'Mensagem enviada com sucesso.';
        } else {
            $codigo = (string)$codigo;
            switch ($codigo) {
                case 'X01':
                    $msg = 'Um ou mais parâmetros com erro';
                    break;
                case 'X02':
                    $msg = 'Um ou mais parâmetros com erro';
                    break;
                case '001':
                    $msg = 'Credencial inválida';
                    break;
                case '005':
                    $msg = 'MOBILE com formato inválido';
                    break;
                case '008':
                    $msg = 'MESSAGE ou MESSAGE + NOME_PROJETO com mais de 160 posições ou SMS concatenado com mais de 1000 posições';
                    break;
                case '009':
                    $msg = 'Créditos insuficientes em conta';
                    break;
                case '010':
                    $msg = 'Gateway SMS da conta bloqueado';
                    break;
                case '012':
                    $msg = 'MOBILE correto, porém com crítica';
                    break;
                case '013':
                    $msg = 'Conteúdo da mensagem inválido ou vazio';
                    break;
                case '015':
                    $msg = 'País sem cobertura ou não aceita mensagens concatenadas (SMS Longo)';
                    break;
                case '016':
                    $msg = 'MOBILE com código de área inválido';
                    break;
                case '017':
                    $msg = 'Operadora não autorizada para esta credencial';
                    break;
                case '018':
                    $msg = 'MOBILE se encontra em lista negra';
                    break;
                case '019':
                    $msg = 'Token inválido';
                    break;
                case '022':
                    $msg = 'Conta atingiu o limite de envio do dia';
                    break;
                case '900':
                    $msg = 'Erro de autenticação ou limite de segurança excedido';
                    break;
                default:
                    $msg = 'Código não passou!';
                    break;
            }
        }
        return $msg;
    }

    private function AbreSite($url, $dados = NULL) {
        $site_url = $url;
        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, $site_url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

        if (isset($dados) && $dados != NULL) {
            //parametros em post
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dados);
        }
        ob_start();
        curl_exec($ch);
        curl_close($ch);
        $file_contents = ob_get_contents();
        ob_end_clean();
        return $file_contents;
    }    
}
