# ⚡ EV Infrastructure Planner — PHP Edition
## Panduan Instalasi di Laragon

---

## 📁 Struktur Folder

```
ev_planner/
├── index.php               ← Halaman utama (frontend + navigasi)
├── database.sql            ← Script SQL untuk buat & isi database
├── config/
│   └── database.php        ← Konfigurasi koneksi MySQL
├── api/
│   ├── locations.php       ← REST API CRUD Lokasi/Kota
│   ├── ev_facts.php        ← REST API CRUD Data EV
│   ├── vehicles.php        ← REST API CRUD Kendaraan
│   └── stats.php           ← API Statistik Dashboard
└── README.md               ← File ini
```

---

## 🚀 Cara Instalasi

### Langkah 1 — Salin ke Laragon
Salin seluruh folder `ev_planner` ke:
```
C:\laragon\www\ev_planner\
```

### Langkah 2 — Buat Database
1. Buka **Laragon** → klik **Database** (atau buka **HeidiSQL / phpMyAdmin**)
2. Buat database baru bernama: `ev_planner`
3. Import file `database.sql`:
   - Di HeidiSQL: File → Load SQL file → pilih `database.sql` → Execute
   - Di phpMyAdmin: klik database `ev_planner` → tab **Import** → pilih file → klik Go
   - Via CLI: `mysql -u root -p ev_planner < database.sql`

### Langkah 3 — Konfigurasi Koneksi
Buka `config/database.php` dan sesuaikan:
```php
define('DB_HOST',   'localhost');  // biasanya localhost
define('DB_NAME',   'ev_planner');
define('DB_USER',   'root');       // Laragon default: root
define('DB_PASS',   '');           // Laragon default: kosong
```

### Langkah 4 — Akses Aplikasi
Buka browser: **http://localhost/ev_planner/**

---

## ✨ Fitur Lengkap

### 📊 Dashboard
- Statistik total unit EV, jumlah kota, merek, rata-rata jangkauan
- 4 grafik interaktif (Chart.js): per tahun, per merek, BEV vs PHEV, per county
- Tabel Top 5 Kota dengan unit EV terbanyak

### 🗺️ Peta GIS
- Peta interaktif Leaflet.js dengan tile CartoDB dark
- Marker ukuran proporsional terhadap jumlah unit
- Warna berbeda: biru (BEV), ungu (PHEV), hijau (lokasi tanpa data)
- Popup detail kota + statistik

### 📍 Kelola Lokasi (CRUD Lengkap)
- **Tambah** kota baru (hanya kota / lokasi — city, county, postal code, koordinat)
- **Edit** data kota yang ada
- **Hapus** kota (dengan proteksi jika masih dipakai di data EV)
- **Cari** kota berdasarkan nama / kode pos
- **Filter** berdasarkan County
- **Tampilan Tabel** dan **Grid Kartu** (bisa toggle)
- Pagination 15 data per halaman

### 🚗 Data EV Facts (CRUD Lengkap)
- **Tambah** data observasi EV (pilih kota, kendaraan, tipe, tahun, unit, jangkauan)
- **Edit** data yang ada
- **Hapus** data
- **Multi-filter**: cari, kota, tipe EV, merek, tahun
- **Sort** klik header kolom
- Pagination

### 🔧 Kelola Kendaraan (CRUD Lengkap)
- **Tambah** merek/model kendaraan
- **Edit** data kendaraan
- **Hapus** kendaraan (dengan proteksi jika masih dipakai)

---

## 🔌 API Endpoints

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| GET | `/api/locations.php` | List semua lokasi |
| GET | `/api/locations.php?id=1` | Detail lokasi |
| GET | `/api/locations.php?search=seattle&county=King` | Filter |
| POST | `/api/locations.php` | Tambah lokasi |
| PUT | `/api/locations.php?id=1` | Update lokasi |
| DELETE | `/api/locations.php?id=1` | Hapus lokasi |
| GET | `/api/ev_facts.php` | List data EV |
| POST | `/api/ev_facts.php` | Tambah data EV |
| PUT | `/api/ev_facts.php?id=1` | Update data EV |
| DELETE | `/api/ev_facts.php?id=1` | Hapus data EV |
| GET | `/api/vehicles.php` | List kendaraan |
| POST | `/api/vehicles.php` | Tambah kendaraan |
| PUT | `/api/vehicles.php?id=1` | Update kendaraan |
| DELETE | `/api/vehicles.php?id=1` | Hapus kendaraan |
| GET | `/api/stats.php` | Statistik dashboard |

---

## 🛠️ Teknologi
- **Backend**: PHP 8+ dengan PDO MySQL
- **Database**: MySQL (Laragon)
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Peta**: Leaflet.js
- **Grafik**: Chart.js
- **Font**: Orbitron, Rajdhani, Share Tech Mono

## ⚠️ Persyaratan
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- Laragon (atau XAMPP/WAMP)
- Browser modern (Chrome, Firefox, Edge)
