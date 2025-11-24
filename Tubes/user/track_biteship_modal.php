<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../koneksi.php';

header('Content-Type: text/html; charset=utf-8');

// ====================== AUTH ======================
if (!isset($_SESSION['kd_cs'])) {
    ?>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tracking Pengiriman</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning mb-0">
                    Anda harus login terlebih dahulu.
                </div>
            </div>
        </div>
    </div>
    <?php
    exit;
}
$customer_id = (int) $_SESSION['kd_cs'];

// ====================== PARAM ORDER ======================
$order_id = isset($_GET['order_id']) ? trim((string)$_GET['order_id']) : '';
if ($order_id === '') {
    ?>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tracking Pengiriman</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger mb-0">
                    Order ID tidak valid untuk tracking.
                </div>
            </div>
        </div>
    </div>
    <?php
    exit;
}

// ====================== AMBIL DATA ORDER ======================
$sql = "
    SELECT 
        o.order_id,
        o.customer_id,
        o.tgl_order,
        o.total_harga,
        o.code_courier,
        o.shipping_tracking_code,
        o.shipping_status,
        o.shipping_provider_order_id,
        o.provinsi,
        o.kota,
        o.kecamatan,
        o.kelurahan,
        o.alamat,
        o.postal_code
    FROM orders o
    WHERE o.order_id = ?
      AND o.customer_id = ?
    LIMIT 1
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    ?>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tracking Pengiriman</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger mb-0">
                    Gagal memuat data order untuk tracking.
                </div>
            </div>
        </div>
    </div>
    <?php
    exit;
}
$stmt->bind_param('si', $order_id, $customer_id);
$stmt->execute();
$res   = $stmt->get_result();
$order = $res->fetch_assoc();
$stmt->close();

if (!$order) {
    ?>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tracking Pengiriman</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger mb-0">
                    Order tidak ditemukan atau bukan milik akun ini.
                </div>
            </div>
        </div>
    </div>
    <?php
    exit;
}

$courierCode = strtolower(trim((string)($order['code_courier'] ?? '')));
$waybill     = trim((string)($order['shipping_tracking_code'] ?? ''));

// ====================== CONFIG BITESHIP ======================
const BITESHIP_API_KEY_TRACK = 'biteship_test.eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJuYW1lIjoiU3R5cmtfaW5kdXN0cmllcyIsInVzZXJJZCI6IjY5MjA3ZmI0YzMxM2VmYTUyZTM5OThlNCIsImlhdCI6MTc2Mzc4NTQ0OH0.dBPLQHoBBV4gnXux-OMziAO5yr1TBzXTf4T-Js2b0ak';
const BITESHIP_TRACK_URL     = 'https://api.biteship.com/v1/trackings';

$trackingOk   = false;
$trackingData = null;
$trackingErr  = null;

// ====================== KALAU GA ADA RESI / KURIR ======================
if ($courierCode === '' || $waybill === '') {
    $trackingErr = 'Resi atau kode kurir belum tersedia. Silakan cek lagi nanti atau hubungi admin.';
} else {

    // ===== Helper call Biteship =====
    function fetchBiteshipTracking(string $courier, string $waybill): array
    {
        $courier = trim($courier);
        $waybill = trim($waybill);

        if ($courier === '' || $waybill === '') {
            return [
                'ok'    => false,
                'error' => 'Kurir atau nomor resi kosong.',
            ];
        }

        $url = BITESHIP_TRACK_URL . '/' . rawurlencode($waybill) . '/couriers/' . rawurlencode($courier);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET        => true,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Authorization: ' . BITESHIP_API_KEY_TRACK,
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            $err = curl_error($ch);
            curl_close($ch);
            return [
                'ok'        => false,
                'http_code' => $httpCode,
                'error'     => 'cURL Error: ' . $err,
            ];
        }
        curl_close($ch);

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return [
                'ok'        => false,
                'http_code' => $httpCode,
                'error'     => 'Respon Biteship bukan JSON valid.',
                'raw_body'  => $response,
            ];
        }

        return [
            'ok'        => ($httpCode >= 200 && $httpCode < 300 && (!isset($decoded['success']) || $decoded['success'] === true)),
            'http_code' => $httpCode,
            'data'      => $decoded,
        ];
    }

    $track = fetchBiteshipTracking($courierCode, $waybill);
    $trackingOk   = !empty($track['ok']);
    $trackingData = $track['data'] ?? null;
    $trackingErr  = $track['error'] ?? null;

    // Optional: update shipping_status di DB dari Biteship
    if ($trackingOk && is_array($trackingData)) {
        $root      = $trackingData;
        $statusNew = $root['status']
            ?? ($root['delivery_status']
            ?? ($root['shipment_status'] ?? null));

        if ($statusNew) {
            $sqlUpd = "
                UPDATE orders
                SET shipping_status    = ?,
                    shipping_last_sync = NOW()
                WHERE order_id = ?
                LIMIT 1
            ";
            $stmtUpd = $conn->prepare($sqlUpd);
            if ($stmtUpd) {
                $stmtUpd->bind_param('ss', $statusNew, $order_id);
                $stmtUpd->execute();
                $stmtUpd->close();
                $order['shipping_status'] = $statusNew;
            }
        }
    }
}

