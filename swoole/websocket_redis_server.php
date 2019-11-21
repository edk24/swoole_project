<?php
$server = new Swoole\WebSocket\Server("0.0.0.0", 8848);

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);
// clear all cache
$list = $redis->lrange("client:list", 0 ,-1);
$redis->del('client:list');
foreach ($list as $i) {
    $key = 'client:info:'.$i;
    $redis->del($key);
}

// 连接成功
$server->on('open', function (Swoole\WebSocket\Server $server, $request) use ($redis) {
    $redis->lpush('client:list', $request->fd);
    $key = 'client:info:' . $request->fd;
    $info = array(
        'fd'        => $request->fd,
        'symbol'    => '',    // 货币简称
        'type'      => '',    // 类型
        'push'      => 0, // 是否推送
    );
    $redis->hMSet($key, $info);
});

// 连接断开
$server->on('close', function ($ser, $fd) use ($redis) {
    $redis->lrem('client:list', $fd, 0);
    $redis->del('client:info:' . $fd);
});

// 接收数据
$server->on('message', function (Swoole\WebSocket\Server $server, $frame) use ($redis) {
    $data = json_decode($frame->data, true);
    $cmd = $data['cmd'];
    $id  = $data['id'];
    $args= $data['args'];
    $time = time();
    $now = date('Y-m-d H:i:s');

    if ($cmd == 'sub') {
        // get push data
        $key = 'client:info:'.$frame->fd;
        if (!$redis->exists($key)) {
            $dump['code']   = 500;
            $dump['msg']    = '异常';
            $server->push($frame->fd, json_encode($dump));
        }

        $info = array(
            'fd'        => $frame->fd,
            'symbol'    => 'sg',    // 货币简称
            'type'      => $data['type'],    // 类型
            'push'      => 1,       // 是否推送
        );
        $redis->hMSet($key, $info);
        
    } else if ($cmd == 'req') {
        // get history list
        $type = $data['type'];
        $url = "http://14.192.8.82/api/kline/get_history?type=$type&limit=1441";
        $result = file_get_contents($url);
        if ($result) {
            $dump['id'] = $id;
            $dump['ts'] = $time * 1000;
            $dump['data'] = json_decode($result, true);
            $server->push($frame->fd, json_encode($dump));
        } else {
            $server->push($frame->fd, $result);
        }

    } else if ($cmd == 'push') {
        // everbody fucking jump!
        $list = $redis->lrange("client:list", 0 ,-1);
        $url = "http://14.192.8.82/api/kline/get_data";
        $result = file_get_contents($url);
        $fakeList = json_decode($result, true);
        
        foreach ($list as $i) {
            $key = 'client:info:'.$i;
            $info = $redis->hgetall($key);
            if ($info['symbol'] == 'sg' && $frame->fd != $i) {
                if ($info['type'] != '') {
                    $server->push($info['fd'], json_encode($fakeList[$info['type']]));
                }
            }
        }
    }
});

$server->start();