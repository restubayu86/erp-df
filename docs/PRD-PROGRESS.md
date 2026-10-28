# Progres terhadap PRD.md (v0.5)

Status per 25 Okt 2026. Diuji: migration di atas salinan database asli, seeder, dan semua endpoint lewat HTTP (login tiap role,
validasi, alur status, urutan, kalender shift, auto ACC, TV bertoken, Dyeing & Finishing). **Belum diuji di browser sungguhan**
(tampilan DataTables/RowGroup/RowReorder, form dinamis); sintaks JavaScript sudah dicek.

## Sudah dikerjakan
| PRD | Isi |
|---|---|
| 3.1 | `Modules/Shared` → `Modules/Core` (Models, Services, Controllers, Views) |
| 6.2/6.3 | Tabel `shifts`, `shift_patterns`, `shift_pattern_items`, `shift_calendar`, `processes`, `parameters`, `parameter_divisions`; kapasitas mesin |
| 6.4 | `schedules` (header) + `schedule_items` + `schedule_item_params` + `schedule_logs` (action, changes). Data uji lama dibuang |
| 5.9, SHF-01..05 | `ShiftCalendarService` (satu sumber jam efektif), layar Admin **Kalender Shift**, `GET /api/shift/effective` |
| 5.1-5.3, APR-* | `WorkflowService`: draft→submitted→approved/revision/rejected, edit approved = diajukan ulang (Rev. n), auto ACC dicatat "sistem" |
| SCH-01..07, 10, 11 | Form header + item + parameter dinamis per divisi, validasi bentrok mesin / divisi / shift aktif / parameter wajib, Salin sebagai Baru, urutan drag-drop |
| 5.8 | Daftar: 8 kartu insight, filter (periode, mesin, shift, status banyak, prioritas, cari), grup per mesin dengan total dari server, state collapse di localStorage, filter di URL |
| AUD-01..03 | Log semua aksi + nilai lama-baru, soft delete, halaman detail dengan riwayat |
| 8 | Permission & role sesuai PRD 8.1/8.2 (+ group `display`), `division.*` menggantikan `module.*`, registrasi & magic link dimatikan |
| TV | `/display/{dyeing|finishing}` + `/display/api/board/{div}`, jam shift dari kalender, tanda overshift |
| 5.5 (sebagian) | Deteksi overshift dihitung saat dibaca (kartu, badge tabel/tablet/TV) |

## Belum dikerjakan (urut sprint PRD)
- Sprint 1: CRUD master (mesin, proses, parameter) dan edit isi pola shift (kini lewat seeder/database); tanda "perlu dijadwal ulang" (SHF-04)
- Sprint 4: tarik dari Dyeing (SCH-12), tautan IFS (IFS-*), tabel `schedule_external_links`, `ifs_dummy_orders`
- Sprint 5: antrean tablet per mesin & rute `/operator/api/...`, input parameter aktual saat Selesai, peringatan perangkat TV
- Sprint 6: delegasi / serah terima overshift, eskalasi 15 menit, assign paksa Kabag
- Sprint 7: token TV yang bisa dicabut (kini token `.env`), peringatan kapasitas (SCH-08), laporan
- DataTables/jQuery masih dari CDN (PRD: aset lokal untuk LAN tanpa internet). Kabag belum bisa mengurutkan ulang (hanya `schedule.edit`).

## Menjalankan
```
php spark migrate
php spark db:seed MasterSeeder          # produksi: master saja
php spark db:seed DatabaseSeeder        # dev: master + jadwal contoh
php spark erp:module <username> both    # beri akses bagian (division.dyeing / division.finishing); admin otomatis
```
Catatan: user lama masih tergabung di grup `viewer`, sehingga operator ikut punya `schedule.view`. Untuk mengikuti PRD (operator tanpa
akses daftar jadwal), keluarkan operator dari grup `viewer`.
