# enp-backend

Backend PHP per **Emo Night Palermo** — API REST che gestisce eventi, archivio e foto.  
Progetto companion: [enp-web](https://github.com/Adriano-Rotondo-dev/enp-web/blob/master/README.md)

---

## Stack

- PHP 8.1
- MySQL (PDO)
- Apache / XAMPP (sviluppo)
- JWT per autenticazione admin

---

## Struttura cartelle

```
enp-backend/
├── config/
│   ├── config.php              # Costanti globali (DB, JWT, upload dirs, max file size)
│   └── db.php                  # Connessione PDO singleton
├── middleware/
│   ├── cors.php                # Header CORS + gestione preflight OPTIONS
│   └── auth.php                # Verifica JWT — require_auth()
├── public/
│   ├── login.php               # POST — autenticazione admin
│   ├── get-event.php           # GET — prossimo evento + lineup
│   ├── update-event.php        # POST — aggiorna evento + lineup (auth)
│   ├── archive-events/
│   │   ├── get.php             # GET — lista eventi archivio
│   │   ├── add.php             # POST — aggiunge evento (auth, multipart)
│   │   ├── update.php          # POST — modifica evento (auth)
│   │   └── delete.php          # DELETE — elimina evento (auth)
│   └── photos/
│       ├── get.php             # GET — lista foto con JOIN archive_events
│       ├── upload.php          # POST — carica foto (auth, multipart)
│       ├── update.php          # POST — aggiorna metadati foto (auth)
│       └── delete.php          # DELETE — elimina foto + file fisico (auth)
└── uploads/
    ├── photos/                 # Foto eventi caricate
    └── posters/                # Poster archivio eventi caricati
```

---

## Setup locale

### Requisiti
- XAMPP con PHP 8.1+ e MySQL
- MySQL in ascolto su porta `3307` (default XAMPP Windows)

### Configurazione

1. Clona il repo in `C:\xampp\htdocs\enp-backend\`
2. Crea il database `enp_db` su phpMyAdmin
3. Esegui le migration SQL (vedi sezione Database)
4. Configura `config/config.php`:

```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307');
define('DB_NAME', 'enp_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('JWT_SECRET', 'cambia_questa_chiave_in_produzione');
define('TOKEN_EXPIRY', 86400); // 24 ore
define('UPLOAD_DIR_POSTERS', __DIR__ . '/../uploads/posters/');
define('UPLOAD_DIR_PHOTOS', __DIR__ . '/../uploads/photos/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
```

5. Crea le cartelle upload:
```
uploads/photos/
uploads/posters/
```

---

## Database

### Schema completo

#### `admins`
Credenziali admin per l'accesso alla dashboard.

```sql
CREATE TABLE admins (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  last_login TIMESTAMP NULL
);
```

#### `next_event`
Dati del prossimo evento in programma. Contiene una sola riga attiva.

```sql
CREATE TABLE next_event (
  id INT PRIMARY KEY AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  date DATETIME NOT NULL,
  time VARCHAR(10),
  location VARCHAR(255),
  address VARCHAR(255),
  description TEXT,
  price DECIMAL(5,2) DEFAULT 0
);
```

> `maps_url` è stato rimosso — il link Google Maps viene generato dinamicamente dal frontend a partire da `location` e `address`.

#### `next_event_lineup`
Scaletta del prossimo evento. Relazione 1:N con `next_event`.

```sql
CREATE TABLE next_event_lineup (
  id INT PRIMARY KEY AUTO_INCREMENT,
  event_id INT NOT NULL,
  time VARCHAR(10),
  act VARCHAR(255),
  sort_order INT DEFAULT 0,
  FOREIGN KEY (event_id) REFERENCES next_event(id) ON DELETE CASCADE
);
```

#### `archive_events`
Storico delle notti passate. Ogni record rappresenta una serata.

```sql
CREATE TABLE archive_events (
  id INT PRIMARY KEY AUTO_INCREMENT,
  vol VARCHAR(50),
  name VARCHAR(255) NOT NULL,
  date VARCHAR(50),
  description TEXT,
  poster_url VARCHAR(500) DEFAULT '/poster_placeholder.webp',
  spotify_url VARCHAR(500) NULL,
  live_music_url VARCHAR(500) NULL
);
```

| Campo | Descrizione |
|-------|-------------|
| `vol` | Numero volume della serata (es. `VOL. 8`) |
| `spotify_url` | Link playlist Spotify della serata — opzionale |
| `live_music_url` | Link Instagram del feat/live exhibition — opzionale |

#### `photos`
Foto delle serate. Collegata a `archive_events` tramite FK.

```sql
CREATE TABLE photos (
  id INT PRIMARY KEY AUTO_INCREMENT,
  url VARCHAR(500) NOT NULL,
  title VARCHAR(255),
  tag VARCHAR(100),
  event_date VARCHAR(50),
  author VARCHAR(255),
  archive_event_id INT NULL,
  FOREIGN KEY (archive_event_id) REFERENCES archive_events(id) ON DELETE SET NULL
);
```

| Campo | Descrizione |
|-------|-------------|
| `tag` | Categoria libera (es. Crowd, Stage, Backstage) |
| `archive_event_id` | FK verso `archive_events` — permette il filtro per serata nella galleria |

### Inserimento admin
```bash
# Genera hash bcrypt
C:\xampp\php\php.exe tools/generate-hash.php
```
```sql
INSERT INTO admins (username, password_hash) VALUES ('admin', '$2y$10$...');
```

---

## Autenticazione

Il login restituisce un JWT firmato con `JWT_SECRET`. Il token ha scadenza configurabile tramite `TOKEN_EXPIRY` (default 24 ore).

Tutti gli endpoint protetti richiedono:
```
Authorization: Bearer <token>
```

`auth.php` espone `require_auth()` — restituisce `401` se il token è mancante o scaduto.

---

## Endpoint

### Pubblici

| Metodo | Path | Descrizione |
|--------|------|-------------|
| POST | `/login.php` | Login admin — body: `{ password }` |
| GET | `/get-event.php` | Prossimo evento + lineup |
| GET | `/archive-events/get.php` | Lista eventi archivio con `spotify_url` e `live_music_url` |
| GET | `/photos/get.php` | Lista foto con JOIN `archive_events` (restituisce `eventVol`, `eventName`) |

### Protetti (JWT richiesto)

| Metodo | Path | Descrizione |
|--------|------|-------------|
| POST | `/update-event.php` | Aggiorna evento e lineup |
| POST | `/archive-events/add.php` | Aggiunge evento archivio (multipart/form-data) |
| POST | `/archive-events/update.php` | Modifica evento archivio |
| DELETE | `/archive-events/delete.php?id=` | Elimina evento |
| POST | `/photos/upload.php` | Carica foto (multipart/form-data + `archive_event_id`) |
| POST | `/photos/update.php` | Aggiorna metadati foto + `archive_event_id` |
| DELETE | `/photos/delete.php?id=` | Elimina foto + file fisico |


