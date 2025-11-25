<?php
// admin_shipping_pickup.php
declare(strict_types=1);

session_start();
include 'koneksi.php';
include 'header_admin.php';

/**
 * ===========================
 *  KONFIG KOMERCE / shipping
 * ===========================
 */

if (!defined('KOMERCE_ORDER_BASE_URL')) {
    define('KOMERCE_ORDER_BASE_URL', 'https://api-sandbox.collaborator.komerce.id');
}

if (!defined('KOMERCE_API_KEY')) {
    define('KOMERCE_API_KEY', '3I7kuf7B3e00fb2d23c692a69owo8BSW'); // sesuaikan kalau perlu
}

/**
 * Helper: Panggil Detail Order shipping lalu update AWB + status ke DB.
 *
 * @return array{success:bool,message:string}
 */
function syncshippingDetailAndUpdateAwb(mysqli $conn, string $orderId, string $orderNo): array
{
    $url = KOMERCE_ORDER_BASE_URL . '/order/api/v1/orders/detail?order_no=' . urlencode($orderNo);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'x-api-key: ' . KOMERCE_API_KEY,
        ],
        CURLOPT_TIMEOUT        => 30,
    ]);

    $responseBody = curl_exec($ch);
    $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr      = curl_error($ch);
    curl_close($ch);

    @file_put_contents(
        __DIR__ . '/shipping_detail_last_response.log',
        "=== " . date('Y-m-d H:i:s') . " ===\n" .
        "order_id: {$orderId}\n" .
        "order_no: {$orderNo}\n" .
        "HTTP: {$httpCode}\n" .
        "Response:\n{$responseBody}\n\n",
        FILE_APPEND
    );

    if ($curlErr) {
        return [
            'success' => false,
            'message' => 'Sync AWB gagal: cURL error: ' . $curlErr,
        ];
    }

    $json = json_decode($responseBody, true);
    if (!is_array($json)) {
        return [
            'success' => false,
            'message' => 'Sync AWB gagal: respon bukan JSON valid (HTTP ' . $httpCode . ').',
        ];
    }

    $meta = $json['meta'] ?? [];
    $data = $json['data'] ?? [];

    $code   = (int)($meta['code'] ?? 0);
    $status = (string)($meta['status'] ?? '');
    $msg    = (string)($meta['message'] ?? 'Unknown');

    if ($code !== 200 || $status !== 'success') {
        return [
            'success' => false,
            'message' => "Sync AWB gagal: {$msg} (HTTP {$httpCode})",
        ];
    }

    // Asumsi struktur: data.awb & data.order_status
    $awb         = isset($data['awb']) ? (string)$data['awb'] : '';
    $orderStatus = isset($data['order_status']) ? (string)$data['order_status'] : '';

    $sqlUpd = "
        UPDATE orders
        SET shipping_awb      = ?,
            shipping_status   = ?,
            shipping_last_sync = NOW()
        WHERE order_id = ?
        LIMIT 1
    ";
    $stmtUpd = $conn->prepare($sqlUpd);
    if (!$stmtUpd) {
        return [
            'success' => false,
            'message' => 'Sync AWB gagal: tidak bisa prepare UPDATE: ' . $conn->error,
        ];
    }

    $stmtUpd->bind_param('sss', $awb, $orderStatus, $orderId);
    $stmtUpd->execute();
    $affected = $stmtUpd->affected_rows;
    $stmtUpd->close();

    if ($awb === '') {
        return [
            'success' => true,
            'message' => "Detail order shipping berhasil di-sync, tapi AWB masih kosong. Status: {$orderStatus} (rows updated: {$affected})",
        ];
    }

    return [
        'success' => true,
        'message' => "AWB berhasil di-sync: {$awb} (status: {$orderStatus}, rows updated: {$affected})",
    ];
}

// ============================
// HANDLING FORM (PICKUP & SYNC)
// ============================

