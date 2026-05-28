# EV Infrastructure Planner — Website

Website **Decision Support System (DSS)** untuk perencanaan lokasi SPKLU (Stasiun Pengisian Kendaraan Listrik Umum) berbasis Business Intelligence.

> Proyek UAS Mata Kuliah Business Intelligence — Kelompok 5

---

## Fitur Utama

| Fitur | Deskripsi | Konsep BI |
|-------|-----------|-----------|
| **Dashboard MIS** | KPI cards, bar chart EV per kota, pie chart distribusi merek | Management Information System |
| **GIS Heatmap** | Peta interaktif lokasi EV + marker prioritas SPKLU | Geographic Information System |
| **DSS Rekomendasi** | Ranking lokasi berdasarkan skor prioritas, filter & sort | Decision Support System |
| **Data Table** | Tabel fact + dimension sesuai star schema | Data Warehouse |

---

## Struktur Proyek

```
lib/
├── main.dart                     # Entry point + navigasi utama
├── theme/
│   ├── app_theme.dart            # Light & Dark theme
│   └── theme_provider.dart       # Provider untuk toggle tema
├── models/
│   └── ev_models.dart            # Model sesuai Star Schema
│       ├── DimVehicle
│       ├── DimLocation
│       ├── DimEvType
│       ├── FactEvPopulation
│       ├── SpkluRecommendation
│       └── MisSummary
├── data/
│   └── ev_repository.dart        # Data & business logic
├── screens/
│   ├── dashboard_screen.dart     # MIS Dashboard
│   ├── map_screen.dart           # GIS Heatmap
│   ├── dss_screen.dart           # DSS Rekomendasi SPKLU
│   └── data_table_screen.dart    # Tabel Data (Fact + Dimension)
└── widgets/
    └── common_widgets.dart       # Reusable widgets
```

---

## Star Schema

```
                    ┌─────────────────────┐
                    │  fact_ev_population  │
                    │─────────────────────│
                    │  fact_id       PK   │
                    │  vehicle_key   FK ──┼──► dim_vehicle
                    │  location_key  FK ──┼──► dim_location
                    │  ev_type_key   FK ──┼──► dim_ev_type
                    │  total_units        │
                    │  avg_electric_range │
                    │  model_year         │
                    └─────────────────────┘
```

### Tabel Dimensi
- **dim_vehicle**: vehicle_key, make, model
- **dim_location**: location_key, county, city, state, postal_code, latitude, longitude
- **dim_ev_type**: ev_type_key, ev_type, clean_alt_fuel_eligibility

---

## DSS — Algoritma Prioritas SPKLU

Skor prioritas dihitung berdasarkan kepadatan EV per kota:

```
priority_score = (total_ev_kota / max_ev_semua_kota) × 100
```

| Skor | Level |
|------|-------|
| ≥ 60% | 🔴 Tinggi |
| 25–59% | 🟡 Sedang |
| < 25% | 🟢 Rendah |

---

## Cara Menjalankan

### Prerequisites
- Flutter SDK ≥ 3.0.0
- Dart ≥ 3.0.0
- Android Studio / VS Code

### Langkah

```bash
# 1. Clone / pindah ke direktori proyek
cd ev_infrastructure_planner

# 2. Install dependencies
flutter pub get

# 3. Jalankan di emulator/device
flutter run

# 4. Build APK
flutter build apk --release
```

### Dependencies Utama

```yaml
fl_chart: ^0.68.0        # Bar chart, pie chart
flutter_map: ^6.1.0      # Peta OpenStreetMap
latlong2: ^0.9.0         # Koordinat lat/lng
provider: ^6.1.2         # State management tema
google_fonts: ^6.2.1     # Font Inter
```

---

## Dataset

Berdasarkan **Electric Vehicle Population Data** (Washington State, USA)
- Sumber: Data.gov / Kaggle
- ETL: Notebook `Electric_Vehicle_Population.ipynb`
- Proses: Extract CSV → Transform koordinat → Load ke star schema

---

## Anggota Pengembang Wesbite

**Kelompok 5 — UAS Business Intelligence**
| Nama | NIM | Kelas |
|-------|-----------|-----------|
| Nabil Daffa Athalasyah | 2409116090 | C 2024 |
| Moreno Ferdinand Farhantino | 2409116097 | C 2024 |
| Luthfi Daffa Purbaya | 2409116102 | C 2024 |
