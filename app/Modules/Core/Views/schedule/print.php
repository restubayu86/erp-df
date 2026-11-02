<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Cetak Jadwal Produksi</title>
    <style>
        @page {
            size: 215mm 330mm landscape;
            margin: 10mm;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 9pt;
            margin: 0;
            padding: 0;
            color: #000;
        }

        .header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .logo {
            width: 60px;
            margin-right: 20px;
        }

        .company-info h1 {
            margin: 0;
            font-size: 14pt;
            text-transform: uppercase;
        }

        .company-info h2 {
            margin: 0;
            font-size: 12pt;
            font-weight: normal;
        }

        .company-info h3 {
            margin: 5px 0 0 0;
            font-size: 11pt;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 4px;
            word-wrap: break-word;
            vertical-align: top;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }

        .text-center {
            text-align: center;
        }

        .fw-bold {
            font-weight: bold;
        }

        /* Column widths */
        .col-no {
            width: 25px;
        }

        .col-machine {
            width: 80px;
        }

        .col-lot {
            width: 90px;
        }

        .col-dop {
            width: 80px;
        }

        .col-date {
            width: 70px;
        }

        .col-design {
            width: 120px;
        }

        .col-color {
            width: 80px;
        }

        .col-process {
            width: 80px;
        }

        .col-params {
            width: auto;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body onload="window.print()">
    <div class="header">
        <img src="<?= base_url('assets/img/logos/logo-regency.png') ?>" class="logo" alt="Logo">
        <div class="company-info">
            <h1>PT. Sinar Continental</h1>
            <h2>Divisi 3A</h2>
            <h3>Jadwal Produksi Departemen Dyeing</h3>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="col-no">No</th>
                <th class="col-machine">Mesin</th>
                <th class="col-lot">Dye Lot / Kategori</th>
                <th class="col-dop">No DOP</th>
                <th class="col-date">Tgl / Shift</th>
                <th class="col-design">Desain / Segment</th>
                <th class="col-color">Warna</th>
                <th class="col-process">Proses</th>
                <th class="col-params">Parameter Rencana</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1;
            foreach ($schedules as $s): ?>
                <?php 
                $items = !empty($s['items']) ? $s['items'] : [['dye_lot_no' => null, 'source_item_id' => null, 'design_name' => null, 'article' => null, 'color_name' => null, 'params' => null]];
                $itemCount = count($items);
                foreach ($items as $idx => $item): 
                ?>
                    <tr>
                        <?php if ($idx === 0): ?>
                            <td rowspan="<?= $itemCount ?>" class="text-center"><?= $no++ ?></td>
                            <td rowspan="<?= $itemCount ?>" class="text-center fw-bold"><?= esc((string)$s['machine_code']) ?></td>
                        <?php endif; ?>

                        <td>
                            <div class="fw-bold"><?= esc(($item['dye_lot_no'] ?? '') ?: $s['schedule_no']) ?></div>
                            <div style="font-size: 8pt; color: #666;"><?= esc(ucfirst((string)($s['category'] ?? ''))) ?></div>
                        </td>
                        <td class="text-center"><?= esc((string)($item['source_item_id'] ?? '') ?: '-') ?></td>

                        <?php if ($idx === 0): ?>
                            <td rowspan="<?= $itemCount ?>" class="text-center">
                                <?= date('d/m/y', strtotime($s['work_date'])) ?><br>
                                <small><?= esc((string)$s['shift_name']) ?></small>
                            </td>
                        <?php endif; ?>

                        <td><?= esc(($item['design_name'] ?? '') ?: ($item['article'] ?? '-')) ?></td>
                        <td><?= esc((string)($item['color_name'] ?? '-')) ?></td>

                        <?php if ($idx === 0): ?>
                            <td rowspan="<?= $itemCount ?>" class="text-center"><?= esc((string)($s['process_name'] ?? '') ?: '-') ?></td>
                        <?php endif; ?>

                        <td style="font-size: 8pt;">
                            <?= esc((string)($item['params'] ?? '') ?: '-') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <?php if (empty($schedules)): ?>
                <tr>
                    <td colspan="9" class="text-center">Tidak ada data jadwal untuk filter ini.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>

</html>