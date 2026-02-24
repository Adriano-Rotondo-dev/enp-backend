<?php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307');
define('DB_NAME', 'enp_db');
define('DB_USER', 'root');
define('DB_PASS', '');

define('JWT_SECRET', 'enp_super_secret_key_cambiami_in_produzione');
define('TOKEN_EXPIRY', 3600); // 1 ora

// Genera con: password_hash('tua_password', PASSWORD_BCRYPT)
define('ADMIN_PASSWORD_HASH', password_hash('emo', PASSWORD_BCRYPT));

define('UPLOAD_DIR_POSTERS', __DIR__ . '/../uploads/posters/');
define('UPLOAD_DIR_PHOTOS', __DIR__ . '/../uploads/photos/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB