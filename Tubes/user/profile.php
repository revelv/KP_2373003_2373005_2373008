<?php

declare(strict_types=1);

// ====== START: LOGIC TANPA OUTPUT ======
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../koneksi.php';

// Cek login
if (!isset($_SESSION['kd_cs'])) {
    header("Location: produk.php");
    exit();
}

$customer_id = (int)$_SESSION['kd_cs'];

// ====== HANDLE UPDATE PROFIL (POST) ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama      = trim($_POST['nama'] ?? '');
    $telepon   = trim($_POST['telepon'] ?? '');
    $alamat    = trim($_POST['alamat'] ?? '');

    // DI SINI: yang dikirim dari form adalah NAMA (value option), bukan ID
    $provinsi  = trim($_POST['provinsi_id']   ?? '');
    $kota      = trim($_POST['kota_id']       ?? '');
    $kecamatan = trim($_POST['kecamatan_id']  ?? '');
    $kelurahan = trim($_POST['kelurahan_id']  ?? '');

    if (
        $nama === '' || $telepon === '' || $alamat === '' ||
        $provinsi === '' || $kota === '' || $kecamatan === '' || $kelurahan === ''
    ) {
        $_SESSION['profile_error'] = 'Lengkapi semua data profil dan alamat (provinsi / kota / kecamatan / kelurahan).';
        header('Location: profile.php');
        exit();
    }

    // Simpan NAMA provinsi, kota, kecamatan, kelurahan ke DB
    $sql = "UPDATE customer 
            SET nama = ?, 
                no_telepon = ?, 
                alamat = ?, 
                provinsi = ?,   -- simpan NAMA provinsi
                kota = ?,       -- simpan NAMA kota
                kecamatan = ?,  -- simpan NAMA kecamatan
                kelurahan = ?   -- simpan NAMA kelurahan
            WHERE customer_id = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $_SESSION['profile_error'] = 'Gagal prepare statement: ' . $conn->error;
        header('Location: profile.php');
        exit();
    }

    $stmt->bind_param(
        "sssssssi",
        $nama,
        $telepon,
        $alamat,
        $provinsi,
        $kota,
        $kecamatan,
        $kelurahan,
        $customer_id
    );

    if ($stmt->execute()) {
        $_SESSION['profile_success'] = 'Profil berhasil diperbarui.';
    } else {
        $_SESSION['profile_error'] = 'Gagal update profil: ' . $stmt->error;
    }
    $stmt->close();

    header('Location: profile.php');
    exit();
}


// ====== AMBIL DATA CUSTOMER UNTUK PREFILL FORM (GET) ======
$nama = $telepon = $alamat = '';
$provinsi_name = $kota_name = $kecamatan_name = $kelurahan_name = '';

$query = "SELECT nama, no_telepon, alamat, provinsi, kota, kecamatan, kelurahan
          FROM customer 
          WHERE customer_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $nama            = $row['nama'] ?? '';
    $telepon         = $row['no_telepon'] ?? '';
    $alamat          = $row['alamat'] ?? '';
    $provinsi_name   = $row['provinsi'] ?? '';   // NAMA provinsi
    $kota_name       = $row['kota'] ?? '';       // NAMA kota
    $kecamatan_name  = $row['kecamatan'] ?? '';  // NAMA kecamatan
    $kelurahan_name  = $row['kelurahan'] ?? '';  // NAMA kelurahan
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Profil Saya - STYRK INDUSTRIES</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap / CSS lain kalau perlu -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
        }
    </style>
</head>

