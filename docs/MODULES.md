# Struktur modul

```
app/Modules/
├── BaseModuleController.php          # dasar: getMachines(), modulePage(), pencarian view
├── Core/
│   ├── Controllers/                  # logika bersama (ScheduleController, OperatorController)
│   └── Views/                        # view bersama: schedule/{index,form}, operator/index
├── Dyeing/Controllers/               # Schedule.php, Operator.php (hanya isi $moduleName/$moduleType)
└── Finishing/Controllers/
```

## Pencarian view
`modulePage('schedule/index')` mencari berurutan:
1. `app/Modules/{Modul}/Views/schedule/index.php`  (khusus modul, opsional)
2. `app/Modules/Core/Views/schedule/index.php`   (bersama)

Untuk membuat tampilan khusus satu modul, cukup buat file dengan path yang sama di folder `Views`
modul tersebut. Tidak ada lagi view modul di `app/Views/modules/`.

## Menambah modul baru
1. Buat `app/Modules/{Nama}/Controllers/Schedule.php` (dan `Operator.php`) yang meng-extend
   `Core\Controllers\ScheduleController` dengan `$moduleName` / `$moduleType`.
2. Tambahkan `'slug' => 'Nama'` pada daftar modul di `app/Config/Routes.php`.
3. Daftarkan di `Config\Menu::$modules` dan permission `module.slug` di `Config\AuthGroups`.

## Base URL
Pada `CI_ENVIRONMENT = development`, `Config\App` membaca host/port/folder dari request sehingga
URL AJAX selalu satu origin. Pada production, `app.baseURL` di `.env` dipakai apa adanya.
