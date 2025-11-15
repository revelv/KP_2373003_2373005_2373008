<?php
// user/komship_destination_search.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// ====== CONFIG ======
const KOMSHIP_API_KEY  = 'nN6Mv6Areb8ec09cadbb3a60uWRzzahg';
const KOMSHIP_BASE_URL = 'https://api-sandbox.collaborator.komerce.id';
// ganti ke https://api.collaborator.komerce.id kalau pakai production

// ====== INPUT KEYWORD ======
$keyword = trim($_GET['q'] ?? '');
if ($keyword === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Keyword kosong',
        'data'    => []
    ]);
    exit;
}

// ====== CURL KE KOMSHIP (PERSIS DARI SNIPPET LU) ======
$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => KOMSHIP_BASE_URL . '/tariff/api/v1/destination/search?keyword=' . urlencode($keyword),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING       => '',
    CURLOPT_MAXREDIRS      => 10,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST  => 'GET',
    CURLOPT_HTTPHEADER     => array(
        'accept: application/json',
        'x-api-key: ' . KOMSHIP_API_KEY
    ),
));

$response = curl_exec($curl);
$http     = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$err      = ($response === false) ? curl_error($curl) : null;
curl_close($curl);

if ($response === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal konek ke Komship: ' . $err,
        'data'    => []
    ]);
    exit;
}

$body = json_decode($response, true);

if ($http >= 400 || !is_array($body)) {
    echo json_encode([
        'success' => false,
        'message' => 'HTTP ' . $http,
        'data'    => [],
        'raw'     => $response
    ]);
    exit;
}

// ====== NORMALISASI OUTPUT KE FORMAT RAPIH ======
$list = $body['data'] ?? $body;
$out  = [];

if (is_array($list)) {
    foreach ($list as $dst) {
        $out[] = [
            'id'          => $dst['id']              ?? null,
            'label'       => $dst['label']           ?? '',
            'subdistrict' => $dst['subdistrict_name'] ?? '',
            'district'    => $dst['district_name']   ?? '',
            'city'        => $dst['city_name']       ?? '',
            'zip'         => $dst['zip_code']        ?? '',
        ];
    }
}

echo json_encode([
    'success' => true,
    'message' => 'OK',
    'data'    => $out
]);
