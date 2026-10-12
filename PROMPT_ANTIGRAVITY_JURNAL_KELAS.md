# PROMPT: Web Jurnal Kelas Digital (Presensi & Jurnal)

## 0. Peran & Cara Kerja

Kamu adalah senior full-stack engineer. Bangun **Web Jurnal Kelas Digital** yang akan benar-benar dipakai satu kelas di sekolah, menggantikan buku jurnal fisik (presensi siswa, jurnal kelas, jurnal materi guru, surat izin).

Cara kerja yang wajib diikuti:
1. **Baca dulu** seluruh prompt ini dan semua gambar di `docs/design/` (lihat bagian 2). Gambar adalah sumber kebenaran visual.
2. **Buat implementation plan** (daftar task per fase di bagian 12) dan tunjukkan sebelum menulis kode. Jangan bertanya hal yang sudah dijawab di prompt; kalau ada keputusan kecil yang belum ada, ambil keputusan paling sederhana, lalu catat di `docs/DECISIONS.md`.
3. Kerjakan **per fase**. Setiap fase selesai harus: build lolos, lint/typecheck lolos, dan diverifikasi lewat browser di 3 lebar layar (**375px HP, 768px tablet, 1280px laptop**).
4. Jangan membuat fitur di luar daftar ini.

---

## 1. Tujuan Produk

Web multi-platform (responsif, satu codebase) untuk HP dan PC/laptop. Empat jenis pengguna dengan hak akses berbeda (kasta). Fungsi inti:
- Sekretaris mengisi **presensi harian** (Hadir/Sakit/Izin/Alpha) dan **jurnal kelas**, serta mengirim **surat izin**.
- Guru Pengajar mengisi **jurnal materi** dan memantau rekap.
- Guru Pembimbing **menyetujui surat izin**, mengelola user, dan memantau semuanya.
- Siswa melihat **riwayat kehadiran** pribadi dan **materi**.
- Semua dapat **notifikasi**.

Bahasa antarmuka: **Bahasa Indonesia**. Zona waktu **Asia/Jakarta (WIB)**, format tanggal `id-ID` (mis. "Kamis, 27 Februari 2026").

---

## 2. Referensi Desain (WAJIB dibaca)

Folder `docs/design/` berisi gambar dari dokumen analisis (nama file berurutan). Setiap halaman yang kamu bangun harus meniru layout, hierarki, warna, dan komponen pada gambar yang sesuai.

| File | Isi |
|---|---|
| `01-dfd-level0.png`, `02-dfd-level1.png` | DFD: proses 1.0 Autentikasi, 2.0 Jurnal Kelas, 3.0 Jurnal Materi, 4.0 Kehadiran, 5.0 Surat Izin, 6.0 Rekap Otomatis, 7.0 Manajemen User, 8.0 Notifikasi. Data store D1 Pengguna, D2 Jurnal Kelas, D3 Jurnal Materi, D4 Kehadiran, D5 Surat Izin, D6 Notifikasi, D7 OTP Reset, D8 Data Kelas/Master |
| `03-flow-autentikasi.png` s.d. `07-flow-pembimbing.png` | Alur login/lupa sandi dan alur menu tiap peran |
| `08-login.png` | Login |
| `09`, `10`, `12`, `11` | Lupa sandi: langkah 1 email, 2 OTP, 3 sandi baru, sukses |
| `13`-`15` | Siswa: dashboard, jurnal pelajaran (materi), riwayat kehadiran |
| `16`-`20` | Sekretaris: dashboard, input presensi, laporan jurnal kelas, rekap harian, jurnal pelajaran |
| `21`-`24` | Guru Pengajar: rekap harian, dashboard, rekap semester, jurnal materi |
| `25`-`30` | Guru Pembimbing: rekap harian, dashboard, persetujuan surat izin, rekap semester, manajemen user, laporan surat izin |
| `31-notifikasi-log-audit.png` | Notifikasi + log audit |

**Halaman yang TIDAK punya mockup** (buat konsisten dengan gaya yang sama, reuse komponen yang ada):
- Sekretaris: form **Jurnal Kelas** (pilih mapel & jam → isi kegiatan & catatan → simpan & kirim).
- Sekretaris: form **Kirim Surat Izin** + daftar surat izin miliknya.
- Pembimbing: tab **Konfigurasi Hak Akses** di Manajemen User.
- Pengajar: **Laporan Jurnal Kelas** (pakai komponen yang sama dengan `18`).