// ====================== EXTRACT HISTORY ======================
$historyEvents = [];
if ($trackingOk && is_array($trackingData ?? null)) {
    $root = $trackingData;
    if (isset($root['history']) && is_array($root['history'])) {
        $historyEvents = $root['history'];
    } elseif (isset($root['tracking_history']) && is_array($root['tracking_history'])) {
        $historyEvents = $root['tracking_history'];
    } elseif (isset($root['logs']) && is_array($root['logs'])) {
        $historyEvents = $root['logs'];
    }
}

// Sort history terbaru di atas kalau ada timestamp
if ($historyEvents) {
    usort($historyEvents, function ($a, $b) {
        $ta = strtotime($a['updated_at'] ?? $a['timestamp'] ?? $a['time'] ?? '1970-01-01');
        $tb = strtotime($b['updated_at'] ?? $b['timestamp'] ?? $b['time'] ?? '1970-01-01');
        return $tb <=> $ta;
    });
}

// ====================== RENDER MODAL CONTENT ======================
?>
<div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Tracking Pengiriman – Order #<?= htmlspecialchars($order_id) ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
            <style>
                .badge-status {
                    font-size: .8rem;
                    text-transform: uppercase;
                }
                .timeline {
                    list-style: none;
                    padding-left: 0;
                    margin: 0;
                    position: relative;
                }
                .timeline::before {
                    content: "";
                    position: absolute;
                    left: 10px;
                    top: 0;
                    bottom: 0;
                    width: 2px;
                    background: #e5e7eb;
                }
                .timeline-item {
                    position: relative;
                    padding-left: 36px;
                    padding-bottom: 16px;
                }
                .timeline-item:last-child {
                    padding-bottom: 0;
                }
                .timeline-dot {
                    position: absolute;
                    left: 6px;
                    top: 4px;
                    width: 10px;
                    height: 10px;
                    border-radius: 999px;
                    background: #6b7280;
                }
                .timeline-item.active .timeline-dot {
                    background: #22c55e;
                }
            </style>

            <div class="row mb-3">
                <div class="col-md-6">
                    <h6 class="text-muted mb-1">Informasi Order</h6>
                    <p class="mb-1">
                        Tanggal Order:
                        <strong><?= htmlspecialchars((string)$order['tgl_order']) ?></strong><br>
                        Total:
                        <strong>Rp <?= number_format((int)$order['total_harga'], 0, ',', '.') ?></strong>
                    </p>
                    <p class="mb-0">
                        Kurir:
                        <strong><?= htmlspecialchars(strtoupper($courierCode ?: '-')) ?></strong><br>
                        No. Resi:
                        <strong><?= htmlspecialchars($waybill ?: '-') ?></strong><br>
                        Status (DB):
                        <span class="badge bg-primary badge-status">
                            <?= htmlspecialchars((string)($order['shipping_status'] ?? '-')) ?>
                        </span>
                    </p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-1">Alamat Pengiriman</h6>
                    <p class="mb-0">
                        <?= nl2br(htmlspecialchars((string)$order['alamat'])) ?><br>
                        <?= htmlspecialchars((string)$order['kota']) ?>
                        <?= $order['kecamatan'] ? ' - ' . htmlspecialchars((string)$order['kecamatan']) : '' ?>
                        <?= $order['kelurahan'] ? ' - ' . htmlspecialchars((string)$order['kelurahan']) : '' ?><br>
                        <?= htmlspecialchars((string)$order['provinsi']) ?>
                        <?php if (!empty($order['postal_code'])): ?>
                            - <?= htmlspecialchars((string)$order['postal_code']) ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <?php if (!$courierCode || !$waybill): ?>
                <div class="alert alert-warning mb-0">
                    Resi atau kode kurir belum tersedia. Silakan cek lagi nanti atau hubungi admin.
                </div>
            <?php elseif (!$trackingOk): ?>
                <div class="alert alert-danger">
                    Gagal memuat tracking dari Biteship.<br>
                    <small><?= htmlspecialchars((string)($trackingErr ?: 'Unknown error')) ?></small>
                </div>
                <?php if (!empty($trackingData)): ?>
                    <details>
                        <summary>Detail respon tracking (debug)</summary>
                        <pre class="small bg-dark text-light p-2 rounded mt-2" style="max-height:220px;overflow:auto;">
