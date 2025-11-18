<?php
// ========= BOOTSTRAP DASAR =========
require_once __DIR__ . '/vendor/autoload.php';

// koneksi DB (bisa dari sini, atau dari header yang include file ini)
include_once __DIR__ . '/koneksi.php';

// Kalau di koneksi lama pakai $koneksi, normalisasi ke $conn
if (!isset($conn) && isset($koneksi)) {
    $conn = $koneksi;
}

// Kalau tetap belum ada koneksi yang valid, berhenti saja
if (!($conn instanceof mysqli)) {
    return;
}

// Pastikan voucher.php (buatVoucherDb & kirimVoucherEmail) sudah ada
// Biar gak undefined function
if (file_exists(__DIR__ . '/voucher.php')) {
    include_once __DIR__ . '/voucher.php';
}

$today = new DateTime('now', new DateTimeZone('Asia/Jakarta'));

// ========= Helper: cek apakah kolom ada =========
function kolomAda(mysqli $conn, string $table, string $column): bool
{
    $q = $conn->prepare("
        SELECT 1 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = ? 
          AND COLUMN_NAME = ?
    ");
    if (!$q) return false;

    $q->bind_param('ss', $table, $column);
    $q->execute();
    $res = $q->get_result();
    $exists = (bool) $res->fetch_row();
    $q->close();
    return $exists;
}

// ========= Helper: ambil data voucher by id =========
function getVoucherDataById(mysqli $conn, int $voucherId): ?array
{
    $sql = "SELECT code AS kode, discount_amount AS nilai, expire_at AS kadaluarsa
            FROM vouchers WHERE voucher_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return null;

    $stmt->bind_param('i', $voucherId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

/* =========================
   1) KAMPANYE NATAL (sekali, tanggal tertentu)
   ========================= */
if ($today->format('Y-m-d') === '2025-12-25') {

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
            $voucher = buatVoucherDb(
                (int)$user['customer_id'],
                (int)$nilaiVoucher,
                30,
                "Voucher Spesial Natal 2025"
            );

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

                $sent = kirimVoucherEmail($user['email'], $user['nama'], $subjek, $pesan, $voucherData);

                if ($sent) {
                    if (kolomAda($conn, 'customer', 'last_christmas_sent')) {
                        $updateSql = "UPDATE customer SET last_christmas_sent = NOW() WHERE customer_id = ?";
                        $stmt = $conn->prepare($updateSql);
                        if ($stmt) {
                            $stmt->bind_param('i', $user['customer_id']);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                    // echo ini akan kebuang di header (ob_start + ob_end_clean), jadi aman
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
   2) RE-ENGAGEMENT (> 3 bulan tidak login)
   ========================= */
$tigaBulanLalu = date('Y-m-d H:i:s', strtotime("-3 months"));

$sqlPasif = "
    SELECT *
    FROM customer
    WHERE last_login <= ?
      AND (last_reengagement_sent IS NULL OR last_reengagement_sent <= ?)
";
$stmtPasif = $conn->prepare($sqlPasif);
if ($stmtPasif) {
    $stmtPasif->bind_param('ss', $tigaBulanLalu, $tigaBulanLalu);
    $stmtPasif->execute();
    $resultPasif = $stmtPasif->get_result();
    $stmtPasif->close();
} else {
    $resultPasif = false;
}

if ($resultPasif && $resultPasif->num_rows > 0) {
    while ($user = $resultPasif->fetch_assoc()) {

        $pilihanNominal = [10000, 12500, 15000, 20000, 25000, 30000, 40000, 50000, 100000];
        $nilaiVoucher   = $pilihanNominal[array_rand($pilihanNominal)];

        // Buat voucher 7 hari
        $voucher = buatVoucherDb(
            (int)$user['customer_id'],
            (int)$nilaiVoucher,
            7,
            "Voucher Comeback!"
        );

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
                if (kolomAda($conn, 'customer', 'last_reengagement_sent')) {
                    $updateSql = "UPDATE customer SET last_reengagement_sent = NOW() WHERE customer_id = ?";
                    $stmt = $conn->prepare($updateSql);
                    if ($stmt) {
                        $stmt->bind_param('i', $user['customer_id']);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
                echo "Voucher re-engagement terkirim ke " . $user['email'] . PHP_EOL;
            } else {
                error_log("Gagal kirim ke {$user['email']} (customer_id={$user['customer_id']})");
            }
        } else {
            error_log("Gagal membuat/ambil data voucher untuk customer_id={$user['customer_id']}");
        }
    }
}