### Design tokens (dari mockup)
- Primary merah `#C62828` (hover lebih gelap), latar halaman `#FAFAFA`, kartu putih, border `#E5E7EB`, radius kartu ~12-16px, shadow sangat tipis.
- Font **Inter**. Judul halaman tebal, label kecil uppercase abu-abu untuk field.
- Status presensi (satu sumber warna, pakai di mana pun): **Hadir hijau, Sakit oranye, Izin biru, Alpha merah**.
- Status verifikasi: **Belum Konfirmasi merah, Pending/Menunggu oranye, Terverifikasi hijau**. Status surat izin: **Pending oranye, Disetujui hijau, Ditolak merah**.
- Logo: kotak merah berisi ikon buku + teks "Jurnal Kelas" / "EDISI DIGITAL". Footer sidebar: "Tahun Ajaran 2025/2026 - Ganjil" (ambil dari data semester aktif, jangan hardcode).
- Ikon: `lucide-react`.

> Beberapa label menu di mockup tidak konsisten antar peran. **Tabel menu di bagian 5 adalah acuan final.**

---

## 3. Tech Stack

- **Next.js (App Router) + TypeScript (strict)**, **Tailwind CSS**, **shadcn/ui** sebagai primitif (Button, Input, Dialog, Sheet, Tabs, Select, Toast) supaya komponen tidak ditulis ulang.
- **Prisma + PostgreSQL** (boleh Supabase Postgres). Migrasi lewat `prisma migrate`.
- **Zod** untuk validasi; skema yang sama dipakai di klien dan server. **React Hook Form** untuk form.
- **Recharts** untuk donat dan batang. **exceljs** untuk XLSX; satu library ringan untuk PDF (`@react-pdf/renderer` atau `jspdf` + `autotable`).
- Auth: **sesi cookie httpOnly** (tabel `Session`, token acak disimpan hash-nya) + hash password **argon2** (atau bcrypt). Tidak memakai `localStorage` untuk token.
- Upload file lewat **interface `StorageProvider`**: implementasi disk lokal untuk dev, siap diganti S3/Supabase Storage. File disajikan lewat route yang memeriksa hak akses, **bukan** folder publik.

---

## 4. Prinsip "Ponytail": Kode Ringkas, Tidak Berulang

Anggap prinsip ini sebagai aturan keras: **satu sumber kebenaran, satu komponen untuk satu pola, perbedaan antar peran dipindahkan ke konfigurasi, bukan ke salinan kode.**

1. **Satu konfigurasi peran** `src/config/access.ts` yang memuat: daftar peran, permission per peran, menu sidebar per peran, route yang boleh diakses, dan kartu dashboard per peran. Sidebar, route guard, middleware, dan pengecekan di server **semuanya membaca file ini**. Menambah peran/menu = edit satu tempat.
2. **Halaman bersama diparameterisasi peran**, bukan diduplikasi per peran:
   - `MaterialListPage` → dipakai Siswa, Sekretaris, Pengajar
   - `ClassJournalReportPage` → Sekretaris, Pengajar, Pembimbing
   - `DailyRecapPage` → Sekretaris, Pengajar, Pembimbing
   - `SemesterRecapPage` → Pengajar, Pembimbing
   - `NotificationsPage` → semua peran
   - `DashboardPage` → merender kartu/blok dari konfigurasi peran
3. **Komponen UI generik** di `src/components/` (tulis sekali, pakai di mana-mana): `AppShell`, `PageHeader`, `StatCard` + `StatGrid`, `DataTable` (responsif, pencarian, filter, pagination), `FilterBar`, `StatusBadge` (satu peta status → warna/label), `FormField`, `FileUpload` (validasi tipe+ukuran), `ExportButton`, `ConfirmDialog`, `EmptyState`, `Skeleton`, `NotificationBell`, `AuthCard`, `Stepper`, `AttendanceCharts`.
4. **Lapisan server tunggal**: `requireUser()`, `requirePermission(perm)`, `audit(action)`, `notify(userIds, payload)`, `exportTable(format, columns, rows)`. Setiap server action memakai helper ini. Tidak ada logika otorisasi atau penulisan log yang ditulis ulang di tiap fitur.
5. **Tipe dan skema Zod satu kali** di `src/lib/schemas/`; tipe TypeScript diturunkan dari sana (`z.infer`) dan dari Prisma. Tidak ada tipe kembar.
6. **Konstanta satu kali**: status, label, warna, batas upload (5 MB), durasi OTP (5 menit) dan cooldown kirim ulang (60 detik) berada di `src/config/constants.ts`.
7. **Aturan praktis**: bila JSX/logika yang sama muncul **dua kali**, ekstrak. Tetapi **jangan membuat abstraksi untuk sesuatu yang hanya dipakai sekali**. Tujuannya kode lebih sedikit, bukan lebih rumit.
8. Tidak ada kode mati, komentar basa-basi, atau file placeholder kosong.

