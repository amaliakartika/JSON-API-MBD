<?php

declare(strict_types=1);

use Slim\App;

return function (App $app) {
    //Tabel Mahasiswa
    $mahasiswaRoutes = require __DIR__.'/mahasiswa.php';
    $mahasiswaRoutes($app);

    //Tabel Dosen
    $dosenRoutes = require __DIR__.'/dosen.php';
    $dosenRoutes($app);

    //Tabel Matkul
    $matkulRoutes = require __DIR__.'/matkul.php';
    $matkulRoutes($app);

    //Tabel Mahasiswa
    $nilaiMahasiswaRoutes = require __DIR__.'/nilai_mahasiswa.php';
    $nilaiMahasiswaRoutes($app);
};