$flashMessage = '';
$flashType    = ''; // 'success' / 'error'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action      = $_POST['action'] ?? '';
    $orderId     = trim((string)($_POST['order_id'] ?? ''));
    $pickupDate  = trim((string)($_POST['pickup_date'] ?? ''));
    $pickupTime  = trim((string)($_POST['pickup_time'] ?? ''));

    if ($orderId === '') {
        $flashMessage = 'Order ID tidak boleh kosong.';
        $flashType    = 'error';
    } else {
        // Ambil shipping_order_no dari DB
        $sql = "SELECT shipping_order_no FROM orders WHERE order_id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $flashMessage = 'Gagal prepare SELECT orders: ' . $conn->error;
            $flashType    = 'error';
        } else {
            $stmt->bind_param('s', $orderId);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            $stmt->close();

            if (!$row || empty($row['shipping_order_no'])) {
                $flashMessage = 'Order ini belum memiliki shipping_order_no (belum berhasil create order ke shipping).';
                $flashType    = 'error';
            } else {
                $orderNo = (string)$row['shipping_order_no'];

                if ($action === 'pickup') {
                    /**
                     * ============
                     *  PICKUP FLOW
                     * ============
                     */

                    // Kalau admin tidak isi, default besok jam 10:00
                    $tz  = new DateTimeZone('Asia/Jakarta');
                    $now = new DateTime('now', $tz);

                    if ($pickupDate === '') {
                        $pickupDate = $now->modify('+1 day')->format('Y-m-d');
                        // reset now biar ga lari 1 hari ke depan terus
                        $now = new DateTime('now', $tz);
                    }
                    if ($pickupTime === '') {
                        $pickupTime = '10:00';
                    }

                    // Build DateTime dari input
                    $pickupDt = DateTime::createFromFormat('Y-m-d H:i', $pickupDate . ' ' . $pickupTime, $tz);
                    if (!$pickupDt) {
                        // kalau parsing gagal → fallback ke now + 2 jam
                        $pickupDt = new DateTime('now', $tz);
                        $pickupDt->modify('+2 hours');
                    }

                    // Minimal 90 menit dari sekarang
                    $minDt = new DateTime('now', $tz);
                    $minDt->modify('+90 minutes');
                    if ($pickupDt < $minDt) {
                        $pickupDt = $minDt;
                    }

                    $pickupDate = $pickupDt->format('Y-m-d');
                    $pickupTime = $pickupDt->format('H:i'); // API harusnya terima HH:MM

                    $payload = [
                        'pickup_date'    => $pickupDate,
                        'pickup_time'    => $pickupTime,
                        'pickup_vehicle' => 'Motor',
                        'orders'         => [
                            ['order_no' => $orderNo],
                        ],
                    ];

                    $url = KOMERCE_ORDER_BASE_URL . '/order/api/v1/pickup/request';

                    $ch = curl_init($url);
                    curl_setopt_array($ch, [
                        CURLOPT_POST           => true,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HTTPHEADER     => [
                            'Content-Type: application/json',
                            'Accept: application/json',
                            'x-api-key: ' . KOMERCE_API_KEY,
                        ],
                        CURLOPT_POSTFIELDS     => json_encode($payload),
                        CURLOPT_TIMEOUT        => 30,
                    ]);

                    $responseBody = curl_exec($ch);
                    $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $curlErr      = curl_error($ch);
                    curl_close($ch);

                    @file_put_contents(
                        __DIR__ . '/shipping_pickup_last_response.log',
                        "=== " . date('Y-m-d H:i:s') . " ===\n" .
                        "order_id: {$orderId}\n" .
                        "order_no: {$orderNo}\n" .
                        "HTTP: {$httpCode}\n" .
                        "Payload:\n" . json_encode($payload, JSON_PRETTY_PRINT) . "\n" .
                        "Response:\n{$responseBody}\n\n",
                        FILE_APPEND
                    );

                    if ($curlErr) {
                        $flashMessage = 'Pickup gagal: cURL error: ' . $curlErr;
                        $flashType    = 'error';
                    } else {
                        $json = json_decode($responseBody, true);

                        if (!is_array($json)) {
                            $flashMessage = 'Pickup gagal: respon bukan JSON valid (HTTP ' . $httpCode . ').';
                            $flashType    = 'error';
                        } else {
                            $meta = $json['meta'] ?? [];
                            $code   = (int)($meta['code'] ?? 0);
                            $status = (string)($meta['status'] ?? '');
                            $msg    = (string)($meta['message'] ?? 'Unknown');

                            if (($code === 201 || $code === 200) && $status === 'success') {
                                // Update status dasar: pickup_requested
                                $sqlUpd = "
                                    UPDATE orders
                                    SET shipping_status = ?,
                                        shipping_last_sync = NOW()
                                    WHERE order_id = ?
                                    LIMIT 1
                                ";
                                $stmtUpd = $conn->prepare($sqlUpd);
                                if ($stmtUpd) {
                                    $pickupStatusText = 'pickup_requested';
                                    $stmtUpd->bind_param('ss', $pickupStatusText, $orderId);
                                    $stmtUpd->execute();
                                    $stmtUpd->close();
                                }

                                // Lanjut sync detail (AWB + status)
                                $syncResult = syncshippingDetailAndUpdateAwb($conn, $orderId, $orderNo);

                                if ($syncResult['success']) {
                                    $flashMessage = 'Pickup request sukses. ' . $syncResult['message'];
                                    $flashType    = 'success';
                                } else {
                                    $flashMessage = 'Pickup request sukses, tapi sync AWB gagal: ' . $syncResult['message'];
                                    $flashType    = 'error';
                                }
                            } else {
                                $flashMessage = 'Pickup gagal: ' . $msg . ' (HTTP ' . $httpCode . ')';
                                $flashType    = 'error';
                            }
                        }
                    }
                } elseif ($action === 'sync_awb') {
                    /**
                     * =====================
                     *  HANYA SYNC DETAIL
                     * =====================
                     */
                    $syncResult = syncshippingDetailAndUpdateAwb($conn, $orderId, $orderNo);

                    if ($syncResult['success']) {
                        $flashMessage = $syncResult['message'];
                        $flashType    = 'success';
                    } else {
                        $flashMessage = $syncResult['message'];
                        $flashType    = 'error';
                    }
                } else {
                    $flashMessage = 'Aksi tidak dikenal.';
                    $flashType    = 'error';
                }
            }
        }
    }
}

