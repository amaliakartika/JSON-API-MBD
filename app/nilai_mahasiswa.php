<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app) {
    //Tabel Nilai Mahasiswa

    // Get Data
    //Mengambil seluruh data nilai mahasiswa
    $app->get('/nilai_mahasiswa', function (Request $request, Response $response) {
        $db = $this->get(PDO::class);
    
        try {
            $query = $db->query('CALL select_nilai_mahasiswa()');
            $results = $query->fetchAll(PDO::FETCH_ASSOC);
            $response->getBody()->write(json_encode($results));
        } catch (PDOException $e) {
            $response = $response->withStatus(500);
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Terjadi kesalahan dalam mengambil data nilai mahasiswa.',
                    'message' => $e->getMessage(),
                ]
            ));
        }
    
        return $response->withHeader("Content-Type", "application/json");
    });
    
    // Mengambil data nilai mahasiswa by nim
    $app->get('/nilai_mahasiswa/{nim}', function ($request, $response, $args) {
        $db = $this->get(PDO::class);
        $nim = $args['nim'];
    
        try {
            $query = $db->prepare('CALL select_nilai_mahasiswa_by_nim(?)');
            $query->bindParam(1, $nim, PDO::PARAM_INT);
            $query->execute();
    
            $results = $query->fetchAll(PDO::FETCH_ASSOC);
    
            if (count($results) > 0) {
                $response->getBody()->write(json_encode($results));
            } else {
                $response = $response->withStatus(404);
                $response->getBody()->write(json_encode(
                    [
                        'message' => 'Nim mahasiswa tidak ditemukan'
                    ]
                ));
            }
        } catch (PDOException $e) {
            $response = $response->withStatus(500);
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Terjadi kesalahan dalam mengambil data nilai mahasiswa.',
                    'message' => $e->getMessage(),
                ]
            ));
        }
    
        return $response->withHeader("Content-Type", "application/json");
    });
    
    // Post Data
    // Menambahkan data nilai mahasiswa
    $app->post('/nilai_mahasiswa', function ($request, $response) {
        $parsedBody = $request->getParsedBody();
        $new_student_nim = $parsedBody["nim"];
        $new_course_code = $parsedBody["kode_matkul"];
        $new_grade_value = $parsedBody["nilai"];

        // Validasi data tidak boleh kosong
        if (empty($new_student_nim) || empty($new_course_code) || empty($new_grade_value)) {
            $response = $response->withStatus(400); // Bad Request
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Data nim, kode_matkul, dan nilai tidak boleh kosong.',
                ]
            ));
            return $response->withHeader("Content-Type", "application/json");
        }

        $db = $this->get(PDO::class);

        try {
            $query = $db->prepare('CALL insert_nilai(?, ?, ?)');
            $query->bindParam(1, $new_student_nim, PDO::PARAM_INT);
            $query->bindParam(2, $new_course_code, PDO::PARAM_STR);
            $query->bindParam(3, $new_grade_value, PDO::PARAM_STR);
            $query->execute();

            $lastId = $db->lastInsertId();

            if ($query->rowCount() > 0) {
                $response->getBody()->write(json_encode(
                    [
                        'message' => 'Nilai mahasiswa dengan nim ' . $new_student_nim . ' berhasil disimpan'
                    ]
                ));
            } else {
                $response = $response->withStatus(500);
                $response->getBody()->write(json_encode(
                    [
                        'message' => 'Gagal menyimpan nilai mahasiswa'
                    ]
                ));
            }
        } catch (PDOException $e) {
            $response = $response->withStatus(500);
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Terjadi kesalahan dalam menyimpan data nilai mahasiswa.',
                    'message' => $e->getMessage(),
                ]
            ));
        }

        return $response->withHeader("Content-Type", "application/json");
    });

    // Put Data
    // Update data nilai mahasiswa by id_nilai
    $app->put('/nilai_mahasiswa/{id_nilai}', function ($request, $response, $args) {
        $db = $this->get(PDO::class);
        $id_nilai = $args['id_nilai'];

        // Check if the specified id_nilai exists
        try {
            $checkQuery = $db->prepare('SELECT COUNT(*) FROM nilai_mahasiswa WHERE id_nilai = ?');
            $checkQuery->bindParam(1, $id_nilai, PDO::PARAM_STR);
            $checkQuery->execute();
            $exists = $checkQuery->fetchColumn();

            if ($exists == 0) {
                $response = $response->withStatus(404); // Not Found
                $response->getBody()->write(json_encode(
                    [
                        'error' => 'Data nilai mahasiswa dengan id_nilai ' . $id_nilai . ' tidak ditemukan.',
                    ]
                ));
                return $response->withHeader("Content-Type", "application/json");
            }
        } catch (PDOException $e) {
            $response = $response->withStatus(500);
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Terjadi kesalahan dalam memeriksa data nilai mahasiswa.',
                    'message' => $e->getMessage(),
                ]
            ));
            return $response->withHeader("Content-Type", "application/json");
        }

        $parsedBody = $request->getParsedBody();
        $nilai_new = $parsedBody["nilai"];

        // Validasi data tidak boleh kosong
        if (empty($nilai_new)) {
            $response = $response->withStatus(400); // Bad Request
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Data nilai tidak boleh kosong.',
                ]
            ));
            return $response->withHeader("Content-Type", "application/json");
        }

        try {
            $query = $db->prepare('CALL update_nilai_mahasiswa(?, ?)');
            $query->bindParam(1, $id_nilai, PDO::PARAM_STR);
            $query->bindParam(2, $nilai_new, PDO::PARAM_STR);
            $query->execute();

            $response->getBody()->write(json_encode(
                [
                    'message' => 'Nilai mahasiswa dengan id_nilai ' . $id_nilai . ' telah diupdate'
                ]
            ));
        } catch (PDOException $e) {
            $response = $response->withStatus(500);
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Terjadi kesalahan dalam memperbarui data nilai mahasiswa.',
                    'message' => $e->getMessage(),
                ]
            ));
        }

        return $response->withHeader("Content-Type", "application/json");
    });

    //Delete Data
    // Menghapus data nilai mahasiswa by id_nilai
    $app->delete('/nilai_mahasiswa/{id_nilai}', function ($request, $response, $args) {
        $db = $this->get(PDO::class);
        $id_nilai = $args['id_nilai'];
    
        try {
            $query = $db->prepare('CALL delete_nilai_mahasiswa(?)');
            $query->bindParam(1, $id_nilai, PDO::PARAM_INT);
            $query->execute();
    
            if ($query->rowCount() === 0) {
                $response = $response->withStatus(404);
                $response->getBody()->write(json_encode(
                    [
                        'message' => 'Data nilai mahasiswa dengan id_nilai ' . $id_nilai . ' tidak ditemukan'
                    ]
                ));
            } else {
                $response->getBody()->write(json_encode(
                    [
                        'message' => 'Nilai mahasiswa dengan id_nilai ' . $id_nilai . ' dihapus dari database'
                    ]
                ));
            }
        } catch (PDOException $e) {
            $response = $response->withStatus(500);
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Terjadi kesalahan dalam menghapus data nilai mahasiswa.',
                    'message' => $e->getMessage(),
                ]
            ));
        }
    
        return $response->withHeader("Content-Type", "application/json");
    });
};