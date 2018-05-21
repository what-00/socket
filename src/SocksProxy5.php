<?php

namespace What00\Socket;

use What00\Socket\Exception\SocksProxyException;

class SocksProxy5
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
        $get_auth_type = pack('C4', 0x05, 0x02, 0x00, 0x02);

        if(fputs($this->sock, $get_auth_type) == false){
            throw new SocksProxyException('链接到代理服务器出错 (send get auth type):' . $this->sock_errstr);
        }
        $auth_type = $this->read($this->sock);

        $auth_type_response = str_split(bin2hex($auth_type), 2);
        // 判断是否需要验证
        if(!isset($auth_type_response[1])){
            throw new SocksProxyException('链接到代理服务器出错 (get auth type response)' . $this->sock_errstr);
        }
        switch ($auth_type_response[1]) {
            // 不需要验证
            case '00':
            break;
            // GSSAPI验证
            case '01':
            break;
            // 用户名和密码验证
            case '02':
            break;
            default:
                throw new SocksProxyException('链接到代理服务器出错, 未知sock5验证方式');
        }

    }
    // 目标服务器
    public function target($ip, $port)
    {
        $proxy_info_packet = pack('C4', 0x05, 0x01, 0x00, 0x01) . inet_pton($ip) . pack('n', $port);
        if(fputs($this->sock, $proxy_info_packet) == false){
            throw new SocksProxyException('链接到代理服务器出错 (send proxy info):' . $this->sock_errstr);
        }

        $proxy_info_response = $this->read($this->sock);
        $proxy_response = str_split(bin2hex($proxy_info_response), 2);
        if(!isset($proxy_response[1]) || $proxy_response[1] != '00'){
            throw new SocksProxyException('链接到代理服务器出错 (proxy response): ' . bin2hex($proxy_info_response) . ' ;' .  $this->sock_errstr);
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