// ============================
// AMBIL LIST ORDER untuk TABEL
// ============================
$sqlList = "
    SELECT 
        o.order_id,
        o.tgl_order,
        o.shipping_order_no,
        o.shipping_awb,
        o.shipping_status,
        o.code_courier,
        o.shipping_type,
        c.nama AS customer_name
    FROM orders o
    JOIN customer c ON c.customer_id = o.customer_id
    WHERE o.shipping_order_no IS NOT NULL
    ORDER BY o.tgl_order DESC
    LIMIT 50
";
$resList = $conn->query($sqlList);

$defaultPickupDate = date('Y-m-d', strtotime('+1 day'));
$defaultPickupTime = '10:00';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - shipping Pickup & AWB Sync</title>
    <style>
        :root {
            --bg-main: #0f172a;
            --bg-card: #111827;
            --bg-table-header: #1f2937;
            --bg-table-row: #020617;
            --bg-table-row-alt: #020617;
            --text-main: #e5e7eb;
            --text-muted: #9ca3af;
            --accent: #3b82f6;
            --danger: #f87171;
            --success: #34d399;
            --border-subtle: #1f2933;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: radial-gradient(circle at top, #111827 0, #020617 45%, #000 100%);
            color: var(--text-main);
        }

        .page-wrapper {
            padding: 24px;
        }

        .card {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(15, 23, 42, 0.96);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.25);
            box-shadow:
                0 18px 45px rgba(15, 23, 42, 0.9),
                0 0 0 1px rgba(15, 23, 42, 0.9);
            padding: 20px 22px 24px;
        }

        h1 {
            margin-top: 0;
            font-size: 20px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #f9fafb;
        }

        .subtitle {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 18px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            background: rgba(15, 118, 110, 0.12);
            color: #6ee7b7;
            border: 1px solid rgba(45, 212, 191, 0.35);
            margin-bottom: 10px;
        }

        .badge-dot {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: #6ee7b7;
            box-shadow: 0 0 10px rgba(45, 212, 191, 0.7);
        }

        .alert {
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 14px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }
        .alert-success {
            background: rgba(22, 163, 74, 0.14);
            border: 1px solid rgba(34, 197, 94, 0.55);
            color: #bbf7d0;
        }
        .alert-error {
            background: rgba(185, 28, 28, 0.14);
            border: 1px solid rgba(248, 113, 113, 0.6);
            color: #fecaca;
        }
        .alert strong {
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 13px;
            border-radius: 10px;
            overflow: hidden;
            background: rgba(15, 23, 42, 0.9);
        }
        thead {
            background: var(--bg-table-header);
        }
        th, td {
            padding: 8px 10px;
            border-bottom: 1px solid var(--border-subtle);
            text-align: left;
            white-space: nowrap;
        }
        th {
            font-weight: 600;
            color: #e5e7eb;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        tbody tr:nth-child(odd) {
            background: rgba(15, 23, 42, 0.85);
        }
        tbody tr:nth-child(even) {
            background: rgba(15, 23, 42, 0.75);
        }
        tbody tr:hover {
            background: rgba(30, 64, 175, 0.28);
        }

        .text-muted {
            color: var(--text-muted);
        }

        .badge-status {
            display: inline-flex;
            align-items: center;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 11px;
            border: 1px solid rgba(148, 163, 184, 0.5);
            color: #cbd5f5;
            background: rgba(15, 23, 42, 0.9);
        }

        .badge-status.success {
            border-color: rgba(34, 197, 94, 0.7);
            color: #bbf7d0;
            background: rgba(21, 128, 61, 0.35);
        }

        .badge-status.pending {
            border-color: rgba(234, 179, 8, 0.7);
            color: #fef3c7;
            background: rgba(202, 138, 4, 0.3);
        }

        .actions-cell {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        form.inline-form {
            display: inline-block;
            margin: 0;
        }

        .btn {
            border-radius: 999px;
            border: 1px solid transparent;
            padding: 5px 10px;
            font-size: 11px;
            font-weight: 500;
            cursor: pointer;
            background: var(--accent);
            color: #f9fafb;
            transition: background 0.15s ease, transform 0.1s ease, border-color 0.15s ease;
        }
        .btn:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }
        .btn-outline {
            background: transparent;
            border-color: rgba(148, 163, 184, 0.7);
            color: #e5e7eb;
        }
        .btn-outline:hover {
            border-color: var(--accent);
            background: rgba(37, 99, 235, 0.1);
        }

        .note {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 6px;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 7px;
            border-radius: 999px;
            font-size: 11px;
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid rgba(51, 65, 85, 0.8);
            color: #cbd5f5;
        }

        .pill-dot {
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: #22c55e;
        }

        input[type="date"],
        input[type="time"] {
            background: #020617;
            border-radius: 999px;
            border: 1px solid rgba(55, 65, 81, 0.9);
            color: #e5e7eb;
            padding: 3px 7px;
            font-size: 11px;
        }
        input[type="date"]:focus,
        input[type="time"]:focus {
            outline: none;
            border-color: var(--accent);
        }

        @media (max-width: 960px) {
            .card {
                padding: 16px;
            }
            th, td {
                font-size: 11px;
                padding: 6px 8px;
            }
            .btn {
                padding: 4px 8px;
                font-size: 10px;
            }
        }
    </style>
