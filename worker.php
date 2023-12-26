<?php

const AUTH_KEY = '';
const API_SERVER = 'http://';

function curlUrl($url, $data = [], $encode_data_to_json = false)
{
    $headers[] = 'X-Auth-Key: ' . AUTH_KEY;

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_URL, $url);
    if (!empty($data)) {
        curl_setopt($curl, CURLOPT_POST, true);
        if ($encode_data_to_json) {
            $data = json_encode($data);
            $headers[] = 'Content-Type:application/json';
            $headers[] = 'Content-Length: ' . strlen($data);
        }
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    //curl_setopt($curl, CURLOPT_TIMEOUT, 3);
    return json_decode(curl_exec($curl), true);
}

function ping($host)
{
    if (exec('echo EXEC') == 'EXEC') {
        exec(sprintf('ping -c 3 -W 2 %s', escapeshellarg($host)), $res, $rval);
    } elseif (function_exists('fsocketopen')) {
        $port = 80;
        $timeout = 2;
        $fsock = fsockopen($host, $port, $errno, $errstr, $timeout);
        if (!$fsock) {
            $rval = 0;
        } else {
            $rval = 1;
        }
    }
    $status = 'ping ok';
    if ($rval) {
        $status = 'not ping';
    }
    echo $host . ' -> ' . $status . "\r\n";
    return $rval === 0;
}

$time_start = microtime(true);
$result = curlUrl(API_SERVER . '/api/v1/device');
if ($result && $result['statusCode'] == 200) {
    $devices_to_check = [];
    foreach ($result['data'] as $item) {
        if ($item['model']['vendor'] == 'ZTE' && $item['model']['type'] == 'OLT') {
            if (isset($item['params']['diag_ips'])) {
                $devices_to_check[] = $item;
            }
        }
    }
    if (empty($devices_to_check)) {
        echo "no diag_ips to check\r\n";
    }
    $data_to_reboot = [];
    foreach ($devices_to_check as $item) {
        foreach ($item['params']['diag_ips'] as $key => $value) {
            echo $item['ip'] . ' -> ' . $item['model']['key'] . ' -> ' . $key . " checking\r\n";
            $temp_not_ping = [];
            foreach ($value as $ip) {
                if (!ping($ip)) {
                    $temp_not_ping[] = $ip;
                }
            }
            if (count($value) == count($temp_not_ping)) {
                $data_to_reboot[$key]['item'] = $item;
                $data_to_reboot[$key]['not_ping'] = $temp_not_ping;
            }
        }
    }

    if (empty($data_to_reboot)) {
        echo "nothing to reboot\r\n";
    }

    foreach ($data_to_reboot as $key => $value) {
        echo "{$value['item']['ip']} {$value['item']['model']['key']} {$key} going to reset\r\n";
        $device_id_in_wildcore = $value['item']['id'];
        $data = [
            'interface' => $key
        ];
        $reset_port_res = curlUrl(API_SERVER . '/api/v1/switcher-core/device/ctrl_reset_port/' . $device_id_in_wildcore, $data);
        print_r($reset_port_res);
        if ($reset_port_res && $reset_port_res['statusCode'] == 200) {
            $data = [
                'action' => 'auto:port_reseted',
                'device' => ['id' => $device_id_in_wildcore],
                'user' => ['id' => 1],
                'status' => 'SUCCESS',
                'message' => "{$value['item']['ip']} {$value['item']['model']['key']} {$key} successfully reseted",
                'meta' => [
                    'interface' => [
                        'id' => 1,
                        'name' => $key
                    ]
                ]
            ];
            $result = curlUrl(API_SERVER . '/api/v1/logs/action', $data, true);
        }
    }
} else {
    throw new \Exception('/api/v1/device response not a 200 code');
}
echo 'Total execution time: ' . (microtime(true) - $time_start);