---

## 5. Pengguna, Kasta, dan Hak Akses

Empat peran. Enum: `SISWA`, `SEKRETARIS`, `GURU_PENGAJAR`, `GURU_PEMBIMBING`. Sekretaris adalah **siswa yang punya hak tambahan** (punya NIS dan kelas). Tidak ada peran lain: label "Wali Kelas" / "Guru Produktif" / "Administrator Pusat" di mockup dianggap sebagai Guru Pembimbing / Guru Pengajar.

| Fitur | Siswa | Sekretaris | G. Pengajar | G. Pembimbing |
|---|:-:|:-:|:-:|:-:|
| Login, Lupa Password, Notifikasi | ✔ | ✔ | ✔ | ✔ |
| Mengisi Jurnal (kelas) | | ✔ | | |
| Mengisi Kehadiran (+ kunci PIN) | | ✔ | | |
| Mengirim Surat Izin | | ✔ | | |
| Melihat Materi | ✔ | ✔ | ✔ | |
| Melihat Laporan Jurnal Kelas | | ✔ | ✔ | ✔ |
| Melihat Riwayat Kehadiran pribadi | ✔ | | | |
| Melihat Rekap Harian (read-only) | | ✔ | ✔ | ✔ |
| Melihat Rekap Kehadiran Semester (+ekspor) | | | ✔ | ✔ |
| Mengisi Jurnal Materi + konfirmasi jurnal kelas | | | ✔ | |
| Menyetujui Surat Izin | | | | ✔ |
| Melihat Laporan Surat Izin (+ekspor XLSX) | | | | ✔ |
| Menambah/mengelola User | | | | ✔ |

### Menu sidebar final (urutan = urutan tampil)
- **Siswa**: Dashboard · Jurnal Pelajaran · Riwayat Kehadiran
- **Sekretaris**: Dashboard · Presensi Kehadiran · Jurnal Kelas · Surat Izin · Rekap Harian · Laporan Jurnal Kelas · Jurnal Pelajaran
- **Guru Pengajar**: Dashboard · Jurnal Materi · Jurnal Pelajaran · Laporan Jurnal Kelas · Rekap Harian · Rekap Semester
- **Guru Pembimbing**: Dashboard · Laporan Jurnal Kelas · Rekap Harian · Rekap Semester · Persetujuan Surat Izin · Laporan Surat Izin · Manajemen User

Lonceng notifikasi ada di header semua peran (badge jumlah belum dibaca) dan membuka halaman Notifikasi.

### Penegakan hak akses (wajib berlapis)
1. Middleware: belum login → `/login`; sudah login membuka `/login` → `/dashboard`.
2. Route guard dari `access.ts`: akses route di luar peran → halaman 403.
3. **Server action/route handler wajib memanggil `requirePermission()`**. Menyembunyikan menu di UI **tidak cukup**.
4. Pembatasan data: Siswa hanya data dirinya; Sekretaris hanya kelasnya; Pengajar hanya kelas yang ia ajar; Pembimbing hanya kelas binaannya.

---

## 6. Model Data (Prisma)

Rancang tabel berikut (nama boleh disesuaikan, relasi dan aturan harus sama). Sertakan `createdAt/updatedAt` dan index untuk kolom filter.

