<?php

namespace What00\Socket;

use What00\Socket\Exception\SocksProxyException;

class SocksProxy4
{
    private $sock = null;
    private $sock_errno = null;
    private $sock_errstr = null;
    private $sock_read_time = 10;
    public function __construct($ip, $port, $timeout = 30)
    {
        $host = 'tcp://' . $ip;
        $this->sock = @fsockopen($host, $port, $this->sock_errno, $this->sock_errstr, $timeout);

        if(!$this->sock) {
            throw new SocksProxyException('链接到代理服务器出错: ' . $this->sock_errstr);
        }
        stream_set_blocking($this->sock, true);
    }
    // 目标服务器
    public function target($ip, $port)
    {
        $hello_packet = pack('C2', 0x04, 0x01) . pack('n', $port) . inet_pton($ip) .pack('C', 0x00);
        if(fputs($this->sock, $hello_packet) == false){
            throw new SocksProxyException('链接到代理服务器出错 (send hello):' . $this->sock_errstr);
        }
        $hello_response = $this->read($this->sock);
        $proxy_response = str_split(bin2hex($hello_response), 2);
        if(!isset($proxy_response[1]) ||  $proxy_response[1] != '5a'){
            throw new SocksProxyException('链接到代理服务器出错 (proxy response)' .  $this->sock_errstr);
        }

        return $this->sock;
    }

    public function read($fp)
    {
        $return = '';
        stream_set_timeout($fp, $this->sock_read_time);
        while (true){
            $return .= fgetc($fp);
            $meta = stream_get_meta_data($fp);
            if($meta['unread_bytes'] <= 0 ){
                break;
            }
        }
        stream_set_timeout($fp, $this->sock_read_time / 10);
        $_fgetc = fgetc($fp);
        if($_fgetc !== false){
            $return .= $_fgetc . $this->read($fp);
        }
        return $return;
    }

}
