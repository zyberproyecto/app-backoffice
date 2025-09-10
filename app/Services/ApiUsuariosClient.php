<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class ApiUsuariosClient
{
    private Client $http;
    private string $base;

    public function __construct()
    {
        $this->base = rtrim(config('services.usuarios.base'), '/');
        $this->http = new Client([
            'base_uri' => $this->base,
            'timeout'  => 5.0,
        ]);
    }

    public function perfilConToken(string $token): array
    {
        try {
            $res = $this->http->get('/api/perfil', [
                'headers' => [
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer '.$token,
                ],
            ]);
            $json = json_decode((string)$res->getBody(), true);
            return [
                'ok'     => true,
                'data'   => $json['data'] ?? $json ?? null,
                'status' => $res->getStatusCode(),
            ];
        } catch (GuzzleException $e) {
            return ['ok'=>false, 'data'=>null, 'status'=>0];
        }
    }
}