| Model | Isi pokok |
|---|---|
| `User` (D1) | id, name, email unik, passwordHash, phone (format 62…), nipNis, role, isActive, classId? (siswa/sekretaris) |
| `Session` | id, userId, tokenHash, expiresAt, ip, userAgent |
| `Class` (D8) | id, name (XI RPL 1), `secretaryId?`, `advisorId` (pembimbing) |
| `TeacherClass` | pengajar ↔ kelas yang diajar (many-to-many) |
| `Subject`, `Schedule` (D8) | mapel; jadwal: classId, subjectId, teacherId, hari, jamMulai, jamSelesai, ruangan |
| `Term` | tahun ajaran + semester, tanggal mulai/selesai, `isActive`, daftar hari efektif/libur |
| `AttendanceSession` (D4) | classId, date, status `DRAFT`/`LOCKED`, lockedById, lockedAt, verifikasi (`BELUM_KONFIRMASI`/`PENDING`/`TERVERIFIKASI`), jam sekolah, ruangan? |
| `AttendanceRecord` | sessionId, studentId, status (`HADIR/SAKIT/IZIN/ALPHA`), note, attachmentId?, unik(sessionId, studentId) |
| `ClassJournal` (D2) | classId, scheduleId, date, activity, notes, status verifikasi, createdById (sekretaris), confirmedById? (pengajar), confirmedAt |
| `MaterialJournal` (D3) | teacherId, classId, subjectId, scheduleId?, date, topic (pokok bahasan/KD), plan, assignment, + lampiran |
| `LeaveRequest` (D5) | studentId, submittedById (sekretaris), type (`SAKIT/IZIN/DISPENSASI`), startDate, endDate, recipient, reason, attachmentId, signature, status (`PENDING/APPROVED/REJECTED`), reviewerId, reviewNote, reviewedAt |
| `Attachment` | id, ownerId, path/key, mime, size, originalName |
| `Notification` (D6) | userId, type (`JURNAL`/`SURAT_IZIN`/`USER`/…), title, body, readAt |
| `AuditLog` | userId?, action, ip, createdAt |
| `OtpToken` (D7) | userId, codeHash, expiresAt, usedAt, attempts, sentAt |

### Aturan bisnis
- **Presensi per kelas per tanggal** (satu `AttendanceSession`). Tombol "Tandai Semua Hadir" mengisi semua baris Hadir. Draft boleh diubah bebas; setelah **Simpan & Kunci** (verifikasi password akun sekretaris sebagai PIN) data **terkunci** dan hanya bisa dibuka ulang oleh Pembimbing.
- Sakit/Izin boleh disertai keterangan dan lampiran bukti (PDF/PNG/JPG, **maks 5 MB**, validasi tipe MIME sebenarnya, bukan hanya ekstensi).
- **Verifikasi berjenjang**: Sekretaris kirim → `PENDING` ("Menunggu") → Guru Pengajar menekan **Konfirmasi** → `TERVERIFIKASI`. Awal: `BELUM_KONFIRMASI`.
- Surat izin: Sekretaris mengisi atas nama siswa terpilih (nama, NISN, kelas terisi otomatis), jenis, rentang tanggal, tujuan penerima, alasan, lampiran, tanda tangan digital sederhana → status `PENDING` + notifikasi ke Pembimbing. Pembimbing Setujui/Tolak + catatan (wajib saat tolak) → notifikasi ke Sekretaris. **Saat disetujui, presensi siswa pada rentang tanggal itu otomatis tercatat Sakit/Izin** (dipakai rekap).
- **Rekap harian dan semester dihitung otomatis dari data presensi** (tidak ada input manual). Persentase = hadir / hari efektif. Pembimbing dan Pengajar bisa ekspor **PDF dan Excel**.
- Pengajar hanya boleh mengisi/konfirmasi jurnal kelas yang ia ajar.
- Setiap aksi penting memanggil `audit()`. Setiap peristiwa (jurnal dikirim, izin disetujui/ditolak, user baru, dll.) memanggil `notify()`.

---

## 7. Autentikasi (halaman `08`-`12`)

**Login** (`/login`): email + kata sandi (tombol lihat/sembunyi), tombol "Masuk Ke Dashboard", link "Lupa kata sandi?". Pesan error generik ("Email atau kata sandi salah"). Rate limit percobaan gagal per IP+email. Akun nonaktif tidak bisa masuk. Setelah login diarahkan ke `/dashboard` sesuai peran.

**Quick Login** (tombol kecil di bawah kartu, per peran): **hanya aktif bila `ENABLE_QUICK_LOGIN=true` dan `NODE_ENV !== 'production'`**, dan dicek **di server**, bukan hanya disembunyikan di UI. Sediakan untuk keempat peran.

