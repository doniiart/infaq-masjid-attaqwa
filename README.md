# Sistem Informasi Pengelolaan Infaq Pembangunan Masjid At-Taqwa

Aplikasi web transparansi dan pengelolaan keuangan pembangunan **Masjid At-Taqwa** (Candi Rejo, RT 23 RW 09, Jelobo, Wonosari, Klaten, Jawa Tengah 57473).

## Fitur Utama

1. **Dashboard Transparansi Publik (`/`)**
   - Menampilkan Saldo Awal, Total Pemasukan Terverifikasi, Total Pengeluaran Terverifikasi, dan Saldo Dana Tersedia secara *real-time*.
   - Grafik perbandingan arus kas bulanan (Pemasukan vs Pengeluaran).
   - Riwayat transaksi terverifikasi dengan filter jenis, rentang tanggal, pencarian, dan perlindungan privasi donatur (`Hamba Allah` untuk infaq anonim).
   - Timeline progres tahapan pembangunan dan galeri dokumentasi foto lapangan.
   - Unduh Laporan Keuangan PDF (Mingguan, Bulanan, Tiga Bulanan, dan Periode Khusus).

2. **Manajemen Keuangan & Audit Pengurus (`/dashboard`)**
   - **Saldo Awal:** Dikelola khusus oleh Super Admin dan tercatat pada log audit.
   - **Pemasukan Infaq:** Pencatatan pemasukan dengan opsi privasi tampilan nama donatur di publik.
   - **Pengeluaran Pembangunan:** Validasi otomatis **anti-saldo negatif** di dalam transaksi database serta unggah bukti nota (publik/privat).
   - **Pembatalan Transaksi Tanpa Hapus:** Pembatalan mewajibkan alasan, menyimpan *snapshot* data sebelumnya, dan mengecualikan transaksi dari saldo aktif.
   - **Log Aktivitas Audit:** Pencatatan seluruh tindakan penting pengurus beserta perbandingan data sebelum dan sesudah perubahan.

3. **Manajemen Pengguna, Profil Masjid, & Backup/Restore (Super Admin)**
   - Pendaftaran publik dinonaktifkan; pembuatan/penonaktifan akun pengurus dikendalikan penuh oleh Super Admin.
   - Pengaturan identitas resmi masjid dan kop laporan PDF.
   - Backup manual & terjadwal otomatis (`backup:run-scheduled`) terkompresi `.json.gz` dengan checksum SHA-256 serta fitur *restore* terkontrol dengan *safety backup* otomatis.

## Panduan Instalasi Lokal (Laragon)

1. Clone repositori dan masuk ke direktori proyek:
   ```bash
   git clone [https://github.com/doniiart/infaq-masjid-attaqwa.git](https://github.com/doniiart/infaq-masjid-attaqwa.git)
   cd infaq-masjid-attaqwa