<body>

    <?php include 'header.php'; ?>

    <div class="container" style="max-width: 700px; margin-top: 30px; margin-bottom: 30px;">
        <h2 class="mb-4">Profil Saya</h2>

        <?php if (!empty($_SESSION['profile_error'])): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($_SESSION['profile_error']) ?>
            </div>
            <?php unset($_SESSION['profile_error']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['profile_success'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['profile_success']) ?>
            </div>
            <?php unset($_SESSION['profile_success']); ?>
        <?php endif; ?>

        <form method="post" action="profile.php">
            <div class="mb-3">
                <label for="nama" class="form-label">Nama Lengkap</label>
                <input type="text" class="form-control" id="nama" name="nama"
                    value="<?= htmlspecialchars($nama) ?>" required>
            </div>

            <div class="mb-3">
                <label for="telepon" class="form-label">No. Telepon</label>
                <input type="text" class="form-control" id="telepon" name="telepon"
                    value="<?= htmlspecialchars($telepon) ?>" required>
            </div>

            <div class="mb-3">
                <label for="alamat" class="form-label">Alamat Lengkap (Detail Jalan, RT/RW, No Rumah, dll)</label>
                <textarea class="form-control" id="alamat" name="alamat" rows="3" required><?= htmlspecialchars($alamat) ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Provinsi</label>
                <select id="provinsi" name="provinsi_id" class="form-select" required>
                    <option value="">-- Pilih Provinsi --</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Kota / Kabupaten</label>
                <select id="kota" name="kota_id" class="form-select" required disabled>
                    <option value="">-- Pilih Kota --</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Kecamatan</label>
                <select id="kecamatan" name="kecamatan_id" class="form-select" required disabled>
                    <option value="">-- Pilih Kecamatan --</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Kelurahan / Desa (Sub-district)</label>
                <select id="kelurahan" name="kelurahan_id" class="form-select" required disabled>
                    <option value="">-- Pilih Kelurahan --</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Simpan Profil</button>
        </form>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        // Yang disimpan di DB: NAMA, jadi ke JS juga bawa NAMA
        const currentProvName = <?= json_encode($provinsi_name) ?>;
        const currentCityName = <?= json_encode($kota_name) ?>;
        const currentDistName = <?= json_encode($kecamatan_name) ?>;
        const currentSubName = <?= json_encode($kelurahan_name) ?>;

        const provSel = document.getElementById('provinsi');
        const kotaSel = document.getElementById('kota');
        const kecSel = document.getElementById('kecamatan');
        const kelSel = document.getElementById('kelurahan');

        // Helper: select option berdasarkan TEXT (nama), bukan value mentah
        function selectByText(selectEl, text) {
            if (!selectEl || !text) return;
            const options = Array.from(selectEl.options);
            const found = options.find(o => o.text.trim().toUpperCase() === text.trim().toUpperCase());
            if (found) {
                selectEl.value = found.value;
            }
        }

        // ====== LOAD PROVINCE ======
        async function loadProvinces() {
            if (!provSel) return;
            provSel.innerHTML = '<option value="">Loading...</option>';
            try {
                const res = await fetch('./rajaongkir/get-province.php?t=' + Date.now());
                const txt = await res.text();
                let data;
                try {
                    data = JSON.parse(txt);
                } catch (e) {
                    console.error('Province JSON parse error:', e, txt);
                    provSel.innerHTML = '<option value="">Gagal load provinsi</option>';
                    return;
                }

                const list = Array.isArray(data) ?
                    data :
                    (Array.isArray(data.data) ? data.data : (data.rajaongkir?.results || []));

                const options = list.map(p => {
                    const id = String(p.province_id ?? p.id ?? p.provinceId ?? '');
                    const name = String(p.province ?? p.name ?? p.provinceName ?? '');
                    if (!id || !name) return '';
                    // value = NAMA, data-id = ID buat hit API lanjut
                    return `<option value="${name}" data-id="${id}">${name}</option>`;
                }).join('');

                provSel.innerHTML = '<option value="">-- Pilih Provinsi --</option>' + options;

                // Prefill kalau di DB sudah ada nama provinsi
                if (currentProvName) {
                    selectByText(provSel, currentProvName);
                    const opt = provSel.options[provSel.selectedIndex];
                    if (opt && opt.dataset.id) {
                        await loadCities(opt.dataset.id, true);
                    }
                }
            } catch (err) {
                console.error('Province fetch error:', err);
                provSel.innerHTML = '<option value="">Gagal load provinsi</option>';
            }
        }

        // ====== LOAD CITIES (berdasarkan province_id) ======
        async function loadCities(provId, autoSelect = false) {
            if (!kotaSel) return;
            kotaSel.innerHTML = '<option value="">Loading...</option>';
            kotaSel.disabled = true;

            // Reset kecamatan & kelurahan
            if (kecSel) {
                kecSel.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
                kecSel.disabled = true;
            }
            if (kelSel) {
                kelSel.innerHTML = '<option value="">-- Pilih Kelurahan --</option>';
                kelSel.disabled = true;
            }

            if (!provId) {
                kotaSel.innerHTML = '<option value="">-- Pilih Kota --</option>';
                return;
            }

            try {
                const res = await fetch('./rajaongkir/get-cities.php?province=' + encodeURIComponent(provId) + '&t=' + Date.now());
                const txt = await res.text();
                let data;
                try {
                    data = JSON.parse(txt);
                } catch (e) {
                    console.error('City JSON parse error:', e, txt);
                    kotaSel.innerHTML = '<option value="">Gagal load kota</option>';
                    return;
                }

                const list = Array.isArray(data) ?
                    data :
                    (Array.isArray(data.data) ? data.data : (data.rajaongkir?.results || []));

                const options = list.map(c => {
                    const id = String(c.city_id ?? c.id ?? c.cityId ?? '');
                    const name = String(c.city_name ?? c.name ?? c.cityName ?? '').trim();
                    if (!id || !name) return '';
                    return `<option value="${name}" data-id="${id}">${name}</option>`;
                }).join('');

                kotaSel.innerHTML = '<option value="">-- Pilih Kota --</option>' + options;
                kotaSel.disabled = false;

                if (autoSelect && currentCityName) {
                    selectByText(kotaSel, currentCityName);
                    const opt = kotaSel.options[kotaSel.selectedIndex];
                    if (opt && opt.dataset.id) {
                        await loadDistricts(opt.dataset.id, true);
                    }
                }

            } catch (err) {
                console.error('City fetch error:', err);
                kotaSel.innerHTML = '<option value="">Gagal load kota</option>';
                kotaSel.disabled = true;
            }
        }

        // ====== LOAD DISTRICTS (kecamatan) BERDASARKAN city_id ======
        async function loadDistricts(cityId, autoSelect = false) {
            if (!kecSel) return;
            kecSel.innerHTML = '<option value="">Loading...</option>';
            kecSel.disabled = true;

            if (kelSel) {
                kelSel.innerHTML = '<option value="">-- Pilih Kelurahan --</option>';
                kelSel.disabled = true;
            }

            if (!cityId) {
                kecSel.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
                return;
            }

            try {
                const res = await fetch('./rajaongkir/get-district.php?city=' + encodeURIComponent(cityId) + '&t=' + Date.now());
                const txt = await res.text();
                console.log('DISTRICT RAW (profile):', txt);
                let data;
                try {
                    data = JSON.parse(txt);
                } catch (e) {
                    console.error('District JSON parse error:', e, txt);
                    kecSel.innerHTML = '<option value="">Gagal load kecamatan</option>';
                    return;
                }

                const list = Array.isArray(data) ?
                    data :
                    (Array.isArray(data.data) ? data.data : (data.rajaongkir?.results || []));

                const options = list.map(d => {
                    const id = String(
                        d.subdistrict_id ??
                        d.id ??
                        d.district_id ??
                        ''
                    );
                    const name = String(
                        d.subdistrict_name ??
                        d.name ??
                        d.district_name ??
                        ''
                    ).trim();
                    if (!id || !name) return '';
                    return `<option value="${name}" data-id="${id}">${name}</option>`;
                }).join('');

                if (!options) {
                    kecSel.innerHTML = '<option value="">(Tidak ada kecamatan)</option>';
                    return;
                }

                kecSel.innerHTML = '<option value="">-- Pilih Kecamatan --</option>' + options;
                kecSel.disabled = false;

                if (autoSelect && currentDistName) {
                    selectByText(kecSel, currentDistName);
                    const opt = kecSel.options[kecSel.selectedIndex];
                    if (opt && opt.dataset.id) {
                        await loadSubDistricts(opt.dataset.id, true);
                    }
                }

            } catch (err) {
                console.error('District fetch error:', err);
                kecSel.innerHTML = '<option value="">Gagal load kecamatan</option>';
                kecSel.disabled = true;
            }
        }

        // ====== LOAD SUB-DISTRICTS (kelurahan) BERDASARKAN district_id ======
        async function loadSubDistricts(districtId, autoSelect = false) {
            if (!kelSel) return;
            kelSel.innerHTML = '<option value="">Loading...</option>';
            kelSel.disabled = true;

            if (!districtId) {
                kelSel.innerHTML = '<option value="">-- Pilih Kelurahan --</option>';
                kelSel.disabled = true;
                return;
            }

            try {
                const res = await fetch(
                    './rajaongkir/get-subdistrict.php?district=' +
                    encodeURIComponent(districtId) +
                    '&t=' + Date.now()
                );

                const txt = await res.text();
                console.log('SUB-DISTRICT RAW (profile):', txt);

                let parsed;
                try {
                    // --- FIX PENTING: buang noise sebelum JSON ---
                    let cleaned = txt.trim();

                    // Cari posisi awal { atau [
                    const bracePos = cleaned.indexOf('{');
                    const bracketPos = cleaned.indexOf('[');
                    let start = -1;

                    if (bracePos === -1 && bracketPos === -1) {
                        throw new Error('Tidak ditemukan awal JSON ({ atau [).');
                    } else if (bracePos === -1) {
                        start = bracketPos;
                    } else if (bracketPos === -1) {
                        start = bracePos;
                    } else {
                        start = Math.min(bracePos, bracketPos);
                    }

                    cleaned = cleaned.slice(start);

                    parsed = JSON.parse(cleaned);
                } catch (e) {
                    console.error('Subdistrict JSON parse error:', e);
                    kelSel.innerHTML = '<option value="">Gagal load kelurahan (JSON)</option>';
                    kelSel.disabled = true;
                    return;
                }

                // ====== NORMALISASI BENTUK RESPON ======
                let list = [];

                if (Array.isArray(parsed)) {
                    list = parsed;
                } else if (Array.isArray(parsed.data)) {
                    list = parsed.data;
                } else if (parsed.rajaongkir && Array.isArray(parsed.rajaongkir.results)) {
                    list = parsed.rajaongkir.results;
                } else if (parsed.success && Array.isArray(parsed.data)) {
                    list = parsed.data;
                } else {
                    console.error('Subdistrict shape tidak dikenal:', parsed);
                    kelSel.innerHTML = '<option value="">Gagal load kelurahan (shape)</option>';
                    kelSel.disabled = true;
                    return;
                }

                if (!list.length) {
                    kelSel.innerHTML = '<option value="">(Tidak ada kelurahan)</option>';
                    kelSel.disabled = false;
                    return;
                }

                const options = list.map(s => {
                    const id = String(
                        s.subdistrict_id ??
                        s.id ??
                        s.district_id ??
                        ''
                    );
                    const name = String(
                        s.subdistrict_name ??
                        s.name ??
                        s.district_name ??
                        ''
                    ).trim();
                    if (!id || !name) return '';
                    // value = NAMA kelurahan, data-id = ID-nya
                    return `<option value="${name}" data-id="${id}">${name}</option>`;
                }).join('');

                kelSel.innerHTML = '<option value="">-- Pilih Kelurahan --</option>' + options;
                kelSel.disabled = false;

                // auto-select kalau di DB sudah ada nama kelurahan
                if (autoSelect && currentSubName) {
                    selectByText(kelSel, currentSubName);
                }

            } catch (err) {
                console.error('Subdistrict fetch error:', err);
                kelSel.innerHTML = '<option value="">Gagal load kelurahan (fetch)</option>';
                kelSel.disabled = true;
            }
        }




        // ====== EVENT HANDLER ======
        document.addEventListener('change', (e) => {
            const t = e.target;

            // Ganti provinsi → reload kota
            if (t === provSel) {
                const opt = provSel.options[provSel.selectedIndex];
                const provId = opt && opt.dataset.id ? opt.dataset.id : '';

                if (kotaSel) {
                    kotaSel.innerHTML = '<option value="">-- Pilih Kota --</option>';
                    kotaSel.disabled = true;
                }
                if (kecSel) {
                    kecSel.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
                    kecSel.disabled = true;
                }
                if (kelSel) {
                    kelSel.innerHTML = '<option value="">-- Pilih Kelurahan --</option>';
                    kelSel.disabled = true;
                }

                loadCities(provId, false);
            }

            // Ganti kota → reload kecamatan
            if (t === kotaSel) {
                const opt = kotaSel.options[kotaSel.selectedIndex];
                const cityId = opt && opt.dataset.id ? opt.dataset.id : '';

                if (kecSel) {
                    kecSel.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
                    kecSel.disabled = true;
                }
                if (kelSel) {
                    kelSel.innerHTML = '<option value="">-- Pilih Kelurahan --</option>';
                    kelSel.disabled = true;
                }

                loadDistricts(cityId, false);
            }

            // Ganti kecamatan → reload kelurahan
            if (t === kecSel) {
                const opt = kecSel.options[kecSel.selectedIndex];
                const districtId = opt && opt.dataset.id ? opt.dataset.id : '';

                if (kelSel) {
                    kelSel.innerHTML = '<option value="">-- Pilih Kelurahan --</option>';
                    kelSel.disabled = true;
                }

                loadSubDistricts(districtId, false);
            }
        });

        // Init pertama kali
        loadProvinces().catch(console.error);
    </script>

</body>

</html>