**Lupa Sandi** (`/forgot-password`), 3 langkah dengan `Stepper` 1-2-3 dan layar sukses:
1. Input email → sistem mencari nomor WhatsApp terhubung → kirim OTP. Respons tidak boleh membocorkan apakah email terdaftar (tampilan tetap sama).
2. Input OTP 6 digit. Tampilkan email dan nomor yang disamarkan (`sekre***@gmail.com`, `+62 85* **** 3414`), teks "Kode berlaku selama 5 menit", tombol **Kirim ulang (58s)** dengan hitung mundur 60 detik, link "Ganti Email". Batasi 5 kali salah per kode.
3. Sandi baru (min. 8 karakter) + konfirmasi, tombol lihat sandi. Setelah sukses: layar "Kata Sandi Berhasil Diperbarui!" + tombol ke login. Semua sesi lama user dicabut.

### OTP: buat halaman dan alurnya saja, pengirimannya nanti saya sambungkan sendiri
- Buat interface `OtpSender { send(phone: string, code: string): Promise<void> }` di `src/lib/otp/`.
- Implementasi **`ConsoleOtpSender`** (default dev): mencetak kode ke log server. **Jangan** menampilkan kode ke browser.
- Buat file **`FonnteOtpSender.ts` berupa stub** berisi `TODO` dan membaca `FONNTE_TOKEN` dari env; saya yang akan mengisinya. **Jangan memanggil API Fonnte atau layanan WhatsApp apa pun.**
- Pemilihan sender lewat env `OTP_PROVIDER=console|fonnte`.
- Seluruh logika lain tetap **fungsional penuh**: generate kode acak aman, simpan **hash**-nya di `OtpToken`, kedaluwarsa 5 menit, cooldown kirim ulang, batas percobaan, sekali pakai.

---

## 8. Spesifikasi Halaman per Peran

Ikuti mockup sesuai nomor file. Semua tabel memakai `DataTable`, semua kartu angka memakai `StatCard`, semua badge memakai `StatusBadge`.

### Siswa
- **Dashboard** (`13`): 4 kartu (Hadir bulan ini + %, Total Sakit, Total Izin Disetujui, Alpha), tabel "Riwayat Kehadiran Terakhir", tabel "Jurnal Guru / Materi Pembelajaran" dengan tombol Unduh.
- **Jurnal Pelajaran** (`14`): filter bulan + pencarian; kolom Tanggal/Jam, Instruktur, Mapel, Bahasan & Rencana Belajar, Lampiran (n file), tombol "Baca Materi" (detail + unduh lampiran).
- **Riwayat Kehadiran** (`15`): kartu ringkasan, diagram donat (Hadir/Sakit/Izin/Alpha + persen) dan batang per minggu, tabel terperinci (tanggal, ruangan, mapel, jam, status, keterangan) dengan filter bulan dan status.

### Sekretaris
- **Dashboard** (`16`): kartu hari ini (Hadir, Izin, Sakit, Alpha + % kehadiran), tabel presensi kelas hari ini (No, Nama, NIS, Status, Keterangan, TTD/Terverifikasi) dan tombol **Simpan Jurnal**.
- **Presensi Kehadiran** (`17`): ringkasan (Total/Hadir/Sakit/Izin/Alpha), "Tandai Semua Hadir", pencarian, baris dengan 4 tombol status, keterangan, upload bukti, pagination; panel kanan **Verifikasi Status** (status berjenjang), input PIN/password, **Simpan & Kunci Presensi** dan **Simpan Draft**.
- **Jurnal Kelas** (tanpa mockup): pilih tanggal, mapel dan jam dari jadwal → kegiatan dan catatan → Simpan & Kirim (→ status `PENDING`).
- **Surat Izin** (tanpa mockup): form (siswa, jenis, rentang tanggal, tujuan, alasan, lampiran maks 5 MB, tanda tangan) + daftar pengajuan dan statusnya.
- **Rekap Harian** (`19`), **Laporan Jurnal Kelas** (`18`, tombol Export Rekap), **Jurnal Pelajaran** (`20`).

