# E-BOOK PANDUAN PENGGUNAAN DAN DESKRIPSI SISTEM (USER MANUAL)
## SISTEM INFORMASI RENTAL MOBIL MULTI-VENDOR BERBASIS WEB

---

### KATA PENGANTAR

Puji syukur ke hadirat Tuhan Yang Maha Esa atas rahmat dan karunia-Nya, sehingga Dokumen Buku Panduan Penggunaan (*User Manual E-Book*) untuk **Sistem Informasi Rental Mobil Multi-Vendor Berbasis Web** ini dapat diselesaikan dengan baik.

Buku panduan ini disusun secara terstruktur untuk memberikan panduan operasional langkah demi langkah (*step-by-step*) serta penjelasan teknis mendalam mengenai tata cara kerja sistem kepada seluruh pemangku kepentingan (*multi-stakeholders*). Sistem ini menghubungkan berbagai pihak dalam satu ekosistem digital transportasi terintegrasi, yang mencakup Pelanggan (*Customer*), Mitra Pengusaha Rental (*Vendor*), Pengawas Platform (*Administrator*), hingga Pengemudi Lapangan (*Driver*).

Buku panduan ini dibatasi khusus hingga **Bab 5 (Panduan Pengoperasian Sistem Secara Menyeluruh)** sebagai manual operasional dan deskripsi arsitektur sistem, sementara dokumen kelengkapan yuridis pendaftaran Hak Cipta (HAKI) disusun dalam berkas terpisah.

Semoga e-book panduan ini dapat memberikan manfaat nyata, memudahkan proses implementasi, serta menjadi rujukan operasional yang komprehensif bagi seluruh pengguna sistem.

Surabaya, 2026  
**Tim Pengembang Sistem Informasi Rental Mobil**

---

### GLOSARIUM DAN DAFTAR ISTILAH TEKNIS

Untuk mempermudah pemahaman pengguna dalam mengoperasikan sistem, berikut adalah definisi operasional dari istilah-istilah yang digunakan:

* **Multi-Vendor Marketplace:** Model ekosistem digital terdistribusi di mana berbagai pemilik bisnis rental mobil independen dapat mendaftarkan garasi mereka, memajang katalog armada, menetapkan tarif mandiri, dan bertransaksi langsung dengan penyewa di bawah pengawasan Superadmin.
* **Lepas Kunci (*Self-Drive*):** Layanan sewa kendaraan mandiri di mana penyewa mengendarai armada sendiri tanpa didampingi oleh supir resmi rental.
* **Dengan Supir (*With Driver*):** Layanan sewa armada yang menyertakan pengemudi profesional berlisensi dari pihak mitra rental dengan perhitungan tarif harian terintegrasi.
* **Smart Availability Engine:** Mesin validasi otomatis pada lapisan logika bisnis yang memeriksa ketersediaan tanggal armada mobil dan jadwal penugasan supir secara simultan untuk meniadakan benturan pesanan ganda (*double-booking*).
* **Date Blocking Calendar:** Fitur kalender interaktif yang memberikan wewenang kepada mitra rental untuk mengunci ketersediaan armada pada rentang tanggal tertentu (misalnya saat mobil masuk bengkel/perawatan rutin atau disewa secara luring/offline).
* **Handover (Serah Terima):** Prosedur formal pemeriksaan kondisi fisik kendaraan, pencatatan odometer (kilometer awal/akhir), level bahan bakar, dan penyerahan kunci antara vendor/sopir dan penyewa.
* **Late Fee Charge:** Tagihan denda keterlambatan finansial otomatis yang diterbitkan kepada penyewa saat pengembalian kendaraan melewati batas toleransi durasi sewa.
* **Dispute Resolution (Sengketa Denda):** Fasilitas sanggahan digital bagi penyewa untuk mengajukan banding atas tagihan denda keterlambatan jika diakibatkan kendala teknis armada atau kelalaian vendor, dengan putusan akhir dimediasi secara independen oleh Administrator.
* **Car Change Request:** Fitur pengajuan penukaran tipe unit kendaraan sebelum masa sewa dimulai (maksimal H-1) yang menghitung selisih pembayaran atau *refund* secara otomatis.
* **Live GPS Tracking & Emergency SOS:** Sistem telemetri pemantauan rute kendaraan secara *real-time* berbasis Leaflet JS & OpenStreetMap yang dilengkapi tombol alarm darurat bahaya di jalan raya.
* **Vendor Subscription Plan:** Mekanisme keanggotaan berkala (berjenjang) bagi vendor yang menentukan batas kuota armada aktif yang dapat dipasarkan di platform.
* **TALL Stack:** Kombinasi teknologi antarmuka modern yang terdiri dari **T**ailwind CSS, **A**lpine.js, **L**aravel, dan **L**ivewire.

---

### DAFTAR ISI

