<?php
session_start();
$old = $_SESSION['form_data'] ?? [];
$error_message = $_SESSION['register_error'] ?? '';
unset($_SESSION['form_data'], $_SESSION['register_error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        /* Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #121212;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            color: #e0e0e0;
            font-family: 'Montserrat', sans-serif;
            line-height: 1.6;
        }

        /* Container Styles */
        .container {
            background-color: #1e1e1e;
            border-radius: 12px;
            padding: 40px;
            width: 100%;
            max-width: 800px;
            border: 1px solid #333;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: fadeIn 0.5s ease-out;
        }

        /* Typography */
        h2 {
            color: #d4af37;
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: 600;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 20px;
        }

        label {
            color: #d4af37;
            font-weight: 500;
            margin-bottom: 8px;
            display: block;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            background-color: #2a2a2a;
            border: 2px solid #333;
            border-radius: 8px;
            color: #e0e0e0;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #d4af37;
            outline: none;
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
        }

        /* Button Styles */
        .btn-success {
            background: linear-gradient(135deg, #d4af37 0%, #f9d423 100%);
            color: #1e1e1e;
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.4);
        }

        .btn-success:active {
            transform: translateY(0);
        }

        /* Grid Layout */
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }

        .col-md-6 {
            padding: 0 10px;
            flex: 0 0 50%;
            max-width: 50%;
            margin-bottom: 15px;
        }

        /* Alert Message */
        .alert {
            background-color: #b91c1c;
            color: #fff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            border: 1px solid #f87171;
            text-align: center;
        }

        /* Link Styles */
        .back-link {
            color: #b0b0b0;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
            text-align: center;
            width: 100%;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: #d4af37;
            text-decoration: underline;
        }

        /* Button Container */
        .form-actions {
            margin-top: 20px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 30px 20px;
            }

            .col-md-6 {
                flex: 0 0 100%;
                max-width: 100%;
            }

            h2 {
                font-size: 24px;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Form Registrasi</h2>

        <?php if ($error_message): ?>
            <div class="alert"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <form action="proses_registrasi.php" method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="nama">Nama Lengkap</label>
                        <input type="text" id="nama" name="nama" class="form-control" required
                            value="<?= htmlspecialchars($old['nama'] ?? '') ?>" placeholder="Masukkan nama lengkap">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="email">Alamat Email</label>
                        <input type="email" id="email" name="email" class="form-control" required
                            value="<?= htmlspecialchars($old['email'] ?? '') ?>" placeholder="contoh@email.com">
                    </div>
                </div>

                <!-- Provinsi -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="provinsi">Provinsi</label>
                        <select id="provinsi" name="provinsi" class="form-control" required>
                            <option value="">-- Pilih Provinsi --</option>
                        </select>
                    </div>
                </div>

                <!-- Kota -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="kota">Kota</label>
                        <select id="kota" name="kota" class="form-control" required disabled>
                            <option value="">-- Pilih Provinsi Dahulu --</option>
                        </select>
                    </div>
                </div>

                <!-- Kecamatan -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="kecamatan">Kecamatan</label>
                        <select id="kecamatan" name="kecamatan" class="form-control" required disabled>
                            <option value="">-- Pilih Kota Dahulu --</option>
                        </select>
                    </div>
                </div>

                <!-- Kelurahan (Sub-district) -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="kelurahan">Kelurahan</label>
                        <select id="kelurahan" name="kelurahan" class="form-control" required disabled>
                            <option value="">-- Pilih Kecamatan Dahulu --</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="alamat">Alamat</label>
                        <input type="text" id="alamat" name="alamat" class="form-control" required
                            value="<?= htmlspecialchars($old['alamat'] ?? '') ?>" placeholder="Alamat lengkap">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="telp">Nomor Telepon</label>
                        <input type="text" id="telp" name="telp" class="form-control" required
                            value="<?= htmlspecialchars($old['telp'] ?? '') ?>" placeholder="+62">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required
                            placeholder="Minimal 8 karakter">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="konfirmasi">Konfirmasi Password</label>
                        <input type="password" id="konfirmasi" name="konfirmasi" class="form-control" required
                            placeholder="Ketik ulang password">
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-success">Daftar Sekarang</button>
                <a href="../produk.php" class="back-link">Kembali ke Halaman Produk</a>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const selProv = document.getElementById('provinsi');
            const selKota = document.getElementById('kota');
            const selKec = document.getElementById('kecamatan');
            const selKel = document.getElementById('kelurahan');

            const oldProv = <?= json_encode($old['provinsi'] ?? '') ?>;   // NAMA provinsi
            const oldKota = <?= json_encode($old['kota'] ?? '') ?>;       // NAMA kota
            const oldKec  = <?= json_encode($old['kecamatan'] ?? '') ?>;  // NAMA kecamatan
            const oldKel  = <?= json_encode($old['kelurahan'] ?? '') ?>;  // NAMA kelurahan

            function selectByText(selectEl, text) {
                if (!selectEl || !text) return;
                const opts = Array.from(selectEl.options);
                const found = opts.find(o => o.text.trim().toUpperCase() === text.trim().toUpperCase());
                if (found) selectEl.value = found.value;
            }

            async function loadProvinces() {
                if (!selProv) return;
                selProv.innerHTML = '<option value="">Loading…</option>';
                try {
                    const res = await fetch('../../user/rajaongkir/get-province.php', {
                        cache: 'no-store'
                    });
                    const text = await res.text();
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        console.error('Province JSON error:', e, text);
                        selProv.innerHTML = '<option value="">Gagal parsing data provinsi</option>';
                        return;
                    }

                    const list = Array.isArray(data) ? data : (data.data || []);
                    if (!Array.isArray(list) || list.length === 0) {
                        selProv.innerHTML = '<option value="">Tidak ada data provinsi</option>';
                        return;
                    }

                    selProv.innerHTML = '<option value="">-- Pilih Provinsi --</option>';
                    for (const p of list) {
                        const id = p.id ?? p.province_id ?? '';
                        const name = p.name ?? p.province ?? '';
                        if (!id || !name) continue;
                        const opt = document.createElement('option');
                        opt.value = name;         // ke PHP: NAMA
                        opt.dataset.id = id;      // buat JS panggil RajaOngkir
                        opt.textContent = name;
                        selProv.appendChild(opt);
                    }

                    if (oldProv) {
                        selectByText(selProv, oldProv);
                        const opt = selProv.options[selProv.selectedIndex];
                        if (opt && opt.dataset.id) {
                            await loadCities(opt.dataset.id, true);
                        }
                    }

                } catch (err) {
                    console.error(err);
                    selProv.innerHTML = '<option value="">Fetch error provinsi</option>';
                }
            }

            async function loadCities(provinceId, autoSelect = false) {
                if (!provinceId || !selKota) {
                    selKota.innerHTML = '<option value="">-- Pilih Provinsi Dahulu --</option>';
                    selKota.disabled = true;
                    if (selKec) {
                        selKec.innerHTML = '<option value="">-- Pilih Kota Dahulu --</option>';
                        selKec.disabled = true;
                    }
                    if (selKel) {
                        selKel.innerHTML = '<option value="">-- Pilih Kecamatan Dahulu --</option>';
                        selKel.disabled = true;
                    }
                    return;
                }

                selKota.innerHTML = '<option value="">Loading…</option>';
                selKota.disabled = true;

                if (selKec) {
                    selKec.innerHTML = '<option value="">-- Pilih Kota Dahulu --</option>';
                    selKec.disabled = true;
                }
                if (selKel) {
                    selKel.innerHTML = '<option value="">-- Pilih Kecamatan Dahulu --</option>';
                    selKel.disabled = true;
                }

                try {
                    const res = await fetch('../../user/rajaongkir/get-cities.php?province=' + encodeURIComponent(provinceId), {
                        cache: 'no-store'
                    });
                    const text = await res.text();
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        console.error('City JSON error:', e, text);
                        selKota.innerHTML = '<option value="">Gagal parsing data kota</option>';
                        return;
                    }

                    const list = Array.isArray(data) ? data : (data.data || []);
                    if (!Array.isArray(list) || list.length === 0) {
                        selKota.innerHTML = '<option value="">(tidak ada data kota)</option>';
                        return;
                    }

                    selKota.innerHTML = '<option value="">-- Pilih Kota --</option>';
                    for (const c of list) {
                        const id = c.id ?? c.city_id ?? '';
                        const name = (c.name ?? c.city_name ?? '').trim();
                        if (!id || !name) continue;
                        const opt = document.createElement('option');
                        opt.value = name; // ke PHP: NAMA
                        opt.dataset.id = id; // buat load district
                        opt.textContent = name + (c.zip_code && c.zip_code !== '0' ? ` (${c.zip_code})` : '');
                        selKota.appendChild(opt);
                    }
                    selKota.disabled = false;

                    if (autoSelect && oldKota) {
                        selectByText(selKota, oldKota);
                        const opt = selKota.options[selKota.selectedIndex];
                        if (opt && opt.dataset.id) {
                            await loadDistricts(opt.dataset.id, true);
                        }
                    }

                } catch (e) {
                    console.error(e);
                    selKota.innerHTML = '<option value="">Error load kota</option>';
                }
            }

            async function loadDistricts(cityId, autoSelect = false) {
                if (!cityId || !selKec) {
                    selKec.innerHTML = '<option value="">-- Pilih Kota Dahulu --</option>';
                    selKec.disabled = true;
                    if (selKel) {
                        selKel.innerHTML = '<option value="">-- Pilih Kecamatan Dahulu --</option>';
                        selKel.disabled = true;
                    }
                    return;
                }

                selKec.innerHTML = '<option value="">Loading…</option>';
                selKec.disabled = true;

                if (selKel) {
                    selKel.innerHTML = '<option value="">-- Pilih Kecamatan Dahulu --</option>';
                    selKel.disabled = true;
                }

                try {
                    const res = await fetch('../../user/rajaongkir/get-district.php?city=' + encodeURIComponent(cityId), {
                        cache: 'no-store'
                    });
                    const text = await res.text();
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        console.error('District JSON error:', e, text);
                        selKec.innerHTML = '<option value="">Gagal parsing data kecamatan</option>';
                        return;
                    }

                    const list = Array.isArray(data) ? data : (data.data || []);
                    if (!Array.isArray(list) || list.length === 0) {
                        selKec.innerHTML = '<option value="">(tidak ada data kecamatan)</option>';
                        return;
                    }

                    selKec.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
                    for (const d of list) {
                        const id = d.subdistrict_id ?? d.id ?? d.district_id ?? '';
                        const name = (d.subdistrict_name ?? d.name ?? d.district_name ?? '').trim();
                        if (!id || !name) continue;
                        const opt = document.createElement('option');
                        opt.value = name;   // ke PHP: NAMA
                        opt.dataset.id = id; // buat load subdistrict
                        opt.textContent = name;
                        selKec.appendChild(opt);
                    }
                    selKec.disabled = false;

                    if (autoSelect && oldKec) {
                        selectByText(selKec, oldKec);
                        const opt = selKec.options[selKec.selectedIndex];
                        if (opt && opt.dataset.id) {
                            await loadSubDistricts(opt.dataset.id, true);
                        }
                    }

                } catch (e) {
                    console.error(e);
                    selKec.innerHTML = '<option value="">Error load kecamatan</option>';
                }
            }

            async function loadSubDistricts(districtId, autoSelect = false) {
                if (!selKel) return;

                if (!districtId) {
                    selKel.innerHTML = '<option value="">-- Pilih Kecamatan Dahulu --</option>';
                    selKel.disabled = true;
                    return;
                }

                selKel.innerHTML = '<option value="">Loading…</option>';
                selKel.disabled = true;

                try {
                    const res = await fetch('../../user/rajaongkir/get-subdistrict.php?district=' +
                        encodeURIComponent(districtId) + '&t=' + Date.now(), {
                        cache: 'no-store'
                    });
                    const text = await res.text();
                    let root;
                    try {
                        root = JSON.parse(text);
                    } catch (e) {
                        console.error('Subdistrict JSON error:', e, text);
                        selKel.innerHTML = '<option value="">Gagal parsing data kelurahan</option>';
                        return;
                    }

                    if (!root.success) {
                        console.error('Subdistrict API error:', root.message);
                        selKel.innerHTML = '<option value="">Gagal load kelurahan</option>';
                        return;
                    }

                    const list = Array.isArray(root.data) ? root.data : [];
                    if (!list.length) {
                        selKel.innerHTML = '<option value="">(tidak ada kelurahan)</option>';
                        selKel.disabled = false;
                        return;
                    }

                    selKel.innerHTML = '<option value="">-- Pilih Kelurahan --</option>';
                    for (const s of list) {
                        const id = s.subdistrict_id ?? s.id ?? '';
                        const name = (s.subdistrict_name ?? s.name ?? '').trim();
                        if (!id || !name) continue;
                        const opt = document.createElement('option');
                        opt.value = name;      // ke PHP: NAMA
                        opt.dataset.id = id;   // kalau mau dipakai lagi
                        opt.textContent = name + (s.zip_code && s.zip_code !== '0' ? ` (${s.zip_code})` : '');
                        selKel.appendChild(opt);
                    }
                    selKel.disabled = false;

                    if (autoSelect && oldKel) {
                        selectByText(selKel, oldKel);
                    }

                } catch (e) {
                    console.error('Subdistrict fetch error:', e);
                    selKel.innerHTML = '<option value="">Error load kelurahan</option>';
                    selKel.disabled = true;
                }
            }

            // Event handlers
            selProv.addEventListener('change', () => {
                const opt = selProv.options[selProv.selectedIndex];
                const provId = opt && opt.dataset.id ? opt.dataset.id : '';

                selKota.innerHTML = '<option value="">-- Pilih Provinsi Dahulu --</option>';
                selKota.disabled = true;

                selKec.innerHTML = '<option value="">-- Pilih Kota Dahulu --</option>';
                selKec.disabled = true;

                selKel.innerHTML = '<option value="">-- Pilih Kecamatan Dahulu --</option>';
                selKel.disabled = true;

                loadCities(provId, false);
            });

            selKota.addEventListener('change', () => {
                const opt = selKota.options[selKota.selectedIndex];
                const cityId = opt && opt.dataset.id ? opt.dataset.id : '';

                selKec.innerHTML = '<option value="">-- Pilih Kota Dahulu --</option>';
                selKec.disabled = true;

                selKel.innerHTML = '<option value="">-- Pilih Kecamatan Dahulu --</option>';
                selKel.disabled = true;

                loadDistricts(cityId, false);
            });

            selKec.addEventListener('change', () => {
                const opt = selKec.options[selKec.selectedIndex];
                const districtId = opt && opt.dataset.id ? opt.dataset.id : '';

                selKel.innerHTML = '<option value="">-- Pilih Kecamatan Dahulu --</option>';
                selKel.disabled = true;

                loadSubDistricts(districtId, false);
            });

            // Init
            loadProvinces();
        });
    </script>
</body>

</html>
