<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kebijakan Privasi — POS Cafe</title>
    <style>
        :root {
            --bg: #FBF6EE;
            --surface: #FFFFFF;
            --border: #EBDFCB;
            --text: #241B12;
            --muted: #6E5E48;
            --primary: #B8743D;
            --primary-dark: #8A5527;
        }
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            margin: 0;
            line-height: 1.6;
        }
        .container {
            max-width: 760px;
            margin: 0 auto;
            padding: 48px 24px 80px;
        }
        h1 {
            font-size: 28px;
            margin: 0 0 8px;
        }
        .subtitle {
            color: var(--muted);
            margin: 0 0 32px;
            font-size: 14px;
        }
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 16px;
        }
        h2 {
            color: var(--primary-dark);
            font-size: 18px;
            margin: 0 0 12px;
        }
        ul { padding-left: 20px; margin: 8px 0; }
        a { color: var(--primary); }
        .footer {
            margin-top: 32px;
            text-align: center;
            font-size: 12px;
            color: var(--muted);
        }
    </style>
</head>
<body>
<main class="container">
    <h1>Kebijakan Privasi</h1>
    <p class="subtitle">POS Cafe · Berlaku efektif 27 Mei 2026</p>

    <div class="card">
        <h2>1. Tentang Aplikasi</h2>
        <p>POS Cafe adalah aplikasi <em>point-of-sale</em> mobile yang dibuat untuk
            membantu pemilik kedai kopi mencatat pesanan, mencetak struk, dan
            mengelola laporan penjualan. Aplikasi ini berpasangan dengan backend
            kami yang melayani sinkronisasi data multi-perangkat.</p>
    </div>

    <div class="card">
        <h2>2. Data yang Kami Kumpulkan</h2>
        <ul>
            <li><strong>Identitas akun:</strong> nama, email, nomor telepon
                (opsional), avatar (opsional) — dipakai untuk login dan
                identifikasi kasir di laporan.</li>
            <li><strong>Data transaksi:</strong> daftar produk, total, metode bayar,
                nominal kembalian, waktu transaksi, kasir yang menangani.</li>
            <li><strong>Data shift kas:</strong> modal awal, kas masuk/keluar,
                fisik kas saat tutup, catatan shift.</li>
            <li><strong>Nama pelanggan (opsional):</strong> hanya jika kasir
                mengetikkannya saat menyimpan draft order.</li>
            <li><strong>Foto produk:</strong> diunggah saat menambah menu.</li>
        </ul>
        <p>Aplikasi <strong>tidak</strong> mengumpulkan: lokasi pengguna, kontak,
            SMS, riwayat browsing, atau iklan pengidentifikasi (advertising ID).</p>
    </div>

    <div class="card">
        <h2>3. Izin Perangkat</h2>
        <ul>
            <li><strong>Kamera:</strong> hanya saat memindai barcode/QR produk.</li>
            <li><strong>Bluetooth:</strong> hanya untuk terhubung ke printer
                thermal saat mencetak struk. Tidak dipakai untuk lokasi.</li>
            <li><strong>Galeri:</strong> hanya saat memilih foto produk.</li>
            <li><strong>Internet:</strong> untuk sinkronisasi ke server dan
                pembayaran QRIS.</li>
        </ul>
    </div>

    <div class="card">
        <h2>4. Penyimpanan Data</h2>
        <p>Data transaksi dan katalog produk disimpan baik di perangkat
            (SQLite, untuk mode offline) maupun di server kami (cloud database
            terenkripsi). Bearer token autentikasi disimpan di Keystore Android
            atau Keychain iOS. Token gateway pembayaran (jika diaktifkan)
            disimpan lokal di perangkat dan tidak pernah dikirim ke server kami.</p>
    </div>

    <div class="card">
        <h2>5. Pembagian Data</h2>
        <p>Kami <strong>tidak</strong> menjual atau membagikan data Anda ke
            pihak ketiga untuk tujuan iklan. Data dibagikan hanya ke:</p>
        <ul>
            <li><strong>Penyedia pembayaran QRIS</strong> (jika Anda aktifkan)
                — untuk memproses transaksi.</li>
            <li><strong>Hosting server</strong> kami — penyimpanan database.</li>
        </ul>
    </div>

    <div class="card">
        <h2>6. Hak Anda</h2>
        <ul>
            <li>Mengakses data akun Anda lewat menu Setting → Akun.</li>
            <li>Memperbarui data profil lewat panel admin web.</li>
            <li><strong>Menghapus akun</strong> kapan saja lewat aplikasi:
                Setting → Akun → Hapus Akun. Penghapusan langsung mencabut
                semua token sesi, menghapus PII Anda, dan menonaktifkan akun.
                Riwayat transaksi yang sudah tercatat tetap disimpan dengan
                identitas teranonimisasi untuk keperluan audit.</li>
            <li>Alternatif: kirim permintaan penghapusan via email ke
                <a href="mailto:support@jagofullstack.com">support@jagofullstack.com</a>.
                Kami merespons dalam 7 hari kerja.</li>
        </ul>
    </div>

    <div class="card">
        <h2>7. Keamanan</h2>
        <p>Semua komunikasi ke server kami menggunakan HTTPS. Password
            di-hash dengan bcrypt sebelum disimpan. Token sesi memakai Sanctum
            dan bisa dicabut dari panel admin atau lewat logout.</p>
    </div>

    <div class="card">
        <h2>8. Perubahan Kebijakan</h2>
        <p>Kebijakan ini dapat diperbarui dari waktu ke waktu. Versi terbaru
            selalu dapat diakses di
            <a href="https://poscafe.jagofullstack.com/api/privacy">poscafe.jagofullstack.com/api/privacy</a>.
            Perubahan signifikan akan diumumkan lewat notifikasi di aplikasi.</p>
    </div>

    <div class="card">
        <h2>9. Kontak</h2>
        <p>Pertanyaan atau keluhan privasi:
            <a href="mailto:support@jagofullstack.com">support@jagofullstack.com</a></p>
    </div>

    <p class="footer">© 2026 JagoFullstack · POS Cafe</p>
</main>
</body>
</html>
