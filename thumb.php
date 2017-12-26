<?php
$expires = 60*60*24; // how long to cache in secs..
header("Pragma: public");
header("Cache-Control: maxage=".$expires);
header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');

$cam_cache  = '';
if (isset($_GET["img"]) && $_GET["img"] != NULL && $_GET["img"] != "") {
    $separa_arquivo = explode('.', $_GET['img']);
    $cam_cache = "../arquivos/" . $_GET['img'];
}
list($width, $height) = getimagesize($cam_cache);
if($_GET['w'] == $width && $height == $_GET['h']){
    echo file_get_contents($cam_cache);
}else{
#Cabeçalho que ira definir a saida da pagina
    if (strtolower($separa_arquivo[1]) == "jpg") {
        header('Content-type: image/jpeg');
        $image = imagecreatefromjpeg($cam_cache);
    } elseif (strtolower($separa_arquivo[1]) == "png") {
        header('Content-type: image/png');
        imagealphablending($image_p, false);
        imagesavealpha($image_p, true);
        $image = imagecreatefrompng($cam_cache);
    }  

#pegando as dimensoes reais da imagem, largura e altura
    list($width, $height) = getimagesize($cam_cache);

#gerando a a miniatura da imagem
    $image_p = imagecreatetruecolor($_GET['w'], $_GET['h']);

    imagecopyresampled($image_p, $image, 0, 0, 0, 0, $_GET['w'], $_GET['h'], $width, $height);

#o 3º argumento é a qualidade da miniatura de 0 a 100
    if ($separa_arquivo[1] == "jpg") {
        imagejpeg($image_p, $cam_cache, 70);
    } elseif ($separa_arquivo[1] == "png") {
        imagepng($image_p, $cam_cache, 6);
    }

    imagedestroy($image_p);
}