<?php

// Robokassa payment processing gateway emulator

require_once 'config.php';
require_once 'template.php';

try {
    if (!isset($_GET['MrchLogin']) || !isset($config[$_GET['MrchLogin']])) {
        throw new Exception('Unknown merchant name');
    }

    if (!isset($_GET['MrchLogin'])
        || !isset($_GET['OutSum'])
        || !isset($_GET['Desc'])
        || !isset($_GET['SignatureValue'])
    ) {
        throw new Exception('There are no required parameters');
    }

    $cfg = $config[$_GET['MrchLogin']];

    // Signature
    $params = [
        $_GET['MrchLogin'],
        $_GET['OutSum'],
        $_GET['Desc'],
        $cfg['pass1'],
    ];
    $shp_params = [];
    foreach ($_GET as $key => $value) {
        if ('shp_' == substr($key, 0, 4)) {
            $shp_params[] = $key.'='.$value;
        }
    }
    $signature_value = md5(implode(':', array_merge($params, $shp_params)));

    if ($signature_value != $_GET['SignatureValue']) {
        throw new Exception('The signature is invalid');
    }

    if (('POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['action'])) || defined('IS_TEST')) {
        switch ($_POST['action']) {
            case 'success':
                $inv_id = uniqid(); // Random payment identifier

                // Signature for payment notification
                $params = [
                    $_GET['OutSum'],
                    $inv_id,
                    $cfg['pass2'],
                ];
                $signature_value = md5(implode(':', array_merge($params, $shp_params)));

                // Payment notification URL
                $uri = $cfg['result_uri'].'?';
                $uri .= 'OutSum='.$_GET['OutSum'];
                $uri .= '&InvId='.$inv_id;
                $uri .= '&SignatureValue='.$signature_value;
                $shp_str = implode('&', $shp_params);
                if ($shp_str) {
                    $uri .= '&'.$shp_str;
                }

                if (defined('IS_TEST')) {
                    echo '<a href="'.$cfg['host'].$uri.'">Show the answer</a>';
                    exit;
                }

                if (isset($cfg['http_auth']) && is_array($cfg['http_auth'])) {
                    $auth = base64_encode($cfg['http_auth']['username'].':'.$cfg['http_auth']['password']);
                    $context = stream_context_create([
                        'http' => [
                            'header' => 'Authorization: Basic '.$auth,
                    ]]);
                    $response = file_get_contents($cfg['host'].$uri, false, $context);
                } else {
                    $response = file_get_contents($cfg['host'].$uri);
                }

                if ($response != 'OK'.$inv_id) {
                    throw new Exception('Invalid answer');
                }

                header('Location: '.$cfg['host'].$cfg['success_uri']);
                exit;
            case 'fail':
                header('Location: '.$cfg['host'].$cfg['fail_uri']);
                exit;
            default:
                throw new Exception('Unknown operation type');
        }
    } else {
        load_template('index', ['query_string' => $_SERVER['QUERY_STRING']], 'layout');
    }
} catch (Exception $e) {
    header('Bad request', true, 400);
    if (defined('IS_TEST')) {
        echo $e->getMessage();
    } else {
        load_template('error', ['message' => $e->getMessage()], 'layout');
    }
}