### Guru Pengajar
- **Dashboard** (`22`): kartu (Kelas Diampu, Jam Mengajar Hari Ini, Jurnal Terisi, Belum Diisi), "Jadwal Mengajar Hari Ini" dengan status jurnal dan tombol Isi Jurnal, "Ringkasan Presensi Kelas" (progress bar), Pengingat, Aktivitas Terbaru.
- **Jurnal Materi** (`24`): daftar jadwal hari ini + status (Terverifikasi/Menunggu/Belum Diisi) + Detail Jurnal + tombol **Konfirmasi** untuk jurnal kelas yang menunggu; form Input Materi & Rencana (mapel & kelas, pokok bahasan/KD, catatan/tugas, upload dokumentasi, **Kirim Rencana Belajar**).
- **Rekap Harian** (`21`), **Rekap Semester** (`23`: pilih semester + kelas, kartu Hari Efektif/Rata-rata/Alpha terendah, grafik batang mingguan, tabel detail per siswa berwarna sesuai persentase, **Cetak PDF/Excel**).

### Guru Pembimbing
- **Dashboard** (`26`): kartu (Kelas Binaan, Jurnal Terisi Hari Ini, Surat Izin Pending), tabel pemantauan jurnal per kelas, kartu **Persetujuan Surat Izin** dengan tombol Setujui/Tolak cepat.
- **Persetujuan Surat Izin** (`27`): daftar pending di kiri, detail di kanan (pemohon, NISN, jenis, durasi, alasan, lampiran unduh, catatan opsional, Tolak/Setujui).
- **Laporan Surat Izin** (`30`): filter status (Semua/Disetujui/Ditolak/Pending), kelas, bulan; pagination; Lihat Detail; **Ekspor Rekap (XLSX)**.
- **Manajemen User** (`29`): tab **Daftar Pengguna** (cari nama/NIP/NIS, filter peran, tabel, panel/Sheet **Tambah Pengguna Baru**: nama, peran, NIP/NISN, alokasi kelas, email, telepon, Batal/Simpan) dan tab **Konfigurasi Hak Akses** (tampilkan matriks peran × fitur dari `access.ts` secara **read-only**). Dukung aktif/nonaktifkan user dan reset sandi sementara. Setelah user baru dibuat → notifikasi.
- **Rekap Harian** (`25`), **Rekap Semester** (`28`), **Laporan Jurnal Kelas** (`18`).

### Notifikasi (semua peran, `31`)
- Filter: Semua / Belum Dibaca (n) / Jurnal / Surat Izin; tandai dibaca (satu atau semua).
- **Log Audit Aktivitas**: waktu, pengguna, tindakan, alamat IP. **Pembimbing melihat semua; peran lain hanya aktivitasnya sendiri.**

---

## 9. Responsif Multi-Perangkat (HP & PC/Laptop)

Satu codebase, mobile-first. Breakpoint: `<768px` HP, `768-1023px` tablet, `≥1024px` desktop.

- **AppShell**: desktop = sidebar tetap di kiri (seperti mockup) + header atas; HP = header ringkas + **bottom navigation** (4 menu utama) dan tombol "Lainnya" yang membuka **Sheet** berisi sisa menu, profil, dan Keluar. Perhatikan `safe-area-inset`.
- **StatGrid**: 4 kolom desktop → 2 kolom tablet/HP.
- **DataTable**: tabel penuh di ≥768px; di HP berubah menjadi **daftar kartu bertumpuk** (kolom utama sebagai judul kartu, sisanya baris label-nilai). Tidak boleh ada scroll horizontal pada body halaman.
- **Presensi di HP**: setiap siswa = satu kartu dengan 4 tombol status berukuran besar (segmented control), keterangan dan upload muncul di bawahnya, panel Verifikasi Status menjadi bagian bawah/sticky.
- Layout dua kolom (mis. persetujuan surat izin, manajemen user) → di HP menjadi satu kolom dengan detail dibuka sebagai halaman/Sheet.
- Target sentuh minimal 44×44px, input font ≥16px (cegah zoom iOS), grafik menyesuaikan lebar kontainer.
- Dukung light mode (sesuai mockup). Aksesibilitas dasar: label form, fokus terlihat, kontras cukup, warna status selalu disertai teks.
- Sertakan state **loading (skeleton), kosong, dan error** di setiap daftar/form.

---

## 10. Keamanan & Kualitas

