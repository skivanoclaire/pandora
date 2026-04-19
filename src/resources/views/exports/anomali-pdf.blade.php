<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; }
        h1 { font-size: 18px; margin-bottom: 5px; }
        .subtitle { color: #666; font-size: 12px; margin-bottom: 15px; }
        .stats { display: flex; margin-bottom: 15px; }
        .stat-box { background: #f5f5f5; padding: 8px 15px; margin-right: 10px; border-radius: 4px; text-align: center; }
        .stat-box .num { font-size: 20px; font-weight: bold; color: #1e3a5f; }
        .stat-box .label { font-size: 9px; color: #888; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #1e3a5f; color: white; padding: 6px 8px; text-align: left; font-size: 10px; text-transform: uppercase; }
        td { padding: 5px 8px; border-bottom: 1px solid #eee; font-size: 10px; }
        tr:nth-child(even) { background: #f9f9f9; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 9px; font-weight: bold; }
        .badge-t1 { background: #ffe0e0; color: #c00; }
        .badge-t2 { background: #fff3d0; color: #b80; }
        .badge-t3 { background: #d0f0ff; color: #068; }
        .footer { margin-top: 20px; text-align: center; font-size: 9px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
    <h1>PANDORA — Laporan Deteksi Anomali</h1>
    <div class="subtitle">Periode: {{ $summary['dari'] }} s/d {{ $summary['sampai'] }} | Digenerate: {{ now()->format('d M Y H:i') }} WITA</div>

    <table style="width: auto; margin-bottom: 15px;">
        <tr>
            <td style="border: none; padding: 5px 20px 5px 0;"><strong style="font-size: 16px;">{{ $summary['total'] }}</strong><br><span style="color:#888;">Total</span></td>
            <td style="border: none; padding: 5px 20px 5px 0;"><strong style="font-size: 16px; color: #c00;">{{ $summary['tingkat1'] }}</strong><br><span style="color:#888;">Tingkat 1</span></td>
            <td style="border: none; padding: 5px 20px 5px 0;"><strong style="font-size: 16px; color: #068;">{{ $summary['tingkat3'] }}</strong><br><span style="color:#888;">Tingkat 3</span></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>T</th>
                <th>Pegawai</th>
                <th>NIP</th>
                <th>Instansi</th>
                <th>Tanggal</th>
                <th>Jenis</th>
                <th>Confidence</th>
                <th>Metode</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($anomalies as $a)
            <tr>
                <td><span class="badge {{ $a->tingkat == 1 ? 'badge-t1' : ($a->tingkat == 2 ? 'badge-t2' : 'badge-t3') }}">{{ $a->tingkat }}</span></td>
                <td>{{ $a->nama }}</td>
                <td>{{ $a->nip }}</td>
                <td>{{ Str::limit($a->nama_unit ?? '-', 25) }}</td>
                <td>{{ $a->tanggal }}</td>
                <td>{{ str_replace('_', ' ', $a->jenis_anomali) }}</td>
                <td>{{ round($a->confidence * 100) }}%</td>
                <td>{{ str_replace('_', ' ', $a->metode_deteksi) }}</td>
                <td>{{ $a->status_review }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        PANDORA — Portal Analitik Data Kehadiran ASN | DKISP Pemerintah Provinsi Kalimantan Utara<br>
        Dokumen ini digenerate secara otomatis. Data bersifat rahasia.
    </div>
</body>
</html>
