<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app) {
    //Tabel Matkul

    // Get Data
    //Mengambil seluruh data matkul
    $app->get('/matkul', function (Request $request, Response $response) {
        $db = $this->get(PDO::class);
    
        try {
            $query = $db->query('CALL select_matkul()');
            $results = $query->fetchAll(PDO::FETCH_ASSOC);
            $response->getBody()->write(json_encode($results));
        } catch (PDOException $e) {
            $response = $response->withStatus(500);
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Terjadi kesalahan dalam mengambil data mata kuliah.',
                    'message' => $e->getMessage(),
                ]
            ));
        }
    
        return $response->withHeader("Content-Type", "application/json");
    });
    
    // Mengambil data mata kuliah by kode_matkul
    $app->get('/matkul/{kode_matkul}', function ($request, $response, $args) {
        $db = $this->get(PDO::class);
        $kode_matkul = $args['kode_matkul'];
    
        try {
            $query = $db->prepare('CALL select_matkul_by_kode_matkul(?)');
            $query->bindParam(1, $kode_matkul, PDO::PARAM_STR);
            $query->execute();
    
            $results = $query->fetchAll(PDO::FETCH_ASSOC);
    
            if (count($results) > 0) {
                $response->getBody()->write(json_encode($results[0]));
            } else {
                $response = $response->withStatus(404);
                $response->getBody()->write(json_encode(
                    [
                        'message' => 'Mata kuliah tidak ditemukan'
                    ]
                ));
            }
        } catch (PDOException $e) {
            $response = $response->withStatus(500);
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Terjadi kesalahan dalam mengambil data mata kuliah.',
                    'message' => $e->getMessage(),
                ]
            ));
        }
    
        return $response->withHeader("Content-Type", "application/json");
    });
    
    // Post Data
    // Menambahkan data matkul
    $app->post('/matkul', function ($request, $response) {
        $parsedBody = $request->getParsedBody();
        $new_kode_matkul = $parsedBody["kode_matkul"];
        $new_id_dosen = $parsedBody["id_dosen"];
        $new_nama_matkul = $parsedBody["nama_matkul"];
        $new_sks = $parsedBody["sks"];

        // Validasi data tidak boleh kosong
        if (empty($new_kode_matkul) || empty($new_id_dosen) || empty($new_nama_matkul) || empty($new_sks)) {
            $response = $response->withStatus(400); // Bad Request
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Data kode_matkul, id_dosen, nama_matkul, dan sks tidak boleh kosong.',
                ]
            ));
            return $response->withHeader("Content-Type", "application/json");
        }

        $db = $this->get(PDO::class);

        // Validasi id_matkul tidak boleh duplikat
        if (isIdMatkulExists($db, $new_kode_matkul)) {
            $response = $response->withStatus(400); // Bad Request
            $response->getBody()->write(json_encode(
                [
                    'error' => 'ID Mata Kuliah ' . $new_kode_matkul . ' sudah ada dalam database.',
                ]
            ));
            return $response->withHeader("Content-Type", "application/json");
        }

        try {
            $query = $db->prepare('CALL insert_matkul(?, ?, ?, ?)');
            $query->bindParam(1, $new_kode_matkul, PDO::PARAM_STR);
            $query->bindParam(2, $new_id_dosen, PDO::PARAM_STR);
            $query->bindParam(3, $new_nama_matkul, PDO::PARAM_STR);
            $query->bindParam(4, $new_sks, PDO::PARAM_INT);
            $query->execute();

            $lastId = $db->lastInsertId();

            if ($query->rowCount() > 0) {
                $response->getBody()->write(json_encode(
                    [
                        'message' => 'Mata kuliah disimpan dengan kode_matkul ' . $new_kode_matkul
                    ]
                ));
            } else {
                $response = $response->withStatus(500);
                $response->getBody()->write(json_encode(
                    [
                        'message' => 'Gagal menyimpan mata kuliah'
                    ]
                ));
            }
        } catch (PDOException $e) {
            $response = $response->withStatus(500);
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Terjadi kesalahan dalam menyimpan data mata kuliah.',
                    'message' => $e->getMessage(),
                ]
            ));
        }

        return $response->withHeader("Content-Type", "application/json");
    });

    // Fungsi untuk memeriksa apakah ID Mata Kuliah sudah ada dalam database
    function isIdMatkulExists($db, $id_matkul) {
        $query = $db->prepare('SELECT COUNT(*) FROM matkul WHERE kode_matkul = ?');
        $query->bindParam(1, $id_matkul, PDO::PARAM_STR);
        $query->execute();
        $count = $query->fetchColumn();

        return $count > 0;
    }

    // Put Data
    // Update data matkul by kode_matkul
    $app->put('/matkul/{kode_matkul}', function ($request, $response, $args) {
        $db = $this->get(PDO::class);
        $kode_matkul = $args['kode_matkul'];

        // Check if the specified kode_matkul exists
        try {
            $checkQuery = $db->prepare('SELECT COUNT(*) FROM matkul WHERE kode_matkul = ?');
            $checkQuery->bindParam(1, $kode_matkul, PDO::PARAM_STR);
            $checkQuery->execute();
            $exists = $checkQuery->fetchColumn();

            if ($exists == 0) {
                $response = $response->withStatus(404); // Not Found
                $response->getBody()->write(json_encode(
                    [
                        'error' => 'Data matkul dengan kode_matkul ' . $kode_matkul . ' tidak ditemukan.',
                    ]
                ));
                return $response->withHeader("Content-Type", "application/json");
            }
        } catch (PDOException $e) {
            $response = $response->withStatus(500);
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Terjadi kesalahan dalam memeriksa data matkul.',
                    'message' => $e->getMessage(),
                ]
            ));
            return $response->withHeader("Content-Type", "application/json");
        }

        $parsedBody = $request->getParsedBody();
        $id_dosen_new = $parsedBody['id_dosen'];
        $nama_matkul_new = $parsedBody['nama_matkul'];
        $sks_new = $parsedBody['sks'];

        // Validasi data tidak boleh kosong
        if (empty($id_dosen_new) || empty($nama_matkul_new) || empty($sks_new)) {
            $response = $response->withStatus(400); // Bad Request
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Data id_dosen, nama_matkul, dan sks tidak boleh kosong.',
                ]
            ));
            return $response->withHeader("Content-Type", "application/json");
        }

        try {
            $query = $db->prepare('CALL update_matkul(?, ?, ?, ?)');
            $query->bindParam(1, $kode_matkul, PDO::PARAM_STR);
            $query->bindParam(2, $id_dosen_new, PDO::PARAM_STR);
            $query->bindParam(3, $nama_matkul_new, PDO::PARAM_STR);
            $query->bindParam(4, $sks_new, PDO::PARAM_INT);
            $query->execute();

            $response->getBody()->write(json_encode(
                [
                    'message' => 'Mata kuliah dengan kode_matkul ' . $kode_matkul . ' telah diupdate'
                ]
            ));
        } catch (PDOException $e) {
            $response = $response->withStatus(500);
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Terjadi kesalahan dalam memperbarui data mata kuliah.',
                    'message' => $e->getMessage(),
                ]
            ));
        }

        return $response->withHeader("Content-Type", "application/json");
    });

    //Delete Data
    // Menghapus data matkul by kode_matkul
    $app->delete('/matkul/{kode_matkul}', function ($request, $response, $args) {
        $db = $this->get(PDO::class);
        $kode_matkul = $args['kode_matkul'];
    
        try {
            $query = $db->prepare('CALL delete_matkul(?)');
            $query->bindParam(1, $kode_matkul, PDO::PARAM_STR);
            $query->execute();
    
            if ($query->rowCount() === 0) {
                $response = $response->withStatus(404);
                $response->getBody()->write(json_encode(
                    [
                        'message' => 'Data mata kuliah tidak ditemukan'
                    ]
                ));
            } else {
                $response->getBody()->write(json_encode(
                    [
                        'message' => 'Mata kuliah dengan kode ' . $kode_matkul . ' dihapus dari database'
                    ]
                ));
            }
        } catch (PDOException $e) {
            $response = $response->withStatus(500);
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Terjadi kesalahan dalam menghapus data mata kuliah.',
                    'message' => $e->getMessage(),
                ]
            ));
        }
    
        return $response->withHeader("Content-Type", "application/json");
    });
};