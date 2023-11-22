<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app) { 
    
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

        // Check if id_dosen exists
        try {
            $checkQuery = $db->prepare('SELECT COUNT(*) FROM dosen WHERE id_dosen = ?');
            $checkQuery->bindParam(1, $id_dosen, PDO::PARAM_STR);
            $checkQuery->execute();

            $count = $checkQuery->fetchColumn();

            if ($count == 0) {
                $response = $response->withStatus(404); // Not Found
                $response->getBody()->write(json_encode(
                    [
                        'error' => 'Data dosen dengan id_dosen ' . $id_dosen . ' tidak ditemukan.',
                    ]
                ));
                return $response->withHeader("Content-Type", "application/json");
            }
        } catch (PDOException $e) {
            $response = $response->withStatus(500);
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Terjadi kesalahan dalam mengambil data dosen.',
                    'message' => $e->getMessage(),
                ]
            ));
            return $response->withHeader("Content-Type", "application/json");
        }

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
};