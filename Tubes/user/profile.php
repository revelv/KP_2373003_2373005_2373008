<?php
include 'header.php';

// Redirect ke login jika belum login
if (!isset($_SESSION['kd_cs'])) {
    header("Location: produk.php");
    exit();
}

$customer_id = (int)$_SESSION['kd_cs'];


if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf'];

$query = "SELECT customer_id, email, nama, no_telepon, alamat,
                 COALESCE(provinsi,'') AS provinsi,
                 COALESCE(kota,'') AS kota
          FROM customer WHERE customer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("Customer not found.");
}
$customer = $result->fetch_assoc();
$stmt->close();

// Proses update data
$update_success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf'], $_POST['csrf_token'])) {
        $errors[] = "Invalid CSRF token.";
    }

    $nama       = trim($_POST['nama'] ?? '');
    $no_telepon = trim($_POST['no_telepon'] ?? '');
    $alamat     = trim($_POST['alamat'] ?? '');
    $provinsi   = trim($_POST['provinsi'] ?? '');
    $kota       = trim($_POST['kota'] ?? '');

    // Validasi server-side
    if ($nama === '')        $errors[] = "Full name is required";
    if ($no_telepon === '')  $errors[] = "Phone number is required";
    elseif (!preg_match('/^[0-9]{10,15}$/', $no_telepon)) $errors[] = "Invalid phone number format";
    if ($alamat === '')      $errors[] = "Address is required";
    if ($provinsi === '')    $errors[] = "Province is required";
    if ($kota === '')        $errors[] = "City is required";
    if ($provinsi !== '' && !preg_match('/^\d+$/', $provinsi)) $errors[] = "Invalid province value";
    if ($kota !== '' && !preg_match('/^\d+$/', $kota))         $errors[] = "Invalid city value";

    if (empty($errors)) {
        $update_query = "UPDATE customer
                         SET nama = ?, no_telepon = ?, alamat = ?, provinsi = ?, kota = ?
                         WHERE customer_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("sssssi", $nama, $no_telepon, $alamat, $provinsi, $kota, $customer_id);
        if ($update_stmt->execute()) {
            $update_success = true;
            $_SESSION['nama']    = $nama;
            $customer['nama']     = $nama;
            $customer['no_telepon'] = $no_telepon;
            $customer['alamat']   = $alamat;
            $customer['provinsi'] = $provinsi;
            $customer['kota']     = $kota;
        } else {
            $errors[] = "Failed to update profile. Please try again.";
        }
        $update_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Profile - Styrk Industries</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: var(--black);
        }

        .profile-container {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, .1);
            padding: 30px;
            margin-bottom: 30px;
        }

        .profile-title {
            font-size: 28px;
            font-weight: 600;
            color: var(--primary-blue);
            margin: 0;
        }

        .btn-save {
            background-color: var(--primary-yellow);
            color: #000;
            font-weight: 700;
        }

        .btn-save:hover {
            background: #e67e22;
            color: #fff;
        }

        .btn-cancel {
            background: red;
            color: #fff;
            font-weight: 700;
            margin-right: 10px;
        }

        .btn-cancel:hover {
            box-shadow: 0 0 10px rgba(231, 76, 60, .6);
            transform: scale(1.02);
        }

        .form-control:disabled {
            background: #f8f9fa;
            opacity: 1;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
    </style>
</head>

<body>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="profile-container">
                    <div class="profile-header d-flex justify-content-between align-items-center mb-4">
                        <h1 class="profile-title">Edit Profile</h1>
                        <a href="produk.php" class="btn btn-cancel"><i class="bi bi-arrow-left me-2"></i>Back</a>
                    </div>

                    <?php if ($update_success): ?>
                        <div class="alert alert-success mb-4" role="alert" aria-live="polite">
                            <i class="bi bi-check-circle-fill me-2"></i>Profile updated successfully!
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger mb-4" role="alert" aria-live="polite">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?php foreach ($errors as $error): ?>
                                <div><?= htmlspecialchars($error) ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" autocomplete="on" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                        <div class="mb-4">
                            <label for="customer_id" class="form-label">Customer ID</label>
                            <input type="text" class="form-control" id="customer_id" value="<?= htmlspecialchars($customer['customer_id']) ?>" disabled>
                        </div>

                        <div class="mb-4">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" value="<?= htmlspecialchars($customer['email']) ?>" disabled>
                        </div>

                        <div class="mb-4">
                            <label for="nama" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama" name="nama" value="<?= htmlspecialchars($customer['nama']) ?>" required autocomplete="name">
                        </div>

                        <div class="mb-4">
                            <label for="no_telepon" class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="no_telepon" name="no_telepon"
                                value="<?= htmlspecialchars($customer['no_telepon']) ?>" required
                                inputmode="tel" autocomplete="tel" pattern="^[0-9]{10,15}$"
                                title="Please enter 10-15 digits (e.g., 081234567890)">
                            <small class="text-muted">Format: 10-15 digits (e.g., 081234567890)</small>
                        </div>

                        <div class="mb-4">
                            <label for="alamat" class="form-label">Address <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="alamat" name="alamat" rows="3" required autocomplete="street-address"><?= htmlspecialchars($customer['alamat']) ?></textarea>
                        </div>

                        <!-- Provinsi (DB: provinsi) -->
                        <div class="mb-4">
                            <label for="provinsi" class="form-label">Province <span class="text-danger">*</span></label>
                            <select id="provinsi" name="provinsi" class="form-control" required
                                data-old="<?= htmlspecialchars($customer['provinsi']) ?>">
                                <option value="">-- loading provinces… --</option>
                            </select>
                            <small class="text-muted">Pilih provinsi domisili.</small>
                        </div>

                        <!-- Kota (DB: kota) -->
                        <div class="mb-4">
                            <label for="kota" class="form-label">City <span class="text-danger">*</span></label>
                            <select id="kota" name="kota" class="form-control" required disabled
                                data-old="<?= htmlspecialchars($customer['kota']) ?>">
                                <option value="">-- pilih provinsi dulu --</option>
                            </select>
                            <small class="text-muted">Pilih kota/kabupaten sesuai provinsi.</small>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-save"><i class="bi bi-save me-2"></i>Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // --- Client validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const phoneInput = document.getElementById('no_telepon');
            const phoneRegex = /^[0-9]{10,15}$/;
            if (!phoneRegex.test(phoneInput.value)) {
                e.preventDefault();
                alert('Please enter a valid phone number (10-15 digits)');
                phoneInput.focus();
                return;
            }
            const prov = document.getElementById('provinsi');
            const city = document.getElementById('kota');
            if (!prov.value) {
                e.preventDefault();
                alert('Please select a province');
                prov.focus();
                return;
            }
            if (!city.value) {
                e.preventDefault();
                alert('Please select a city');
                city.focus();
                return;
            }
        });

        //Dynamic Provinsi & Kota
        document.addEventListener('DOMContentLoaded', () => {
            const selProv = document.getElementById('provinsi');
            const selCity = document.getElementById('kota');

            async function fetchJSON(url) {
                const res = await fetch(url, {
                    cache: 'no-store'
                });
                if (!res.ok) throw new Error(`HTTP ${res.status} ${res.statusText}`);
                const ct = res.headers.get('content-type') || '';
                if (ct.includes('application/json')) {
                    return await res.json();
                }
                const clone = res.clone();
                const txt = await clone.text();
                try {
                    return JSON.parse(txt);
                } catch (e) {
                    console.error('Invalid JSON from', url, '->', txt);
                    throw new Error('Invalid JSON from server');
                }
            }

            function getCachedProvinces() {
                try {
                    const raw = localStorage.getItem('prov_cache');
                    if (!raw) return null;
                    const obj = JSON.parse(raw);
                    if (!obj || !obj.expires || Date.now() > obj.expires) return null;
                    return obj.data;
                } catch {
                    return null;
                }
            }

            function setCachedProvinces(list) {
                try {
                    localStorage.setItem('prov_cache', JSON.stringify({
                        data: list,
                        expires: Date.now() + 24 * 60 * 60 * 1000
                    }));
                } catch {}
            }

            function normalizeProvinceList(payload) {
                const list = Array.isArray(payload) ? payload : (payload?.data ?? []);
                return list.map(p => {
                    const id = p.id ?? p.province_id ?? '';
                    const name = p.name ?? p.province ?? '';
                    return (id && name) ? {
                        id: String(id),
                        name: String(name)
                    } : null;
                }).filter(Boolean);
            }

            function normalizeCityList(payload) {
                const list = Array.isArray(payload) ? payload : (payload?.data ?? []);
                return list.map(c => {
                    const id = c.id ?? c.city_id ?? '';
                    const name = c.name ?? c.city_name ?? '';
                    const zip = c.zip_code ?? '';
                    const label = name + ((zip && zip !== '0') ? ` (${zip})` : '');
                    return (id && name) ? {
                        id: String(id),
                        name: String(name),
                        label
                    } : null;
                }).filter(Boolean);
            }

            function fillSelect(select, items, placeholder, useLabel = false) {
                select.innerHTML = '';
                const opt0 = document.createElement('option');
                opt0.value = '';
                opt0.textContent = placeholder;
                select.appendChild(opt0);

                for (const it of items) {
                    const opt = document.createElement('option');
                    opt.value = it.id;
                    opt.textContent = useLabel ? (it.label || it.name) : it.name;
                    select.appendChild(opt);
                }
            }

            function restoreOld(selectEl) {
                const oldVal = (selectEl.getAttribute('data-old') || '').trim();
                if (oldVal) selectEl.value = oldVal;
            }

            async function loadProvinces() {
                try {
                    fillSelect(selProv, [], '(loading…)');
                } catch {}
                const cached = getCachedProvinces();
                if (cached && Array.isArray(cached) && cached.length) {
                    fillSelect(selProv, cached, '-- pilih provinsi --');
                    restoreOld(selProv);
                    if (selProv.value) {
                        await loadCities(selProv.value);
                    }
                    return;
                }

                try {
                    const data = await fetchJSON('./rajaongkir/get-province.php');
                    const list = normalizeProvinceList(data);
                    if (!list.length) {
                        selProv.innerHTML = `<option value="">Tidak ada data provinsi</option>`;
                        return;
                    }
                    fillSelect(selProv, list, '-- pilih provinsi --');
                    setCachedProvinces(list);
                    restoreOld(selProv);
                    if (selProv.value) {
                        await loadCities(selProv.value);
                    }
                } catch (e) {
                    console.error('Prov fetch error:', e);
                    selProv.innerHTML = `<option value="">Gagal memuat provinsi: ${e.message}</option>`;
                }
            }

            async function loadCities(provinceId) {
                selCity.disabled = true;
                try {
                    fillSelect(selCity, [], '(loading…)');
                } catch {}

                try {
                    const data = await fetchJSON('./rajaongkir/get-cities.php?province=' + encodeURIComponent(provinceId));
                    const list = normalizeCityList(data);
                    if (!list.length) {
                        selCity.innerHTML = `<option value="">(tidak ada data)</option>`;
                        return;
                    }
                    fillSelect(selCity, list, '-- pilih kota --', true);
                    selCity.disabled = false;
                    restoreOld(selCity);
                } catch (e) {
                    console.error('City fetch error:', e);
                    selCity.innerHTML = `<option value="">Error: ${e.message}</option>`;
                }
            }

            selProv.addEventListener('change', () => {
                const pid = selProv.value;
                if (!pid) {
                    selCity.disabled = true;
                    selCity.innerHTML = `<option value="">-- pilih provinsi dulu --</option>`;
                    return;
                }
                loadCities(pid);
            });

            loadProvinces();
        });
    </script>
</body>

</html>