- Validasi Zod di server untuk **semua** input. Pagination di server.
- Upload: whitelist PDF/PNG/JPG, maks 5 MB, nama file di-sanitasi, disajikan lewat route terotorisasi.
- Header keamanan dasar, cookie `httpOnly; Secure; SameSite=Lax`, proteksi CSRF (server actions/same-site), rate limit untuk login dan OTP.
- Tidak ada rahasia di repo; sediakan `.env.example` (`DATABASE_URL`, `SESSION_SECRET`, `OTP_PROVIDER`, `FONNTE_TOKEN`, `ENABLE_QUICK_LOGIN`, `STORAGE_DRIVER`).
- Query efisien (hindari N+1), index pada `classId`, `date`, `status`, `userId`.
- Tes minimal: unit test untuk aturan bisnis inti (hitung rekap/persentase, kunci presensi, siklus OTP, `requirePermission`) dan e2e/browser untuk 4 alur utama (login, presensi+kunci, surat izin kirim→setujui, lupa sandi).

---

## 11. Seed & Siap Pakai (kelas nyata)

- `prisma/seed.ts` **khusus dev**: 4 kelas (XI RPL 1, XI RPL 2, XII RPL 1, XII RPL 2), ±36 siswa di XI RPL 1, dan akun contoh yang sama dengan mockup: **Rina Safitri** (Sekretaris XI RPL 1), **Ahmad Fadillah** (Siswa), **Budi Santoso, S.Pd** (Guru Pengajar), **Siti Aminah, M.Pd** (Guru Pembimbing); jadwal, materi, presensi beberapa minggu, dan beberapa surat izin agar semua halaman terlihat hidup.
- Untuk produksi: perintah `npm run bootstrap:admin` membuat **akun Guru Pembimbing pertama** dari env/prompt CLI (tanpa data contoh), lalu Pembimbing menambah user lain lewat Manajemen User.
- (Opsional, akhir) **Impor CSV siswa** di Manajemen User agar satu kelas bisa dimasukkan sekaligus.
- Sertakan `README.md`: cara install, env, migrasi, seed, menjalankan, deploy, dan cara menyambungkan Fonnte.

---

## 12. Rencana Fase (kerjakan berurutan, berhenti & verifikasi tiap fase)

1. **Fondasi**: init proyek, Tailwind + shadcn, design tokens, skema Prisma + migrasi, `access.ts`, `constants.ts`, helper server (`requireUser`, `requirePermission`, `audit`, `notify`), `AppShell` responsif, komponen generik (`DataTable`, `StatCard`, `StatusBadge`, dll.).
2. **Autentikasi**: login, sesi, middleware, quick login (dev), lupa sandi 3 langkah + sukses, OTP (console + stub Fonnte), 403/404.
3. **Siswa**: dashboard, jurnal pelajaran, riwayat kehadiran + grafik.
4. **Sekretaris**: presensi (draft/kunci), jurnal kelas, surat izin, dashboard, rekap harian.
5. **Guru Pengajar**: jurnal materi, konfirmasi jurnal, dashboard, laporan jurnal kelas, rekap harian, rekap semester + ekspor.
6. **Guru Pembimbing**: dashboard, persetujuan & laporan surat izin (+XLSX), manajemen user, tab hak akses.
7. **Notifikasi & Log Audit** (semua peran).
8. **Polesan & QA**: audit duplikasi kode (lihat bagian 4), uji 375/768/1280px, tes, seed, README.

---

## 13. Kriteria Selesai (Definition of Done)

- [ ] Keempat peran bisa login dan hanya melihat menu/halaman/data yang menjadi haknya; akses langsung via URL atau server action ditolak.
- [ ] Alur utama berjalan end-to-end: Sekretaris isi presensi → kunci PIN → rekap pengajar/pembimbing ter-update otomatis; surat izin dikirim → disetujui → presensi terisi → notifikasi terkirim; jurnal kelas dikirim → dikonfirmasi pengajar → `Terverifikasi`.
- [ ] Lupa sandi 3 langkah berfungsi penuh dengan `ConsoleOtpSender`; `FonnteOtpSender` berupa stub yang siap saya isi; tidak ada panggilan WhatsApp nyata.
- [ ] Ekspor PDF/Excel (rekap) dan XLSX (laporan surat izin) menghasilkan file yang benar.
- [ ] Tampilan rapi dan fungsional di 375px, 768px, dan 1280px; tidak ada scroll horizontal pada body.
- [ ] Tidak ada duplikasi pola yang bisa diekstrak; perbedaan antar peran hanya di `access.ts`; tidak ada tipe/konstanta kembar.
- [ ] Build, lint, typecheck, dan tes lolos. `README.md` dan `.env.example` lengkap.

Mulai dengan **membaca `docs/design/`, lalu tampilkan implementation plan** sebelum menulis kode.
