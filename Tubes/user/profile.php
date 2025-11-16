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

    // RajaOngkir ID (bukan nama)
    $provinsi_id   = trim($_POST['provinsi_id'] ?? '');
    $kota_id       = trim($_POST['kota_id'] ?? '');
    $kecamatan_id  = trim($_POST['kecamatan_id'] ?? '');

    if (
        $nama === '' || $telepon === '' || $alamat === '' ||
        $provinsi_id === '' || $kota_id === '' || $kecamatan_id === ''
    ) {
        $_SESSION['profile_error'] = 'Lengkapi semua data profil dan alamat (provinsi/kota/kecamatan).';
        header('Location: profile.php');
        exit();
    }

    // pastikan ID-nya angka
    if (
        !preg_match('/^\d+$/', $provinsi_id) ||
        !preg_match('/^\d+$/', $kota_id) ||
        !preg_match('/^\d+$/', $kecamatan_id)
    ) {
        $_SESSION['profile_error'] = 'ID provinsi/kota/kecamatan harus berupa angka yang valid.';
        header('Location: profile.php');
        exit();
    }

    // ⬇⬇⬇ DI SINI YANG PENTING: pakai no_telepon, bukan telepon
    $sql = "UPDATE customer 
            SET nama = ?, 
                no_telepon = ?, 
                alamat = ?, 
                provinsi = ?,   -- simpan province_id
                kota = ?,       -- simpan city_id
                kecamatan = ?   -- simpan subdistrict_id
            WHERE customer_id = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $_SESSION['profile_error'] = 'Gagal prepare statement: ' . $conn->error;
        header('Location: profile.php');
        exit();
    }

    $stmt->bind_param(
        "ssssssi",
        $nama,
        $telepon,
        $alamat,
        $provinsi_id,
        $kota_id,
        $kecamatan_id,
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
$provinsi_id = $kota_id = $kecamatan_id = '';

$query = "SELECT nama, no_telepon, alamat, provinsi, kota, kecamatan 
          FROM customer 
          WHERE customer_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $nama         = $row['nama'] ?? '';
    $telepon      = $row['no_telepon'] ?? '';
    $alamat       = $row['alamat'] ?? '';
    $provinsi_id  = $row['provinsi'] ?? '';
    $kota_id      = $row['kota'] ?? '';
    $kecamatan_id = $row['kecamatan'] ?? '';
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
                <label for="alamat" class="form-label">Alamat Lengkap</label>
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

            <button type="submit" class="btn btn-primary">Simpan Profil</button>
        </form>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        // ====== DATA ID DARI PHP (untuk preselect) ======
        const currentProvId = <?= json_encode($provinsi_id) ?>;
        const currentCityId = <?= json_encode($kota_id) ?>;
        const currentDistId = <?= json_encode($kecamatan_id) ?>;

        const provSel = document.getElementById('provinsi');
        const kotaSel = document.getElementById('kota');
        const kecSel = document.getElementById('kecamatan');

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

                const list = Array.isArray(data) ? data : (data.data || []);
                const options = list.map(p => {
                    const id = String(p.province_id ?? p.id ?? p.provinceId ?? '');
                    const name = String(p.province ?? p.name ?? p.provinceName ?? '');
                    if (!id || !name) return '';
                    return `<option value="${id}">${name}</option>`;
                }).join('');

                provSel.innerHTML = '<option value="">-- Pilih Provinsi --</option>' + options;

                if (currentProvId) {
                    provSel.value = currentProvId;
                    if (provSel.value === currentProvId) {
                        await loadCities(currentProvId, true);
                    }
                }
            } catch (err) {
                console.error('Province fetch error:', err);
                provSel.innerHTML = '<option value="">Gagal load provinsi</option>';
            }
        }

        // ====== LOAD CITIES ======
        async function loadCities(provId, autoSelect = false) {
            if (!kotaSel) return;
            kotaSel.innerHTML = '<option value="">Loading...</option>';
            kotaSel.disabled = true;
            kecSel.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
            kecSel.disabled = true;

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

                const list = Array.isArray(data) ? data : (data.data || []);
                const options = list.map(c => {
                    const id = String(c.city_id ?? c.id ?? c.cityId ?? '');
                    const name = String(c.city_name ?? c.name ?? c.cityName ?? '').trim();
                    if (!id || !name) return '';
                    return `<option value="${id}">${name}</option>`;
                }).join('');

                kotaSel.innerHTML = '<option value="">-- Pilih Kota --</option>' + options;
                kotaSel.disabled = false;

                if (autoSelect && currentCityId) {
                    kotaSel.value = currentCityId;
                    if (kotaSel.value === currentCityId) {
                        await loadDistricts(currentCityId, true);
                    }
                }

            } catch (err) {
                console.error('City fetch error:', err);
                kotaSel.innerHTML = '<option value="">Gagal load kota</option>';
                kotaSel.disabled = true;
            }
        }

        // ====== LOAD DISTRICTS ======
        async function loadDistricts(cityId, autoSelect = false) {
            if (!kecSel) return;
            kecSel.innerHTML = '<option value="">Loading...</option>';
            kecSel.disabled = true;

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

                const list = Array.isArray(data) ? data : (data.data || []);
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
                    return `<option value="${id}">${name}</option>`;
                }).join('');

                if (!options) {
                    kecSel.innerHTML = '<option value="">(Tidak ada kecamatan)</option>';
                    return;
                }

                kecSel.innerHTML = '<option value="">-- Pilih Kecamatan --</option>' + options;
                kecSel.disabled = false;

                if (autoSelect && currentDistId) {
                    kecSel.value = currentDistId;
                }

            } catch (err) {
                console.error('District fetch error:', err);
                kecSel.innerHTML = '<option value="">Gagal load kecamatan</option>';
                kecSel.disabled = true;
            }
        }

        // ====== EVENT HANDLER ======
        document.addEventListener('change', (e) => {
            const t = e.target;
            if (t === provSel) {
                const val = provSel.value;
                kecSel.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
                kecSel.disabled = true;
                loadCities(val, false);
            }
            if (t === kotaSel) {
                const val = kotaSel.value;
                loadDistricts(val, false);
            }
        });

        // Init
        loadProvinces().catch(console.error);
    </script>

</body>

</html>