</head>
<body>
<div class="page-wrapper">
    <div class="card">
        <div class="badge">
            <span class="badge-dot"></span>
            shipping INTEGRATION · ADMIN
        </div>
        <h1>Pickup Request & AWB Sync</h1>
        <div class="subtitle">
            Halaman ini buat <strong>request pickup</strong> ke shipping dan
            <strong>sync AWB + status</strong> dari Delivery API berdasarkan <code>shipping_order_no</code>.
        </div>

        <?php if ($flashMessage !== ''): ?>
            <div class="alert <?= $flashType === 'success' ? 'alert-success' : 'alert-error' ?>">
                <strong><?= $flashType === 'success' ? 'OK' : 'Error' ?>:</strong>
                <span><?= htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        <?php endif; ?>

        <table>
            <thead>
            <tr>
                <th>Order ID</th>
                <th>Tanggal</th>
                <th>Customer</th>
                <th>Kurir</th>
                <th>Service</th>
                <th>shipping Order No</th>
                <th>AWB</th>
                <th>Status</th>
                <th style="min-width: 240px;">Aksi</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($resList && $resList->num_rows > 0): ?>
                <?php while ($row = $resList->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['order_id']) ?></td>
                        <td><?= htmlspecialchars($row['tgl_order']) ?></td>
                        <td><?= htmlspecialchars($row['customer_name']) ?></td>
                        <td><?= htmlspecialchars(strtoupper($row['code_courier'])) ?></td>
                        <td><?= htmlspecialchars($row['shipping_type']) ?></td>
                        <td>
                            <span class="pill">
                                <span class="pill-dot"></span>
                                <?= htmlspecialchars($row['shipping_order_no']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($row['shipping_awb'])): ?>
                                <code><?= htmlspecialchars($row['shipping_awb']) ?></code>
                            <?php else: ?>
                                <span class="text-muted">Belum ada</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $status = $row['shipping_status'] ?? '';
                            $statusLower = strtolower($status);
                            $statusClass = '';
                            if (in_array($statusLower, ['pickup_success', 'delivered'], true)) {
                                $statusClass = 'success';
                            } elseif (in_array($statusLower, ['pickup_requested', 'diajukan'], true)) {
                                $statusClass = 'pending';
                            }
                            ?>
                            <span class="badge-status <?= $statusClass ?>">
                                <?= $status !== '' ? htmlspecialchars($status) : '—' ?>
                            </span>
                        </td>
                        <td>
                            <div class="actions-cell">
                                <!-- Pickup + auto sync AWB -->
                                <form method="post" class="inline-form" style="margin-bottom:4px;">
                                    <input type="hidden" name="order_id" value="<?= htmlspecialchars($row['order_id']) ?>">
                                    <input type="hidden" name="action" value="pickup">

                                    <input type="date"
                                           name="pickup_date"
                                           value="<?= $defaultPickupDate ?>">
                                    <input type="time"
                                           name="pickup_time"
                                           value="<?= $defaultPickupTime ?>">

                                    <button type="submit" class="btn">
                                        Pickup + Sync AWB
                                    </button>
                                </form>

                                <!-- Hanya sync AWB / status dari Detail Order -->
                                <form method="post" class="inline-form">
                                    <input type="hidden" name="order_id" value="<?= htmlspecialchars($row['order_id']) ?>">
                                    <input type="hidden" name="action" value="sync_awb">
                                    <button type="submit" class="btn btn-outline">
                                        Sync AWB / Status
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="text-muted">Belum ada order yang punya <code>shipping_order_no</code>.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

        <div class="note">
            Catatan:
            <ul>
                <li>Kalau waktu pickup yang dipilih terlalu dekat, script otomatis geser ke minimal <strong>+90 menit</strong> dari sekarang.</li>
                <li>AWB & status diambil dari endpoint <code>/order/api/v1/orders/detail?order_no=...</code>.</li>
                <li>Log request tersimpan di <code>shipping_pickup_last_response.log</code> dan <code>shipping_detail_last_response.log</code>.</li>
            </ul>
        </div>
    </div>
</div>
</body>
</html>
