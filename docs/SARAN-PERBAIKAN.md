# ERP DF — Catatan Analisis & Saran Perbaikan

Tanggal analisis: 21 Oktober 2026
Cakupan: kode kustom di `app/` (CodeIgniter 4 + Shield + DataTables), tanpa `vendor`.

## Ringkasan aplikasi

Aplikasi schedule produksi per mesin / tanggal / shift (pagi, siang, malam).

- Alur status: draft → submitted → approved / rejected → running ⇄ paused → done (+ cancelled).
- Semua perpindahan status lewat `ScheduleWorkflow::move()` (cek hak akses, log ke `schedule_logs`, alasan tolak wajib, satu mesin satu order berjalan).
- Auto ACC lewat `app_settings` (`schedule.auto_approve`).
- Peran: admin, scheduler, kabag, operator, viewer (16 permission).
- Tampilan: daftar schedule (DataTables), tablet operator, layar TV (token).

## A. Keamanan (prioritas tertinggi)

1. Arsip pernah memuat rahasia: `.env` (DB user `root`, token TV), `writable/session`, log, debugbar, `user.txt`.
   - Ganti password DB dan token TV bila arsip pernah dibagikan.
   - Pakai user DB khusus aplikasi, bukan `root`.
   - Jangan sertakan `.env`, `writable/*`, `user.txt` saat membagikan project.
2. `Config\Auth::$allowRegistration = true` — matikan; buat user lewat admin.
3. `Config\Auth::$allowMagicLinkLogins = true` — matikan jika email belum dikonfigurasi / tidak dipakai.
4. `CI_ENVIRONMENT = development` — set `production` saat deploy; aktifkan `forceGlobalSecureRequests` bila memakai HTTPS.
5. Token TV lewat query string `?key=` — lebih baik header `X-TV-Key` (sudah didukung `TvToken`).

## B. Logika & bug potensial

1. Tidak ada validasi bentrok jadwal pada mesin yang sama (rentang `start_at`–`end_at` tumpang tindih).
2. `start_at` / `end_at` tidak divalidasi berada di dalam `work_date` dan `shift`.
3. Prioritas: `MAX(priority)+1` tanpa lock (race condition); `reorder()` tidak memastikan nomor unik dan berurutan.
4. Operator bisa memulai order approved milik shift / tanggal lain lewat endpoint `transition`.
5. Transisi `cancelled` ada di backend tetapi tidak ada tombolnya di UI.
6. Menu mengarah ke halaman yang belum ada: `report`, `checksheet`, `admin/users`, `admin/tv` (404).
7. `Home::index` masih welcome page bawaan CodeIgniter; Dashboard belum ada isinya.
8. `down()` migration `ScheduleWorkflow` tidak mengembalikan enum `status` ke bentuk awal.
9. Belum ada tes untuk logika bisnis (hanya tes contoh bawaan).

## C. Urutan pengerjaan yang disarankan

1. Amankan kredensial & konfigurasi (bagian A).
2. UI/UX: template Phoenix, navbar modular, theme switch, notifikasi, profil (sedang dikerjakan).
3. Validasi bentrok jadwal + penomoran prioritas yang aman.
4. Batasi aksi operator ke shift & tanggal yang sedang berjalan.
5. Lengkapi modul laporan produksi & checksheet.
6. Tes PHPUnit untuk `ScheduleWorkflow` dan `Shifts::productionDate()` (termasuk shift malam).

## D. Status pengerjaan

### 21 Okt 2026 — Template Phoenix & navbar (selesai)

- Navbar Phoenix horizontal + slim + darker; nama aplikasi **ERP DF** (`Config\Menu::$appName`).
- Menu modular: dropdown **Dyeing** / **Finishing** muncul sesuai bagian kerja user (permission Shield `module.dyeing`, `module.finishing`; admin otomatis keduanya).
  - Atur per user: `php spark erp:module <username> <dyeing|finishing|both|none>`
  - User yang belum diatur bagiannya hanya melihat Dashboard (sengaja ketat).
- Ganti tema terang/gelap (disimpan di browser, `phoenixTheme`).
- Notifikasi "perlu tindakan" dari data schedule, dibatasi ke bagian user: menunggu ACC (kabag), schedule ditolak (scheduler pembuat), order siap dijalankan di shift ini (operator). Tidak ada status "dibaca".
- Profil: inisial, nama, email, role, bagian, tombol keluar. Halaman "Profil saya" belum ada (tampil "Segera").
- Menu yang halamannya belum ada (Laporan Produksi, Checksheet, User & Role, Layar TV) tampil nonaktif dengan label "Segera", bukan lagi 404.

### Belum dikerjakan dari bagian B

- Link modul membawa `?module=dyeing|finishing`, tetapi daftar Schedule dan Tablet Operator belum memfilter data per bagian (mesin `machines.type`). Ini langkah berikutnya.
- Halaman Dashboard masih welcome page bawaan CodeIgniter.
