<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
error_reporting(0);
ini_set("display_errors", 0);

// Configurações
$MAX_UPLOAD_SIZE = 100 * 1024 * 1024; // 100 MB
$BLOCKED_EXT = ["php","phtml","phar","sh","exe","bat","js","cgi","pl"];
$API_KEY = "33c0c284315e538a5c166a8d0ccda4ccaeb360a14d4fc11094535e091e5e0664"; // sua API Key

function respond($arr){
    echo json_encode($arr, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
    exit;
}

// Apenas POST
if($_SERVER["REQUEST_METHOD"] !== "POST") respond(["status"=>"error","message"=>"Método inválido"]);

// Checa API Key
$headers = getallheaders();
if(!isset($headers['X-API-KEY']) || $headers['X-API-KEY'] !== $GLOBALS['API_KEY']){
    respond(["status"=>"error","message"=>"Chave de API inválida"]);
}

// Verifica arquivo
if(!isset($_FILES["file"])) respond(["status"=>"error","message"=>"Nenhum arquivo enviado"]);

$file = $_FILES["file"];
if($file["error"] !== UPLOAD_ERR_OK) respond(["status"=>"error","message"=>"Erro no upload","code"=>$file["error"]]);
if($file["size"] > $MAX_UPLOAD_SIZE) respond(["status"=>"error","message"=>"Arquivo muito grande"]);

// Cria pasta uploads
$baseDir = __DIR__ . "/uploads/";
if(!is_dir($baseDir)) mkdir($baseDir,0777,true);

// UID e pasta específica do upload
$uid = uniqid("up_",true);
$uploadDir = $baseDir . $uid . "/";
if(!mkdir($uploadDir,0777,true)) respond(["status"=>"error","message"=>"Falha ao criar pasta do upload"]);

$originalName = basename($file["name"]);
$originalName = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
if(in_array($ext,$BLOCKED_EXT)) respond(["status"=>"error","message"=>"Tipo de arquivo não permitido"]);

$finalName = $originalName;
$finalPath = $uploadDir . $finalName;
if(file_exists($finalPath)){
    $nameOnly = pathinfo($originalName,PATHINFO_FILENAME);
    $finalName = $nameOnly."_".time().".".$ext;
    $finalPath = $uploadDir . $finalName;
}

if(!move_uploaded_file($file["tmp_name"],$finalPath)) respond(["status"=>"error","message"=>"Falha ao salvar arquivo"]);

// Informações do arquivo
$size = filesize($finalPath);
$mime = function_exists("mime_content_type") ? mime_content_type($finalPath) : "application/octet-stream";

// URL incluindo a pasta FireUpload
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=="off") ? "https" : "http";
$host = $_SERVER["HTTP_HOST"];
$scriptDir = basename(__DIR__); // "FireUpload"
$url = $protocol."://".$host."/".$scriptDir."/uploads/$uid/$finalName";

respond([
    "status"=>"OK",
    "nome"=>$originalName,
    "file-type"=>$ext,
    "mime"=>$mime,
    "size_bytes"=>$size,
    "size_kb"=>round($size/1024,2),
    "size_mb"=>round($size/1024/1024,2),
    "url"=>$url,
    "uid"=>$uid
]);
