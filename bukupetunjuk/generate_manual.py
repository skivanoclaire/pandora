#!/usr/bin/env python3
"""
Generate Buku Petunjuk Admin PANDORA dalam format PDF.
Menggunakan fpdf2.
"""

from fpdf import FPDF
import os

class ManualPDF(FPDF):
    ACCENT = (99, 102, 241)      # indigo
    DARK = (30, 30, 46)
    TEXT = (55, 55, 75)
    LIGHT_BG = (245, 245, 250)
    WHITE = (255, 255, 255)
    DANGER = (220, 50, 50)
    SUCCESS = (34, 160, 90)
    GOLD = (200, 140, 20)
    BLUE = (0, 120, 215)

    def __init__(self):
        super().__init__(orientation="P", unit="mm", format="A4")
        self.set_auto_page_break(auto=True, margin=20)
        self._setup_fonts()
        self.chapter_num = 0

    def _setup_fonts(self):
        self.add_font("dejavu", "", "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf", uni=True)
        self.add_font("dejavu", "B", "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf", uni=True)
        self.add_font("dejavu", "I", "/usr/share/fonts/truetype/dejavu/DejaVuSans-Oblique.ttf", uni=True)
        self.add_font("dejavumono", "", "/usr/share/fonts/truetype/dejavu/DejaVuSansMono.ttf", uni=True)

    # ── Header / Footer ──
    def header(self):
        if self.page_no() == 1:
            return
        self.set_font("dejavu", "I", 7)
        self.set_text_color(*self.TEXT)
        self.cell(0, 6, "Buku Petunjuk Admin — PANDORA v1.0", align="L")
        self.cell(0, 6, f"Halaman {self.page_no()}", align="R", new_x="LMARGIN", new_y="NEXT")
        self.set_draw_color(*self.ACCENT)
        self.set_line_width(0.3)
        self.line(10, self.get_y(), 200, self.get_y())
        self.ln(4)

    def footer(self):
        self.set_y(-15)
        self.set_font("dejavu", "I", 7)
        self.set_text_color(150, 150, 150)
        self.cell(0, 10, "© 2026 PANDORA — Pemerintah Provinsi Kalimantan Utara", align="C")

    # ── Helpers ──
    def _section_bg(self, h=8):
        self.set_fill_color(*self.LIGHT_BG)
        self.rect(10, self.get_y(), 190, h, "F")

    def cover_page(self):
        self.add_page()
        self.ln(50)
        self.set_font("dejavu", "B", 28)
        self.set_text_color(*self.ACCENT)
        self.cell(0, 14, "PANDORA", align="C", new_x="LMARGIN", new_y="NEXT")
        self.set_font("dejavu", "", 11)
        self.set_text_color(*self.TEXT)
        self.cell(0, 8, "Portal Analitik Data Kehadiran ASN", align="C", new_x="LMARGIN", new_y="NEXT")
        self.ln(12)
        self.set_draw_color(*self.ACCENT)
        self.set_line_width(0.6)
        self.line(70, self.get_y(), 140, self.get_y())
        self.ln(12)
        self.set_font("dejavu", "B", 18)
        self.set_text_color(*self.DARK)
        self.cell(0, 12, "Buku Petunjuk Penggunaan", align="C", new_x="LMARGIN", new_y="NEXT")
        self.set_font("dejavu", "", 12)
        self.cell(0, 8, "Level Administrator", align="C", new_x="LMARGIN", new_y="NEXT")
        self.ln(30)
        self.set_font("dejavu", "", 10)
        self.set_text_color(*self.TEXT)
        info = [
            "Versi Dokumen: 1.0",
            "Tanggal: 20 April 2026",
            "Domain: pandora.kaltaraprov.go.id",
            "Pemerintah Provinsi Kalimantan Utara",
        ]
        for line in info:
            self.cell(0, 7, line, align="C", new_x="LMARGIN", new_y="NEXT")

    def toc_page(self):
        self.add_page()
        self.set_font("dejavu", "B", 16)
        self.set_text_color(*self.DARK)
        self.cell(0, 12, "Daftar Isi", new_x="LMARGIN", new_y="NEXT")
        self.ln(4)
        toc = [
            ("1", "Pendahuluan", "3"),
            ("2", "Login & Navigasi", "4"),
            ("3", "Dashboard", "5"),
            ("4", "Master Data", "8"),
            ("  4.1", "Master Instansi", "8"),
            ("  4.2", "Master Pegawai", "9"),
            ("  4.3", "Zona Geofence", "10"),
            ("  4.4", "Whitelist Pegawai", "11"),
            ("5", "Kehadiran", "12"),
            ("  5.1", "Rekap Kehadiran Harian", "12"),
            ("  5.2", "Log Presensi", "13"),
            ("6", "Analitik", "14"),
            ("  6.1", "Tren Kehadiran", "14"),
            ("  6.2", "Detail Harian", "16"),
            ("  6.3", "Deteksi Anomali", "17"),
            ("  6.4", "Detail Anomali & Review", "19"),
            ("  6.5", "Clustering & Pola Spasial", "22"),
            ("7", "Sinkronisasi Data", "24"),
            ("8", "Integritas Ledger (Blockchain)", "25"),
            ("9", "Literasi Data", "26"),
            ("10", "Pengaturan Sistem", "27"),
            ("  10.1", "Manajemen Pengguna", "27"),
            ("  10.2", "Audit Trail", "28"),
            ("11", "Perintah Artisan (CLI)", "29"),
            ("12", "Glosarium & Kode Status", "30"),
        ]
        self.set_font("dejavu", "", 10)
        for num, title, pg in toc:
            self.set_text_color(*self.DARK)
            bold = not num.startswith(" ")
            self.set_font("dejavu", "B" if bold else "", 10 if bold else 9)
            self.cell(12, 6, num.strip())
            self.cell(150, 6, title)
            self.set_text_color(*self.ACCENT)
            self.cell(0, 6, pg, align="R", new_x="LMARGIN", new_y="NEXT")

    def chapter_title(self, title):
        self.chapter_num += 1
        self.add_page()
        self.set_fill_color(*self.ACCENT)
        self.rect(10, self.get_y(), 190, 12, "F")
        self.set_font("dejavu", "B", 14)
        self.set_text_color(*self.WHITE)
        self.cell(0, 12, f"  {self.chapter_num}. {title}", new_x="LMARGIN", new_y="NEXT")
        self.ln(6)
        self.set_text_color(*self.TEXT)

    def sub_title(self, num, title):
        self.ln(3)
        if self.get_y() > 260:
            self.add_page()
        self.set_font("dejavu", "B", 12)
        self.set_text_color(*self.ACCENT)
        self.cell(0, 9, f"{num}  {title}", new_x="LMARGIN", new_y="NEXT")
        self.set_text_color(*self.TEXT)
        self.ln(2)

    def sub_sub_title(self, title):
        self.ln(2)
        if self.get_y() > 265:
            self.add_page()
        self.set_font("dejavu", "B", 10)
        self.set_text_color(*self.DARK)
        self.cell(0, 7, title, new_x="LMARGIN", new_y="NEXT")
        self.set_text_color(*self.TEXT)
        self.ln(1)

    def para(self, text):
        self.set_font("dejavu", "", 9)
        self.set_text_color(*self.TEXT)
        self.multi_cell(0, 5.5, text)
        self.ln(2)

    def bullet(self, text, indent=10):
        x = self.get_x()
        self.set_font("dejavu", "", 9)
        self.set_text_color(*self.TEXT)
        self.set_x(x + indent)
        self.cell(4, 5.5, "•")
        self.multi_cell(0, 5.5, text)
        self.set_x(x)

    def bold_bullet(self, label, desc, indent=10):
        x = self.get_x()
        self.set_x(x + indent)
        self.set_font("dejavu", "", 9)
        self.cell(4, 5.5, "•")
        self.set_font("dejavu", "B", 9)
        self.cell(self.get_string_width(label) + 1, 5.5, label)
        self.set_font("dejavu", "", 9)
        self.multi_cell(0, 5.5, f" — {desc}")
        self.set_x(x)

    def info_box(self, title, text):
        self.ln(2)
        y = self.get_y()
        self.set_fill_color(235, 240, 255)
        self.set_draw_color(*self.ACCENT)
        self.rect(12, y, 186, 5, "D")
        self.set_font("dejavu", "B", 8)
        self.set_text_color(*self.ACCENT)
        self.set_xy(14, y)
        self.cell(0, 5, f"ℹ  {title}")
        self.ln(6)
        self.set_font("dejavu", "", 8)
        self.set_text_color(*self.TEXT)
        self.set_x(14)
        self.multi_cell(182, 4.5, text)
        self.ln(3)

    def step(self, num, text):
        self.set_font("dejavu", "B", 9)
        self.set_text_color(*self.ACCENT)
        self.cell(8, 5.5, f"{num}.")
        self.set_font("dejavu", "", 9)
        self.set_text_color(*self.TEXT)
        self.multi_cell(0, 5.5, text)

    def url_text(self, url):
        self.set_font("dejavumono", "", 8)
        self.set_text_color(*self.BLUE)
        self.cell(0, 5, url, new_x="LMARGIN", new_y="NEXT")
        self.set_text_color(*self.TEXT)
        self.set_font("dejavu", "", 9)


