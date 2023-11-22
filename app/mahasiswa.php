<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app) {
    //Tabel Mahasiswa

    // Get Data
    //Mengambil seluruh data mahasiswa
    $app->get('/mahasiswa', function (Request $request, Response $response) {
        $db = $this->get(PDO::class);
    
        try {
            $query = $db->query('CALL select_mahasiswa()');
            $results = $query->fetchAll(PDO::FETCH_ASSOC);
            $response->getBody()->write(json_encode($results));
        } catch (PDOException $e) {
            $response = $response->withStatus(500);
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Terjadi kesalahan dalam mengambil data mahasiswa.',
                    'message' => $e->getMessage(),
                ]
            ));
        }
    
        return $response->withHeader("Content-Type", "application/json");
    });  

    // Mengambil data mahasiswa by nim
    $app->get('/mahasiswa/{nim}', function ($request, $response, $args) {
        $db = $this->get(PDO::class);
        $nim = $args['nim'];
    
        try {
            $query = $db->prepare('CALL select_mahasiswa_by_nim(:nim)');
            $query->execute(array(':nim' => $nim));
    
            $results = $query->fetchAll(PDO::FETCH_ASSOC);
    
            if (count($results) > 0) {
                $response->getBody()->write(json_encode($results[0]));
            } else {
                $response->getBody()->write(json_encode(array('message' => 'Data mahasiswa tidak ditemukan')));
            }
        } catch (PDOException $e) {
            $response = $response->withStatus(500);
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Terjadi kesalahan dalam mengambil data mahasiswa.',
                    'message' => $e->getMessage(),
                ]
            ));
        }
    
        return $response->withHeader("Content-Type", "application/json");
    });    

    // Post Data
    // Menambahkan data mahasiswa
    $app->post('/mahasiswa', function ($request, $response) {
        $parsedBody = $request->getParsedBody();
        $new_nim = $parsedBody["nim"];
        $new_nama = $parsedBody["nama"];
        $new_prodi = $parsedBody["prodi"];

        // Validasi data tidak boleh kosong
        if (empty($new_nim) || empty($new_nama) || empty($new_prodi)) {
            $response = $response->withStatus(400); // Bad Request
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Data nim, nama, dan prodi tidak boleh kosong.',
                ]
            ));
            return $response->withHeader("Content-Type", "application/json");
        }

        $db = $this->get(PDO::class);

        // Validasi NIM tidak boleh duplikat
        if (isNimExists($db, $new_nim)) {
            $response = $response->withStatus(400); // Bad Request
            $response->getBody()->write(json_encode(
                [
                    'error' => 'NIM ' . $new_nim . ' sudah ada dalam database.',
                ]
            ));
            return $response->withHeader("Content-Type", "application/json");
        }

        try {
            $query = $db->prepare('CALL insert_mahasiswa(?, ?, ?)');
            $query->bindParam(1, $new_nim, PDO::PARAM_INT);
            $query->bindParam(2, $new_nama, PDO::PARAM_STR);
            $query->bindParam(3, $new_prodi, PDO::PARAM_STR);
            $query->execute();

            $response->getBody()->write(json_encode(
                [
                    'message' => 'Data mahasiswa disimpan dengan sukses'
                ]
            ));
        } catch (PDOException $e) {
            $response = $response->withStatus(500);
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Terjadi kesalahan dalam menyimpan data mahasiswa.',
                    'message' => $e->getMessage(),
                ]
            ));
        }

        return $response->withHeader("Content-Type", "application/json");
    });

    // Fungsi untuk memeriksa apakah nim sudah ada dalam database
    function isNimExists($db, $nim) {
        $query = $db->prepare('SELECT COUNT(*) FROM mahasiswa WHERE nim = ?');
        $query->bindParam(1, $nim, PDO::PARAM_INT);
        $query->execute();
        $count = $query->fetchColumn();

        return $count > 0;
    }

    // Put Data
    // Update data mahasiswa by nim
    $app->put('/mahasiswa/{nim}', function ($request, $response, $args) {
        $db = $this->get(PDO::class);
        $nim = $args['nim'];

        // Check if the specified nim exists
        try {
            $exists = isNimExists($db, $nim);

            if (!$exists) {
                $response = $response->withStatus(404); // Not Found
                $response->getBody()->write(json_encode(
                    [
                        'error' => 'Data mahasiswa dengan nim ' . $nim . ' tidak ditemukan.',
                    ]
                ));
                return $response->withHeader("Content-Type", "application/json");
            }
        } catch (PDOException $e) {
            $response = $response->withStatus(500);
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Terjadi kesalahan dalam memeriksa data mahasiswa.',
                    'message' => $e->getMessage(),
                ]
            ));
            return $response->withHeader("Content-Type", "application/json");
        }

        $parsedBody = $request->getParsedBody();
        $nama = $parsedBody["nama"];
        $prodi = $parsedBody["prodi"];

        // Validasi data tidak boleh kosong
        if (empty($nama) || empty($prodi)) {
            $response = $response->withStatus(400); // Bad Request
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Data nama dan prodi tidak boleh kosong.',
                ]
            ));
            return $response->withHeader("Content-Type", "application/json");
        }

        try {
            $query = $db->prepare('CALL update_mahasiswa(?, ?, ?)');
            $query->bindParam(1, $nim, PDO::PARAM_INT);
            $query->bindParam(2, $nama, PDO::PARAM_STR);
            $query->bindParam(3, $prodi, PDO::PARAM_STR);
            $query->execute();

            $response->getBody()->write(json_encode(
                [
                    'message' => 'Data mahasiswa dengan nim ' . $nim . ' telah diperbarui dengan nama ' . $nama . ' dan prodi ' . $prodi
                ]
            ));
        } catch (PDOException $e) {
            $response = $response->withStatus(500);
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Terjadi kesalahan dalam memperbarui data mahasiswa.',
                    'message' => $e->getMessage(),
                ]
            ));
        }

        return $response->withHeader("Content-Type", "application/json");
    });

    //Delete Data
    // Menghapus data mahasiswa by nim
    $app->delete('/mahasiswa/{nim}', function ($request, $response, $args) {
        $db = $this->get(PDO::class);
        $nim = $args['nim'];
    
        try {
            $query = $db->prepare('CALL delete_mahasiswa(?)');
            $query->bindParam(1, $nim, PDO::PARAM_INT);
            $query->execute();
    
            if ($query->rowCount() === 0) {
                $response = $response->withStatus(404);
                $response->getBody()->write(json_encode(
                    [
                        'message' => 'Data mahasiswa tidak ditemukan'
                    ]
                ));
            } else {
                $response->getBody()->write(json_encode(
                    [
                        'message' => 'Mahasiswa dengan nim ' . $nim . ' dihapus dari database'
                    ]
                ));
            }
        } catch (PDOException $e) {
            $response = $response->withStatus(500);
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Terjadi kesalahan dalam menghapus data mahasiswa.',
                    'message' => $e->getMessage(),
                ]
            ));
        }
    
        return $response->withHeader("Content-Type", "application/json");
    });    
};