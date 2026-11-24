<?php

declare(strict_types=1);

namespace App\Config;

use MongoDB\Client;
use MongoDB\Database as MongoDatabase;

/** Conexão única e configurável com o MongoDB (local ou Atlas). */
final class Database
{
    private static ?Client $client = null;

    /** URI de conexão: MONGO_URI/MONGODB_URI ou .env, com fallback local. */
    public static function uri(): string
    {
        self::carregarEnv();
        $uri = getenv('MONGO_URI') ?: getenv('MONGODB_URI') ?: ($_ENV['MONGO_URI'] ?? $_ENV['MONGODB_URI'] ?? '');

        return $uri !== '' ? $uri : 'mongodb://localhost:27017';
    }

    /** Nome do banco (MONGO_DB/MONGODB_DB), com valor padrão. */
    public static function nome(): string
    {
        self::carregarEnv();
        $nome = getenv('MONGO_DB') ?: getenv('MONGODB_DB') ?: ($_ENV['MONGO_DB'] ?? $_ENV['MONGODB_DB'] ?? '');

        return $nome !== '' ? $nome : 'sistema_estudantes';
    }

    /** Acesso ao banco selecionado. */
    public static function db(): MongoDatabase
    {
        if (self::$client === null) {
            self::$client = new Client(self::uri(), ['serverSelectionTimeoutMS' => 5000]);
        }

        return self::$client->selectDatabase(self::nome());
    }

    /** Carrega um .env simples, sem sobrescrever variáveis já definidas. */
    private static function carregarEnv(): void
    {
        static $carregado = false;
        if ($carregado) {
            return;
        }
        $carregado = true;

        $arquivo = dirname(__DIR__) . '/.env';
        if (!is_file($arquivo)) {
            return;
        }

        foreach (file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $linha) {
            $linha = trim($linha);
            if ($linha === '' || str_starts_with($linha, '#')) {
                continue;
            }

            [$chave, $valor] = array_pad(explode('=', $linha, 2), 2, '');
            $chave = trim($chave);
            $valor = trim($valor);
            if (strlen($valor) >= 2 && (($valor[0] === '"' && str_ends_with($valor, '"')) || ($valor[0] === "'" && str_ends_with($valor, "'")))) {
                $valor = substr($valor, 1, -1);
            }
            if ($chave === '' || getenv($chave) !== false) {
                continue;
            }

            $_ENV[$chave] = $valor;
            putenv($chave . '=' . $valor);
        }
    }
}
