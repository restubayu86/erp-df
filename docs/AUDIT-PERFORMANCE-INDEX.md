# Audit Halaman Schedule Index & Pengujian Performa

Tanggal Audit: 29 Oktober 2026  
Halaman: `app/Modules/Core/Views/schedule/index.php`  
Modul: Core (Dyeing & Finishing Schedule)

---

## 1. 📊 Status Integrasi Data: Real Database vs Dummy Data

Berdasarkan penelusuran pada arsitektur CodeIgniter 4, Controller (`ScheduleController`), Service (`ScheduleService`), dan View (`index.php`), berikut adalah status integrasi data pada halaman utama:

| Komponen / Fitur | Status | Sumber Data / Keterangan |
| :--- | :---: | :--- |
| **Tabel Utama Schedule** | 🟢 **Real DB** | Dimuat secara *Server-side* via DataTables AJAX (`/schedule/datatable`) dari gabungan tabel `schedules`, `schedule_items`, `schedule_item_params`, `machines`, `shifts`, `processes`, dan `users`. |
| **Kartu Insight / Ringkasan** | 🟢 **Real DB** | Menghitung agregasi riil via AJAX (`/schedule/insight`): Total Jadwal, Menunggu Approval, Disetujui, Berjalan, Overshift, Terlambat Mulai, Utilisasi Mesin, & Total Qty. |
| **Filter Dropdown Sidebar** | 🟢 **Real DB** | Opsi **Mesin** dan **Shift** diambil langsung dari query database tabel `machines` dan `shifts`. Opsi **Status** disinkronkan dengan konfigurasi `ScheduleFlow`. |
| **Modal Integration IFS** | 🟢 **Real DB** | Data DOP / Order / Customer diintegrasikan melalui API IFS (`/api/ifs/datatable`). |
| **Aksi Workflow & Reorder** | 🟢 **Real DB** | Aksi Submit, Approve, Revise, Reject, Cancel, Delete, dan Drag-and-Drop Reorder terkoneksi langsung ke Service (`ScheduleService` & `WorkflowService`). |
| **Halaman Cetak (Print)** | 🟢 **Real DB** | Mengambil data riil header, item, dan parameter terenkapsulasi dari database untuk dicetak dalam format F4 Landscape. |
| **Aset Visual & Styling** | 🔵 **Aset Statis** | Menggunakan aset ilustrasi tema Falcon (`corner-1.png` s.d. `corner-5.png`) dan pemetaan warna status/badge. |

> **Kesimpulan Data:** **100% data operasional pada modul Schedule telah menggunakan Real Database.** Tidak ada data *dummy* atau *hardcoded content* pada informasi jadwal.

---

## 2. ⚡ Analisis Performa Web

### **Backend & Query Database (Sangat Baik 🟢)**
* **Server-Side Pagination:** Menggunakan `Hermawan\DataTables` (`serverSide: true`), sehingga pembacaan data di-page langsung pada query SQL (`LIMIT` & `OFFSET`). Halaman tetap responsif meskipun total data mencapai puluhan ribu baris.
* **Agregasi Insight Efisien:** Kalkulasi statistik menggunakan query agregat SQL (`COUNT`, `SUM`, `TIMESTAMPDIFF`) tanpa memuat objek masif ke memori PHP.

### **Frontend & Rendering Visual (Sangat Baik 🟢)**
* **Freeze Header Ringan:** Penggunaan CSS `position: sticky` murni untuk *Freeze Header* jauh lebih cepat dan mulus (*smooth scrolling 60 FPS*) dibandingkan dengan penggunaan plugin JavaScript *sticky table*.
* **Penanganan DOM:** Component *Select2* dan *Flatpickr* diinisialisasi secara efisien tanpa menghambat proses *rendering* utama tabel.
* **Aset Teroptimasi:** Penggunaan logo dan aset gambar lokal dengan ukuran file terkompresi (< 50KB) memastikan waktu *initial load* tetap singkat.

---

## 3. 🔍 Catatan Audit Kode (`index.php`)

1. **Keamanan (Security):**
   * Semua *output* variabel pada HTML disanitasi menggunakan helper `esc()` untuk mencegah kerentanan XSS (*Cross-Site Scripting*).
   * Token CSRF terintegrasi otomatis pada setiap *request* AJAX `erp.post()`.

2. **Resiliensi & Error Handling:**
   * Terdapat penanganan *error* DataTables (`error.dt`) yang menampilkan pesan ramah pengguna jika server mengalami kendala/HTTP 500.
   * State baris yang ditutup/dibuka (*collapsed row group*) disimpan pada `localStorage` pengguna sehingga preferensi tampilan tetap diingat saat *refresh*.

---

## 4. 📁 Dokumentasi Terkait
* Controller: `app/Modules/Core/Controllers/ScheduleController.php`
* Service: `app/Modules/Core/Services/ScheduleService.php`
* View Utama: `app/Modules/Core/Views/schedule/index.php`
* View Cetak: `app/Modules/Core/Views/schedule/print.php`
