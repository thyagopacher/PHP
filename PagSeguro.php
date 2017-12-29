<?php

/**
 * Description of PagSeguro
 *
 * @author Thyago Henrique Pacher - <thyago.pacher@gmail.com>
 */
header('Content-Type: application/x-www-form-urlencoded; charset=utf-8');

class PagSeguro {

    private $urlCheckout = 'https://ws.pagseguro.uol.com.br/v2/checkout?email={email}&token={token}';
    private $urlConsulta = 'https://ws.pagseguro.uol.com.br/v2/transactions?initialDate={dataInicial}T00:00&finalDate={dataFinal}T00:00&page=1&maxPageResults=100&email={email}&token={token}';
    private $urlConsulta2 = 'https://ws.pagseguro.uol.com.br/v3/transactions/{codigo}?email={email}&token={token}';
    private $token = '';
    private $email = '';
    public $valorCompra = '';
    private $conexao;

    public function __construct($conexao = NULL) {
        if ($conexao != NULL) {
            $this->conexao = $conexao;
            $configuracaop = $this->conexao->comandoArray("select tokenpagseguro, emailpagseguro from configuracao where tokenpagseguro <> ''");
            if (isset($configuracaop["tokenpagseguro"]) && $configuracaop["tokenpagseguro"] != NULL && $configuracaop["tokenpagseguro"] != "") {
                $configuracaop["tokenpagseguro"] = str_replace(' ', '', $configuracaop["tokenpagseguro"]);

                $this->token = $configuracaop["tokenpagseguro"];
                $this->email = $configuracaop["emailpagseguro"];
            }
        }
    }

    public function consultaCodigo($codigo) {
        $this->urlConsulta2 = str_replace('{email}', $this->email, $this->urlConsulta2);
        $this->urlConsulta2 = str_replace('{token}', $this->token, $this->urlConsulta2);
        $this->urlConsulta2 = str_replace('{codigo}', $codigo, $this->urlConsulta2);
        return $this->AbreSite($this->urlConsulta2);
    }

    public function consultaData($dataInicial, $dataFim) {
        if (!isset($dataInicial) || $dataInicial == NULL || $dataInicial == "") {
            $dataInicial = date("Y-m-d");
        }
        if (!isset($dataFim) || $dataFim == NULL || $dataFim == "") {
            $dataFim = date("Y-m-d");
        }
        $this->urlConsulta = str_replace('{email}', $this->email, $this->urlConsulta);
        $this->urlConsulta = str_replace('{token}', $this->token, $this->urlConsulta);
        $this->urlConsulta = str_replace('{dataInicial}', $dataInicial, $this->urlConsulta);
        $this->urlConsulta = str_replace('{dataFinal}', $dataFim, $this->urlConsulta);
        return $this->AbreSite($this->urlConsulta);
    }

    public function compra($codpessoa) {
        $this->urlCheckout = str_replace('{email}', $this->email, $this->urlCheckout);
        $this->urlCheckout = str_replace('{token}', $this->token, $this->urlCheckout);

        $data['email'] = $this->email;
        $data['token'] = $this->token;
        $data['currency'] = 'BRL';

        $sql = "select TOP(1) produto.NOM_PRODUTO, produto.COD_PRODUTO, 
            produto.VAL_PRODUTO, produto.DES_PRODUTO, VAL_PAGO, cliente.COD_CLIENTE, 
            cliente.NOM_CLIENTE, cliente.DES_EMAIL 
        from cliente_produto as cp 
        inner join cliente on cliente.COD_CLIENTE = cp.COD_CLIENTE 
        inner join produto on produto.COD_PRODUTO = cp.COD_PRODUTO
        where cp.DAT_COMPRA <= '" . date("Y-m-d") . " 23:59:59' 
        and cp.COD_CLIENTE = {$codpessoa}
        and cp.LIBERADO = 0 order by cp.DAT_COMPRA DESC";
        $curso = $this->conexao->comandoArray($sql);
        $data['itemId1'] = $curso["COD_PRODUTO"];
        $data['itemQuantity1'] = 1;
        $vlPago =  (isset($this->valorCompra) && $this->valorCompra != NULL && $this->valorCompra != "") ? $this->valorCompra : $curso["VAL_PAGO"];
        $data['itemAmount1'] = number_format($vlPago, 2, '.', '');
        $data['itemDescription1'] = "Curso " . utf8_decode($curso["DES_PRODUTO"]);        
        $data['reference'] = $codpessoa;
        $data['senderName'] = str_replace('  ', ' ', $curso["NOM_CLIENTE"]);
        $data['senderEmail'] = $curso["DES_EMAIL"];
        $data['redirectURL'] = 'http://comexito.com.br';
        $data = http_build_query($data);
        return $this->AbreSite($this->urlCheckout, $data);
    }

