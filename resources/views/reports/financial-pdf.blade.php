<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan Pembangunan Masjid At-Taqwa</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5px;
            color: #1e293b;
            line-height: 1.45;
            margin: 0;
            padding: 16px 20px;
        }
        .header-table {
            width: 100%;
            border-bottom: 2.5px solid #008B45;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .logo-cell {
            width: 78px;
            vertical-align: middle;
        }
        .logo-img {
            width: 68px;
            height: auto;
        }
        .title-cell {
            vertical-align: middle;
            padding-left: 10px;
        }
        .mosque-name {
            font-size: 16px;
            font-weight: bold;
            color: #008B45;
            margin: 0 0 3px 0;
            text-transform: uppercase;
        }
        .mosque-meta {
            font-size: 9.5px;
            color: #475569;
            margin: 0;
        }
        .report-banner {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 16px;
        }
        .report-title {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 3px 0;
        }
        .report-period {
            font-size: 10px;
            color: #008B45;
            font-weight: bold;
            margin: 0;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .summary-table td {
            width: 25%;
            border: 1px solid #cbd5e1;
            padding: 9px 10px;
            vertical-align: top;
        }
        .summary-label {
            font-size: 8.5px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .summary-value {
            font-size: 11.5px;
            font-weight: bold;
            color: #0f172a;
        }
        .text-green { color: #008B45; }
        .text-amber { color: #d97706; }
        .bg-highlight { background-color: #ecfdf5; border-color: #6ee7b7 !important; }
        .chart-box {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 12px;
            margin-bottom: 18px;
            background-color: #f8fafc;
        }
        .chart-title {
            font-size: 9.5px;
            font-weight: bold;
            color: #334155;
            margin-bottom: 6px;
        }
        .bar-track {
            width: 100%;
            height: 12px;
            background-color: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }
        .section-heading {
            font-size: 11.5px;
            font-weight: bold;
            color: #008B45;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            margin: 16px 0 8px 0;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 9.5px;
        }
        table.data-table th {
            background-color: #008B45;
            color: #ffffff;
            text-align: left;
            padding: 6px 8px;
            font-weight: bold;
        }
        table.data-table th.right,
        table.data-table td.right {
            text-align: right;
        }
        table.data-table td {
            border-bottom: 1px solid #e2e8f0;
            padding: 6px 8px;
            vertical-align: top;
        }
        table.data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .total-row td {
            font-weight: bold;
            background-color: #f1f5f9 !important;
            border-top: 1.5px solid #94a3b8;
            border-bottom: 1.5px solid #94a3b8;
        }
        .footer-meta {
            margin-top: 22px;
            border-top: 1px solid #cbd5e1;
            padding-top: 10px;
            font-size: 9px;
            color: #475569;
        }
        .footer-meta table {
            width: 100%;
        }
    </style>
</head>
<body>
    @php
        $fmtRp = fn($val) => 'Rp ' . number_format((float) $val, 0, ',', '.');
    @endphp

    <!-- Kop Laporan Resmi -->
    <table class="header-table">
        <tr>
            @if($logoBase64)
                <td class="logo-cell">
                    <img src="{{ $logoBase64 }}" alt="Logo Masjid At-Taqwa" class="logo-img">
                </td>
            @endif
            <td class="title-cell">
                <p class="mosque-name">{{ $mosque->name ?? 'Masjid At-Taqwa' }}</p>
                <p class="mosque-meta">{{ $mosque->address ?? 'Candi Rejo, RT 23 RW 09, Jelobo, Wonosari, Klaten, Jawa Tengah 57473' }}</p>
                <p class="mosque-meta">Email: {{ $mosque->email ?? 'masjidattaqwa2309@gmail.com' }}</p>
            </td>
        </tr>
    </table>

    <!-- Judul & Periode Laporan -->
    <div class="report-banner">
        <p class="report-title">LAPORAN KEUANGAN PEMBANGUNAN MASJID AT-TAQWA — {{ strtoupper($report['period']['period_title']) }}</p>
        <p class="report-period">Periode: {{ $report['period']['period_range_indo'] }}</p>
    </div>

    <!-- 4 Kartu Ringkasan Keuangan -->
    <table class="summary-table">
        <tr>
            <td>
                <div class="summary-label">Saldo Awal Periode</div>
                <div class="summary-value">{{ $fmtRp($report['period_opening_balance']) }}</div>
            </td>
            <td>
                <div class="summary-label">Total Pemasukan</div>
                <div class="summary-value text-green">+ {{ $fmtRp($report['total_income']) }}</div>
            </td>
            <td>
                <div class="summary-label">Total Pengeluaran</div>
                <div class="summary-value text-amber">- {{ $fmtRp($report['total_expense']) }}</div>
            </td>
            <td class="bg-highlight">
                <div class="summary-label text-green">Saldo Akhir Periode</div>
                <div class="summary-value text-green">{{ $fmtRp($report['period_closing_balance']) }}</div>
            </td>
        </tr>
    </table>

    <!-- Ringkasan Visual Proporsi Mutasi -->
    <div class="chart-box">
        <div class="chart-title">Ringkasan Proporsi Arus Kas Periode Ini</div>
        <table style="width: 100%; font-size: 9px; margin-bottom: 5px;">
            <tr>
                <td class="text-green"><strong>Pemasukan:</strong> {{ $report['income_percent'] }}% ({{ $fmtRp($report['total_income']) }})</td>
                <td style="text-align: right;" class="text-amber"><strong>Pengeluaran:</strong> {{ $report['expense_percent'] }}% ({{ $fmtRp($report['total_expense']) }})</td>
            </tr>
        </table>
        <table style="width: 100%; border-collapse: collapse; height: 10px;">
            <tr>
                @if($report['income_percent'] > 0)
                    <td style="width: {{ $report['income_percent'] }}%; background-color: #008B45; height: 10px;"></td>
                @endif
                @if($report['expense_percent'] > 0)
                    <td style="width: {{ $report['expense_percent'] }}%; background-color: #d97706; height: 10px;"></td>
                @endif
                @if($report['income_percent'] == 0 && $report['expense_percent'] == 0)
                    <td style="width: 100%; background-color: #cbd5e1; height: 10px;"></td>
                @endif
            </tr>
        </table>
    </div>

    <!-- Detail Pemasukan Infaq -->
    <div class="section-heading">A. Rincian Pemasukan Infaq Terverifikasi ({{ count($report['incomes']) }} Transaksi)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 17%;">Tanggal</th>
                <th style="width: 16%;">Kode</th>
                <th style="width: 18%;">Sumber / Metode</th>
                <th style="width: 18%;">Donatur</th>
                <th style="width: 11%;">Keterangan</th>
                <th class="right" style="width: 15%;">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['incomes'] as $idx => $item)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $item['transaction_date_indo'] }}</td>
                    <td>{{ $item['transaction_code'] }}</td>
                    <td>{{ $item['source_or_purpose'] }}</td>
                    <td>{{ $item['donor_display'] }}</td>
                    <td>{{ $item['description'] ?: '-' }}</td>
                    <td class="right text-green">{{ $fmtRp($item['amount']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: #64748b; padding: 12px;">
                        Tidak ada transaksi pemasukan terverifikasi pada periode ini.
                    </td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="6" class="right">TOTAL PEMASUKAN PERIODE INI</td>
                <td class="right text-green">{{ $fmtRp($report['total_income']) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Detail Pengeluaran Pembangunan -->
    <div class="section-heading">B. Rincian Pengeluaran Pembangunan Terverifikasi ({{ count($report['expenses']) }} Transaksi)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 18%;">Tanggal</th>
                <th style="width: 17%;">Kode</th>
                <th style="width: 26%;">Keperluan Pengeluaran</th>
                <th style="width: 19%;">Keterangan</th>
                <th class="right" style="width: 15%;">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['expenses'] as $idx => $item)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $item['transaction_date_indo'] }}</td>
                    <td>{{ $item['transaction_code'] }}</td>
                    <td>{{ $item['source_or_purpose'] }}</td>
                    <td>{{ $item['description'] ?: '-' }}</td>
                    <td class="right text-amber">{{ $fmtRp($item['amount']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: #64748b; padding: 12px;">
                        Tidak ada transaksi pengeluaran terverifikasi pada periode ini.
                    </td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="5" class="right">TOTAL PENGELUARAN PERIODE INI</td>
                <td class="right text-amber">{{ $fmtRp($report['total_expense']) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Metadata Pembuatan Laporan -->
    <div class="footer-meta">
        <table>
            <tr>
                <td>
                    <strong>Dicetak / Dibuat oleh:</strong> {{ $generatorName }}<br>
                    <strong>Tanggal Pembuatan Laporan:</strong> {{ $report['generated_at_indo'] }}
                </td>
                <td style="text-align: right; vertical-align: bottom;">
                    Dokumen ini dihasilkan secara otomatis oleh<br>
                    <strong>Sistem Informasi Pengelolaan Infaq Pembangunan {{ $mosque->name ?? 'Masjid At-Taqwa' }}</strong>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>