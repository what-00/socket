<?php

require_once 'vendor/autoload.php';

$proxy_ip = '36.66.213.167';
$proxy_prot = '1080';
$server_ip = '198.41.214.88';
$server_prot = '80';
try {
    $socket = new What00\Socket\SocksProxy4($proxy_ip, $proxy_prot);
    $proxy_socket = $socket->target($server_ip, $server_prot);

    $request = "GET / HTTP/1.1\r\n";
    $request .= "Host: {$server_prot}\r\n";
    $request .= "Connection: Close\r\n";
    $request .= "\r\n";

    fputs($proxy_socket, $request);
    echo '内容:<pre>' . PHP_EOL;
    echo($socket->read($proxy_socket));

} catch (Exception $e){
    echo '错误:' . $e->getMessage() . PHP_EOL;
}
// var_dump($proxy_socket);
