<?php
/*
 * @author Thyago Henrique Pacher - thyago.pacher@gmail.com
 */

ini_set('session.save_path',realpath(dirname($_SERVER['DOCUMENT_ROOT']) . '/../session')); session_start();
if (!isset($_SESSION)) {
    die(json_encode(array('mensagem' => 'Sua sessão caiu, por favor logue novamente!!!', 'situacao' => false)));
}

function __autoload($class_name) {
    if (file_exists("../model/" . $class_name . '.php')) {
        include "../model/" . $class_name . '.php';
    } elseif (file_exists("../visao/" . $class_name . '.php')) {
        include "../visao/" . $class_name . '.php';
    } elseif (file_exists("./" . $class_name . '.php')) {
        include "./" . $class_name . '.php';
    }
}

$conexao = new Conexao();
$cache = new Cache();
$configuracaop = $cache->read('configuracaop');
if (!isset($configuracaop) || $configuracaop == NULL) {
    $configuracaop = $conexao->comandoArray('select * from configuracao where codconfiguracao = 1');
    $cache->save('configuracaop', $configuracaop, '180 minutes');
}

if (!isset($_GET["cartao"])) {
    $resforma = $conexao->comando("select nome, imagem, tagpagseguro, codforma from formapagamento where internacional = 'n' and codforma not in(6, 9, 15, 16) and etapa3 = 'n' order by nome");
    $qtdforma = $conexao->qtdResultado($resforma);
    if ($qtdforma > 0) {
        echo 'Escolha um dos cartões abaixo para simular parcelas:<br><br>';
        while ($forma = $conexao->resultadoArray($resforma)) {
            ?>
            <img style="width: 45px;height: 30px;" class="parcela_img_pagseguro" id="parcela_img_pagseguro<?= $forma["tagpagseguro"] ?>" onclick="parcelamentoPagSeguro(<?= $_GET["valor"] ?>, '<?= $forma["tagpagseguro"] ?>')" src="../arquivos/<?= $forma['imagem'] ?>" alt="botão de pagamento cartão"/>
            <?php
        }
    }
    echo '<br><br>';
    echo '<div style="display: none" id="div_pagamento_opcao"> Você paga<select onchange="defineValorPagamento()" name="parcelas_pagseguro" style="display: none" id="parcelas_pagseguro"></select><span id="valor_parcela_pagseguro"></span></div>';
    exit;
}
if (!isset($_GET["valor"])) {
    $_GET["valor"] = "252.03";
} else {
    $_GET["valor"] = number_format($_GET["valor"], 2, '.', '');
}
$url = "https://ws.pagseguro.uol.com.br/v2/installments?email={$configuracaop["emailpagseguro"]}&token={$configuracaop["tokenpagseguro"]}&amount={$_GET["valor"]}&cardBrand={$_GET["cartao"]}";
$retorno = AbreSite($url);
$retorno = simplexml_load_string($retorno);
if (isset($retorno->installment) && $retorno->installment != NULL) {
    if($_GET["cartao"] == "avista"){
        $maximo = 1;
    }else{
        $maximo = 10;
    }
    for ($i = 0; $i < $maximo; $i++) {
        $parcela = $retorno->installment[$i];
        echo '<option value="R$ ' . str_replace('.', ',', $parcela->amount) . '">' . $parcela->quantity . '</option>';
    }
}

function AbreSite($url) {
    $site_url = $url;
    $ch = curl_init();
    $timeout = 5;
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_URL, $site_url);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    ob_start();
    curl_exec($ch);
    curl_close($ch);
    $file_contents = ob_get_contents();
    ob_end_clean();
    return $file_contents;
}