- [KATA PENGANTAR](#kata-pengantar)
- [GLOSARIUM DAN DAFTAR ISTILAH TEKNIS](#glosarium-dan-daftar-istilah-teknis)
- [BAB 1: PENDAHULUAN DAN GAMBARAN UMUM SISTEM](#bab-1-pendahuluan-dan-gambaran-umum-sistem)
  - [1.1 Latar Belakang Permasalahan](#11-latar-belakang-permasalahan)
  - [1.2 Tujuan dan Sasaran Sistem](#12-tujuan-dan-sasaran-sistem)
  - [1.3 Nilai Kebaruan (*Novelty*) dan Keunggulan Inovasi](#13-nilai-kebaruan-novelty-dan-keunggulan-inovasi)
  - [1.4 Sasaran Pengguna (*User Roles*)](#14-sasaran-pengguna-user-roles)
- [BAB 2: ARSITEKTUR DAN SPESIFIKASI TEKNOLOGI](#bab-2-arsitektur-dan-spesifikasi-teknologi)
  - [2.1 Kebutuhan Perangkat Keras (*Hardware Requirements*)](#21-kebutuhan-perangkat-keras-hardware-requirements)
  - [2.2 Kebutuhan Perangkat Lunak (*Software Stack*)](#22-kebutuhan-perangkat-lunak-software-stack)
  - [2.3 Arsitektur Multi-Layer Perangkat Lunak](#23-arsitektur-multi-layer-perangkat-lunak)
  - [2.4 Integrasi Layanan Pihak Ketiga (*APIs & External Services*)](#24-integrasi-layanan-pihak-ketiga-apis--external-services)
- [BAB 3: PERANCANGAN BASIS DATA DAN KAMUS DATA](#bab-3-perancangan-basis-data-dan-kamus-data)
  - [3.1 Diagram Hubungan Entitas (*Entity Relationship Diagram*)](#31-diagram-hubungan-entitas-entity-relationship-diagram)
  - [3.2 Kamus Data Entitas Utama (*Data Dictionary*)](#32-kamus-data-entitas-utama-data-dictionary)
- [BAB 4: STRUKTUR PENGGUNA DAN DIAGRAM ALUR BISNIS](#bab-4-struktur-pengguna-dan-diagram-alur-bisnis)
  - [4.1 Matriks Peran dan Hak Akses Pengguna (*Role Access Matrix*)](#41-matriks-peran-dan-hak-akses-pengguna-role-access-matrix)
  - [4.2 Use Case Diagram Sistem](#42-use-case-diagram-sistem)
  - [4.3 Sequence Diagram Alur Transaksi Kritis](#43-sequence-diagram-alur-transaksi-kritis)
- [BAB 5: PANDUAN PENGOPERASIAN SISTEM SECARA MENYELURUH (USER MANUAL)](#bab-5-panduan-pengoperasian-sistem-secara-menyeluruh-user-manual)
  - [5.1 Membuka dan Menjalankan Sistem (*Accessing System*)](#51-membuka-dan-menjalankan-sistem-accessing-system)
  - [5.2 Panduan Modul Pelanggan (*Customer Web Portal*)](#52-panduan-modul-pelanggan-customer-web-portal)
  - [5.3 Panduan Modul Mitra Usaha (*Vendor Filament Panel*)](#53-panduan-modul-mitra-usaha-vendor-filament-panel)
  - [5.4 Panduan Modul Pengawas Utama (*Administrator Filament Panel*)](#54-panduan-modul-pengawas-utama-administrator-filament-panel)
  - [5.5 Panduan Modul Pengemudi Lapangan (*Driver Quick Mobile Web*)](#55-panduan-modul-pengemudi-lapangan-driver-quick-mobile-web)

---

# BAB 1: PENDAHULUAN DAN GAMBARAN UMUM SISTEM

### 1.1 Latar Belakang Permasalahan
Industri transportasi dan penyewaan mobil memegang peranan krusial dalam menyokong mobilitas pariwisata, kegiatan korporasi, serta logistik masyarakat. Kendati demikian, sebagian besar pelaku usaha rental skala Usaha Mikro, Kecil, dan Menengah (UMKM) masih mengandalkan sistem konvensional (pencatatan manual di buku kas atau pemesanan melalui WhatsApp).

Metode manual tersebut kerap menimbulkan persoalan fatal:
1. **Pemesanan Ganda (*Double-Booking*):** Terjadinya benturan jadwal dua pelanggan yang menyewa unit yang sama di waktu beririsan akibat ketiadaan sinkronisasi sistem.
2. **Ketidakpastian Jadwal Sopir:** Manajemen penugasan pengemudi yang tidak terintegrasi menyebabkan supir yang masih bertugas di luar kota terpilih kembali untuk pesanan baru.
3. **Konflik Biaya Denda Keterlambatan:** Ketiadaan regulasi perhitungan denda berbasis jam yang transparan serta nihilnya wadah pengajuan keberatan (*dispute*) yang netral ketika terjadi kendala di jalan.
4. **Resiko Keamanan di Perjalanan:** Minimnya fasilitas pelacakan lokasi kendaraan langsung (*real-time GPS*) serta tidak tersedianya tombol darurat terintegrasi saat pelanggan menghadapi masalah di jalan raya.
5. **Keterbatasan Skalabilitas Vendor:** Sulitnya vendor rental lokal memperluas pangsa pasar karena mahalnya biaya pembuatan aplikasi digital mandiri.

Berangkat dari problematika tersebut, dibangun **"Sistem Informasi Rental Mobil Multi-Vendor Berbasis Web"** sebagai solusi pasar bersama (*marketplace*) yang aman, transparan, dan terotomatisasi.

### 1.2 Tujuan dan Sasaran Sistem
Tujuan pengembangan sistem informasi ini meliputi:
1. **Pusat Ekosistem Terpadu:** Membangun platform multi-vendor yang memungkinkan pelaku usaha rental mendaftar, memvalidasi legalitas usaha, mengelola armada mobil, mengatur tarif, dan memonitor pendapatan secara otonom.
2. **Otomatisasi Validasi Ketersediaan:** Mengimplementasikan mesin validasi ketersediaan (*Smart Availability Engine*) yang memfilter benturan armada dan sopir secara *real-time*.
3. **Pembayaran Multi-Kanal Terotomatisasi:** Mengintegrasikan gerbang pembayaran digital Midtrans (QRIS, Virtual Account, E-Wallet) dan verifikasi transfer manual untuk menjamin transaksi aman.
4. **Perlindungan Sengketa Berkeadilan:** Menyediakan modul *Late Fee Dispute Resolution* yang memungkinkan pelanggan menyanggah denda keterlambatan dengan verifikasi data objektif oleh Administrator.
5. **Telemetri & Keamanan:** Menyediakan pelacakan lokasi rute berbasis peta Leaflet OSM dan pengiriman peringatan darurat seketika (*Emergency SOS*).

### 1.3 Nilai Kebaruan (*Novelty*) dan Keunggulan Inovasi
Karya perangkat lunak ini memiliki keunggulan kompetitif yang membedakannya dari sistem rental konvensional:
1. **Multi-Guard Independent Authentication:** Memisahkan sesi otentikasi antara pelanggan (`web` guard), mitra rental (`vendor` guard via Filament), dan pengawas sistem (`admin` guard via Filament) guna menjamin batas hak akses yang aman dan terisolasi.
2. **Sinkronisasi Ganda Armada & Supir:** Validasi cerdas yang memeriksa waktu bebas tugas mobil sekaligus pengemudi sebelum pesanan disetujui.
3. **Fasilitas Tukar Mobil Pra-Sewa (*Car Change Request*):** Fleksibilitas bagi konsumen untuk menukar unit mobil sebelum H-1 masa sewa dengan kalkulasi selisih tarif otomatis.
4. **Pusat Sengketa Denda (*Dispute Center*):** Mekanisme mediasi peradilan digital untuk menelaah bukti foto, argumen kronologi, dan log GPS saat terjadi klaim denda.
5. **Portal Sopir Mobile Nir-Instalasi:** Antarmuka web responsif berbasis tanda tangan terenkripsi yang memungkinkan sopir mengonfirmasi tugas dan melaporkan kendala tanpa harus menginstal aplikasi native di ponsel.

### 1.4 Sasaran Pengguna (*User Roles*)
Sistem ini dirancang untuk melayani empat kategori pengguna utama:
1. **Pelanggan (*Customer*):** Konsumen yang mencari, membandingkan spesifikasi, memesan, membayar sewa mobil, memantau posisi GPS, dan menyampaikan ulasan.
2. **Mitra Rental (*Vendor*):** Pemilik usaha rental terverifikasi yang mengelola armada, kalender pemblokiran, supir, serah terima mobil, serta memantau pencairan pendapatan.
3. **Administrator (*Superadmin*):** Pengelola platform yang memverifikasi izin usaha mitra, menyetujui pembayaran manual, memediasi sengketa denda, dan mengawasi lalu lintas darurat.
4. **Pengemudi (*Driver*):** Staf lapangan yang bertugas mengantar armada, mengonfirmasi serah terima fisik (*handover*), dan melaporkan hambatan perjalanan.

---

# BAB 2: ARSITEKTUR DAN SPESIFIKASI TEKNOLOGI

### 2.1 Kebutuhan Perangkat Keras (*Hardware Requirements*)

#### A. Lingkungan Server Produksi (*Host Server*)
* **Processor:** Minimal Multi-Core 2.0 GHz vCPU (Disarankan 4 vCPU atau lebih tinggi).
* **RAM:** Minimal 2.0 GB (Disarankan 4.0 GB untuk menangani pemrosesan PDF dan transaksi bersamaan).
* **Penyimpanan:** Minimal 20 GB NVMe / SSD dengan partisi aman untuk dokumen terenkripsi.
* **Jaringan:** Koneksi internet stabil minimal 100 Mbps dengan IP Statis dan sertifikat SSL/TLS (HTTPS).

#### B. Lingkungan Perangkat Klien (*End-User Devices*)
* **Perangkat:** Komputer Desktop, Laptop, Tablet, atau Ponsel Pintar (*Smartphone* Android/iOS).
* **Resolusi Layar:** Mendukung resolusi dinamis mulai 360 x 640 piksel (*mobile viewport*) hingga 1920 x 1080 piksel (*Full HD desktop*).
* **Peramban Web:** Google Chrome, Mozilla Firefox, Microsoft Edge, atau Safari dengan fitur JavaScript aktif.

### 2.2 Kebutuhan Perangkat Lunak (*Software Stack*)

```
+-------------------------------------------------------------------------------+
|                      KOMPOSISI SPESIFIKASI TEKNOLOGI SISTEM                   |
+----------------------+--------------------------------------------------------+
| Komponen             | Spesifikasi / Pustaka yang Digunakan                   |
+----------------------+--------------------------------------------------------+
| Sistem Operasi Server| Ubuntu Linux 22.04 LTS / Windows Server (XAMPP/Laragon)|
| Bahasa Pemrograman   | PHP v8.2+ atau v8.3+, JavaScript (ES6+), SQL, HTML5    |
| Web Server Engine    | Nginx v1.24+ / Apache HTTP Server v2.4+                |
| Framework Backend    | Laravel Framework v11.x (Service-Driven MVC Engine)    |
| Admin & Vendor Panel | Filament PHP Framework v3.x (TALL Stack)               |
| Sistem Basis Data    | MySQL Database Engine v8.0+ / MariaDB v10.4+           |
| Kompiler Frontend    | Node.js LTS Engine v20.x & Vite Asset Bundler v5.x     |
| Pustaka UI & Desain  | Tailwind CSS v3.x, Alpine.js v3.x, Livewire v3.x       |
| Geospasial & Peta    | Leaflet.js v1.9+ & OpenStreetMap Tile Provider         |
| Format Pertukaran    | RESTful JSON Data Standard                             |
+----------------------+--------------------------------------------------------+
```

### 2.3 Arsitektur Multi-Layer Perangkat Lunak
Sistem menerapkan arsitektur berjenjang (*layered architecture*) yang mengombinasikan konsep **Model-View-Controller (MVC)** dengan **Service Layer Pattern**:

```
+-----------------------------------------------------------------------------------+
|                            PRESENTATION & VIEW LAYER                              |
|   - Blade View Templates (Customer Portal)        - Livewire Components (Reaktif) |
|   - Filament Resource Pages (Vendor & Admin)      - Mobile Driver Portal Web View |
+-----------------------------------------+-----------------------------------------+
                                          |
                                          v
+-----------------------------------------------------------------------------------+
|                        ROUTING, GUARDS & SECURITY MIDDLEWARE                      |
|   - Multi-Guard Auth (web, vendor, admin)         - CSRF Protection Layer         |
|   - Throttle Rate Limiter (Anti-Brute Force)      - Encrypted Signature Middleware|
+-----------------------------------------+-----------------------------------------+
                                          |
                                          v
+-----------------------------------------------------------------------------------+
|                       CONTROLLERS & RESTFUL API HANDLERS                          |
|   - BookingController        - PaymentController       - LiveTrackingController   |
|   - LateFeeController        - CarChangeController     - EmergencyController      |
+-----------------------------------------+-----------------------------------------+
                                          |
                                          v
+-----------------------------------------------------------------------------------+
|                     SERVICE LAYER (CORE BUSINESS LOGIC ENGINE)                    |
|   - CarAvailabilityService   - LateReturnService       - MidtransPaymentService   |
|   - DriverAssignmentService  - SubscriptionService     - PayoutCalculationService |
+-----------------------------------------+-----------------------------------------+
                                          |
                                          v
+-----------------------------------------------------------------------------------+
|                     DATA ACCESS LAYER (ELOQUENT ORM & REPOSITORY)                 |
|   - Model Relationships (Polymorphic, HasMany, BelongsTo)  - Database Query Scopes|
+-----------------------------------------+-----------------------------------------+
                                          |
                                          v
+-----------------------------------------------------------------------------------+
|                       PERSISTENCE LAYER (MySQL / MariaDB RDBMS)                   |
+-----------------------------------------------------------------------------------+
```

### 2.4 Integrasi Layanan Pihak Ketiga (*APIs & External Services*)
1. **Midtrans Snap Payment Gateway:** Memproses transaksi digital otomatis melalui QRIS, Bank Virtual Account (BCA, BNI, BRI, Mandiri), dan Dompet Digital (GoPay, ShopeePay) secara *real-time* dengan validasi tanda tangan kriptografi SHA-512.
2. **Google Identity OAuth 2.0:** Fasilitas login dan registrasi cepat satu klik (*one-click social login*) bagi penyewa.
3. **OpenStreetMap & Leaflet Geolocation Engine:** Menampilkan pemetaan rute kendaraan, koordinat lokasi penjemputan, serta titik lokasi darurat.
4. **DomPDF Engine & Maatwebsite Excel:** Pustaka otomatisasi pencetakan invoice sewa resmi, bukti kuitansi denda, lembar serah terima, dan pembukuan finansial bulanan.

---

# BAB 3: PERANCANGAN BASIS DATA DAN KAMUS DATA

### 3.1 Diagram Hubungan Entitas (*Entity Relationship Diagram*)
Struktur relasi basis data menghubungkan entitas utama dengan integritas referensial yang kuat:

```
  +------------------+         +------------------+         +------------------+
  |      USERS       | 1     1 |     CUSTOMERS    | 1     N |     BOOKINGS     |
  |------------------|---------|------------------|---------|------------------|
  | id (PK)          |         | id (PK)          |         | id (PK)          |
  | email            |         | user_id (FK)     |         | code (Unique)    |
  | password         |         | phone, ktp_no    |         | customer_id (FK) |
  | role             |         | driver_license   |         | car_id (FK)      |
  +------------------+         +------------------+         | driver_id (FK)   |
           | 1                                              | start_date, end  |
           |                                                | grand_total      |
           | 1                                              | status           |
  +------------------+                                      +------------------+
  |     VENDORS      | 1     N +------------------+                  | 1
  |------------------|---------|       CARS       | 1                |
  | id (PK)          |         |------------------|                  |
  | user_id (FK)     |         | id (PK)          |                  | 1..N
  | company_name     |         | vendor_id (FK)   |         +------------------+
  | nib_number       |         | brand, model     |         |     PAYMENTS     |
  | is_verified      |         | license_plate    |         |------------------|
  +------------------+         | price_per_day    |         | id (PK)          |
           | 1                 | is_active        |         | booking_id (FK)  |
           |                   +------------------+         | payment_method   |
           | 1..N                                           | amount, status   |
  +------------------+                                      +------------------+
  |     DRIVERS      |                                               | 1
  |------------------|                                               |
  | id (PK)          |                                               | 1..N
  | vendor_id (FK)   |                                      +--------------------+
  | name, phone      |                                      |  LATE_FEE_CHARGES  |
  | sim_number       |                                      |--------------------|
  | is_active        |                                      | id (PK)            |
  +------------------+                                      | booking_id (FK)    |
                                                            | hours_delayed      |
                                                            | total_charge       |
                                                            | dispute_status     |
                                                            +--------------------+
```

### 3.2 Kamus Data Entitas Utama (*Data Dictionary*)

#### 1. Entitas Tabel `users`
Menyimpan kredensial otentikasi akun seluruh aktor sistem:
* `id` (BigInt, PK, Auto Increment): ID unik pengguna.
* `name` (Varchar 255): Nama lengkap pengguna.
* `email` (Varchar 255, Unique): Alamat surel untuk otentikasi login.
* `password` (Varchar 255): Kata sandi terenkripsi algoritma Bcrypt.
* `role` (Enum: 'admin', 'vendor', 'customer'): Penentu tingkat hak akses pengguna.
* `email_verified_at` (Timestamp, Nullable): Waktu konfirmasi aktivasi akun surel.

#### 2. Entitas Tabel `vendors`
Menyimpan profil badan usaha mitra rental mobil:
* `id` (BigInt, PK, Auto Increment): ID unik mitra.
* `user_id` (BigInt, FK): Referensi berelasi ke akun pada tabel `users`.
* `company_name` (Varchar 255): Nama komersial usaha rental.
* `nib_number` (Varchar 100, Nullable): Nomor Induk Berusaha (NIB) / NPWP.
* `address` (Text): Alamat lengkap lokasi kantor garasi fisik.
* `bank_name`, `bank_account_number`, `bank_account_holder` (Varchar): Rekening pencairan pendapatan.
* `status` (Enum: 'pending', 'approved', 'rejected', 'suspended'): Status verifikasi legalitas mitra.

#### 3. Entitas Tabel `cars`
Menyimpan katalog spesifikasi dan tarif armada sewa:
* `id` (BigInt, PK, Auto Increment): ID unik unit mobil.
* `vendor_id` (BigInt, FK): Pemilik armada (mitra rental terkait).
* `brand_id`, `model_id`, `category_id` (BigInt, FK): Klasifikasi pabrikan dan tipe unit.
* `license_plate` (Varchar 20, Unique): Nomor plat registrasi kendaraan.
* `transmission` (Enum: 'manual', 'automatic'): Jenis sistem transmisi.
* `fuel_type` (Enum: 'bensin', 'diesel', 'hybrid', 'electric'): Jenis bahan bakar.
* `seat_capacity` (Integer): Jumlah kapasitas penumpang.
* `price_per_day` (Decimal 12,2): Tarif dasar sewa harian (lepas kunci).
* `driver_fee_per_day` (Decimal 12,2): Biaya harian tambahan untuk supir resmi.
* `status` (Enum: 'active', 'maintenance', 'inactive'): Status kesiapan unit.

#### 4. Entitas Tabel `bookings`
Menyimpan transaksi sewa menyewa armada kendaraan:
* `id` (BigInt, PK, Auto Increment): ID unik transaksi.
* `code` (Varchar 50, Unique): Kode alfanumerik pesanan (contoh: `BK-202608-0012`).
* `customer_id` (BigInt, FK): Referensi penyewa.
* `car_id` (BigInt, FK): Unit armada mobil yang disewa.
* `driver_id` (BigInt, FK, Nullable): Pengemudi yang ditugaskan (jika opsi supir dipilih).
* `with_driver` (Boolean): Indikator sewa lepas kunci (false) atau dengan supir (true).
* `start_date`, `end_date` (DateTime): Periode rentang waktu pemakaian sewa.
* `pickup_location`, `dropoff_location` (Text): Alamat titik serah terima armada.
* `grand_total` (Decimal 12,2): Total nominal pembayaran transaksi.
* `status` (Enum: 'pending', 'confirmed', 'ongoing', 'completed', 'cancelled', 'rejected'): Status siklus hidup pesanan.

#### 5. Entitas Tabel `late_fee_charges`
Mencatat tagihan denda keterlambatan dan alur sanggahan sengketa:
* `id` (BigInt, PK, Auto Increment): ID tagihan denda.
* `booking_id` (BigInt, FK): Referensi transaksi sewa terkait.
* `hours_delayed` (Integer): Jumlah jam keterlambatan pengembalian unit.
* `hourly_rate` (Decimal 12,2): Tarif denda per jam yang berlaku.
* `total_charge` (Decimal 12,2): Total nilai denda yang harus dilunasi.
* `payment_status` (Enum: 'unpaid', 'paid', 'waived'): Status pembayaran denda.
* `dispute_status` (Enum: 'none', 'pending', 'approved', 'rejected'): Status sanggahan denda penyewa.
* `dispute_reason` (Text, Nullable): Argumen alasan sanggahan pelanggan.
* `dispute_proof_path` (Varchar, Nullable): Lokasi berkas foto bukti sanggahan.
* `admin_resolution_notes` (Text, Nullable): Catatan putusan independen Administrator.

#### 6. Entitas Tabel `emergency_reports`
Menyimpan data peringatan bahaya insiden di jalan raya:
* `id` (BigInt, PK, Auto Increment): ID laporan darurat.
* `booking_id` (BigInt, FK): Referensi transaksi sewa yang mengalami kendala.
* `latitude`, `longitude` (Decimal 10,8): Koordinat GPS saat tombol SOS diaktifkan.
* `emergency_type` (Enum: 'accident', 'breakdown', 'security_threat', 'medical'): Jenis insiden.
* `description` (Text): Rincian kronologi insiden darurat.
* `status` (Enum: 'reported', 'in_progress', 'resolved'): Progres penanganan posko tanggap darurat.

---

# BAB 4: STRUKTUR PENGGUNA DAN DIAGRAM ALUR BISNIS

### 4.1 Matriks Peran dan Hak Akses Pengguna (*Role Access Matrix*)

| Fitur & Wewenang Sistem | Pelanggan (*Customer*) | Mitra Rental (*Vendor*) | Administrator | Pengemudi (*Driver*) |
| :--- | :---: | :---: | :---: | :---: |
| Registrasi & Manajemen Akun Mandiri | **V** | **V** | **V** | - |
| Pencarian, Filter & Komparasi Armada | **V** | - | **V** | - |
| Pemesanan Mobil & Pembayaran Digital (Midtrans) | **V** | - | - | - |
| Pengajuan Tukar Mobil (*Car Change*) | **V** | - | - | - |
| Pemantauan Live GPS & Tombol Darurat SOS | **V** | **V** | **V** | - |
| Pengajuan Sengketa Denda (*Late Dispute*) | **V** | - | - | - |
| Unggah Dokumen Legalitas Usaha (KYC) | - | **V** | - | - |
| Manajemen Katalog Mobil & Blokir Kalender | - | **V** | **V** | - |
| Manajemen Profil Supir & Penugasan Tugas | - | **V** | - | - |
| Penerbitan Tagihan Denda Keterlambatan | - | **V** | - | - |
| Konfirmasi Check-in/Check-out Serah Terima | - | **V** | - | **V** |
| Verifikasi Akun & Legalitas Mitra Usaha | - | - | **V** | - |
| Validasi Transfer Pembayaran Manual | - | - | **V** | - |
| Mediasi & Putusan Final Sengketa Denda | - | - | **V** | - |
| Ekspor Laporan Finansial (PDF / Excel) | Pribadi | Vendor | Master Global| - |

### 4.2 Use Case Diagram Sistem

```
                         SISTEM INFORMASI RENTAL MOBIL MULTI-VENDOR
  +-----------------------------------------------------------------------------------+
  |                                                                                   |
  |   (Registrasi & Login Multi-Guard) <=============================== [Pengguna]    |
  |                                                                                   |
  |   [Customer] ----> (Cari & Bandingkan Mobil)                                      |
  |              ----> (Booking Armada: Lepas Kunci / Sopir)                          |
  |              ----> (Pembayaran: Midtrans / Transfer Manual)                       |
  |              ----> (Live GPS Tracking & Emergency SOS)                            |
  |              ----> (Ajukan Ganti Mobil H-1)                                       |
  |              ----> (Sanggahan Denda / Late Dispute)                               |
  |              ----> (Ulasan & Rating Transaksi)                                    |
  |                                                                                   |
  |   [Vendor]   ----> (Onboarding Legalitas Usaha)                                   |
  |              ----> (Kelola Data Armada & Blocking Kalender)                       |
  |              ----> (Kelola Sopir & Penugasan)                                     |
  |              ----> (Serah Terima Kendaraan: Handover)                             |
  |              ----> (Terbitkan Denda Keterlambatan)                                |
  |              ----> (Laporan Keuangan & Rekap PDF/CSV)                             |
  |                                                                                   |
  |   [Admin]    ----> (Verifikasi Legalitas Mitra Vendor)                            |
  |              ----> (Validasi Bukti Pembayaran Manual)                             |
  |              ----> (Mediasi & Putusan Sengketa Denda)                             |
  |              ----> (Monitoring Payout & Emergency Global)                         |
  |                                                                                   |
  |   [Driver]   ----> (Konfirmasi Serah Terima / Pengembalian)                       |
  |              ----> (Lapor Hambatan Jalan / Keterlambatan)                         |
  |                                                                                   |
  +-----------------------------------------------------------------------------------+
```

### 4.3 Sequence Diagram Alur Transaksi Kritis

#### A. Alur Pemesanan & Pembayaran Midtrans
Alur interaksi ketika pelanggan memesan kendaraan dan melakukan pembayaran:
1. Pelanggan memilih rentang tanggal pemakaian dan opsi supir.
2. Sistem mengeksekusi `CarAvailabilityService` untuk memverifikasi ketiadaan benturan jadwal.
3. Apabila tanggal tersedia, sistem menampilkan rincian *checkout* dan memanggil Midtrans Snap.
4. Pelanggan melakukan pelunasan tagihan melalui QRIS atau Virtual Account.
5. Webhook Midtrans mengirimkan notifikasi lunas ke sistem, sistem mengotomatisasi status pesanan menjadi `CONFIRMED`, menerbitkan invoice digital PDF, serta mengirimkan notifikasi ke panel vendor.

#### B. Alur Pengembalian, Keterlambatan, dan Sengketa Denda (*Dispute Resolution*)
Alur ketika terjadi keterlambatan pengembalian unit sewa:
1. Pihak vendor melakukan pengecekan waktu kepulangan kendaraan di garasi.
2. Apabila terdeteksi keterlambatan di luar waktu toleransi, vendor menginput jumlah jam terlambat dan sistem menerbitkan tagihan denda (*Late Fee Charge*).
3. Notifikasi tagihan denda masuk ke akun penyewa.
4. Jika penyewa setuju, penyewa melunasi tagihan denda secara langsung.
5. Jika penyewa merasa keterlambatan bukan kesalahannya (misalnya mobil mogok karena kerusakan radiator), penyewa menekan tombol **Ajukan Sengketa (Dispute)**, menyertakan argumen tertulis, dan mengunggah foto bukti derek/kerusakan.
6. Berkas sengketa diteruskan ke Meja Kerja Administrator. Admin memeriksa log GPS, durasi perjalanan, dan tanggapan vendor.
7. Administrator menetapkan putusan mengikat: menerima sengketa (denda dihapuskan/dikurangi) atau menolak sengketa (denda wajib dilunasi penuh).

---

# BAB 5: PANDUAN PENGOPERASIAN SISTEM SECARA MENYELURUH (USER MANUAL)

### 5.1 Membuka dan Menjalankan Sistem (*Accessing System*)
1. Buka peramban web modern (Google Chrome, Mozilla Firefox, atau Microsoft Edge).
2. Masukkan alamat URL resmi aplikasi pada bilah peramban:
   * **Portal Publik Pelanggan:** `http://localhost/` (atau domain produksi).
   * **Panel Manajemen Vendor:** `http://localhost/vendor`
   * **Panel Administrator Utama:** `http://localhost/admin`
   * **Portal Responsif Sopir:** Akses langsung melalui tautan instan terenkripsi yang dikirimkan melalui notifikasi penugasan.
3. Tekan **Enter**. Antarmuka beranda sistem akan tampil secara langsung di layar perangkat Anda.

---

### 5.2 Panduan Modul Pelanggan (*Customer Web Portal*)

#### 1. Pendaftaran Akun, Masuk Sistem, dan Pengalih Akun (*Account Switcher*)
* **Pendaftaran Baru:** Klik tombol **Daftar** di sudut kanan atas. Lengkapi nama lengkap, alamat surel aktif, nomor telepon WhatsApp, dan kata sandi, lalu klik **Daftar Sekarang**. Alternatif lain, klik **Masuk dengan Google** untuk otentikasi otomatis.
* **Masuk Sistem (*Login*):** Masukkan alamat surel dan kata sandi, lalu klik **Masuk**.
* **Fitur Pengalih Akun (*Account Switcher*):** Klik foto profil di sudut kanan atas, lalu pilih **Tambah Akun**. Pengguna dapat mendaftarkan akun kedua (misalnya akun keperluan bisnis kantor) dan berpindah akun secara instan tanpa perlu melakukan proses *logout*.

#### 2. Pencarian Cerdas, Filter Kategori, dan Komparasi Mobil (*Compare Cars*)
* **Pencarian Mobil:** Pada kotak pencarian beranda, tentukan tanggal mulai dan tanggal selesai sewa, lalu pilih preferensi transmisi (Manual/Otomatis). Klik tombol **Cari Mobil**. Sistem hanya akan menampilkan kendaraan yang berstatus bebas sewa pada tanggal tersebut.
* **Membandingkan Spesifikasi (*Compare*):** Klik ikon timbangan pada kartu mobil (dapat memilih hingga 3 unit). Buka tautan `/compare` untuk melihat perbandingan berdampingan mengenai kapasitas penumpang, konsumsi BBM, fasilitas AC, dan tarif harian.
* **Daftar Keinginan (*Wishlist*):** Klik ikon hati untuk menyimpan kendaraan ke daftar favorit akun Anda.

#### 3. Formulir Pemesanan Unit Kendaraan (*Booking Form*)
* Klik kartu mobil yang diinginkan untuk membuka halaman detail lengkap.
* Pada panel pemesanan sebelah kanan:
  1. Periksa kembali tanggal awal dan tanggal akhir sewa.
  2. Pilih jenis layanan: **Lepas Kunci** atau **Dengan Supir**.
  3. Apabila memilih opsi dengan supir, sistem menampilkan daftar pengemudi vendor yang bebas tugas pada tanggal tersebut lengkap dengan penilaian bintang dan pengalaman kerja.
  4. Masukkan alamat penjemputan armada pada kolom catatan.
* Klik tombol **Lanjutkan ke Pembayaran**.

#### 4. Pembayaran Digital (Midtrans) dan Transfer Manual
* **Pembayaran Digital (Midtrans):** Pilih opsi **Midtrans**, lalu klik **Bayar Sekarang**. Jendela popup Midtrans Snap akan muncul menampilkan metode pembayaran QRIS (GoPay/ShopeePay) atau Transfer Virtual Account bank. Lakukan pembayaran sesuai petunjuk. Status pesanan akan otomatis terverifikasi menjadi **Confirmed**.
* **Transfer Bank Manual:** Pilih opsi transfer bank manual, lakukan transfer ke rekening pengelola, kemudian klik tombol **Unggah Bukti Transfer** untuk melampirkan foto struk bukti transfer.
* **Unduh Invoice:** Setelah pesanan berstatus *Confirmed*, klik tombol **Unduh Invoice (PDF)** untuk mendapatkan bukti reservasi resmi.

#### 5. Fitur Pengajuan Tukar Mobil Pra-Sewa (*Car Change Request*)
* Apabila penyewa ingin menukar tipe mobil sebelum tanggal sewa dimulai (maksimal H-1), buka menu **Pesanan Saya** dan pilih transaksi terkait.
* Klik tombol **Ajukan Ganti Mobil**.
* Pilih unit mobil pengganti yang tersedia dari vendor yang sama. Sistem secara otomatis menghitung selisih tarif:
  * Jika harga mobil baru lebih tinggi, penyewa melakukan pelunasan sisa kekurangan.
  * Jika harga mobil baru lebih rendah, sistem mencatat nominal pengembalian dana (*refund*).

#### 6. Pelacakan Lokasi Langsung (*Live GPS*) & Tombol Bahaya Darurat (*Emergency SOS*)
* Selama masa sewa aktif (*Ongoing*), buka halaman rincian pesanan.
* Klik tab **Live Tracking** untuk memantau posisi terkini armada kendaraan pada peta interaktif Leaflet.
* **Tombol Darurat SOS:** Apabila terjadi kecelakaan, gangguan mesin di area sepi, atau ancaman bahaya di perjalanan, tekan tombol merah **Emergency SOS**. Sistem akan seketika merekam koordinat lintang dan bujur serta memicu alarm darurat prioritas tinggi ke posko vendor dan administrator.

#### 7. Penyelesaian Sewa, Penanganan Denda, Sengketa Denda, dan Ulasan
* Saat masa sewa berakhir dan unit diserahkan kembali, vendor melakukan konfirmasi pengembalian.
* **Penanganan Denda Keterlambatan:** Apabila pengembalian melewati batas waktu sewa dan vendor menerbitkan tagihan denda (*Late Fee Charge*), penyewa dapat:
  * Membayar tagihan denda secara langsung, atau
  * Mengajukan **Sengketa (Dispute)** dengan mengisi alasan keberatan dan melampirkan foto bukti pendukung (misalnya struk derek jalan tol).
* Setelah status pesanan menjadi **Completed**, berikan ulasan testimoni dan rating bintang (1–5) terhadap kualitas kendaraan dan pelayanan vendor.
* Klik menu **Rekap Riwayat Sewa** untuk mengunduh rekapitulasi histori transaksi ke format PDF atau Excel.

---

### 5.3 Panduan Modul Mitra Usaha (*Vendor Filament Panel*)
Akses Panel: `http://localhost/vendor`

#### 1. Pendaftaran Mitra & Pengunggahan Legalitas Usaha (KYC Vendor)
1. Buka tautan `/jadi-vendor` pada beranda publik.
2. Lengkapi formulir pendaftaran: Nama Perusahaan Rental, Nomor Induk Berusaha (NIB) / NPWP, Alamat Garasi, serta Rekening Bank Pencairan.
3. Unggah berkas dokumen verifikasi: Foto KTP Pemilik, Berkas NIB/NPWP, dan Foto Fisik Garasi Usaha.
4. Tunggu peninjauan dan persetujuan dari Administrator sebelum dapat mengakses panel vendor.

#### 2. Paket Berlangganan Kuota Armada (*Vendor Subscription Plans*)
1. Masuk ke menu **Paket Langganan (Billing & Plans)** pada bilah navigasi kiri.
2. Pilih paket yang sesuai dengan kapasitas armada usaha Anda (misal: Starter, Professional, atau Enterprise).
3. Selesaikan tagihan pembayaran paket langganan. Kuota kapasitas unit mobil yang dapat dipajang vendor akan terkunci secara otomatis sesuai batas paket yang aktif.

#### 3. Pengelolaan Katalog Armada Mobil (*Cars Resource*)
1. Masuk ke menu **Armada Mobil** > klik **Tambah Mobil**.
2. Isi spesifikasi teknis kendaraan: Merek, Model, Tahun Produksi, Nomor Plat Polisi, Tipe Transmisi, dan Jenis Bahan Bakar.
3. Tentukan Harga Sewa Harian Lepas Kunci dan Biaya Tambahan Supir Harian.
4. Unggah foto-foto unit mobil (tampak depan, samping, dan interior).
5. Atur status unit: *Aktif*, *Maintenance*, atau *Nonaktif*.

#### 4. Kalender Ketersediaan & Pemblokiran Tanggal (*Date Blocking*)
1. Buka menu **Kalender Armada**.
2. Pilih unit mobil tertentu pada tampilan kalender interaktif.
3. Klik dan pilih rentang tanggal yang ingin diblokir (misalnya untuk keperluan perawatan bengkel berkala atau pesanan sewa luring/offline). Pada tanggal yang diblokir, mobil otomatis tidak dapat dipesan oleh pelanggan di web.

#### 5. Manajemen Data Pengemudi (*Driver Management*)
1. Buka menu **Kelola Sopir** > klik **Tambah Sopir**.
2. Masukkan identitas pengemudi: Nama Lengkap, Nomor Kontak WhatsApp aktif, Nomor SIM A/B, Pengalaman Mengemudi, dan Pasfoto.
3. Sistem secara otomatis menjadwalkan penugasan pengemudi agar tidak terjadi benturan perjalanan antar-pesanan.

#### 6. Manajemen Pesanan Masuk, Serah Terima (*Handover*), dan Penerbitan Denda
1. Buka menu **Pesanan Masuk (Bookings)** untuk meninjau pesanan berstatus *Confirmed*.
2. Pada hari serah terima kendaraan, lakukan inspeksi fisik bersama pelanggan dan klik **Konfirmasi Handover Unit** (status berubah menjadi *Ongoing*).
3. Saat kendaraan dikembalikan:
   * Jika tepat waktu: Klik tombol **Konfirmasi Pengembalian** untuk menyelesaikan pesanan (*Completed*).
   * Jika terlambat: Masukkan jumlah jam keterlambatan, lalu sistem menerbitkan tagihan denda keterlambatan (*Late Fee Charge*) kepada penyewa.

#### 7. Laporan Keuangan, Omzet, dan Ekspor Rekapitulasi
1. Buka menu **Laporan Finansial**.
2. Pantau grafik pendapatan kotor harian/bulanan, potongan komisi platform, dan total saldo pendapatan bersih yang dapat ditarik (*Payout*).
3. Klik tombol **Ekspor PDF** atau **Ekspor Excel** untuk mengunduh arsip laporan pembukuan usaha.

---

### 5.4 Panduan Modul Pengawas Utama (*Administrator Filament Panel*)
Akses Panel: `http://localhost/admin`

#### 1. Verifikasi Legalitas Mitra Usaha (KYC Vendor)
1. Buka menu **Manajemen Vendor**.
2. Klik tombol **Tinjau Dokumen** pada pendaftaran vendor baru yang berstatus *Pending*.
3. Periksa berkas identitas KTP, NIB, NPWP, dan kesesuaian foto garasi usaha.
4. Klik tombol **Setujui (Approve)** untuk mengaktifkan akun vendor atau **Tolak (Reject)** dengan mencantumkan alasan perbaikan dokumen.

#### 2. Validasi Transaksi Pembayaran Manual
1. Buka menu **Transaksi Pembayaran Manual**.
2. Periksa kesesuaian mutasi rekening dengan foto bukti struk transfer yang diunggah oleh pelanggan atau vendor.
3. Klik tombol **Konfirmasi Pembayaran** untuk memperbarui status pesanan menjadi *Confirmed* secara instan.

#### 3. Pusat Mediasi Sengketa Denda (*Dispute Resolution Center*)
1. Buka menu **Pusat Sengketa (Disputes)**.
2. Tinjau rekaman kronologi perselisihan: argumen keberatan penyewa, foto bukti di lapangan, rekaman jejak GPS, serta tanggapan mitra rental.
3. Tetapkan putusan mediasi yang mengikat:
   * **Menerima Sengketa:** Menghapus atau memotong nilai denda keterlambatan jika vendor terbukti lalai atau unit mobil mengalami malfungsi teknis.
   * **Menolak Sengketa:** Mewajibkan penyewa melunasi tagihan denda keterlambatan secara penuh.

#### 4. Pusat Tanggap Darurat (*Emergency Dispatch*) & Monitoring Global
1. Buka menu **Pusat Darurat (Emergency Dispatch)** untuk memantau insiden aktif di jalan raya yang dikirimkan melalui tombol SOS secara *real-time*.
2. Buka menu **Laporan Master Global** untuk mengekspor rekapitulasi data seluruh transaksi rental nasional, peringkat vendor terbaik, serta distribusi bagi hasil komisi platform ke format PDF atau Excel.

---

### 5.5 Panduan Modul Pengemudi Lapangan (*Driver Quick Mobile Web*)
Akses Portal: Melalui tautan dinamis aman pada ponsel pengemudi (`http://localhost/driver/late-report/{kode_booking}`)

1. Pengemudi membuka tautan tugas pada peramban ponsel pintar tanpa kewajiban mengunduh aplikasi tambahan dari Play Store/App Store.
2. **Fitur Pengemudi di Lapangan:**
   * Melihat rincian kontak penyewa, waktu sewa, dan titik jemput kendaraan.
   * Menekan tombol **Konfirmasi Serah Terima Awal** saat unit mobil diserahkan ke pelanggan.
   * Mengirimkan **Laporan Kendala Lapangan** apabila terjadi hambatan macet total atau insiden cuaca agar terdokumentasi resmi di sistem.
   * Menekan tombol **Konfirmasi Pengembalian Armada** saat masa sewa selesai dan kunci mobil telah kembali di tangan pengemudi/garasi.

---
*E-Book Panduan Penggunaan dan Deskripsi Sistem Informasi Rental Mobil Multi-Vendor Berbasis Web.*
