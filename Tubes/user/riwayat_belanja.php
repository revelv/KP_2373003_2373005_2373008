<?php

declare(strict_types=1);
require_once __DIR__ . '/header.php';

if (!isset($_SESSION['kd_cs'])) {
    echo "<script>alert('Anda harus login terlebih dahulu.'); window.location.href='produk.php';</script>";
    exit();
}
$customer_id = (int) $_SESSION['kd_cs'];

$sql = "SELECT o.order_id, o.tgl_order, o.provinsi, o.kota, o.alamat,
               o.code_courier, o.ongkos_kirim, o.total_harga, o.status,
               COALESCE(c.nama_kurir, '') AS nama_kurir
        FROM orders o
        LEFT JOIN courier c ON c.code_courier = o.code_courier
        WHERE o.customer_id = ?
        ORDER BY o.tgl_order DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$res = $stmt->get_result();
$orders = [];
while ($row = $res->fetch_assoc()) $orders[] = $row;
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <title>Riwayat Belanja</title>
    <link rel="stylesheet" href="./css/riwayat_belanja.css">
</head>

<body>
    <section class="section-bleed">
        <div class="inner">
            <div class="page-head">
                <h2 class="page-title">Riwayat Belanja</h2>
            </div>

            <div class="card-wrap">
                <div class="table-responsive">
                    <table class="table table-bordered table-gold align-middle">
                        <colgroup>
                            <col class="col-date">
                            <col class="col-order">
                            <col> <!-- tujuan fleksibel -->
                            <col class="col-courier">
                            <col class="col-ongkir">
                            <col class="col-total">
                            <col class="col-status">
                            <col class="col-action">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Order ID</th>
                                <th>Tujuan</th>
                                <th>Kurir</th>
                                <th>Ongkir</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$orders): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">Belum ada transaksi.</td>
                                </tr>
                                <?php else: foreach ($orders as $o):
                                    $tgl     = date('d M Y H:i', strtotime($o['tgl_order']));
                                    $alamat  = trim((string)($o['alamat'] ?? ''));
                                    $provRaw = (string)($o['provinsi'] ?? '');
                                    $kotaRaw = (string)($o['kota'] ?? '');
                                    $kurirNm = trim((string)$o['nama_kurir']);
                                    if ($kurirNm === '') $kurirNm = ($o['code_courier'] ? strtoupper($o['code_courier']) : '-');
                                    $ongkir  = (int)$o['ongkos_kirim'];
                                    $total   = (float)$o['total_harga'];
                                    $status  = strtolower((string)$o['status']);

                                    $looksProvId = ctype_digit($provRaw);
                                    $looksCityId = ctype_digit($kotaRaw);
                                    $hasNames    = (!$looksProvId && !$looksCityId && $provRaw !== '' && $kotaRaw !== '');
                                    $cityProvTxt = $hasNames ? ($kotaRaw . ' - ' . $provRaw) : 'Memuat…';
                                    $addrShown   = $alamat !== '' ? htmlspecialchars($alamat) : '—';
                                    $badgeClass  = 'badge-status ' . match ($status) {
                                        'pending' => 'badge-pending',
                                        'proses' => 'badge-proses',
                                        'selesai' => 'badge-selesai',
                                        'batal' => 'badge-batal',
                                        default => 'bg-secondary'
                                    };
                                ?>
                                    <tr>
                                        <td class="nowrap"><?= htmlspecialchars($tgl) ?></td>
                                        <td class="text-mono nowrap"><?= htmlspecialchars($o['order_id']) ?></td>
                                        <td>
                                            <div class="dest" data-prov="<?= htmlspecialchars($provRaw) ?>" data-city="<?= htmlspecialchars($kotaRaw) ?>">
                                                <div class="address"><?= $addrShown ?></div>
                                                <div class="cityprov js-cityprov"><?= htmlspecialchars($cityProvTxt) ?></div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($kurirNm) ?></td>
                                        <td>Rp <?= number_format($ongkir, 0, ',', '.') ?></td>
                                        <td><strong>Rp <?= number_format((int)round($total), 0, ',', '.') ?></strong></td>
                                        <td><span class="<?= $badgeClass ?> px-2 py-1 rounded-2"><?= htmlspecialchars($status) ?></span></td>
                                        <td class="td-aksi text-center">
                                            <button
                                                type="button"
                                                class="btn btn-outline-secondary btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalTrack"
                                                data-order="<?= htmlspecialchars($o['order_id']) ?>"
                                                data-courier="<?= htmlspecialchars($o['code_courier']) ?>">
                                                Lacak
                                            </button>
                                        </td>

                                    </tr>
                            <?php endforeach;
                            endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal Tracking -->
    <div class="modal fade" id="modalTrack" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Pelacakan Pengiriman</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div id="trackSummary" class="mb-3 small text-muted"></div>
                    <ul id="trackEvents" class="list-group"></ul>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (async function() {
            const dests = Array.from(document.querySelectorAll('.dest'));
            if (!dests.length) return;

            const isNum = v => typeof v === 'string' && /^[0-9]+$/.test(v);
            let provMap = null;
            const cityMap = new Map();

            async function getProvMap() {
                if (provMap) return provMap;
                try {
                    const r = await fetch('./rajaongkir/get-province.php?t=' + Date.now());
                    const t = await r.text();
                    const d = JSON.parse(t);
                    const list = Array.isArray(d) ? d : (d.data || []);
                    provMap = {};
                    list.forEach(p => {
                        const id = String(p.province_id ?? p.id ?? p.provinceId ?? '').trim();
                        const nm = String(p.province ?? p.name ?? p.provinceName ?? '').trim();
                        if (id && nm) provMap[id] = nm;
                    });
                    return provMap;
                } catch {
                    return {};
                }
            }
            async function getCityMap(provId) {
                if (cityMap.has(provId)) return cityMap.get(provId);
                try {
                    const r = await fetch('./rajaongkir/get-cities.php?province=' + encodeURIComponent(provId) + '&t=' + Date.now());
                    const t = await r.text();
                    const d = JSON.parse(t);
                    const list = Array.isArray(d) ? d : (d.data || []);
                    const map = {};
                    list.forEach(c => {
                        const id = String(c.city_id ?? c.id ?? c.cityId ?? '').trim();
                        const nm = String(c.city_name ?? c.name ?? c.cityName ?? '').trim();
                        if (id && nm) map[id] = nm;
                    });
                    cityMap.set(provId, map);
                    return map;
                } catch {
                    return {};
                }
            }

            const pMap = await getProvMap();
            for (const el of dests) {
                const cityprov = el.querySelector('.js-cityprov');
                const provRaw = (el.getAttribute('data-prov') || '').trim();
                const cityRaw = (el.getAttribute('data-city') || '').trim();
                if (!isNum(provRaw) && !isNum(cityRaw)) continue;

                let provName = provRaw,
                    cityName = cityRaw;
                if (isNum(provRaw)) provName = pMap[provRaw] || provRaw;
                if (isNum(cityRaw)) {
                    const cm = await getCityMap(provRaw);
                    cityName = cm[cityRaw] || cityRaw;
                }

                cityprov.textContent = (cityName && provName) ? (cityName + ' - ' + provName) : (cityName || provName || '-');
            }
        })();

        // Modal tracking (resi = order_id)

        // Listener saat modal "Lacak" dibuka
        const modalEl = document.getElementById('modalTrack');
        modalEl?.addEventListener('show.bs.modal', async (ev) => {
            const btn = ev.relatedTarget;
            const orderId = btn?.getAttribute('data-order') || '';
            const courier = btn?.getAttribute('data-courier') || '';

            // Ambil konteks alamat dari baris tabel
            const tr = btn.closest('tr');
            const dest = tr?.querySelector('.dest');
            const addrText = tr?.querySelector('.address')?.textContent?.trim() || '';
            const cityProvText = tr?.querySelector('.js-cityprov')?.textContent?.trim() || '';

            // raw (bisa jadi ID atau nama—tergantung data yang disimpan)
            const provRaw = dest?.getAttribute('data-prov') || '';
            const cityRaw = dest?.getAttribute('data-city') || '';

            // kirim ke backend
            const fd = new FormData();
            fd.append('order_id', orderId);
            fd.append('courier', courier);
            fd.append('alamat', addrText);
            fd.append('cityprov', cityProvText);
            fd.append('prov_raw', provRaw);
            fd.append('city_raw', cityRaw);

            // UI target
            const $sum = document.getElementById('trackSummary');
            const $ul = document.getElementById('trackEvents');
            $sum.textContent = 'Memuat…';
            $ul.innerHTML = '';

            try {
                const res = await fetch('./rajaongkir/track-resi.php', {
                    method: 'POST',
                    body: fd
                });
                const text = await res.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch {
                    throw new Error('Respon bukan JSON: ' + text);
                }

                if (!data.success) {
                    $sum.textContent = data.message || 'Gagal memuat.';
                    return;
                }

                const s = data.summary || {};
                $sum.innerHTML = `
        <div><strong>Kurir:</strong> ${s.courier || '-'}</div>
        <div><strong>Waybill/Order:</strong> <span class="text-mono">${s.waybill || orderId}</span></div>
        <div><strong>Status:</strong> ${s.status || '-'}</div>
        <div class="small mt-2 text-muted"><strong>Tujuan:</strong> ${data.dest_text || '-'}</div>
      `;

                (data.events || []).forEach(e => {
                    const li = document.createElement('li');
                    li.className = 'list-group-item';
                    li.innerHTML =
                        `<div class="d-flex justify-content-between">
              <strong>${e.desc || '-'}</strong>
              <span class="small text-muted">${e.time || ''}</span>
            </div>
            <div class="small text-muted">${e.loc || ''}</div>`;
                    $ul.appendChild(li);
                });
                if (!$ul.children.length)
                    $ul.innerHTML = '<li class="list-group-item text-muted">Belum ada event.</li>';
            } catch (e) {
                $sum.textContent = (e && e.message) ? e.message : String(e);
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <?php include __DIR__ . '/footer.php'; ?>
</body>

</html>