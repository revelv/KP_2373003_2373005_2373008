<?php
// user/komship/search_destination.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// ================== KONFIG KOMSHIP ==================
const KOMSHIP_API_KEY       = '3I7kuf7B3e00fb2d23c692a69owo8BSW'; // TODO: ganti kalau perlu
const KOMSHIP_DEST_ENDPOINT = 'https://api-sandbox.collaborator.komerce.id/tariff/api/v1/destination/search';

// ================== AMBIL INPUT DARI AJAX ==================
$city       = trim((string)($_POST['city_name']      ?? '')); // contoh: BANDUNG
$district   = trim((string)($_POST['district_name']  ?? '')); // contoh: MARGAASIH
$province   = trim((string)($_POST['province_name']  ?? '')); // contoh: JAWA BARAT (opsional)

if ($city === '' || $district === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Kota dan kecamatan harus diisi.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Keyword sesuai logic baru: "KECAMATAN, KOTA"
$keyword = $district . ', ' . $city;

// ================== HIT API KOMSHIP ==================
$ch  = curl_init();
$url = KOMSHIP_DEST_ENDPOINT . '?keyword=' . urlencode($keyword);

curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING       => '',
    CURLOPT_MAXREDIRS      => 10,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST  => 'GET',
    CURLOPT_HTTPHEADER     => [
        'x-api-key: ' . KOMSHIP_API_KEY,
    ],
    // XAMPP sering rewel soal SSL, jadi dimatiin verify (JANGAN dipakai di production beneran)
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);

$resp = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$cerr = ($resp === false) ? curl_error($ch) : null;
curl_close($ch);

if ($resp === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal konek ke Komship: ' . $cerr,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($http >= 400) {
    echo json_encode([
        'success' => false,
        'message' => 'HTTP error dari Komship: ' . $http,
        'raw'     => $resp,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode($resp, true);
if (!is_array($data)) {
    echo json_encode([
        'success' => false,
        'message' => 'Respon Komship bukan JSON valid.',
        'raw'     => $resp,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ================== EXTRACT LIST DESTINATION ==================
$list = [];

// 1) Kalau data langsung array
if (isset($data[0]) && is_array($data[0])) {
    $list = $data;
}
// 2) Kalau di bawah kunci "data"
elseif (isset($data['data'])) {
    if (is_array($data['data']) && isset($data['data'][0])) {
        $list = $data['data'];
    } elseif (isset($data['data']['list']) && is_array($data['data']['list'])) {
        $list = $data['data']['list'];
    }
}

if (empty($list)) {
    echo json_encode([
        'success' => false,
        'message' => 'Tidak ada destinasi ditemukan untuk keyword tersebut.',
        'raw'     => $data,
        'keyword' => $keyword,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ================== NORMALISASI & FILTER ==================
$options = [];

foreach ($list as $d) {
    if (!is_array($d)) continue;

    $destId = $d['destination_id'] ?? ($d['id'] ?? null);
    $prov   = $d['province_name']  ?? ($d['province']  ?? '');
    $cityNm = $d['city_name']      ?? ($d['city']      ?? '');
    $dist   = $d['district_name']  ?? ($d['subdistrict_name'] ?? ($d['kecamatan'] ?? ''));
    $postal = $d['postal_code']    ?? ($d['zipcode'] ?? '');

    if (!$destId) continue;

    // Filter provinsi kalau frontend ngirim
    if ($province !== '') {
        if (mb_strtoupper($prov) !== mb_strtoupper($province)) {
            continue;
        }
    }

    // Filter kota biar makin precise
    if ($city !== '') {
        if (mb_strtoupper($cityNm) !== mb_strtoupper($city)) {
            continue;
        }
    }

    $labelParts = [];
    if ($dist   !== '') $labelParts[] = $dist;
    if ($cityNm !== '') $labelParts[] = $cityNm;
    if ($prov   !== '') $labelParts[] = $prov;
    if ($postal !== '') $labelParts[] = 'Kodepos ' . $postal;

    $options[] = [
        'destination_id' => (int)$destId,
        'label'          => implode(' - ', $labelParts),
        'province_name'  => $prov,
        'city_name'      => $cityNm,
        'district_name'  => $dist,
        'postal_code'    => $postal,
    ];
}

if (empty($options)) {
    echo json_encode([
        'success' => false,
        'message' => 'Tidak ada destinasi yang cocok dengan kota/kecamatan tersebut.',
        'raw'     => $list,
        'keyword' => $keyword,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Destinasi ditemukan.',
    'options' => $options,
    'keyword' => $keyword,
], JSON_UNESCAPED_UNICODE);
exit;
