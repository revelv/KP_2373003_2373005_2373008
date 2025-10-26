<style>
    /* 1. Biar judul FAQ kelihatan */
    footer h2,
    footer h2.text-center {
        color: #fff !important;
        font-weight: 600;
        text-transform: none;
        letter-spacing: .03em;
        text-shadow: 0 0 12px rgba(0, 0, 0, 0.8);
    }

    /* Matikan garis kuning blur lama di atas judul */
    footer h2.text-center::before,
    footer h2.text-center::after {
        content: none !important;
    }

    /* Wrapper konten footer biar bisa hampir full width */
    footer .footer-inner {
        max-width: 100%;
        padding-left: 1rem;
        padding-right: 1rem;
    }

    /* 2. Rapihin panel accordion */
    footer .accordion {
        max-width: 100%;
        border-radius: 8px;
    }

    footer .accordion-item {
        background-color: #2a2d2f !important;
        border: 1px solid rgba(255, 255, 255, 0.12) !important;
        border-radius: 4px !important;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(0, 0, 0, .6);
        margin-bottom: .75rem;
    }

    footer .accordion-item::before,
    footer .accordion-item::after {
        content: none !important;
    }

    /* tombol pertanyaan */
    footer .accordion-button {
        background-color: #2a2d2f !important;
        color: #f5f5f5 !important;
        font-weight: 500;
        border: 0 !important;
        box-shadow: none !important;
        padding: 1rem 1.25rem;
    }

    /* state kebuka */
    footer .accordion-button:not(.collapsed) {
        background-color: #34383c !important;
        color: #d5b958 !important;
        box-shadow: inset 0 -1px 0 rgba(255, 255, 255, 0.08) !important;
    }

    /* body jawaban */
    footer .accordion-body {
        background-color: #34383c !important;
        color: #dcdcdc !important;
        font-size: .9rem;
        line-height: 1.5rem;
        border-top: 1px solid rgba(255, 255, 255, 0.08) !important;
    }

    /* caret ▼ bootstrap */
    footer .accordion-button::after {
        filter: brightness(0) saturate(100%) invert(90%) sepia(28%) saturate(210%) hue-rotate(7deg) brightness(105%) contrast(97%);
        opacity: 0.9;
    }

    footer .accordion-button:not(.collapsed)::after {
        transform: rotate(-180deg);
    }

    /* copyright text */
    footer .footer-copy {
        color: #aaa;
        font-size: .85rem;
        margin-top: 2rem;
        text-align: center;
    }
</style>

<footer class="bg-dark text-white py-5 mt-5">
    <!-- kita TIDAK pake .container bootstrap lagi -->
    <div class="footer-inner">

        <h2 class="text-center mb-4">Pertanyaan Umum (FAQ)</h2>

        <div class="accordion" id="faqAccordion" data-bs-theme="dark">

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button collapsed" type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapseOne"
                        aria-expanded="false"
                        aria-controls="collapseOne">
                        Bagaimana Cara Melakukan Order?
                    </button>
                </h2>
                <div id="collapseOne"
                    class="accordion-collapse collapse"
                    aria-labelledby="headingOne"
                    data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <strong>1. Pilih Produk:</strong> Jelajahi halaman 'Products' dan klik 'Add to Cart' pada barang yang Anda inginkan.<br>
                        <strong>2. Cek Keranjang:</strong> Masuk ke halaman 'Carts'. Centang barang yang ingin Anda bayar, lalu klik 'Lanjut ke Pembayaran'.<br>
                        <strong>3. Bayar:</strong> Pilih metode pembayaran Anda (QRIS atau Transfer) dan selesaikan transaksi sesuai instruksi.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingTwo">
                    <button class="accordion-button collapsed" type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapseTwo"
                        aria-expanded="false"
                        aria-controls="collapseTwo">
                        Bagaimana Cara Melakukan Pembayaran?
                    </button>
                </h2>
                <div id="collapseTwo"
                    class="accordion-collapse collapse"
                    aria-labelledby="headingTwo"
                    data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Kami menyediakan dua metode pembayaran:<br>
                        <strong>- Transfer Bank:</strong> Lakukan transfer ke nomor rekening BCA yang tertera di halaman pembayaran dan upload bukti transfer Anda.<br>
                        <strong>- QRIS:</strong> Scan kode QR yang muncul di layar menggunakan e-wallet (OVO, GoPay, Dana) atau m-banking Anda. Pembayaran akan terverifikasi otomatis.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingThree">
                    <button class="accordion-button collapsed" type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapseThree"
                        aria-expanded="false"
                        aria-controls="collapseThree">
                        Bagaimana Cara Menggunakan Voucher?
                    </button>
                </h2>
                <div id="collapseThree"
                    class="accordion-collapse collapse"
                    aria-labelledby="headingThree"
                    data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Voucher hanya dapat digunakan di halaman <b>Keranjang Belanja (Cart)</b> sebelum Anda melanjutkan ke pembayaran.<br>
                        1. Masukkan kode voucher unik Anda di kolom "Masukkan Kode Voucher".<br>
                        2. Klik tombol "Use".<br>
                        3. Total harga Anda akan otomatis terpotong jika voucher valid dan memenuhi syarat.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingFour">
                    <button class="accordion-button collapsed" type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapseFour"
                        aria-expanded="false"
                        aria-controls="collapseFour">
                        Kapan Pesanan Saya Akan Dikirim?
                    </button>
                </h2>
                <div id="collapseFour"
                    class="accordion-collapse collapse"
                    aria-labelledby="headingFour"
                    data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Pesanan yang pembayarannya terverifikasi sebelum jam 15:00 WIB akan kami proses dan kirim pada hari yang sama. Pesanan yang masuk setelah jam 15:00 WIB akan dikirim pada hari kerja berikutnya. Estimasi standar pengiriman adalah 2-4 hari kerja, tergantung lokasi Anda.
                    </div>
                </div>
            </div>

        </div>

        <div class="footer-copy">
            &copy; <?= date("Y"); ?> Styrk Industries. All Rights Reserved.
        </div>

    </div>
</footer>

<!-- BOOTSTRAP JS BUNDLE SEKALI AJA DI PALING BAWAH -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>