    public function assinatura($codpessoa) {
        $this->urlCheckout = str_replace('{email}', $this->email, $this->urlCheckout);
        $this->urlCheckout = str_replace('{token}', $this->token, $this->urlCheckout);

        $sql = 'select 
        pessoa.nome, pessoa.email, pessoa.celular, pessoa.telefone,
        plano.nome as plano, plano.valor, venda.diapagamento, plano.meses, pessoa.renovaplano
        from pessoa 
        inner join venda on venda.codcliente  = pessoa.codpessoa
        inner join plano on plano.codplano    = venda.codplano
        where pessoa.codpessoa = ' . $codpessoa;
        $clientep = $this->conexao->comandoArray($sql);

        $finalVigencia = date('Y-m-d', strtotime('+' . $clientep["meses"] . ' months'));
        $clientep["valor"] = number_format($clientep["valor"], 2, '.', '');
        $data['email'] = $this->email;
        $data['token'] = $this->token;
        $data['details'] = "Todo dia {$clientep["diapagamento"]} será cobrado o valor de R$ " . number_format($clientep["valor"], 2, ',', '.') . " referente ao {$clientep["plano"]}";
        $data['finalDate'] = $finalVigencia . "T00:00:00";
        if (isset($clientep["renovaplano"]) && $clientep["renovaplano"] != NULL && $clientep["renovaplano"] == "s") {
            $data['charge'] = 'auto';
        } else {
            $data['charge'] = 'manual';
        }
        $data['name'] = "Assinatura plano {$clientep["plano"]}";
        $data['maxTotalAmount'] = $clientep["valor"];
        $data['amountPerPayment'] = $clientep["valor"];
        $data['currency'] = 'BRL';
        $data['itemId1'] = '0001';
        $data['itemQuantity1'] = 1;
        $data['itemAmount1'] = $clientep["valor"];
        $data['itemDescription1'] = "Assinatura plano {$clientep["plano"]}";
        $data['reference'] = "CODPESSOA{$codpessoa}";
        $data['senderName'] = $clientep['nome'];
        $data['senderEmail'] = $clientep['email'];
        $data['redirectURL'] = 'http://bradmontana.com.br';
        if ($clientep["meses"] == 1) {
            $data['period'] = "MONTHLY";
        } elseif ($clientep["meses"] == 2) {
            $data['period'] = "BIMONTHLY";
        } elseif ($clientep["meses"] == 3) {
            $data['period'] = "TRIMONTHLY";
        } elseif ($clientep["meses"] == 6) {
            $data['period'] = "SEMIANNUALLY";
        } elseif ($clientep["meses"] == 12) {
            $data['period'] = "YEARLY";
        }
        $data = http_build_query($data);

        return $this->AbreSite($this->urlCheckout, $data);
    }

    /**
     * @author Thyago Henrique Pacher
     * @param string $url site a ser pesquisado o conteúdo
     * @param array $dados define o post de dados 
     */
    public function AbreSite($url, $dados = NULL) {
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
