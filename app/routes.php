<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

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

    // Put Data
    // Update data mahasiswa by nim
    $app->put('/mahasiswa/{nim}', function ($request, $response, $args) {
        $db = $this->get(PDO::class);
        $nim = $args['nim'];

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

    //Tabel Dosen

    // Get Data
    //Mengambil seluruh data dosen
    $app->get('/dosen', function (Request $request, Response $response) {
        $db = $this->get(PDO::class);
    
        try {
            $query = $db->query('CALL select_dosen()');
            $results = $query->fetchAll(PDO::FETCH_ASSOC);
            $response->getBody()->write(json_encode($results));
        } catch (PDOException $e) {
            $response = $response->withStatus(500);
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Terjadi kesalahan dalam mengambil data dosen.',
                    'message' => $e->getMessage(),
                ]
            ));
        }
    
        return $response->withHeader("Content-Type", "application/json");
    });    

    // Mengambil data dosen by id_dosen
    $app->get('/dosen/{id_dosen}', function ($request, $response, $args) {
        $db = $this->get(PDO::class);
        $id_dosen = $args['id_dosen'];
    
        try {
            $query = $db->prepare('CALL select_dosen_by_id(:id_dosen)');
            $query->execute(array(':id_dosen' => $id_dosen));
    
            $results = $query->fetchAll(PDO::FETCH_ASSOC);
    
            if (count($results) > 0) {
                $response->getBody()->write(json_encode($results[0]));
            } else {
                $response->getBody()->write(json_encode(array('message' => 'Data dosen tidak ditemukan')));
            }
        } catch (PDOException $e) {
            $response = $response->withStatus(500);
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Terjadi kesalahan dalam mengambil data dosen.',
                    'message' => $e->getMessage(),
                ]
            ));
        }
    
        return $response->withHeader("Content-Type", "application/json");
    });    

    // Post Data
    // Menambahkan data dosen
    $app->post('/dosen', function ($request, $response) {
        $parsedBody = $request->getParsedBody();
        $new_id_dosen = $parsedBody["id_dosen"];
        $new_nama_dosen = $parsedBody["nama_dosen"];

        // Validasi data tidak boleh kosong
        if (empty($new_id_dosen) || empty($new_nama_dosen)) {
            $response = $response->withStatus(400); // Bad Request
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Data id_dosen dan nama_dosen tidak boleh kosong.',
                ]
            ));
            return $response->withHeader("Content-Type", "application/json");
        }

        $db = $this->get(PDO::class);

        // Validasi id_dosen tidak boleh duplikat
        if (isIdDosenExists($db, $new_id_dosen)) {
            $response = $response->withStatus(400); // Bad Request
            $response->getBody()->write(json_encode(
                [
                    'error' => 'ID Dosen ' . $new_id_dosen . ' sudah ada dalam database.',
                ]
            ));
            return $response->withHeader("Content-Type", "application/json");
        }

        try {
            $query = $db->prepare('CALL insert_dosen(?, ?)');
            $query->bindParam(1, $new_id_dosen, PDO::PARAM_STR);
            $query->bindParam(2, $new_nama_dosen, PDO::PARAM_STR);
            $query->execute();

            $response->getBody()->write(json_encode(
                [
                    'message' => 'Data dosen disimpan dengan sukses'
                ]
            ));
        } catch (PDOException $e) {
            $response = $response->withStatus(500);
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Terjadi kesalahan dalam menyimpan data dosen.',
                    'message' => $e->getMessage(),
                ]
            ));
        }

        return $response->withHeader("Content-Type", "application/json");
    });

    // Fungsi untuk memeriksa apakah ID Dosen sudah ada dalam database
    function isIdDosenExists($db, $id_dosen) {
        $query = $db->prepare('SELECT COUNT(*) FROM dosen WHERE id_dosen = ?');
        $query->bindParam(1, $id_dosen, PDO::PARAM_STR);
        $query->execute();
        $count = $query->fetchColumn();

        return $count > 0;
    }
    
    // Put Data
    // Update data dosen by id_dosen
    $app->put('/dosen/{id_dosen}', function ($request, $response, $args) {
        $db = $this->get(PDO::class);
        $id_dosen = $args['id_dosen'];
    
        $parsedBody = $request->getParsedBody();
        $nama_dosen = $parsedBody["nama_dosen"];
    
        // Validasi data tidak boleh kosong
        if (empty($nama_dosen)) {
            $response = $response->withStatus(400); // Bad Request
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Data nama_dosen tidak boleh kosong.',
                ]
            ));
            return $response->withHeader("Content-Type", "application/json");
        }
    
        try {
            $query = $db->prepare('CALL update_dosen(?, ?)');
            $query->bindParam(1, $id_dosen, PDO::PARAM_STR);
            $query->bindParam(2, $nama_dosen, PDO::PARAM_STR);
            $query->execute();
    
            $response->getBody()->write(json_encode(
                [
                    'message' => 'Data dosen dengan id dosen ' . $id_dosen . ' telah diperbarui dengan nama ' . $nama_dosen
                ]
            ));
        } catch (PDOException $e) {
            $response = $response->withStatus(500);
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Terjadi kesalahan dalam memperbarui data dosen.',
                    'message' => $e->getMessage(),
                ]
            ));
        }
    
        return $response->withHeader("Content-Type", "application/json");
    });    
    
    //Delete Data
    // Menghapus data dosen by id_dosen
    $app->delete('/dosen/{id_dosen}', function ($request, $response, $args) {
        $db = $this->get(PDO::class);
        $id_dosen = $args['id_dosen'];
    
        try {
            $query = $db->prepare('CALL delete_dosen(?)');
            $query->bindParam(1, $id_dosen, PDO::PARAM_STR);
            $query->execute();
    
            if ($query->rowCount() === 0) {
                $response = $response->withStatus(404);
                $response->getBody()->write(json_encode(
                    [
                        'message' => 'Data dosen tidak ditemukan'
                    ]
                ));
            } else {
                $response->getBody()->write(json_encode(
                    [
                        'message' => 'Dosen dengan id dosen ' . $id_dosen . ' dihapus dari database'
                    ]
                ));
            }
        } catch (PDOException $e) {
            $response = $response->withStatus(500);
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Terjadi kesalahan dalam menghapus data dosen.',
                    'message' => $e->getMessage(),
                ]
            ));
        }
    
        return $response->withHeader("Content-Type", "application/json");
    });    

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
        $parsedBody = $request->getParsedBody();

        $nilai_new = $parsedBody['nilai'];

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