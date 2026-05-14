<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; margin: 20px; }
        h1 { font-size: 16px; margin-bottom: 3px; color: #1e3a5f; }
        .subtitle { color: #666; font-size: 11px; margin-bottom: 15px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 9px; font-weight: bold; }
        .badge-t1 { background: #ffe0e0; color: #c00; }
        .badge-t2 { background: #fff3d0; color: #b80; }
        .badge-t3 { background: #d0f0ff; color: #068; }

        .header-grid { width: 100%; margin-bottom: 15px; }
        .header-grid td { vertical-align: top; padding: 0; }

        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .info-table th { text-align: left; background: #f0f0f0; padding: 5px 8px; font-size: 10px; color: #666; text-transform: uppercase; width: 160px; border-bottom: 1px solid #ddd; }
        .info-table td { padding: 5px 8px; border-bottom: 1px solid #eee; font-size: 11px; }

        .section { margin-bottom: 15px; }
        .section-title { font-size: 12px; font-weight: bold; color: #1e3a5f; margin-bottom: 6px; padding-bottom: 3px; border-bottom: 2px solid #1e3a5f; }
        .section-body { font-size: 11px; line-height: 1.6; color: #444; }

        .narasi-box { background: #f8f9fa; border-left: 3px solid #1e3a5f; padding: 8px 12px; margin-bottom: 8px; font-size: 11px; line-height: 1.5; }
        .narasi-box.alert { background: #fff5f5; border-left-color: #c00; }
        .narasi-box.rekomendasi { background: #f0f7ff; border-left-color: #068; }

        table.pola { width: 100%; border-collapse: collapse; font-size: 10px; }
        table.pola th { background: #1e3a5f; color: white; padding: 4px 6px; text-align: left; font-size: 9px; text-transform: uppercase; }
        table.pola td { padding: 4px 6px; border-bottom: 1px solid #eee; }
        table.pola tr:nth-child(even) { background: #f9f9f9; }
        table.pola tr.highlight { background: #fff3d0; }

        .conf-bar { display: inline-block; height: 10px; border-radius: 2px; }
        .footer { margin-top: 25px; text-align: center; font-size: 9px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
        .metadata { background: #f5f5f5; padding: 6px 10px; border-radius: 3px; font-family: monospace; font-size: 9px; color: #555; word-break: break-all; }

        strong { color: #222; }
    </style>
</head>
<body>
    @php
        $tingkatLabels = [1 => 'Ketidakmungkinan Fisik', 2 => 'Pelanggaran Aturan', 3 => 'Anomali Statistik', 4 => 'Kandidat False Positive'];
        $conf = round($anomaly->confidence * 100);
    @endphp

    {{-- Header --}}
    <h1>PANDORA — Laporan Detail Anomali #{{ $anomaly->id }}</h1>
    <div class="subtitle">Digenerate: {{ now()->format('d M Y H:i') }} WITA</div>

    {{-- Info pegawai & anomali --}}
    <table class="info-table">
        <tr>
            <th>Pegawai</th>
            <td><strong>{{ $anomaly->nama }}</strong> ({{ $anomaly->nip }})</td>
        </tr>
        <tr>
            <th>Instansi</th>
            <td>{{ $anomaly->nama_unit ?? '-' }}</td>
        </tr>
        <tr>
            <th>Tanggal Anomali</th>
            <td>{{ \Carbon\Carbon::parse($anomaly->tanggal)->translatedFormat('l, d F Y') }}</td>
        </tr>
        <tr>
            <th>Tingkat</th>
            <td>
                <span class="badge {{ $anomaly->tingkat == 1 ? 'badge-t1' : ($anomaly->tingkat == 2 ? 'badge-t2' : 'badge-t3') }}">
                    Tingkat {{ $anomaly->tingkat }}
                </span>
                {{ $tingkatLabels[$anomaly->tingkat] ?? '-' }}
            </td>
        </tr>
        <tr>
            <th>Jenis Anomali</th>
            <td>{{ str_replace('_', ' ', $anomaly->jenis_anomali) }}</td>
        </tr>
        <tr>
            <th>Confidence</th>
            <td>
                <strong>{{ $conf }}%</strong>
                <span class="conf-bar" style="width: {{ $conf }}px; background: {{ $conf >= 70 ? '#c00' : ($conf >= 50 ? '#b80' : '#068') }};"></span>
            </td>
        </tr>
        <tr>
            <th>Metode Deteksi</th>
            <td>{{ str_replace('_', ' ', $anomaly->metode_deteksi) }}</td>
        </tr>
        <tr>
            <th>Status Review</th>
            <td>{{ str_replace('_', ' ', $anomaly->status_review) }}</td>
        </tr>
        @if($anomaly->catatan_review)
        <tr>
            <th>Catatan Review</th>
            <td>{{ $anomaly->catatan_review }}</td>
        </tr>
        @endif
    </table>

    {{-- Data Kehadiran --}}
    @if($rekap)
    <div class="section">
        <div class="section-title">Data Kehadiran</div>
        <table class="info-table">
            <tr>
                <th>Jam Masuk</th>
                <td>{{ $rekap->jam_masuk ? \Carbon\Carbon::parse($rekap->jam_masuk)->format('H:i:s') : '-' }}</td>
            </tr>
            <tr>
                <th>Jam Pulang</th>
                <td>{{ $rekap->jam_pulang ? \Carbon\Carbon::parse($rekap->jam_pulang)->format('H:i:s') : '-' }}</td>
            </tr>
            @if($rekap->lat_berangkat)
            <tr>
                <th>Lokasi Check-in</th>
                <td>
                    {{ $rekap->nama_lokasi_berangkat ?? '-' }}
                    <br><span style="color:#888; font-size:10px;">{{ $rekap->lat_berangkat }}, {{ $rekap->long_berangkat }}</span>
                    @if($geoBerangkat && $geoBerangkat['display'])
                        <br><span style="font-size:10px;">Nominatim: {{ $geoBerangkat['display'] }}</span>
                    @endif
                </td>
            </tr>
            @endif
            @if($rekap->lat_pulang)
            <tr>
                <th>Lokasi Check-out</th>
                <td>
                    {{ $rekap->nama_lokasi_pulang ?? '-' }}
                    <br><span style="color:#888; font-size:10px;">{{ $rekap->lat_pulang }}, {{ $rekap->long_pulang }}</span>
                    @if($geoPulang && $geoPulang['display'])
                        <br><span style="font-size:10px;">Nominatim: {{ $geoPulang['display'] }}</span>
                    @endif
                </td>
            </tr>
            @endif
            @if(!$rekap->lat_berangkat && !$rekap->lat_pulang)
            <tr>
                <th>Lokasi</th>
                <td style="color:#888;">Tidak tersedia (mesin fingerprint)</td>
            </tr>
            @endif
        </table>
    </div>
    @endif

    {{-- Narasi Analisis --}}
    <div class="section">
        <div class="section-title">Analisis</div>

        <div class="narasi-box">
            <strong>Ringkasan:</strong> {!! strip_tags(\Illuminate\Support\Str::inlineMarkdown($narasi['ringkasan']), '<strong><em>') !!}
        </div>

        @if(isset($narasi['lokasi_alert']))
        <div class="narasi-box alert">
            <strong>Lokasi di Luar Kaltara:</strong> {!! strip_tags(\Illuminate\Support\Str::inlineMarkdown($narasi['lokasi_alert']), '<strong><em>') !!}
        </div>
        @endif

        <div class="narasi-box">
            <strong>Confidence:</strong> {!! strip_tags(\Illuminate\Support\Str::inlineMarkdown($narasi['confidence']), '<strong><em>') !!}
        </div>

        <div class="narasi-box">
            <strong>Mengapa Terdeteksi:</strong> {!! strip_tags(\Illuminate\Support\Str::inlineMarkdown($narasi['jenis']), '<strong><em>') !!}
        </div>

        @if(isset($narasi['kehadiran']))
        <div class="narasi-box">
            <strong>Kehadiran:</strong> {!! strip_tags(\Illuminate\Support\Str::inlineMarkdown($narasi['kehadiran']), '<strong><em>') !!}
        </div>
        @endif

        @if(isset($narasi['ijin']))
        <div class="narasi-box">
            <strong>Status Ijin:</strong> {!! strip_tags(\Illuminate\Support\Str::inlineMarkdown($narasi['ijin']), '<strong><em>') !!}
        </div>
        @endif

        <div class="narasi-box">
            <strong>Pola Historis:</strong> {!! strip_tags(\Illuminate\Support\Str::inlineMarkdown($narasi['pola']), '<strong><em>') !!}
        </div>

        <div class="narasi-box rekomendasi">
            {!! strip_tags(\Illuminate\Support\Str::inlineMarkdown($narasi['rekomendasi']), '<strong><em>') !!}
        </div>
    </div>

    {{-- Pola Kehadiran 14 Hari --}}
    @if($polaKehadiran->count() > 0)
    <div class="section">
        <div class="section-title">Kehadiran & Lokasi 14 Hari Terakhir</div>
        <table class="pola">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Masuk</th>
                    <th>Pulang</th>
                    <th>Status</th>
                    <th>Lokasi Check-in</th>
                </tr>
            </thead>
            <tbody>
                @foreach($polaKehadiran as $pk)
                @php
                    $isToday = $pk->tanggal === $anomaly->tanggal;
                    $hasJamMasuk = $pk->jam_masuk !== null;
                    $statusLabel = '-';
                    if ($pk->dl == 1) $statusLabel = 'DL';
                    elseif ($pk->i == 1) $statusLabel = 'Izin';
                    elseif ($pk->s == 1) $statusLabel = 'Sakit';
                    elseif ($pk->c == 1) $statusLabel = 'Cuti';
                    elseif ($pk->tw == 1 || ($hasJamMasuk && $pk->jam_masuk <= '07:30:00')) $statusLabel = 'TW';
                    elseif ($pk->mkttw == 1 || ($hasJamMasuk && $pk->jam_masuk > '07:30:00')) $statusLabel = 'Terlambat';
                    elseif ($pk->tk == 1 || !$hasJamMasuk) $statusLabel = 'TK';
                @endphp
                <tr class="{{ $isToday ? 'highlight' : '' }}">
                    <td>{{ \Carbon\Carbon::parse($pk->tanggal)->format('d M (D)') }}{{ $isToday ? ' *' : '' }}</td>
                    <td>{{ $hasJamMasuk ? \Carbon\Carbon::parse($pk->jam_masuk)->format('H:i') : '--:--' }}</td>
                    <td>{{ $pk->jam_pulang ? \Carbon\Carbon::parse($pk->jam_pulang)->format('H:i') : '--:--' }}</td>
                    <td>{{ $statusLabel }}</td>
                    <td>
                        @if($pk->lat_berangkat)
                            {{ $pk->nama_lokasi_berangkat ?? '' }}
                            @if($pk->geo_berangkat && $pk->geo_berangkat['display'])
                                ({{ $pk->geo_berangkat['display'] }})
                            @endif
                            @if($pk->diluar_kaltara)
                                <strong style="color:#c00;">LUAR KALTARA</strong>
                            @endif
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <p style="font-size:9px; color:#888; margin-top:3px;">* Tanggal anomali ditandai dengan highlight</p>
    </div>
    @endif

    {{-- Riwayat Anomali 30 Hari --}}
    @if($riwayat->count() > 0)
    <div class="section">
        <div class="section-title">Riwayat Anomali (30 Hari)</div>
        <table class="pola">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tanggal</th>
                    <th>Jenis</th>
                    <th>Tingkat</th>
                    <th>Confidence</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($riwayat as $rw)
                <tr class="{{ $rw->id == $anomaly->id ? 'highlight' : '' }}">
                    <td>#{{ $rw->id }}</td>
                    <td>{{ $rw->tanggal }}</td>
                    <td>{{ str_replace('_', ' ', $rw->jenis_anomali) }}</td>
                    <td><span class="badge {{ $rw->tingkat == 1 ? 'badge-t1' : ($rw->tingkat == 2 ? 'badge-t2' : 'badge-t3') }}">{{ $rw->tingkat }}</span></td>
                    <td>{{ round($rw->confidence * 100) }}%</td>
                    <td>{{ str_replace('_', ' ', $rw->status_review) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Metadata Teknis --}}
    <div class="section">
        <div class="section-title">Metadata Teknis</div>
        <div class="metadata">{{ json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</div>

        @if($features)
        <p style="font-size:10px; color:#666; margin: 8px 0 3px;">Feature Engineering:</p>
        <table class="pola">
            <thead><tr><th>Fitur</th><th>Nilai</th></tr></thead>
            <tbody>
                @foreach(['velocity_berangkat_pulang','velocity_vs_kemarin','jarak_dari_geofence_berangkat','jarak_dari_geofence_pulang','geofence_match_flag','sumber_presensi','deviasi_masuk_vs_jadwal_ekspektasi','deviasi_pulang_vs_jadwal_ekspektasi','deviasi_waktu_masuk_vs_median_personal','deviasi_waktu_masuk_vs_median_unit'] as $fk)
                    @if(isset($features->$fk) && $features->$fk !== null)
                    <tr>
                        <td>{{ str_replace('_', ' ', $fk) }}</td>
                        <td style="font-family: monospace;">{{ is_numeric($features->$fk) ? round($features->$fk, 2) : $features->$fk }}</td>
                    </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <div class="footer">
        PANDORA — Portal Analitik Data Kehadiran ASN | DKISP Pemerintah Provinsi Kalimantan Utara<br>
        Dokumen ini digenerate secara otomatis. Data bersifat rahasia.
    </div>
</body>
</html>
