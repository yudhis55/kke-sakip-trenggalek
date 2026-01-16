# API SAKIP Documentation

## Base URL

```
http://localhost:8000/api
```

## API Endpoints

### Master Data

#### 1. OPD (Perangkat Daerah)
  
**Get All OPD**

```
GET /api/master/opd
```

Query Parameters:

-   `search` - Cari berdasarkan nama OPD
-   `per_page` - Jumlah data per halaman (default: 15)
-   `all` - true untuk mendapatkan semua data tanpa pagination

Example:

```
GET /api/master/opd?search=dinas&per_page=10
GET /api/master/opd?all=true
```

**Get Single OPD**

```
GET /api/master/opd/{id}
```

Example:

```
GET /api/master/opd/1
```

#### 2. Periode SAKIP

**Get All Periode**

```
GET /api/master/periode
```

Query Parameters:

-   `tahun` - Filter periode yang mencakup tahun tertentu
-   `per_page` - Jumlah data per halaman (default: 15)
-   `all` - true untuk mendapatkan semua data tanpa pagination

Example:

```
GET /api/master/periode?tahun=2024
GET /api/master/periode?all=true
```

**Get Single Periode**

```
GET /api/master/periode/{id}
```

#### 3. Tahun SAKIP

**Get All Tahun**

```
GET /api/master/tahun
```

Query Parameters:

-   `periode_id` - Filter berdasarkan periode
-   `tahun` - Filter berdasarkan tahun spesifik
-   `per_page` - Jumlah data per halaman (default: 15)
-   `all` - true untuk mendapatkan semua data tanpa pagination

Example:

```
GET /api/master/tahun?periode_id=1
GET /api/master/tahun?tahun=2024
```

**Get Single Tahun**

```
GET /api/master/tahun/{id}
```

---

### Dokumen

Semua endpoint dokumen memiliki query parameters yang sama:

**Common Query Parameters:**

-   `opd_id` - Filter berdasarkan OPD
-   `status` - Filter berdasarkan status (0 atau 1)
-   `published_only` - true untuk hanya dokumen yang sudah dipublish
-   `sort_by` - Field untuk sorting (default: created_at)
-   `sort_order` - Urutan sorting: asc/desc (default: desc)
-   `per_page` - Jumlah data per halaman (default: 15)
-   `all` - true untuk mendapatkan semua data tanpa pagination

**Dokumen dengan Periode** (periode_id):

-   RPJMD
-   Proses Bisnis
-   Pohon Kinerja (Cascading)
-   Renstra
-   IKU

**Dokumen dengan Tahun** (tahun_id atau tahun):

-   Renja
-   Perjanjian Kinerja
-   Rencana Aksi
-   LPPD
-   LKJIP

---

#### 1. RPJMD

**Get All RPJMD**

```
GET /api/dokumen/rpjmd
```

Additional Query Parameters:

-   `periode_id` - Filter berdasarkan periode
-   `kategori` - Filter berdasarkan kategori

Example:

```
GET /api/dokumen/rpjmd?opd_id=1&periode_id=1&status=1
GET /api/dokumen/rpjmd?published_only=true&kategori=draft
```

**Get Single RPJMD**

```
GET /api/dokumen/rpjmd/{id}
```

---

#### 2. Proses Bisnis

**Get All Proses Bisnis**

```
GET /api/dokumen/proses-bisnis
```

Additional Query Parameters:

-   `periode_id` - Filter berdasarkan periode

Example:

```
GET /api/dokumen/proses-bisnis?opd_id=1&periode_id=1
```

**Get Single Proses Bisnis**

```
GET /api/dokumen/proses-bisnis/{id}
```

---

#### 3. Pohon Kinerja (Cascading)

**Get All Pohon Kinerja**

```
GET /api/dokumen/pohon-kinerja
```

Additional Query Parameters:

-   `periode_id` - Filter berdasarkan periode

Example:

```
GET /api/dokumen/pohon-kinerja?opd_id=1&periode_id=1&published_only=true
```

**Get Single Pohon Kinerja**

```
GET /api/dokumen/pohon-kinerja/{id}
```

---

#### 4. Renstra

**Get All Renstra**

```
GET /api/dokumen/renstra
```

Additional Query Parameters:

-   `periode_id` - Filter berdasarkan periode
-   `kategori` - Filter berdasarkan kategori

Example:

```
GET /api/dokumen/renstra?opd_id=1&periode_id=1&status=1
```

**Get Single Renstra**

```
GET /api/dokumen/renstra/{id}
```

---

#### 5. Renja

**Get All Renja**

```
GET /api/dokumen/renja
```

Additional Query Parameters:

-   `tahun_id` - Filter berdasarkan ID tahun
-   `tahun` - Filter berdasarkan nilai tahun (misal: 2024)
-   `kategori` - Filter berdasarkan kategori

Example:

```
GET /api/dokumen/renja?opd_id=1&tahun=2024&status=1
GET /api/dokumen/renja?tahun_id=1&published_only=true
```

**Get Single Renja**

```
GET /api/dokumen/renja/{id}
```

---

#### 6. IKU (Indikator Kinerja Utama)

**Get All IKU**

```
GET /api/dokumen/iku
```

Additional Query Parameters:

-   `periode_id` - Filter berdasarkan periode

Example:

