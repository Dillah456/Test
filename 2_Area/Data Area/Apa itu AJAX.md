
### JSON (JavaScript Object Notation)
JSON adalah **format untuk menyimpan atau bertukar data**.

{
  "nama": "Altair",
  "umur": 22,
  "hobi": [
    "Programming",
    "IoT",
    "Machine Learning"
  ]
}

JSON hanya data. Dia tidak bisa mengirim dirinya sendiri.

### AJAX (Asynchronous JavaScript and XML)

AJAX adalah **teknik agar JavaScript bisa berkomunikasi dengan server tanpa me-refresh halaman**. Dulu namanya memang **XML**, tapi sekarang hampir semua menggunakan **JSON**.

Contoh menggunakan `fetch()` :
fetch("https://api.example.com/user")
.then(response => response.json())
.then(data => {
    console.log(data.nama);
});

| JSON              | AJAX                                                         |
| ----------------- | ------------------------------------------------------------ |
| **Format data**   | **Teknik komunikasi**                                        |
| Berisi data       | Mengirim/mengambil data                                      |
| Seperti isi surat | Seperti kurir pengantar surat                                |
| Ekstensi `.json`  | Biasanya berupa JavaScript (`XMLHttpRequest` atau `fetch()`) |

Jadi:

- **JSON** = bentuk data yang dikirim.
- **AJAX** = cara JavaScript meminta dan menerima data dari server secara asynchronous.

### Ringkasnya

- 📦 **JSON** → _Apa yang dikirim?_ → Data.
- 🚚 **AJAX** → _Bagaimana data dikirim?_ → Teknik komunikasi.
- 🌐 **API** → _Siapa yang menyediakan data?_ → Server atau layanan yang merespons permintaan.

## Kapan JSON benar-benar tersimpan lokal?

Kalau memang sengaja disimpan.
### 1. Local Storage ✅ (Persisten)
	localStorage.setItem("user", JSON.stringify(data));
Mengambil lagi:

```
const user = JSON.parse(localStorage.getItem("user"));
```

Data tetap ada meskipun browser ditutup.

---

### 2. Session Storage

```
sessionStorage.setItem("user", JSON.stringify(data));
```

Data hilang ketika tab browser ditutup.

---

### 3. IndexedDB

Kalau datanya besar (misalnya cache ribuan item, offline app, PWA).

```
Server
   │
   ▼
IndexedDB
   │
   ▼
JavaScript
```

Ini seperti database mini di browser.

---

### 4. Cache API / Service Worker

Digunakan untuk aplikasi offline (Progressive Web App).

---

## Yang paling sering terjadi

Sebagian besar website hanya melakukan:

```
AJAX (fetch)
      │
      ▼
JSON
      │
      ▼
Variabel JS (RAM)
      │
      ▼
Render HTML
```

Tidak ada penyimpanan permanen.

**AJAX bukan API.** AJAX adalah **klien** yang _mengonsumsi_ API.
Alurnya seperti ini:

```
Browser (JavaScript)
        │
        │ AJAX (fetch/XMLHttpRequest)
        ▼
Server
        │
        │ Menghasilkan JSON
        ▼
Browser
```

Jadi:

- **AJAX** → mengirim HTTP Request (`GET`, `POST`, `PUT`, `DELETE`, dll.).
- **API** → menerima request tersebut, memprosesnya, lalu mengembalikan respons (biasanya JSON).

### Contoh API sederhana

Misalnya ada file PHP:

```
<?php
header("Content-Type: application/json");

echo json_encode([
    "status" => "success",
    "message" => "Halo Dunia"
]);
```

Ini sudah merupakan API sederhana.

Lalu dipanggil dengan AJAX:

```
fetch("api.php")
  .then(response => response.json())
  .then(data => {
    console.log(data.message);
  });
```

---

## Kalau tanpa backend?

Kalau hanya punya file `data.json`:

```
{
  "nama": "Altair",
  "umur": 22
}
```

Lalu dipanggil:

```
fetch("data.json")
  .then(res => res.json())
  .then(data => console.log(data));
```

Secara teknis ini **bukan API**, melainkan membaca **file JSON statis**. Namun dari sisi JavaScript, cara mengaksesnya hampir sama sehingga sering dipakai untuk prototipe.

---

### Ringkasnya

|Yang dibuat|Perlu AJAX?|Perlu Backend?|
|---|---|---|
|Membaca `data.json`|✅ Ya|❌ Tidak|
|REST API sederhana|✅ Ya (di klien)|✅ Ya (PHP, Node.js, Flask, dll.)|
|Website statis|Opsional|❌ Tidak|

Jadi, **AJAX sangat cocok untuk mengakses API sederhana**, tetapi **AJAX sendiri bukan pembuat API**. API tetap berjalan di sisi server (atau berupa file JSON statis jika hanya ingin menyajikan data sederhana).

Sebenarnya **API tidak harus Firebase, Xano, atau REST API khusus**. API hanyalah **sebuah endpoint yang bisa menerima request dan mengirim response**.

Contohnya dari yang paling sederhana sampai kompleks:

### Level 1 — File JSON (bukan API)

```
Website
│
├── index.html
└── data.json
```

JavaScript:

```
fetch("data.json")
```

Ini hanya membaca file. Tidak ada logika.

---

### Level 2 — API Sederhana

```
Website
│
├── index.html
└── api.php
```

`api.php`

```
<?php
echo json_encode([
    "saldo" => 100
]);
```

JavaScript:

```
fetch("api.php")
```

Nah, **`api.php` ini sudah API**. Walaupun sangat sederhana.

---

### Level 3 — REST API Custom

```
GET    /api/user
POST   /api/login
PUT    /api/user/1
DELETE /api/user/1
```

Biasanya dibuat dengan PHP, Node.js, Python, Laravel, Express, Flask, dll.

---

### Level 4 — Backend as a Service

Misalnya:

- Firebase
- Xano
- Supabase

Mereka sudah menyediakan API, jadi kita tinggal memanggilnya.

---

## Kalau "tidak ada API" sama sekali?

Kalau maksudmu **tidak ada backend**, maka JavaScript di browser **tidak bisa menyimpan data secara permanen di server**. Pilihannya hanya:

- Membaca file lokal (`data.json`)
- Menyimpan di `localStorage`
- Menyimpan di `sessionStorage`
- Menyimpan di `IndexedDB`

Browser **tidak bisa membuat endpoint sendiri** yang bisa diakses seperti server.

---

## Analogi

Bayangkan sebuah restoran.

- **AJAX** = Pelayan yang membawa pesanan.
- **API** = Dapur yang menerima pesanan dan memasak.
- **Firebase/Xano** = Restoran waralaba yang dapurnya sudah jadi.
- **PHP/Node.js buatanmu** = Kamu membangun dapur sendiri.

Kalau **tidak ada dapur sama sekali**, pelayan (AJAX) tidak punya tempat untuk mengirim pesanan.

---

Jadi kesimpulannya:

- **AJAX hanya alat komunikasi dari browser.**
- **API bisa sesederhana satu file `api.php` yang mengembalikan JSON.**
- **Firebase, Xano, Supabase** hanyalah layanan yang sudah menyediakan API siap pakai.
- **Kalau benar-benar tidak ada backend**, AJAX hanya bisa mengambil resource yang memang tersedia (misalnya file JSON), tetapi tidak bisa menggantikan fungsi sebuah server.