<?php
header('Content-Type: text/html; charset=utf-8');
 $data['nCdEmpresa'] = '';
 $data['sDsSenha'] = '';
 $data['sCepOrigem'] = '43820080';
 $data['sCepDestino'] = '43810040';
 $data['nVlPeso'] = '1';
 $data['nCdFormato'] = '1';
 $data['nVlComprimento'] = '16';
 $data['nVlAltura'] = '5';
 $data['nVlLargura'] = '15';
 $data['nVlDiametro'] = '0';
 $data['sCdMaoPropria'] = 's';
 $data['nVlValorDeclarado'] = '200';
 $data['sCdAvisoRecebimento'] = 'n';
 $data['StrRetorno'] = 'xml';
 //$data['nCdServico'] = '40010';
 $data['nCdServico'] = '40010,40045,40215,41106';
 $data = http_build_query($data);
	
 $url = 'http://ws.correios.com.br/calculador/CalcPrecoPrazo.aspx';

 $curl = curl_init($url . '?' . $data);
 curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

 $array_servicos = array('40010' => 'SEDEX Varejo', '40045' => 'SEDEX a Cobrar Varejo', '40215' => 'SEDEX 10 Varejo', 
	'40290' => 'SEDEX Hoje Varejo', '41106' => 'PAC Varejo');
 $result = curl_exec($curl);
 $result = simplexml_load_string($result);
 
 foreach($result->cServico as $row) {
 //Os dados de cada serviço estará aqui
 if($row->Erro == 0) {
	 $codigo = $row->Codigo;
     echo '<h3>',$codigo, ' - Serviço: ';
	 echo $array_servicos[(int)$codigo],'</h3>';
     echo 'Valor cobrado: '.$row->Valor . '<br>'; 
     echo 'Prazo de entrega: ',$row->PrazoEntrega . '<br>';
     echo 'Valor mão própria: ',$row->ValorMaoPropria . '<br>';
     echo 'Valor AR: ',$row->ValorAvisoRecebimento . '<br>';
     echo 'Valor declarado: ',$row->ValorValorDeclarado . '<br>';
     echo 'Entrega domiciliar: ',$row->EntregaDomiciliar . '<br>';
     echo 'Entrega no sábado: ',$row->EntregaSabado;
 } else {
     echo $row->MsgErro;
 }
 echo '<hr>';
 }
