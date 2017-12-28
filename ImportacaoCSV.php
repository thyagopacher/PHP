<?php

session_start();
if(!isset($_SESSION['codempresa'])){
    die(json_encode(array('mensagem' => 'Sua sessão caiu, por favor logue novamente!!!', 'situacao' => false)));
}
header('Content-Type: text/html; charset=utf-8');


function __autoload($class_name) {
    if (file_exists("../model/" . $class_name . '.php')) {
        include "../model/" . $class_name . '.php';
    } elseif (file_exists("../visao/" . $class_name . '.php')) {
        include "../visao/" . $class_name . '.php';
    } elseif (file_exists("./" . $class_name . '.php')) {
        include "./" . $class_name . '.php';
    }
}

$msg_retorno = '';
$sit_retorno = true;

try {
    if (isset($_FILES['arquivo'])) {
        $conexao = new Conexao();


        $qtdimportado = 0;
        $qtdjatinha = 0;
        $qtdEmailProblema = 0;

        $empresa = $conexao->comandoArray("select SQL_CACHE razao, email, sitemorador, cidade from empresa where codempresa = {$_SESSION['codempresa']}");
        $delimitador = ';';
        $cerca = '"';
        $f = fopen($_FILES['arquivo']['tmp_name'], 'r');

        if ($f) {
            // Ler cabecalho do arquivo
            $cabecalho = fgetcsv($f, 0, $delimitador, $cerca);
            // Enquanto nao terminar o arquivo
            while (!feof($f)) {
                $linha = fgetcsv($f, 0, $delimitador, $cerca);
                if (!$linha) {
                    continue;
                }

                $nome = addslashes(utf8_encode($linha[0]));
                if (!isset($nome) || $nome == NULL || $nome == '') {
                    continue;
                }
                $valor = str_replace(',', '.', $linha[1]);
                $tipo1 = $linha[2];
                $sql = "select codtipo from tipoconta where LOWER(nome) = '" . strtolower($tipo1) . "' and codempresa = {$_SESSION['codempresa']}";
                $tipo = $conexao->comandoArray($sql);

                $data2 = implode("-",array_reverse(explode('/',$linha[3])));
                $status1 = utf8_encode(trim($linha[4]));
                $status = $conexao->comandoArray("select SQL_CACHE codstatus from statusconta where LOWER(nome) = '" . strtolower($status1) . "'");
                $movimentacao = strtoupper(str_replace(',', '.', $linha[5]));
                $contap = $conexao->comandoArray("select * from conta where data = '{$data2}' and codempresa = {$_SESSION['codempresa']} and codtipo = '{$tipo['codtipo']}' and codstatus = '{$status['codstatus']}' and valor = '{$valor}' and nome = '{$nome}'");
                $conta = new Conta($conexao);
                $conta->codempresa = $_SESSION['codempresa'];
                $conta->codfuncionario = $_SESSION['codpessoa'];
                $conta->codstatus = $status['codstatus'];
                $conta->codtipo = $tipo['codtipo'];
                $conta->data = $data2;
                $conta->dtcadastro = date('Y-m-d H:i:s');
                $conta->movimentacao = $movimentacao;
                $conta->nome = $nome;
                $conta->valor = $valor;
                $conta->numcheque = $linha[6];
                $conta->bloco = $linha[7];
                $conta->apartamento = $linha[8];                
                if (isset($contap) && isset($contap["codconta"])) {
                    $resInserirConta = $conta->atualizar();
                    $qtdjatinha++;
                    continue; //já tem cadastrado
                } else {

                    $resInserirConta = $conta->inserir();
                    if ($resInserirConta !== FALSE) {
                        $qtdimportado++;
                    } else {
                        die(json_encode(array('mensagem' => "Erro na importação causado por:" . mysqli_error($conexao->conexao), 'situacao' => false)));
                    }
                }
            }
            fclose($f);
        }
    } else {
        $msg_retorno = "Sem arquivo não é possivel realizar importação!";
        $sit_retorno = false;
    }

    if ($sit_retorno) {
        $msg_retorno = "Importação realizada com sucesso:
    - {$qtdjatinha} contas já cadastradas   
    - {$qtdimportado} contas importadas";
        $conexao->comando("insert into importacao(data, codfuncionario, codempresa, qtdimportado, qtdnimportado) values('" . date('Y-m-d') . "', '{$_SESSION['codpessoa']}', '{$_SESSION['codempresa']}', '{$qtdimportado}', '{$qtdjatinha}');");
    }
} catch (Exception $ex) {
    $sit_retorno = false;
    $msg_retorno = "Erro ao realizar importação causado por:" . $ex;
}
echo json_encode(array('mensagem' => $msg_retorno, 'situacao' => $sit_retorno));

