<?php

// Ambil token Fonnte kamu dari file .env atau langsung di sini
$token = "19d4EhVUcKsKjcuNuRkb"; // Pastikan ini token Fonnte kamu

// 1. Terima data Webhook dari Fonnte
// Fonnte mengirim data dalam format JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// 2. Pastikan data yang masuk adalah pesan (bukan notif 'read', 'delivered', dll)
// 'sender' dan 'message' biasanya ada jika itu pesan teks
if (isset($data['sender']) && isset($data['message'])) {

    // Ambil nomor HP customer yang kirim pesan
    $target = $data['sender'];

    // Ambil nama customer (jika ada)
    $nama_customer = $data['chat']['name'] ?? 'Pelanggan';

    // 3. Buat Template Pesan Balasan Otomatis
    $reply_message = "Terima kasih telah menghubungi Styrk Industries, " . htmlspecialchars($nama_customer) . ". 🙏
    
Ini adalah balasan otomatis. 
Tim CS kami akan segera merespon pesan Anda satu per satu pada jam kerja (Senin - Jumat, 09:00 - 18:00).

Terima kasih atas perhatiannya!";

    // 4. Kirim balasan menggunakan cURL (Mirip kode kamu, tapi disederhanakan)
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.fonnte.com/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',

        // Data yang dikirim: cuma nomor target dan isi pesannya
        CURLOPT_POSTFIELDS => array(
            'target' => $target,
            'message' => $reply_message,
            'countryCode' => '62', // Asumsi nomor Indonesia
        ),

        CURLOPT_HTTPHEADER => array(
            // PERBAIKAN: Masukkan token-nya langsung, jangan string 'TOKEN'
            "Authorization: $token"
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    // (Opsional) Kamu bisa simpan log balasan ke file
    // file_put_contents('log_balasan.txt', $response . PHP_EOL, FILE_APPEND);

}

// 5. Selalu kirim response "OK" (200) ke Fonnte
// Ini penting agar Fonnte tahu webhook kamu berhasil diterima
http_response_code(200);
echo "OK";
exit;