```
GET /api/dokumen/iku?opd_id=1&periode_id=1&published_only=true
```

**Get Single IKU**

```
GET /api/dokumen/iku/{id}
```

---

#### 7. Perjanjian Kinerja

**Get All Perjanjian Kinerja**

```
GET /api/dokumen/perjanjian-kinerja
```

Additional Query Parameters:

-   `tahun_id` - Filter berdasarkan ID tahun
-   `tahun` - Filter berdasarkan nilai tahun
-   `kategori` - Filter berdasarkan kategori

Example:

```
GET /api/dokumen/perjanjian-kinerja?opd_id=1&tahun=2024
```

**Get Single Perjanjian Kinerja**

```
GET /api/dokumen/perjanjian-kinerja/{id}
```

---

#### 8. Rencana Aksi

**Get All Rencana Aksi**

```
GET /api/dokumen/rencana-aksi
```

Additional Query Parameters:

-   `tahun_id` - Filter berdasarkan ID tahun
-   `tahun` - Filter berdasarkan nilai tahun

Example:

```
GET /api/dokumen/rencana-aksi?opd_id=1&tahun=2024&status=1
```

**Get Single Rencana Aksi**

```
GET /api/dokumen/rencana-aksi/{id}
```

---

#### 9. LPPD

**Get All LPPD**

```
GET /api/dokumen/lppd
```

Additional Query Parameters:

-   `tahun_id` - Filter berdasarkan ID tahun
-   `tahun` - Filter berdasarkan nilai tahun

Example:

```
GET /api/dokumen/lppd?opd_id=1&tahun=2024&published_only=true
```

**Get Single LPPD**

```
GET /api/dokumen/lppd/{id}
```

---

#### 10. LKJIP

**Get All LKJIP**

```
GET /api/dokumen/lkjip
```

Additional Query Parameters:

-   `tahun_id` - Filter berdasarkan ID tahun
-   `tahun` - Filter berdasarkan nilai tahun

Example:

```
GET /api/dokumen/lkjip?opd_id=1&tahun=2024&status=1
```

**Get Single LKJIP**

```
GET /api/dokumen/lkjip/{id}
```

---

## Response Format

### Success Response (List with Pagination)

```json
{
    "data": [
        {
            "id": 1,
            "file": "path/to/file.pdf",
            "file_url": "http://localhost:8000/storage/path/to/file.pdf",
            "keterangan": "Deskripsi dokumen",
            "tanggal_publish": "2024-01-01",
            "status": 1,
            "opd": {
                "id": 1,
                "nama": "Nama OPD"
            },
            "tahun": {
                "id": 1,
                "tahun": 2024
            },
            "created_at": "2024-01-01T00:00:00.000000Z",
            "updated_at": "2024-01-01T00:00:00.000000Z"
        }
    ],
    "links": {
        "first": "http://localhost:8000/api/dokumen/renja?page=1",
        "last": "http://localhost:8000/api/dokumen/renja?page=5",
        "prev": null,
        "next": "http://localhost:8000/api/dokumen/renja?page=2"
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 5,
        "path": "http://localhost:8000/api/dokumen/renja",
        "per_page": 15,
        "to": 15,
        "total": 75
    }
}
```

### Success Response (Single Item)

```json
{
    "data": {
        "id": 1,
        "file": "path/to/file.pdf",
        "file_url": "http://localhost:8000/storage/path/to/file.pdf",
        "keterangan": "Deskripsi dokumen",
        "tanggapan": "Tanggapan dari reviewer",
        "tanggal_publish": "2024-01-01",
        "status": 1,
        "kategori": "draft",
        "opd": {
            "id": 1,
            "nama": "Dinas Kesehatan",
            "id_simonev": "123",
            "id_lapdu": "456"
        },
        "tahun": {
            "id": 1,
            "tahun": 2024,
            "periode": {
                "id": 1,
                "tahun_mulai": 2021,
                "tahun_selesai": 2025,
                "periode": "2021-2025"
            }
        },
        "user": {
            "id": 1,
            "name": "Admin User",
            "email": "admin@example.com"
        },
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### Error Response (404 Not Found)

```json
{
    "message": "No query results for model [App\\Models\\Renja] 999"
}
```

---

## Usage Examples

### 1. Get semua OPD

```bash
curl http://localhost:8000/api/master/opd
```

### 2. Get dokumen Renja untuk OPD tertentu di tahun 2024

```bash
curl "http://localhost:8000/api/dokumen/renja?opd_id=1&tahun=2024&published_only=true"
```

### 3. Get semua dokumen LKJIP yang sudah dipublish

```bash
curl "http://localhost:8000/api/dokumen/lkjip?published_only=true&sort_by=tgl_publish&sort_order=desc"
```

### 4. Get detail dokumen IKU

```bash
curl http://localhost:8000/api/dokumen/iku/1
```

### 5. Get semua data tanpa pagination

```bash
curl "http://localhost:8000/api/master/opd?all=true"
```

---

## Notes

1. Semua endpoint adalah **READ-ONLY** (hanya GET method)
2. Response menggunakan format **JSON**
3. Pagination default adalah 15 items per page
4. File URL akan otomatis di-generate dengan prefix `storage/`
5. Tanggal menggunakan format **ISO 8601**
6. Relasi (OPD, Periode, Tahun, User) akan otomatis di-load dengan eager loading
