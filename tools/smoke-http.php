<?php

// Local HTTP smoke test, including real session and CSRF middleware.
$base = 'http://127.0.0.1:8000';
$credentials = file_get_contents(__DIR__.'/../.local/ACCESS.md');
preg_match('/^Username: (.+)$/m', $credentials, $username);
preg_match('/^Kata sandi: `(.*)`$/m', $credentials, $password);
$cookie = tempnam(sys_get_temp_dir(), 'drc-cookie-');
$call = function (string $path, ?array $data = null) use ($base, $cookie) {
    $ch = curl_init($base.$path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $cookie, CURLOPT_COOKIEFILE => $cookie, CURLOPT_TIMEOUT => 20]);
    if ($data !== null) {
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($data)]);
    }
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$status, $body];
};
$check = function (bool $ok, string $label) {
    if (! $ok) {
        throw new RuntimeException('FAILED: '.$label);
    } echo "PASS: $label\n";
};
try {
    [$status,$html] = $call('/admin/login');
    $check($status === 200, 'login page');
    preg_match('/name="_token" value="([^"]+)"/', $html, $token);
    [$status] = $call('/admin/login/process', ['username' => trim($username[1]), 'password' => $password[1]]);
    $check($status === 419, 'missing CSRF token rejected');
    [$status] = $call('/admin/login/process', ['_token' => $token[1], 'username' => trim($username[1]), 'password' => $password[1]]);
    $check($status === 302, 'login with real CSRF token');
    [$status,$html] = $call('/admin/dashboard');
    $check($status === 200 && str_contains($html, 'ADMIN PANEL'), 'authenticated dashboard');
    preg_match('/name="_token" value="([^"]+)"/', $html, $token);
    [$status] = $call('/admin/logout', ['_token' => $token[1]]);
    $check($status === 302, 'logout');
    [$status] = $call('/admin/dashboard');
    $check($status === 302,'guest redirected after logout');
} finally {
    unlink($cookie);
}
