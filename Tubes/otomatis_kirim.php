<?php
include 'voucher.php';
global $conn;

// Kalau di voucher.php namanya $koneksi:
if (!isset($conn) && isset($koneksi)) {
    $conn = $koneksi;
}

$today = new DateTime('now', new DateTimeZone('Asia/Jakarta'));

// Helper: cek apakah kolom ada (supaya update tidak fatal kalau kolom belum dibuat)
function kolomAda(mysqli $conn, string $table, string $column): bool {
    $q = $conn->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $q->bind_param('ss', $table, $column);
    $q->execute();
    return (bool) $q->get_result()->fetch_row();
}

// Helper: ambil data voucher by id (kalau buatVoucherDb return ID)
function getVoucherDataById(mysqli $conn, int $voucherId): ?array {
    $sql = "SELECT code AS kode, discount_amount AS nilai, expire_at AS kadaluarsa
            FROM vouchers WHERE voucher_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $voucherId);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res->fetch_assoc() ?: null;
}

/* =========================
   KAMPANYE NATAL (sekali, tanggal tertentu)
   ========================= */
if ($today->format('Y-m-d') === '2025-12-25') {

    // Ambil customer yang belum pernah dapat "Voucher Spesial Natal 2025"
    $sqlNatal = "
        SELECT c.*
        FROM customer c
        LEFT JOIN vouchers v
          ON c.customer_id = v.customer_id
         AND v.keterangan = 'Voucher Spesial Natal 2025'
        WHERE v.voucher_id IS NULL
    ";
    $resultNatal = $conn->query($sqlNatal);

    if ($resultNatal && $resultNatal->num_rows > 0) {
        while ($user = $resultNatal->fetch_assoc()) {
            // Nominal promo Natal
            $pilihanNominal = [20000, 25000, 30000, 40000, 50000];
            $nilaiVoucher   = $pilihanNominal[array_rand($pilihanNominal)];

            // Buat voucher (berlaku 30 hari)
            $voucher = buatVoucherDb((int)$user['customer_id'], (int)$nilaiVoucher, 30, "Voucher Spesial Natal 2025");

            // Normalisasi voucherData
            $voucherData = null;
            if (is_array($voucher) && isset($voucher['kode'], $voucher['nilai'], $voucher['kadaluarsa'])) {
                $voucherData = $voucher;
            } elseif (is_numeric($voucher)) {
                $voucherData = getVoucherDataById($conn, (int)$voucher);
            }

            if ($voucherData) {
                $nilaiFormatted      = "Rp " . number_format((int)$voucherData['nilai'], 0, ',', '.');
                $kadaluarsaFormatted = date('d F Y', strtotime($voucherData['kadaluarsa']));

                // Subject Inggris, isi Indonesia (JANGAN pakai htmlspecialchars di sini)
                $subjek = "Merry Christmas — A Special Gift From STYRK";
                $pesan  = "Selamat Hari Natal, " . $user['nama'] . " 🎄\n\n"
                        . "Terima kasih telah menjadi bagian dari komunitas STYRK. "
                        . "Sebagai hadiah Natal, kami berikan voucher belanja senilai {$nilaiFormatted}. "
                        . "Gunakan kode ini sebelum {$kadaluarsaFormatted} untuk menikmati potongan khusus.\n\n"
                        . "Rekomendasi spesial Natal:\n"
                        . "• Keycaps edisi holiday ❄️\n"
                        . "• Switch tactile/linear favorit\n"
                        . "• Deskmat tema winter & aksesoris rakit keyboard\n\n"
                        . "Klik tombol *Belanja Sekarang* di email untuk memakai voucher kamu. Stok terbatas—jangan sampai kehabisan!";

                // Kirim email (fungsi sudah include banner https://i.postimg.cc/qM5FMdxz/styrk-banner-jpg.jpg)
                $sent = kirimVoucherEmail($user['email'], $user['nama'], $subjek, $pesan, $voucherData);

                if ($sent) {
                    // Update penanda (kalau kolom belum dibuat, skip tanpa fatal)
                    if (kolomAda($conn, 'customer', 'last_christmas_sent')) {
                        $updateSql = "UPDATE customer SET last_christmas_sent = NOW() WHERE customer_id = ?";
                        $stmt = $conn->prepare($updateSql);
                        $stmt->bind_param('i', $user['customer_id']);
                        $stmt->execute();
                    }
                    echo "Voucher Natal terkirim ke " . $user['email'] . PHP_EOL;
                } else {
                    error_log("Gagal kirim Voucher Natal ke {$user['email']} (customer_id={$user['customer_id']})");
                }
            } else {
                error_log("Gagal membuat/mengambil data voucher untuk customer_id={$user['customer_id']}");
            }
        }
    }
}

/* =========================
   RE-ENGAGEMENT (> 3 bulan tidak login)
   ========================= */
$tigaBulanLalu = date('Y-m-d H:i:s', strtotime("-3 months"));

// Ambil user pasif & belum dikirimi re-engagement dalam 3 bulan terakhir
$sqlPasif = "
    SELECT *
    FROM customer
    WHERE last_login <= ?
      AND (last_reengagement_sent IS NULL OR last_reengagement_sent <= ?)
";
$stmtPasif = $conn->prepare($sqlPasif);
$stmtPasif->bind_param('ss', $tigaBulanLalu, $tigaBulanLalu);
$stmtPasif->execute();
$resultPasif = $stmtPasif->get_result();

if ($resultPasif && $resultPasif->num_rows > 0) {
    while ($user = $resultPasif->fetch_assoc()) {

        // Pilih nominal re-engagement
        $pilihanNominal = [10000, 12500, 15000, 20000, 25000, 30000, 40000, 50000, 100000];
        $nilaiVoucher   = $pilihanNominal[array_rand($pilihanNominal)];

        // Buat voucher 7 hari
        $voucher = buatVoucherDb((int)$user['customer_id'], (int)$nilaiVoucher, 7, "Voucher Comeback!");

        // Normalisasi voucherData
        $voucherData = null;
        if (is_array($voucher) && isset($voucher['kode'], $voucher['nilai'], $voucher['kadaluarsa'])) {
            $voucherData = $voucher;
        } elseif (is_numeric($voucher)) {
            $voucherData = getVoucherDataById($conn, (int)$voucher);
        }

        if ($voucherData) {
            $subject = "We Miss You — Claim Your Comeback Voucher";
            $pesan = "Sudah lama Anda tidak berkunjung ke STYRK. Kami harap semuanya baik-baik saja! "
                   . "Kami baru menambahkan banyak koleksi part & aksesori keyboard mekanikal yang mungkin Anda suka. "
                   . "Sebagai apresiasi, berikut voucher spesial untuk membantu Anda comeback belanja di STYRK.";

            $sent = kirimVoucherEmail($user['email'], $user['nama'], $subject, $pesan, $voucherData);

            if ($sent) {
                // Update penanda agar tidak spam
                $updateSql = "UPDATE customer SET last_reengagement_sent = NOW() WHERE customer_id = ?";
                $stmt = $conn->prepare($updateSql);
                $stmt->bind_param('i', $user['customer_id']);
                $stmt->execute();

                echo "Voucher re-engagement terkirim ke " . $user['email'] . PHP_EOL;
            } else {
                error_log("Gagal kirim ke {$user['email']} (customer_id={$user['customer_id']})");
            }
        } else {
            error_log("Gagal membuat/ambil data voucher untuk customer_id={$user['customer_id']}");
        }
    }
}
