<?php

/**
 * Import semua class yang digunakan dalam framework ini.
 * Tenang, ini telah dilakukan secara otomatis oleh composer.
 *
 * Sekarang, tinggal menjalankan aplikasi ini saja.
 */

// Set environment variables for Vercel deployment (override any system vars)
$envVars = [
    'APP_KEY' => '1dJhmLwXiklrwCjAlfiBZPcX7Ru8qIcxGx8RNOAj5+Y=::alI8C1BegK2hJ92vmwQhdn0MvaUhNclU15AZZGJ+i5M=',
    'JWT_KEY' => '055143ccf9a618fe8527b19e6e1e2df175d775dc8618c99f4793f31eaf111cd0',
    'JWT_EXP' => '86400',
    'DB_DRIV' => 'pgsql',
    'DB_HOST' => 'db.fsskahbdefatxbvwmcow.supabase.co',
    'DB_PORT' => '6543',
    'DB_NAME' => 'postgres',
    'DB_USER' => 'postgres',
    'DB_PASS' => 'Sed2935391?',
    'DB_OPTIONS' => 'sslmode=require',
    'DEBUG' => 'false',
    'LOG' => 'false',
    'HTTPS' => 'true',
];
foreach ($envVars as $key => $value) {
    putenv("$key=$value");
    $_ENV[$key] = $value;
}

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Okey, sekarang memanggil fungsi web secara static pada kernel.
 * Bentar, fungsi web ini perlu app kernel sebagai penghubung aplikasi.
 * Setelah itu, hanya perlu menjalankannya saja.
 *
 * Ini sangat simple.
 */

\Core\Kernel\Kernel::web(
    new \App\Kernel()
)->run();
