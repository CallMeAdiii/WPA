<?php
// includes/api.php – helper pro volání REST API

// Potlač PHP warning způsobený PCRE JIT na macOS
@ini_set('pcre.jit', '0');

define('API_BASE_URL', 'https://containing-immediately-enhance-shareholders.trycloudflare.com');

/**
 * Odešle HTTP požadavek na REST API.
 * Vrátí ['status' => int, 'data' => array].
 */
function apiRequest(string $method, string $endpoint, array $data = [], string $token = ''): array {
    $url = API_BASE_URL . $endpoint;

    $headers = ['Content-Type: application/json', 'Accept: application/json'];
    if ($token !== '') {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_FOLLOWLOCATION => true,
    ]);

    if ($method === 'GET') {
        $fullUrl = $url . (!empty($data) ? '?' . http_build_query($data) : '');
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
    } elseif ($method === 'POST') {
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($response === false || $curlErr !== '') {
        return ['status' => 503, 'data' => ['message' => 'API nedostupné: ' . $curlErr]];
    }

    $decoded = json_decode($response, true);
    return ['status' => $httpCode, 'data' => $decoded ?? []];
}

/**
 * Vrátí JWT token z aktuální session.
 */
function getToken(): string {
    return $_SESSION['api_token'] ?? '';
}

/**
 * Zkontroluje 401 odpověď – pokud token vypršel, odhlásí a přesměruje.
 */
function handleUnauthorized(array $result): void {
    if ($result['status'] === 401) {
        session_destroy();
        header('Location: login.php');
        exit;
    }
}