<?= htmlspecialchars(json_encode($trackingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>
                        </pre>
                    </details>
                <?php endif; ?>
            <?php else: ?>
                <?php
                $root      = $trackingData;
                $statusNow = $root['status']
                    ?? ($root['delivery_status']
                    ?? ($root['shipment_status'] ?? ''));
                $summary = $root['summary']['status'] ?? '';
                $updated = $root['updated_at']
                    ?? ($root['summary']['updated_at']
                    ?? ($root['last_updated'] ?? ''));
                ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <p class="mb-1">
                            Status Terbaru:
                            <span class="badge bg-success badge-status">
                                <?= htmlspecialchars($statusNow ?: 'Unknown') ?>
                            </span>
                        </p>
                        <?php if ($summary): ?>
                            <p class="mb-1">Ringkasan: <?= htmlspecialchars($summary) ?></p>
                        <?php endif; ?>
                        <?php if ($updated): ?>
                            <p class="mb-0">
                                <small class="text-muted">Terakhir update: <?= htmlspecialchars($updated) ?></small>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        Riwayat Perjalanan Paket
                    </div>
                    <div class="card-body">
                        <?php if (empty($historyEvents)): ?>
                            <p class="text-muted mb-0">Belum ada detail riwayat dari Biteship.</p>
                        <?php else: ?>
                            <ul class="timeline">
                                <?php
                                $first = true;
                                foreach ($historyEvents as $ev):
                                    $time = $ev['updated_at']
                                        ?? ($ev['timestamp']
                                        ?? ($ev['time'] ?? ''));
                                    $stat = $ev['status']
                                        ?? ($ev['status_text'] ?? '');
                                    $loc  = $ev['location'] ?? ($ev['city'] ?? '');
                                    $note = $ev['note'] ?? ($ev['description'] ?? '');
                                ?>
                                    <li class="timeline-item <?= $first ? 'active' : '' ?>">
                                        <span class="timeline-dot"></span>
                                        <div>
                                            <strong><?= htmlspecialchars($stat ?: 'Status') ?></strong>
                                            <?php if ($time): ?>
                                                <div><small class="text-muted"><?= htmlspecialchars($time) ?></small></div>
                                            <?php endif; ?>
                                            <?php if ($loc): ?>
                                                <div><small>Lokasi: <?= htmlspecialchars($loc) ?></small></div>
                                            <?php endif; ?>
                                            <?php if ($note): ?>
                                                <div><small>Keterangan: <?= htmlspecialchars($note) ?></small></div>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php
                                    $first = false;
                                endforeach;
                                ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </div>
</div>  