def build():
    pdf = ManualPDF()

    # ── Cover ──
    pdf.cover_page()

    # ── Daftar Isi ──
    pdf.toc_page()

    # ══════════════════════════════════════════════
    # BAB 1: PENDAHULUAN
    # ══════════════════════════════════════════════
    pdf.chapter_title("Pendahuluan")
    pdf.para(
        "PANDORA (Portal Analitik Data Kehadiran ASN) adalah sistem web portal untuk menganalisis "
        "data kehadiran Aparatur Sipil Negara (ASN) di lingkungan Pemerintah Provinsi Kalimantan Utara. "
        "Sistem ini mengintegrasikan data dari SIKARA/SIMPEG dan menerapkan teknologi machine learning "
        "serta rule engine untuk mendeteksi anomali kehadiran secara otomatis."
    )
    pdf.sub_sub_title("Tujuan Sistem")
    pdf.bullet("Menyediakan dashboard analitik kehadiran ASN secara real-time.")
    pdf.bullet("Mendeteksi anomali kehadiran menggunakan AI dan rule-based engine.")
    pdf.bullet("Menyediakan audit trail dan integritas data melalui blockchain (OpenTimestamps/Bitcoin).")
    pdf.bullet("Membantu pengambilan keputusan terkait disiplin kehadiran ASN.")

    pdf.sub_sub_title("Arsitektur Sistem")
    pdf.para(
        "PANDORA terdiri dari dua layanan utama: (1) Laravel sebagai web portal dan orchestrator, "
        "dan (2) FastAPI (Python) sebagai analytics engine. Keduanya berkomunikasi melalui HTTP internal "
        "di dalam jaringan Docker. Data disimpan di PostgreSQL 16 + PostGIS 3.4, dengan Redis sebagai "
        "cache dan queue driver."
    )

    pdf.sub_sub_title("Peran Pengguna")
    pdf.bold_bullet("Admin", "Akses penuh ke seluruh fitur termasuk manajemen pengguna dan pengaturan sistem.")
    pdf.bold_bullet("HR", "Akses ke dashboard, kehadiran, analitik, master data, sinkronisasi, dan literasi data.")
    pdf.bold_bullet("Pimpinan", "Akses ke dashboard eksekutif dan analitik dengan ringkasan visual.")
    pdf.ln(2)
    pdf.info_box("Catatan", "Buku petunjuk ini ditujukan untuk pengguna dengan peran Admin yang memiliki akses penuh ke seluruh fitur sistem.")

    # ══════════════════════════════════════════════
    # BAB 2: LOGIN & NAVIGASI
    # ══════════════════════════════════════════════
    pdf.chapter_title("Login & Navigasi")

    pdf.sub_sub_title("Cara Login")
    pdf.step(1, "Buka browser dan akses pandora.kaltaraprov.go.id")
    pdf.step(2, "Masukkan alamat email dan password yang telah didaftarkan oleh administrator.")
    pdf.step(3, "Klik tombol \"Masuk\" untuk masuk ke sistem.")
    pdf.ln(2)

    pdf.sub_sub_title("Navigasi Sidebar")
    pdf.para("Setelah login, Anda akan melihat sidebar navigasi di sisi kiri layar dengan menu berikut:")
    pdf.bold_bullet("Dashboard", "Halaman utama dengan ringkasan kehadiran dan anomali.")
    pdf.bold_bullet("Master Data", "Submenu: Instansi, Pegawai, Zona Geofence, Whitelist Pegawai.")
    pdf.bold_bullet("Kehadiran", "Submenu: Rekap Harian, Log Presensi.")
    pdf.bold_bullet("Analitik", "Submenu: Tren Kehadiran, Deteksi Anomali, Clustering.")
    pdf.bold_bullet("Sinkronisasi", "Monitoring status sinkronisasi data dari SIMPEG/SIKARA.")
    pdf.bold_bullet("Literasi Data", "Pusat pengetahuan tentang konsep data science yang digunakan.")
    pdf.bold_bullet("Integritas", "Verifikasi integritas data melalui blockchain.")
    pdf.bold_bullet("Pengaturan", "Manajemen pengguna dan audit trail (khusus Admin).")
    pdf.ln(2)
    pdf.para("Pada layar mobile, sidebar dapat dibuka dengan menekan ikon hamburger (☰) di pojok kiri atas. "
             "Profil pengguna beserta peran (admin/hr/pimpinan) ditampilkan di bagian bawah sidebar.")

    # ══════════════════════════════════════════════
    # BAB 3: DASHBOARD
    # ══════════════════════════════════════════════
    pdf.chapter_title("Dashboard")

    pdf.sub_title("3.1", "Dashboard HR/Admin")
    pdf.url_text("pandora.kaltaraprov.go.id/dashboard")
    pdf.para(
        "Dashboard utama menampilkan ringkasan kehadiran ASN secara real-time. Pada hari libur atau "
        "akhir pekan, sistem otomatis menampilkan data hari kerja terakhir beserta keterangan."
    )

    pdf.sub_sub_title("Kartu Statistik")
    pdf.para("Empat kartu utama ditampilkan di bagian atas:")
    pdf.bold_bullet("Total Pegawai Aktif", "Jumlah seluruh ASN aktif yang terdaftar dalam sistem.")
    pdf.bold_bullet("Hadir Hari Ini", "Jumlah dan persentase pegawai yang hadir, dilengkapi perubahan (delta) dibanding hari sebelumnya.")
    pdf.bold_bullet("Terlambat", "Jumlah pegawai yang terlambat masuk kerja hari ini.")
    pdf.bold_bullet("Tanpa Keterangan", "Jumlah pegawai yang tidak hadir tanpa izin/surat keterangan.")

    pdf.sub_sub_title("Tren Kehadiran 7 Hari")
    pdf.para("Grafik garis menampilkan tren persentase kehadiran selama 7 hari kerja terakhir. "
             "Sumbu kiri menunjukkan persentase kehadiran, sumbu kanan menunjukkan jumlah hadir.")

    pdf.sub_sub_title("Peta Anomali")
    pdf.para(
        "Peta interaktif (Leaflet) menampilkan hingga 200 titik anomali yang belum direview, "
        "diurutkan berdasarkan confidence tertinggi. Warna marker menunjukkan tingkat keparahan:"
    )
    pdf.bold_bullet("Merah", "Tingkat 1 — Ketidakmungkinan Fisik (paling serius).")
    pdf.bold_bullet("Oranye", "Tingkat 2 — Pelanggaran Aturan.")
    pdf.bold_bullet("Biru", "Tingkat 3 — Anomali Statistik (ML-detected).")
    pdf.para("Klik marker untuk melihat detail singkat (jenis anomali, confidence, koordinat) dan tombol \"Lihat Detail\" untuk menuju halaman detail anomali.")

    pdf.sub_sub_title("Anomali Terbaru")
    pdf.para("Panel kanan menampilkan 10 anomali terbaru yang belum direview, mencakup nama pegawai, "
             "NIP, tanggal, jenis anomali, dan persentase confidence.")

    pdf.sub_sub_title("Kehadiran per OPD")
    pdf.para("Daftar seluruh OPD/instansi dengan progress bar persentase kehadiran. "
             "Kode warna: hijau (>80%), oranye (60-80%), merah (<60%). "
             "OPD di bawah 80% ditandai sebagai peringatan.")

    pdf.sub_sub_title("Ranking OPD")
    pdf.para("Dua panel menampilkan Top 10 OPD Terbaik dan Top 10 OPD Terendah berdasarkan persentase kehadiran.")

    pdf.sub_sub_title("Narasi Otomatis")
    pdf.para("Sistem menghasilkan narasi ringkasan secara otomatis berdasarkan data hari ini, "
             "termasuk informasi hari libur, persentase kehadiran, dan jumlah anomali pending.")

    pdf.sub_title("3.2", "Dashboard Pimpinan")
    pdf.para(
        "Dashboard khusus untuk peran pimpinan menampilkan tampilan eksekutif yang lebih ringkas:"
    )
    pdf.bold_bullet("Skor Kesehatan", "Gauge visual (0-100) menunjukkan skor disiplin kehadiran secara keseluruhan. Hijau (>80), oranye (60-80), merah (<60).")
    pdf.bold_bullet("Quick Stats", "Tiga kartu: hadir hari ini, perubahan vs minggu lalu, dan jumlah alert kritis (Tingkat 1).")
    pdf.bold_bullet("5 OPD Terbaik & Terburuk", "Ranking singkat OPD berdasarkan performa kehadiran.")
    pdf.bold_bullet("Alert Kritis", "Daftar anomali Tingkat 1 yang memerlukan perhatian segera.")
    pdf.bold_bullet("Tren 4 Minggu", "Perbandingan kehadiran empat minggu terakhir.")

    # ══════════════════════════════════════════════
    # BAB 4: MASTER DATA
    # ══════════════════════════════════════════════
    pdf.chapter_title("Master Data")

    # 4.1 Instansi
    pdf.sub_title("4.1", "Master Instansi")
    pdf.url_text("pandora.kaltaraprov.go.id/master/instansi")
    pdf.para("Halaman ini menampilkan seluruh unit organisasi/instansi yang tersinkronisasi dari SIKARA.")

    pdf.sub_sub_title("Fitur")
    pdf.bullet("Menampilkan daftar instansi dengan kode, nama, level organisasi, dan jumlah pegawai.")
    pdf.bullet("Filter berdasarkan level unit (dropdown).")
    pdf.bullet("Pencarian berdasarkan nama instansi (case-insensitive).")
    pdf.bullet("Paginasi 30 item per halaman.")
    pdf.ln(2)
    pdf.info_box("Catatan", "Data instansi bersumber dari SIKARA dan bersifat read-only. Perubahan harus dilakukan di sistem SIKARA/SIMPEG.")

    # 4.2 Pegawai
    pdf.sub_title("4.2", "Master Pegawai")
    pdf.url_text("pandora.kaltaraprov.go.id/master/pegawai")
    pdf.para("Halaman ini menampilkan seluruh pegawai ASN yang terdaftar dalam sistem.")

    pdf.sub_sub_title("Fitur")
    pdf.bullet("Menampilkan NIP, nama, unit, status kepegawaian (PNS/CPNS/PPPK/dll), dan flag bebas lokasi.")
    pdf.bullet("Pencarian berdasarkan NIP atau nama pegawai.")
    pdf.bullet("Filter berdasarkan unit/OPD.")
    pdf.bullet("Statistik: total pegawai dan jumlah pegawai dengan status \"bebas lokasi\".")
    pdf.bullet("Paginasi 30 item per halaman.")

    # 4.3 Geofence
    pdf.sub_title("4.3", "Zona Geofence")
    pdf.url_text("pandora.kaltaraprov.go.id/master/geofence")
    pdf.para("Geofence adalah batas wilayah geografis untuk memvalidasi lokasi check-in/check-out pegawai. "
             "Halaman ini dibagi menjadi dua tab:")

    pdf.sub_sub_title("Tab 1: Lokasi Geofence dari SIKARA")
    pdf.para("Menampilkan daftar lokasi geofence yang tersinkronisasi dari SIKARA, mencakup:")
    pdf.bullet("Nama lokasi, koordinat (lat/lng), radius (meter), status aktif/nonaktif.")
    pdf.bullet("Jumlah unit yang diasosiasikan dengan tiap lokasi (hover untuk melihat daftar).")
    pdf.bullet("Peta interaktif menampilkan semua lokasi geofence dengan lingkaran radius.")

    pdf.sub_sub_title("Tab 2: Zona Geofence PANDORA")
    pdf.para("Admin dapat membuat zona geofence tambahan milik PANDORA dengan aturan hari/jam khusus:")
    pdf.step(1, "Klik tombol \"+ Tambah Zona\".")
    pdf.step(2, "Isi nama zona, latitude, longitude, dan radius (10-10.000 meter).")
    pdf.step(3, "Klik \"Simpan\" untuk membuat zona baru.")
    pdf.ln(1)
    pdf.para("Zona PANDORA dapat dihapus dengan mengklik tombol \"Hapus\" pada baris zona yang diinginkan. "
             "Setiap pembuatan dan penghapusan zona tercatat dalam audit trail.")

    # 4.4 Whitelist
    pdf.sub_title("4.4", "Whitelist Pegawai")
    pdf.url_text("pandora.kaltaraprov.go.id/master/whitelist")
    pdf.para("Whitelist digunakan untuk mengecualikan pegawai tertentu dari deteksi anomali, misalnya karena "
             "pegawai tersebut bertugas di lapangan atau memiliki dispensasi khusus.")

    pdf.sub_sub_title("Jenis Whitelist")
    pdf.bold_bullet("Bebas Lokasi", "Pegawai boleh check-in dari lokasi mana saja.")
    pdf.bold_bullet("Dispensasi Khusus", "Pengecualian berdasarkan keputusan pimpinan.")
    pdf.bold_bullet("Tugas Lapangan", "Pegawai yang bertugas di luar kantor secara rutin.")
    pdf.bold_bullet("Lainnya", "Pengecualian dengan alasan lain.")

    pdf.sub_sub_title("Cara Menambah Whitelist")
    pdf.step(1, "Klik tombol \"+ Tambah Whitelist\".")
    pdf.step(2, "Cari pegawai berdasarkan NIP atau nama (autocomplete).")
    pdf.step(3, "Pilih jenis whitelist dari dropdown.")
    pdf.step(4, "Isi alasan pengecualian.")
    pdf.step(5, "Tentukan tanggal mulai berlaku dan tanggal berakhir (kosongkan untuk permanen).")
    pdf.step(6, "Klik \"Simpan\".")
    pdf.ln(1)
    pdf.para("Whitelist dapat dihapus kapan saja. Seluruh operasi tercatat di audit trail.")

    # ══════════════════════════════════════════════
    # BAB 5: KEHADIRAN
    # ══════════════════════════════════════════════
    pdf.chapter_title("Kehadiran")

    pdf.sub_title("5.1", "Rekap Kehadiran Harian")
    pdf.url_text("pandora.kaltaraprov.go.id/kehadiran/rekap")
    pdf.para("Menampilkan rekap kehadiran seluruh pegawai untuk tanggal tertentu.")

    pdf.sub_sub_title("Cara Menggunakan")
    pdf.step(1, "Pilih tanggal yang diinginkan pada date picker.")
    pdf.step(2, "Opsional: filter berdasarkan unit/OPD.")
    pdf.step(3, "Klik \"Tampilkan\" untuk memuat data.")
    pdf.ln(1)

    pdf.sub_sub_title("Informasi yang Ditampilkan")
    pdf.para("Ringkasan statistik di bagian atas menampilkan 5 kartu:")
    pdf.bold_bullet("Tepat Waktu (TW)", "Jumlah pegawai yang datang tepat waktu.")
    pdf.bold_bullet("Terlambat (MKTTW)", "Jumlah pegawai yang terlambat.")
    pdf.bold_bullet("Tidak Hadir (TK)", "Jumlah pegawai tanpa kehadiran.")
    pdf.bold_bullet("Izin/Sakit/Cuti", "Pegawai yang absen dengan keterangan sah.")
    pdf.bold_bullet("DL/Dispensasi", "Pegawai dinas luar atau mendapat dispensasi.")
    pdf.ln(1)
    pdf.para("Tabel detail menampilkan: NIP, nama, unit, jam masuk, jam pulang, status, dan lokasi check-in. "
             "Data dipaginasi 50 item per halaman.")

    pdf.sub_title("5.2", "Log Presensi")
    pdf.url_text("pandora.kaltaraprov.go.id/kehadiran/log")
    pdf.para("Menampilkan catatan presensi mentah (raw log) dari mesin fingerprint dan aplikasi mobile.")

    pdf.sub_sub_title("Fitur")
    pdf.bullet("Filter berdasarkan tanggal dan pencarian NIP/nama.")
    pdf.bullet("Menampilkan jam masuk, jam pulang, lokasi masuk dan pulang (termasuk koordinat GPS).")
    pdf.bullet("Nama lokasi hasil reverse geocoding ditampilkan jika tersedia.")
    pdf.bullet("Paginasi 50 item per halaman.")

    # ══════════════════════════════════════════════
    # BAB 6: ANALITIK
    # ══════════════════════════════════════════════
    pdf.chapter_title("Analitik")

    # 6.1 Tren
    pdf.sub_title("6.1", "Tren Kehadiran")
    pdf.url_text("pandora.kaltaraprov.go.id/analitik/tren")
    pdf.para("Halaman tren menampilkan analisis kehadiran dalam rentang waktu yang dapat dipilih: "
             "7, 14, 30, 60, 90, 180, atau 365 hari. Hari libur dan akhir pekan otomatis dikecualikan.")

    pdf.sub_sub_title("Grafik Tren")
    pdf.bullet("Grafik garis: persentase kehadiran harian (sumbu kiri) dan jumlah hadir (sumbu kanan).")
    pdf.bullet("Grafik batang bertumpuk: komposisi harian (hadir, terlambat, tidak hadir) selama 7 hari terakhir.")

    pdf.sub_sub_title("Tabel Detail")
    pdf.para("Tabel menampilkan breakdown harian yang mencakup:")
    pdf.bullet("Tanggal, total pegawai, hadir, terlambat, tidak hadir.")
    pdf.bullet("Rincian izin: Dinas Luar/Dalam, Cuti, Sakit, Izin, Dispensasi, Diklat.")
    pdf.bullet("Tanpa Keterangan (pegawai yang tidak hadir tanpa alasan sah).")
    pdf.bullet("Persentase kehadiran dengan kode warna (hijau/oranye/merah).")
    pdf.para("Setiap tanggal bisa diklik untuk masuk ke halaman detail harian.")

    # 6.2 Detail Harian
    pdf.sub_title("6.2", "Detail Kehadiran Harian")
    pdf.url_text("pandora.kaltaraprov.go.id/analitik/tren/{tanggal}")
    pdf.para("Halaman detail untuk satu hari tertentu, diakses dari tabel tren kehadiran.")

    pdf.sub_sub_title("Konten Halaman")
    pdf.bold_bullet("Statistik Ringkasan", "4 kartu: total pegawai, hadir, terlambat, tidak hadir.")
    pdf.bold_bullet("Rincian Ketidakhadiran", "7 kategori: Dinas Luar, Dinas Dalam, Cuti, Sakit, Dispensasi, Diklat, Tanpa Keterangan.")
    pdf.bold_bullet("Ranking Instansi Terlambat", "Peringkat OPD dengan pegawai terlambat terbanyak, dilengkapi persentase dan rata-rata menit keterlambatan.")
    pdf.bold_bullet("Daftar Pegawai Terlambat", "Nama, NIP, unit, jam masuk, durasi keterlambatan, dan lokasi check-in.")
    pdf.bold_bullet("Daftar Tidak Hadir", "Pegawai yang tidak hadir tanpa keterangan sah.")

    pdf.sub_sub_title("Halaman Turunan")
    pdf.para("Dari halaman detail, tersedia link ke halaman-halaman berikut:")
    pdf.bullet("Dinas Luar/Dalam: daftar pegawai yang berstatus dinas pada tanggal tersebut.")
    pdf.bullet("Izin per Kategori: cuti, sakit, izin, dispensasi, diklat — masing-masing dengan detail tanggal berlaku dan alasan.")
    pdf.bullet("Tanpa Keterangan: pegawai yang tidak hadir dan tidak memiliki izin apa pun di sistem.")

    # 6.3 Deteksi Anomali
    pdf.sub_title("6.3", "Deteksi Anomali")
    pdf.url_text("pandora.kaltaraprov.go.id/analitik/anomali")
    pdf.para(
        "Fitur utama PANDORA. Halaman ini menampilkan seluruh anomali yang terdeteksi oleh sistem, "
        "baik melalui rule engine maupun machine learning."
    )

    pdf.sub_sub_title("Tingkat Anomali")
    pdf.bold_bullet("Tingkat 1 — Ketidakmungkinan Fisik", "Anomali yang secara fisik tidak mungkin terjadi. Contoh: kecepatan perjalanan >300 km/jam (fake GPS), koordinat di luar wilayah NKRI, koordinat identik selama 3+ hari berturut-turut.")
    pdf.bold_bullet("Tingkat 2 — Pelanggaran Aturan", "Pelanggaran terhadap aturan kehadiran. Contoh: check-in di luar zona geofence yang ditetapkan, check-in di lokasi unit lain, absensi pada hari yang seharusnya dinas luar.")
    pdf.bold_bullet("Tingkat 3 — Anomali Statistik", "Pola yang secara statistik menyimpang dari kebiasaan, terdeteksi oleh model Isolation Forest dan DBSCAN. Contoh: kombinasi fitur yang tidak lazim (waktu, lokasi, jarak).")
    pdf.bold_bullet("Tingkat 4 — Kandidat False Positive", "Deteksi yang kemungkinan besar memiliki konteks legitimate. Contoh: pegawai bebas lokasi, SK dinas luar retroaktif.")

    pdf.sub_sub_title("Filter & Pencarian")
    pdf.para("Anomali dapat difilter berdasarkan:")
    pdf.bullet("Tingkat (1-4)")
    pdf.bullet("Jenis: fake_gps, geofence_violation, velocity_outlier, temporal_outlier, combination")
    pdf.bullet("Status: Belum Direview, Valid, False Positive")
    pdf.bullet("Urutan: berdasarkan tanggal deteksi, confidence, tingkat, atau tanggal kejadian")
    pdf.bullet("Arah: tertinggi atau terendah")

    pdf.sub_sub_title("Tindakan")
    pdf.bullet("Klik baris anomali untuk masuk ke halaman detail.")
    pdf.bullet("Review langsung dari daftar: klik ikon review, isi catatan (opsional), pilih \"Valid\" atau \"False Positive\".")
    pdf.bullet("Export PDF: mengekspor seluruh anomali sesuai filter aktif (maksimal 200 data).")

    # 6.4 Detail Anomali
    pdf.sub_title("6.4", "Detail Anomali & Review")
    pdf.url_text("pandora.kaltaraprov.go.id/analitik/anomali/{id}")
    pdf.para("Halaman detail anomali menampilkan analisis mendalam untuk satu anomali tertentu.")

    pdf.sub_sub_title("Narasi Otomatis")
    pdf.para("Sistem menghasilkan narasi analisis secara otomatis yang mencakup:")
    pdf.bullet("Ringkasan: apa yang terjadi, tingkat, dan confidence.")
    pdf.bullet("Arti Confidence: penjelasan interpretasi persentase confidence.")
    pdf.bullet("Mengapa Terdeteksi: penjelasan teknis metode deteksi yang digunakan.")
    pdf.bullet("Data Kehadiran: jam masuk/pulang, lokasi, sumber presensi pada hari tersebut.")
    pdf.bullet("Status Izin/Cuti/DL: apakah ada catatan izin/dinas yang mempengaruhi penilaian.")
    pdf.bullet("Pola Historis: riwayat anomali pegawai tersebut dalam 30 hari terakhir.")
    pdf.bullet("Rekomendasi: saran tindak lanjut berdasarkan analisis.")

    pdf.sub_sub_title("Peta Lokasi")
    pdf.para("Peta interaktif menampilkan titik check-in (merah) dan check-out (biru) beserta zona geofence terdekat. "
             "Jika lokasi berada di luar Kalimantan Utara, sistem menampilkan peringatan khusus.")

    pdf.sub_sub_title("Riwayat 14 Hari")
    pdf.para("Tabel menampilkan pola kehadiran 14 hari terakhir, mencakup jam masuk/pulang, status, "
             "koordinat, dan nama lokasi. Hari terjadinya anomali ditandai (highlighted). "
             "Lokasi di luar Kaltara ditandai dengan badge \"DI LUAR KALTARA\".")

    pdf.sub_sub_title("Metadata Teknis")
    pdf.para("Section yang dapat dibuka/tutup (collapsible) menampilkan data teknis:")
    pdf.bullet("Velocity berangkat-pulang dan velocity vs hari kemarin (km/jam).")
    pdf.bullet("Jarak dari geofence terdekat (meter).")
    pdf.bullet("Deviasi jam masuk vs jadwal, vs median personal, vs median unit (menit).")
    pdf.bullet("Detail metadata JSON dari hasil deteksi.")

    pdf.sub_sub_title("Melakukan Review Anomali")
    pdf.para("Pada anomali yang belum direview, tersedia dua tombol aksi:")
    pdf.bold_bullet("Tandai Valid", "Konfirmasi bahwa anomali benar terjadi dan merupakan pelanggaran kehadiran yang perlu ditindaklanjuti. Data anomali akan tercatat sebagai \"valid\" dan keluar dari antrian review.")
    pdf.bold_bullet("False Positive", "Tandai bahwa anomali bukan pelanggaran — misalnya karena pegawai bebas lokasi, dinas luar, atau konteks sah lainnya. Anomali keluar dari antrian review.")
    pdf.ln(1)
    pdf.info_box("Penting", "Anomali yang sudah direview tidak akan lagi muncul di dashboard, peta anomali, dan daftar alert. "
                 "Penandaan ini berfungsi sebagai mekanisme triase agar data di dashboard selalu menampilkan kasus yang perlu perhatian. "
                 "Setiap review tercatat dalam audit trail (siapa, kapan, status apa).")

    pdf.sub_sub_title("Export PDF Detail")
    pdf.para("Klik tombol \"Export PDF\" untuk mengunduh laporan lengkap anomali dalam format PDF. "
             "File diberi nama otomatis: anomali-{id}-{nama}-{tanggal}.pdf.")

    # 6.5 Clustering
    pdf.sub_title("6.5", "Clustering & Pola Spasial")
    pdf.url_text("pandora.kaltaraprov.go.id/analitik/clustering")
    pdf.para("Halaman ini menampilkan analisis spasial anomali menggunakan machine learning.")

    pdf.sub_sub_title("Ringkasan Statistik")
    pdf.para("Empat kartu statistik di bagian atas:")
    pdf.bold_bullet("Titik Terisolasi (DBSCAN)", "Lokasi check-in yang secara geografis terisolasi dari pola normal.")
    pdf.bold_bullet("Outlier Multivariate (Isolation Forest)", "Anomali berdasarkan kombinasi banyak fitur sekaligus.")
    pdf.bold_bullet("Hotspot Lokasi", "Lokasi dengan 3+ anomali dari pegawai berbeda.")
    pdf.bold_bullet("Di Luar Kaltara", "Absensi yang terdeteksi dari koordinat di luar Kalimantan Utara.")

    pdf.sub_sub_title("Alert: Absensi dari Luar Kalimantan Utara")
    pdf.para("Jika ada anomali dari luar wilayah Kaltara, sistem menampilkan alert merah dengan tabel berisi: "
             "nama pegawai, instansi, tanggal, lokasi terdeteksi (kota hasil reverse geocoding), dan link ke detail.")

    pdf.sub_sub_title("Peta Anomali Spasial")
    pdf.para("Peta interaktif menampilkan:")
    pdf.bullet("Titik merah: DBSCAN noise points (lokasi terisolasi).")
    pdf.bullet("Titik biru: Isolation Forest outliers (outlier multivariate).")
    pdf.bullet("Lingkaran oranye: hotspot lokasi dengan radius proporsional.")

    pdf.sub_sub_title("Tabel Analisis")
    pdf.bullet("Hotspot Anomali per Lokasi: peringkat lokasi dengan anomali terbanyak.")
    pdf.bullet("Instansi dengan Anomali ML Terbanyak: peringkat unit berdasarkan jumlah anomali.")
    pdf.bullet("Titik Terisolasi — DBSCAN: detail 50 titik terisolasi teratas dengan link ke detail anomali.")

    pdf.sub_sub_title("Filter Rentang Waktu")
    pdf.para("Gunakan date picker \"Dari\" dan \"Sampai\" untuk membatasi rentang analisis, kemudian klik \"Tampilkan\".")

    # ══════════════════════════════════════════════
    # BAB 7: SINKRONISASI
    # ══════════════════════════════════════════════
    pdf.chapter_title("Sinkronisasi Data")
    pdf.url_text("pandora.kaltaraprov.go.id/sinkronisasi")
    pdf.para(
        "Halaman ini menampilkan status sinkronisasi data dari SIMPEG/SIKARA ke database PANDORA. "
        "Sinkronisasi berjalan otomatis melalui scheduled task (cron) dan dapat dipantau di sini."
    )

    pdf.sub_sub_title("Kartu Statistik")
    pdf.bold_bullet("Total Tabel Sync", "Jumlah tabel sumber yang disinkronisasi.")
    pdf.bold_bullet("Sync Sukses Terakhir", "Waktu sinkronisasi sukses terakhir.")
    pdf.bold_bullet("Data Change Alerts", "Jumlah perubahan data yang terdeteksi.")
    pdf.bold_bullet("Critical Alerts", "Jumlah perubahan data berstatus kritis.")

    pdf.sub_sub_title("Status Per Tabel")
    pdf.para("Tabel menampilkan status tiap tabel sumber:")
    pdf.bullet("Nama tabel sumber (SIKARA).")
    pdf.bullet("Status: success (hijau), failed (merah), running (kuning).")
    pdf.bullet("Jumlah baris: fetched, inserted, updated.")
    pdf.bullet("Waktu mulai, durasi, dan pesan error (jika gagal).")

    pdf.sub_sub_title("Deteksi Perubahan Data")
    pdf.para("Sistem mendeteksi perubahan data yang mencurigakan berdasarkan perbandingan checksum. "
             "Perubahan ditampilkan dengan severity: critical (merah), warning (oranye), info (biru).")

    pdf.sub_sub_title("Riwayat Sinkronisasi")
    pdf.para("Section collapsible menampilkan 50 riwayat sinkronisasi terakhir secara kronologis.")

    # ══════════════════════════════════════════════
    # BAB 8: INTEGRITAS
    # ══════════════════════════════════════════════
    pdf.chapter_title("Integritas Ledger (Blockchain)")
    pdf.url_text("pandora.kaltaraprov.go.id/integritas")
    pdf.para(
        "PANDORA menggunakan OpenTimestamps untuk menjangkarkan (anchor) hash data kehadiran harian "
        "ke blockchain Bitcoin. Ini membuktikan bahwa data tidak diubah setelah pencatatan."
    )

    pdf.sub_sub_title("Kartu Statistik")
    pdf.bold_bullet("Total Anchor", "Jumlah total anchor harian yang telah dibuat.")
    pdf.bold_bullet("Confirmed (Bitcoin)", "Anchor yang telah dikonfirmasi masuk ke blok Bitcoin.")
    pdf.bold_bullet("Menunggu Konfirmasi", "Anchor yang masih menunggu konfirmasi blockchain.")

    pdf.sub_sub_title("Tabel Anchor")
    pdf.para("Setiap baris menampilkan:")
    pdf.bullet("Tanggal data kehadiran.")
    pdf.bullet("Merkle Root: hash kriptografis dari seluruh data hari tersebut (partial hex).")
    pdf.bullet("Jumlah record yang di-hash.")
    pdf.bullet("Status: Confirmed, Anchored, Pending, atau Failed.")
    pdf.bullet("Nomor blok Bitcoin (jika sudah dikonfirmasi).")

    pdf.sub_sub_title("Aksi yang Tersedia")
    pdf.bold_bullet("Download .ots", "Mengunduh file bukti OpenTimestamps (.ots) untuk verifikasi independen.")
    pdf.bold_bullet("Cek/Verify", "Memverifikasi integritas data: menghitung ulang merkle root, memeriksa chain, dan validasi status Bitcoin.")

    pdf.sub_sub_title("Verifikasi Manual")
    pdf.para("File .ots yang diunduh dapat diverifikasi secara independen di opentimestamps.org "
             "atau menggunakan tool command-line OpenTimestamps.")

    # ══════════════════════════════════════════════
    # BAB 9: LITERASI DATA
    # ══════════════════════════════════════════════
    pdf.chapter_title("Literasi Data")
    pdf.url_text("pandora.kaltaraprov.go.id/literasi-data")
    pdf.para(
        "Pusat pengetahuan (knowledge base) yang berisi penjelasan konsep-konsep data science dan machine learning "
        "yang digunakan oleh PANDORA. Tujuannya agar pengguna memahami bagaimana sistem bekerja."
    )

    pdf.sub_sub_title("Kategori Konten")
    pdf.bold_bullet("Fondasi", "Konsep dasar statistik dan data science.")
    pdf.bold_bullet("Data Engineering", "Proses pengolahan dan transformasi data.")
    pdf.bold_bullet("Klasifikasi", "Teknik pengelompokan data ke dalam kelas/kategori.")
    pdf.bold_bullet("Estimasi & Regresi", "Teknik prediksi nilai numerik.")
    pdf.bold_bullet("Clustering", "Teknik pengelompokan data tanpa label (unsupervised).")
    pdf.bold_bullet("Association Rule", "Teknik menemukan hubungan antar variabel.")
    pdf.bold_bullet("Data Tak Terstruktur", "Pengolahan data teks, gambar, dan data non-tabular.")

    pdf.sub_sub_title("Fitur")
    pdf.bullet("Pencarian teks penuh (full-text search) di seluruh konten.")
    pdf.bullet("Navigasi per kategori dan per konsep (previous/next).")
    pdf.bullet("Konten disajikan dalam format Markdown yang dirender ke HTML.")
    pdf.bullet("Breadcrumb navigation untuk kemudahan navigasi.")

    # ══════════════════════════════════════════════
    # BAB 10: PENGATURAN SISTEM
    # ══════════════════════════════════════════════
    pdf.chapter_title("Pengaturan Sistem")
    pdf.para("Menu Pengaturan hanya tersedia untuk pengguna dengan peran Admin.")
    pdf.url_text("pandora.kaltaraprov.go.id/pengaturan")

    # 10.1 User
    pdf.sub_title("10.1", "Manajemen Pengguna")
    pdf.url_text("pandora.kaltaraprov.go.id/pengaturan/users")
    pdf.para("Kelola akun pengguna yang memiliki akses ke sistem PANDORA.")

    pdf.sub_sub_title("Menambah Pengguna Baru")
    pdf.step(1, "Klik tombol \"+ Tambah Pengguna\".")
    pdf.step(2, "Isi formulir: Nama (wajib), NIP (opsional, maks 18 karakter), Email (wajib, unik), Password (wajib, minimal 8 karakter).")
    pdf.step(3, "Pilih role: Admin, HR, atau Pimpinan.")
    pdf.step(4, "Klik \"Simpan\".")
    pdf.ln(1)

    pdf.sub_sub_title("Mengedit Pengguna")
    pdf.para("Klik tombol \"Edit\" pada baris pengguna. Ubah field yang diperlukan. "
             "Password bisa dikosongkan jika tidak ingin mengubah password saat ini.")

    pdf.sub_sub_title("Menghapus Pengguna")
    pdf.para("Klik tombol \"Hapus\" pada baris pengguna. Sistem mencegah penghapusan akun sendiri. "
             "Seluruh operasi tercatat di audit trail.")

    pdf.sub_sub_title("Hak Akses per Role")
    pdf.para("Berikut perbandingan hak akses:")
    pdf.bullet("Admin: akses penuh ke seluruh fitur termasuk Pengaturan.")
    pdf.bullet("HR: Dashboard, Master Data, Kehadiran, Analitik, Sinkronisasi, Literasi Data.")
    pdf.bullet("Pimpinan: Dashboard Pimpinan dan Analitik.")

    # 10.2 Audit Trail
    pdf.sub_title("10.2", "Audit Trail")
    pdf.url_text("pandora.kaltaraprov.go.id/pengaturan/audit-trail")
    pdf.para("Log aktivitas lengkap seluruh tindakan yang dilakukan di dalam sistem.")

    pdf.sub_sub_title("Informasi yang Tercatat")
    pdf.bullet("Timestamp (tanggal dan waktu).")
    pdf.bullet("Nama pengguna dan role yang melakukan aksi.")
    pdf.bullet("Jenis aksi: tambah_user, ubah_user, hapus_user, tambah_geofence, hapus_geofence, tambah_whitelist, hapus_whitelist, review_anomali, ekspor, dll.")
    pdf.bullet("Entitas dan ID entitas yang terpengaruh.")
    pdf.bullet("Alamat IP pengguna.")
    pdf.bullet("Detail perubahan dalam format JSON (expandable).")

    pdf.sub_sub_title("Filter")
    pdf.para("Audit trail dapat difilter berdasarkan:")
    pdf.bullet("Jenis aksi (dropdown).")
    pdf.bullet("Pengguna yang melakukan aksi (dropdown).")
    pdf.para("Paginasi 50 item per halaman, diurutkan dari yang terbaru.")

    # ══════════════════════════════════════════════
    # BAB 11: PERINTAH ARTISAN (CLI)
    # ══════════════════════════════════════════════
    pdf.chapter_title("Perintah Artisan (CLI)")
    pdf.para("Perintah-perintah berikut dijalankan melalui terminal di dalam container pandora-app. "
             "Perintah ini umumnya berjalan otomatis via scheduler, tetapi dapat juga dijalankan manual.")

    pdf.sub_sub_title("Sinkronisasi Data")
    pdf.set_font("dejavumono", "", 8)
    pdf.cell(0, 5, "docker compose exec pandora-app php artisan simpeg:sync [--table=NAMA_TABEL]", new_x="LMARGIN", new_y="NEXT")
    pdf.set_font("dejavu", "", 9)
    pdf.para("Menyinkronisasi data dari SIMPEG/SIKARA. Tanpa parameter --table, semua tabel akan disinkronisasi.")

    pdf.sub_sub_title("Pipeline Analitik Harian")
    pdf.set_font("dejavumono", "", 8)
    pdf.cell(0, 5, "docker compose exec pandora-app php artisan pipeline:daily [--date=YYYY-MM-DD]", new_x="LMARGIN", new_y="NEXT")
    pdf.set_font("dejavu", "", 9)
    pdf.para("Menjalankan pipeline deteksi anomali harian. Default: memproses data kemarin. Tahapan: "
             "feature engineering, rule engine (T1), Isolation Forest, dan DBSCAN.")

    pdf.sub_sub_title("Pipeline Bulanan")
    pdf.set_font("dejavumono", "", 8)
    pdf.cell(0, 5, "docker compose exec pandora-app php artisan pipeline:monthly [--month=MM --year=YYYY]", new_x="LMARGIN", new_y="NEXT")
    pdf.set_font("dejavu", "", 9)
    pdf.para("Menjalankan agregasi dan analisis bulanan, termasuk deteksi anomali Tingkat 2 (rule violation).")

    pdf.sub_sub_title("Blockchain Anchoring")
    pdf.set_font("dejavumono", "", 8)
    pdf.cell(0, 5, "docker compose exec pandora-app php artisan ledger:anchor-daily", new_x="LMARGIN", new_y="NEXT")
    pdf.set_font("dejavu", "", 9)
    pdf.para("Membuat anchor harian ke blockchain Bitcoin via OpenTimestamps.")
    pdf.set_font("dejavumono", "", 8)
    pdf.cell(0, 5, "docker compose exec pandora-app php artisan ledger:anchor-upgrade", new_x="LMARGIN", new_y="NEXT")
    pdf.set_font("dejavu", "", 9)
    pdf.para("Meng-upgrade status anchor yang masih pending menjadi confirmed.")
    pdf.set_font("dejavumono", "", 8)
    pdf.cell(0, 5, "docker compose exec pandora-app php artisan ledger:verify", new_x="LMARGIN", new_y="NEXT")
    pdf.set_font("dejavu", "", 9)
    pdf.para("Memverifikasi integritas seluruh chain data kehadiran.")

    pdf.sub_sub_title("Sinkronisasi Log Presensi")
    pdf.set_font("dejavumono", "", 8)
    pdf.cell(0, 5, "docker compose exec pandora-app php artisan ledger:sync-presensi", new_x="LMARGIN", new_y="NEXT")
    pdf.set_font("dejavu", "", 9)
    pdf.para("Menyinkronisasi log presensi ke ledger untuk verifikasi hash chain.")

    # ══════════════════════════════════════════════
    # BAB 12: GLOSARIUM
    # ══════════════════════════════════════════════
    pdf.chapter_title("Glosarium & Kode Status")

    pdf.sub_sub_title("Kode Status Kehadiran")
    codes = [
        ("TW", "Tepat Waktu — pegawai hadir sesuai jadwal."),
        ("MKTTW", "Masuk Kerja Tidak Tepat Waktu — pegawai terlambat."),
        ("TK", "Tanpa Kehadiran — pegawai tidak hadir."),
        ("DL", "Dinas Luar — tugas kedinasan di luar kota."),
        ("DD", "Dinas Dalam — tugas kedinasan di dalam kota."),
        ("I", "Izin — pegawai mengajukan izin tidak masuk."),
        ("S", "Sakit — pegawai tidak masuk karena sakit."),
        ("C", "Cuti — pegawai dalam masa cuti."),
        ("DSP", "Dispensasi — pengecualian khusus oleh pimpinan."),
        ("TA", "Tanpa Absensi — tidak ada data absensi sama sekali."),
    ]
    for code, desc in codes:
        pdf.bold_bullet(code, desc)

    pdf.ln(2)
    pdf.sub_sub_title("Tingkat Anomali")
    levels = [
        ("T1 — Ketidakmungkinan Fisik", "Anomali yang secara fisik tidak mungkin terjadi (fake GPS, kecepatan mustahil, koordinat di luar NKRI)."),
        ("T2 — Pelanggaran Aturan", "Pelanggaran terhadap aturan geofence, jadwal, atau status kedinasan."),
        ("T3 — Anomali Statistik", "Pola statistik yang menyimpang, terdeteksi oleh model machine learning (Isolation Forest, DBSCAN)."),
        ("T4 — Kandidat False Positive", "Deteksi yang kemungkinan besar memiliki penjelasan sah."),
    ]
    for label, desc in levels:
        pdf.bold_bullet(label, desc)

    pdf.ln(2)
    pdf.sub_sub_title("Jenis Anomali")
    types = [
        ("fake_gps", "Koordinat palsu — terdeteksi dari koordinat statis identik berhari-hari atau pola GPS tidak wajar."),
        ("velocity_outlier", "Kecepatan tidak wajar — perpindahan lokasi terlalu cepat (>300 km/jam) antara sesi absensi."),
        ("geofence_violation", "Pelanggaran geofence — check-in dari lokasi di luar zona yang ditetapkan."),
        ("temporal_outlier", "Anomali waktu — pola jam masuk/pulang yang menyimpang signifikan dari kebiasaan."),
        ("combination", "Kombinasi — beberapa fitur sekaligus menyimpang, terdeteksi oleh Isolation Forest atau DBSCAN."),
    ]
    for label, desc in types:
        pdf.bold_bullet(label, desc)

    pdf.ln(2)
    pdf.sub_sub_title("Status Review Anomali")
    statuses = [
        ("belum_direview", "Anomali baru yang belum ditinjau oleh reviewer."),
        ("valid", "Anomali telah dikonfirmasi sebagai pelanggaran nyata."),
        ("false_positive", "Anomali ditandai bukan pelanggaran (ada konteks sah)."),
    ]
    for label, desc in statuses:
        pdf.bold_bullet(label, desc)

    pdf.ln(2)
    pdf.sub_sub_title("Istilah Teknis")
    terms = [
        ("Confidence", "Tingkat keyakinan sistem (0-100%) bahwa suatu anomali benar-benar terjadi. Semakin tinggi, semakin kuat indikasi pelanggaran."),
        ("Geofence", "Batas wilayah geografis virtual berbentuk lingkaran, digunakan untuk memvalidasi lokasi check-in pegawai."),
        ("Merkle Root", "Hash kriptografis gabungan dari seluruh data kehadiran satu hari. Digunakan untuk membuktikan integritas data."),
        ("OpenTimestamps", "Protokol open-source untuk membuktikan bahwa suatu data sudah ada pada waktu tertentu, menggunakan blockchain Bitcoin."),
        ("Isolation Forest", "Algoritma machine learning untuk mendeteksi outlier berdasarkan kombinasi banyak fitur sekaligus."),
        ("DBSCAN", "Algoritma clustering berbasis densitas yang mengidentifikasi titik-titik terisolasi (noise points) secara spasial."),
        ("Reverse Geocoding", "Proses mengubah koordinat GPS (lat/lng) menjadi nama lokasi yang dapat dibaca manusia."),
        ("SIKARA/SIMPEG", "Sistem informasi kepegawaian sumber data yang disinkronisasi ke PANDORA."),
    ]
    for label, desc in terms:
        pdf.bold_bullet(label, desc)

    # ── Output ──
    output_path = "/app/bukupetunjuk/Buku_Petunjuk_Admin_PANDORA.pdf"
    pdf.output(output_path)
    print(f"PDF berhasil dibuat: {output_path}")
    print(f"Total halaman: {pdf.page_no()}")


if __name__ == "__main__":
    build()
