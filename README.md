# Swoole Redis WebSocket 在线聊天

## 后台

````shell script
php service.php
````
### ws端口是 `9502` 需要有redis服务
    $ws = new Swoole\WebSocket\Server('0.0.0.0', 9502);
    $redis = new redis;
    $redis->connect('127.0.0.1', 6379);

## 前台
    修改 ws 到对应的端口，确保没有防火墙能ping通
    const ws = new WebSocket('ws://192.168.0.121:9502')
  
