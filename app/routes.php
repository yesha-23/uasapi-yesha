<?php
declare(strict_types=1);

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use App\Middleware\JwtMiddleware;

return function (App $app) {

    // ===== LOGIN =====
    $app->post('/api/login', function (Request $request, Response $response) {
        $data = $request->getParsedBody();

        if ($data['username'] !== 'admin' || $data['password'] !== '123456789') {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Username atau password salah'
            ]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $payload = [
            'username' => $data['username'],
            'exp' => time() + 3600
        ];

        $token = JWT::encode($payload, 'secret_key_uas_gudang_warehouse_2026', 'HS256');

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'token' => $token
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // ===== CRUD GUDANG =====
    // CREATE
    $app->post('/api/gudang', function (Request $request, Response $response) {
        $db = $this->get('db');
        $data = $request->getParsedBody();
        $id = $db->table('gudang')->insertGetId([
            'nama_gudang' => $data['nama_gudang'],
            'lokasi' => $data['lokasi'],
            'kapasitas_maksimal' => $data['kapasitas_maksimal'],
        ]);
        $result = $db->table('gudang')->where('id', $id)->first();
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => 'Gudang berhasil ditambahkan',
            'data' => $result
        ]));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    })->add(new JwtMiddleware());

    // READ
    $app->get('/api/gudang', function (Request $request, Response $response) {
        $db = $this->get('db');
        $gudangs = $db->table('gudang')->get();

        $result = [];
        foreach ($gudangs as $gudang) {
            $barangs = $db->table('barang')->where('gudang_id', $gudang->id)->get();
            $daftarBarang = [];
            foreach ($barangs as $barang) {
                $kategori = $db->table('kategori')->where('id', $barang->kategori_id)->first();
                $daftarBarang[] = [
                    'id' => $barang->id,
                    'nama_barang' => $barang->nama_barang,
                    'sku' => $barang->sku,
                    'stok' => $barang->stok,
                    'detail_kategori' => [
                        'id' => $kategori->id,
                        'nama_kategori' => $kategori->nama_kategori
                    ]
                ];
            }
            $result[] = [
                'id' => $gudang->id,
                'nama_gudang' => $gudang->nama_gudang,
                'lokasi' => $gudang->lokasi,
                'kapasitas_maksimal' => $gudang->kapasitas_maksimal,
                'daftar_barang' => $daftarBarang
            ];
        }

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $result
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    })->add(new JwtMiddleware());

    // UPDATE
    $app->put('/api/gudang/{id}', function (Request $request, Response $response, $args) {
        $db = $this->get('db');
        $data = $request->getParsedBody();
        $db->table('gudang')->where('id', $args['id'])->update([
            'nama_gudang' => $data['nama_gudang'],
            'lokasi' => $data['lokasi'],
            'kapasitas_maksimal' => $data['kapasitas_maksimal'],
        ]);
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => 'Data gudang dengan ID ' . $args['id'] . ' berhasil diperbarui'
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    })->add(new JwtMiddleware());

    // DELETE
    $app->delete('/api/gudang/{id}', function (Request $request, Response $response, $args) {
        $db = $this->get('db');
        $db->table('gudang')->where('id', $args['id'])->delete();
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => 'Gudang dengan ID ' . $args['id'] . ' telah dihapus'
        ]));
        return $response->withHeader('Content-Type', 'application/json');})->add(new JwtMiddleware());

// ================================ UAS RESTFUL API ====================================
//=== CREATE KEHADIRAN (POST) ===
$app->post('/api/pegawai', function (Request $request, Response $response) {
    $db = $this->get('db');
    $data = $request->getParsedBody();
    $id = $db->table('pegawai')->insertGetId([
            'nip' => $data['nip'],
            'nama_lengkap' => $data['nama_lengkap'],
            'jabatan' => $data['jabatan']
    ]);
    $result = $db->table('pegawai')->where('id', $id)->first();
    $response->getBody()->write(json_encode([
        'status' => 'success',
        'message' => 'Pegawai berhasil ditambahkan',
        'data' => $result
    ]));
    return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    })->add(new JwtMiddleware());

// ==== READ PEGAWAI (GET) ====
    $app->get('/api/pegawai', function (Request $request, Response $response) {
        $db = $this->get('db');
        $pegawais = $db->table('pegawai')->get();
        $result = [];
        foreach ($pegawais as $pegawai) {
            $presensi = $db->table('presensi')->where('pegawai_id', $pegawai->id)->get();
            $riwayat_presensi = [];
            foreach ($presensi as $p) {
                $status = $db->table('status')->where('id', $p->status_id)->first();
                $riwayat_presensi[] = [
                    'id' => $p->id,
                    'tanggal' => $p->tanggal,
                    'jam_masuk' => $p->jam_masuk,
                    'jam_keluar' => $p->jam_keluar,
                    'detail_status' => [
                        'id' => $status->id,
                        'nama_status' => $status->nama_status
                    ]
                ];
            }
            $result[] = [
                'id' => $pegawai->id,
                'nip' => $pegawai->nip,
                'nama_lengkap' => $pegawai->nama_lengkap,
                'jabatan' => $pegawai->jabatan,
                'riwayat_presensi'=> $riwayat_presensi
            ]; 
        }
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $result
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    })->add(new JwtMiddleware());

    // === UPDATE PEGAWAI (PUT) ====
    $app->put('/api/pegawai/{id}', function (Request $request, Response $response, $args) {
        $db = $this->get('db');
        $data = $request->getParsedBody();
        $db->table('pegawai')->where('id', $args['id'])->update([
            'nip' => $data['nip'],
            'nama_lengkap' => $data['nama_lengkap'],
            'jabatan' => $data['jabatan']
        ]);
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => 'Data pegawai dengan ID ' . $args['id'] . ' berhasil diperbarui!'
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    })->add(new JwtMiddleware());

    // === DELETE PEGAWAI (DELETE) ===
    $app->delete('/api/pegawai/{id}', function (Request $request, Response $response, $args) {
        $db = $this->get('db');
        $db->table('pegawai')->where('id', $args['id'])->delete();
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => 'Pegawai dengan ID ' . $args['id'] . ' telah dihapus!'
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    })->add(new JwtMiddleware());
};