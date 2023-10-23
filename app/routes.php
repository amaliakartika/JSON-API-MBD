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

        $query = $db->query('CALL select_mahasiswa()');
        $results = $query->fetchAll(PDO::FETCH_ASSOC);
        $response->getBody()->write(json_encode($results));

        return $response->withHeader("Content-Type", "application/json");
    });

    // Mengambil data mahasiswa by nim
    $app->get('/mahasiswa/{nim}', function ($request, $response, $args) {
        $db = $this->get(PDO::class);
        $nim = $args['nim'];

        $query = $db->prepare('CALL select_mahasiswa_by_nim(:nim)');
        $query->execute(array(':nim' => $nim));

        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        if (count($results) > 0) {
            $response->getBody()->write(json_encode($results[0]));
        } else {
            $response->getBody()->write(json_encode(array('message' => 'Data mahasiswa tidak ditemukan')));
        }

        return $response->withHeader("Content-Type", "application/json");
    });

    // Post Data
    //Menambahkan data mahasiswa
    $app->post('/mahasiswa', function ($request, $response) {
        $parsedBody = $request->getParsedBody();
        $new_nim = $parsedBody["nim"];
        $new_nama = $parsedBody["nama"];
        $new_prodi = $parsedBody["prodi"];
    
        $db = $this->get(PDO::class);
    
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

        return $response->withHeader("Content-Type", "application/json");
    });

    //Delete Data
    // Menghapus data mahasiswa by nim
    $app->delete('/mahasiswa/{nim}', function ($request, $response, $args) {
        $db = $this->get(PDO::class);
        $nim = $args['nim'];

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

        return $response->withHeader("Content-Type", "application/json");
    });

    //Tabel Dosen

    // Get Data
    //Mengambil seluruh data dosen
    $app->get('/dosen', function (Request $request, Response $response) {
        $db = $this->get(PDO::class);

        $query = $db->query('CALL select_dosen()');
        $results = $query->fetchAll(PDO::FETCH_ASSOC);
        $response->getBody()->write(json_encode($results));

        return $response->withHeader("Content-Type", "application/json");
    });

    // Mengambil data dosen by id_dosen
    $app->get('/dosen/{id_dosen}', function ($request, $response, $args) {
        $db = $this->get(PDO::class);
        $id_dosen = $args['id_dosen'];

        $query = $db->prepare('CALL select_dosen_by_id(:id_dosen)');
        $query->execute(array(':id_dosen' => $id_dosen));

        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        if (count($results) > 0) {
            $response->getBody()->write(json_encode($results[0]));
        } else {
            $response->getBody()->write(json_encode(array('message' => 'Data dosen tidak ditemukan')));
        }

        return $response->withHeader("Content-Type", "application/json");
    });

    // Post Data
    //Menambahkan data dosen
    $app->post('/dosen', function ($request, $response) {
        $parsedBody = $request->getParsedBody();
        $new_id_dosen = $parsedBody["id_dosen"];
        $new_nama_dosen = $parsedBody["nama_dosen"];
    
        $db = $this->get(PDO::class);
    
        $query = $db->prepare('CALL insert_dosen(?, ?)');
        $query->bindParam(1, $new_id_dosen, PDO::PARAM_STR);
        $query->bindParam(2, $new_nama_dosen, PDO::PARAM_STR);
        $query->execute();
    
        $response->getBody()->write(json_encode(
            [
                'message' => 'Data dosen disimpan dengan sukses'
            ]
        ));
    
        return $response->withHeader("Content-Type", "application/json");
    });
    
    // Put Data
    // Update data dosen by id_dosen
    $app->put('/dosen/{id_dosen}', function ($request, $response, $args) {
        $db = $this->get(PDO::class);
        $id_dosen = $args['id_dosen'];

        $parsedBody = $request->getParsedBody();
        $nama_dosen = $parsedBody["nama_dosen"];

        $query = $db->prepare('CALL update_dosen(?, ?)');
        $query->bindParam(1, $id_dosen, PDO::PARAM_STR);
        $query->bindParam(2, $nama_dosen, PDO::PARAM_STR);
        $query->execute();

        $response->getBody()->write(json_encode(
            [
                'message' => 'Data dosen dengan id dosen ' . $id_dosen . ' telah diperbarui dengan nama ' . $nama_dosen
            ]
        ));

        return $response->withHeader("Content-Type", "application/json");
    });

    //Delete Data
    // Menghapus data dosen by id_dosen
    $app->delete('/dosen/{id_dosen}', function ($request, $response, $args) {
        $db = $this->get(PDO::class);
        $id_dosen = $args['id_dosen'];

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

        return $response->withHeader("Content-Type", "application/json");
    });

    //Tabel Matkul

    // Get Data
    //Mengambil seluruh data matkul
    $app->get('/matkul', function (Request $request, Response $response) {
        $db = $this->get(PDO::class);

        $query = $db->query('CALL select_matkul()');
        $results = $query->fetchAll(PDO::FETCH_ASSOC);
        $response->getBody()->write(json_encode($results));

        return $response->withHeader("Content-Type", "application/json");
    });

    
    // Mengambil data mata kuliah by kode_matkul
    $app->get('/matkul/{kode_matkul}', function ($request, $response, $args) {
        $db = $this->get(PDO::class);
        $kode_matkul = $args['kode_matkul'];

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

        return $response->withHeader("Content-Type", "application/json");
    });

    // Post Data
    //Menambahkan data matkul
    $app->post('/matkul', function ($request, $response) {
        $parsedBody = $request->getParsedBody();
        $new_kode_matkul = $parsedBody["kode_matkul"];
        $new_id_dosen = $parsedBody["id_dosen"];
        $new_nama_matkul = $parsedBody["nama_matkul"];
        $new_sks = $parsedBody["sks"];
    
        $db = $this->get(PDO::class);
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
    
        return $response->withHeader("Content-Type", "application/json");
    });
    
    // Put Data
    // Update data matkul by kode_matkul
    $app->put('/matkul/{kode_matkul}', function ($request, $response, $args) {
        $db = $this->get(PDO::class);
        $kode_matkul = $args['kode_matkul'];
        $parsedBody = $request->getParsedBody();
    
        $id_dosen_new = $parsedBody['id_dosen'];
        $nama_matkul_new = $parsedBody['nama_matkul'];
        $sks_new = $parsedBody['sks'];
    
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
    
        return $response->withHeader("Content-Type", "application/json");
    });

    //Delete Data
    // Menghapus data matkul by kode_matkul
    $app->delete('/matkul/{kode_matkul}', function ($request, $response, $args) {
        $db = $this->get(PDO::class);
        $kode_matkul = $args['kode_matkul'];
    
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
    
        return $response->withHeader("Content-Type", "application/json");
    });
};