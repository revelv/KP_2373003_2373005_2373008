<?php
// user/test_alamat_komship.php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Test Alamat Komship</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: "Montserrat", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            padding-top: 40px;
        }
        .result-item {
            cursor: pointer;
        }
        .result-item:hover {
            background-color: #f8f9fa;
        }
        .selected-box {
            border: 1px solid #ffc107;
            background: #fff8e1;
        }
    </style>
</head>
<body>
<div class="container">
    <h2 class="mb-3">Dummy Test Alamat Komship</h2>
    <p class="text-muted">
        Ketik nama kecamatan/kota/kabupaten atau kode pos, lalu klik "Cari".
    </p>

    <!-- FORM CARI -->
    <div class="card mb-3">
        <div class="card-body">
            <form id="formCari" class="row g-2">
                <div class="col-md-8">
                    <label for="keyword" class="form-label">Keyword</label>
                    <input type="text" id="keyword" name="keyword" class="form-control"
                           placeholder="Contoh: CIMAUNG / BANDUNG / 40374">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Cari di Komship</button>
                </div>
            </form>
        </div>
    </div>

    <!-- HASIL PENCARIAN -->
    <div class="card mb-3">
        <div class="card-header">
            Hasil Pencarian Komship
        </div>
        <div class="card-body" id="hasilKomship">
            <p class="text-muted m-0">Belum ada hasil. Silakan cari dulu.</p>
        </div>
    </div>

    <!-- ALAMAT TERPILIH (DUMMY REGISTER) -->
    <div class="card">
        <div class="card-header">
            Alamat Terpilih (Dummy)
        </div>
        <div class="card-body selected-box" id="boxSelected">
            <p class="text-muted m-0">
                Klik salah satu hasil di atas untuk melihat detail di sini.
            </p>
        </div>
    </div>
</div>

<script>
    const formCari = document.getElementById('formCari');
    const hasilDiv = document.getElementById('hasilKomship');
    const boxSelected = document.getElementById('boxSelected');

    formCari.addEventListener('submit', async (e) => {
        e.preventDefault();
        const kw = document.getElementById('keyword').value.trim();
        if (!kw) {
            alert('Isi keyword dulu bro.');
            return;
        }

        hasilDiv.innerHTML = '<p class="text-muted">Loading data dari Komship...</p>';
        boxSelected.innerHTML = '<p class="text-muted m-0">Klik salah satu hasil di atas untuk melihat detail di sini.</p>';

        try {
            const res = await fetch('komship_destination_search.php?q=' + encodeURIComponent(kw));
            const txt = await res.text();
            let data;
            try {
                data = JSON.parse(txt);
            } catch (err) {
                console.error('Parse error:', txt);
                hasilDiv.innerHTML = '<div class="text-danger">Respon bukan JSON valid. Cek console.</div>';
                return;
            }

            if (!data.success) {
                hasilDiv.innerHTML = '<div class="text-danger">Gagal: ' + (data.message || 'Unknown error') + '</div>';
                return;
            }

            if (!Array.isArray(data.data) || data.data.length === 0) {
                hasilDiv.innerHTML = '<p class="text-muted">Tidak ada hasil untuk keyword itu.</p>';
                return;
            }

            // Render list
            const html = data.data.map((item, idx) => {
                const label = item.label || (
                    (item.subdistrict || '') + ', ' +
                    (item.district || '')   + ', ' +
                    (item.city || '')       + ' ' +
                    (item.zip || '')
                );
                return `
                    <div class="border rounded p-2 mb-2 result-item" data-idx="${idx}">
                        <div><strong>${label}</strong></div>
                        <div class="small text-muted">ID: ${item.id ?? ''}</div>
                    </div>
                `;
            }).join('');

            hasilDiv.innerHTML = html;

            // Tambahkan interaksi click untuk liat detail di bawah
            const items = hasilDiv.querySelectorAll('.result-item');
            items.forEach(itemEl => {
                itemEl.addEventListener('click', () => {
                    const idx = parseInt(itemEl.getAttribute('data-idx') || '0', 10);
                    const it  = data.data[idx];

                    const label = it.label || (
                        (it.subdistrict || '') + ', ' +
                        (it.district || '')   + ', ' +
                        (it.city || '')       + ' ' +
                        (it.zip || '')
                    );

                    boxSelected.innerHTML = `
                        <p class="mb-1"><strong>Label:</strong> ${label}</p>
                        <p class="mb-1"><strong>ID Komship (destination_id):</strong> ${it.id ?? ''}</p>
                        <p class="mb-1"><strong>Subdistrict:</strong> ${it.subdistrict || '-'}</p>
                        <p class="mb-1"><strong>District:</strong> ${it.district || '-'}</p>
                        <p class="mb-1"><strong>City:</strong> ${it.city || '-'}</p>
                        <p class="mb-1"><strong>Zip:</strong> ${it.zip || '-'}</p>
                        <hr>
                        <p class="small text-muted mb-0">
                            Nanti di versi beneran, ID ini disimpan ke database sebagai <code>komship_destination_id</code>.
                        </p>
                    `;
                });
            });

        } catch (err) {
            console.error(err);
            hasilDiv.innerHTML = '<div class="text-danger">Error koneksi: ' + (err.message || err) + '</div>';
        }
    });
</script>
</body>
</html>
