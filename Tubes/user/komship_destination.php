<?php
// user/komship_destination.php
declare(strict_types=1);

/**
 * Konfigurasi Komship
 * GANTI API KEY kalau perlu.
 */
if (!defined('KOMSHIP_API_KEY')) {
    define('KOMSHIP_API_KEY', '3I7kuf7B3e00fb2d23c692a69owo8BSW'); // sandbox lu kemarin
}

if (!defined('KOMSHIP_DEST_SEARCH_URL')) {
    define('KOMSHIP_DEST_SEARCH_URL', 'https://api-sandbox.collaborator.komerce.id/tariff/api/v1/destination/search');
}

/**
 * Cari komship_destination_id berdasarkan teks alamat.
 *
 * Strategi keyword:
 *   - utamain kecamatan + kota (misal: "MARGAASIH BANDUNG")
 *   - kalau kecamatan kosong, pake kota + provinsi
 *
 * @param string $provinsi  contoh: "JAWA BARAT"
 * @param string $kota      contoh: "BANDUNG"
 * @param string $kecamatan contoh: "MARGAASIH"
 * @return int destination_id atau 0 kalau gagal
 */
function getKomshipDestinationId(string $provinsi, string $kota, string $kecamatan = ''): int
{
    // Susun keyword yang paling spesifik
    $provinsi  = trim($provinsi);
    $kota      = trim($kota);
    $kecamatan = trim($kecamatan);

    $keywordParts = [];
    if ($kecamatan !== '') $keywordParts[] = $kecamatan;
    if ($kota !== '')      $keywordParts[] = $kota;
    if ($provinsi !== '')  $keywordParts[] = $provinsi;

    $keyword = trim(implode(' ', $keywordParts));

    if ($keyword === '') {
        // Alamat sama sekali gak kebaca
        return 0;
    }

    // Panggil API Komship
    $url = KOMSHIP_DEST_SEARCH_URL . '?keyword=' . urlencode($keyword);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => '',
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => 'GET',
        CURLOPT_HTTPHEADER     => [
            'x-api-key: ' . KOMSHIP_API_KEY,
        ],
    ]);

    $resp = curl_exec($ch);
    if ($resp === false) {
        // Kalau mau debug:
        // error_log('Komship dest search cURL error: ' . curl_error($ch));
        curl_close($ch);
        return 0;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode < 200 || $httpCode >= 300) {
        // error_log("Komship dest search HTTP $httpCode: $resp");
        return 0;
    }

    $body = json_decode($resp, true);
    if (!is_array($body)) {
        // error_log('Komship dest search invalid JSON: ' . $resp);
        return 0;
    }

    // Asumsi struktur: { meta: {...}, data: [ { destination_id: 123, ... }, ... ] }
    $data = $body['data'] ?? null;
    if (!is_array($data) || empty($data)) {
        return 0;
    }

    $first = $data[0];

    if (isset($first['destination_id']) && (int)$first['destination_id'] > 0) {
        return (int)$first['destination_id'];
    }

    // Fallback kalau field-nya cuma `id`
    if (isset($first['id']) && (int)$first['id'] > 0) {
        return (int)$first['id'];
    }

    return 0;
}
