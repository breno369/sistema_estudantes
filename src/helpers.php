<?php

declare(strict_types=1);

use MongoDB\BSON\ObjectId;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Normaliza um valor vindo de formulário. */
function app_texto(mixed $valor): string
{
    return trim((string) ($valor ?? ''));
}

/** Escapa saída HTML. */
function app_e(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Lê um campo POST já normalizado. */
function app_post(string $chave): string
{
    return app_texto($_POST[$chave] ?? '');
}

/** Converte ObjectId (ou string) em hexadecimal seguro para exibição/URL. */
function app_id(mixed $valor): string
{
    if ($valor instanceof ObjectId) {
        return $valor->__toString();
    }

    return app_texto($valor);
}

/** Valida e cria um ObjectId a partir de um hexadecimal de 24 caracteres. */
function app_oid(string $hex): ?ObjectId
{
    return preg_match('/^[a-f0-9]{24}$/i', $hex) === 1 ? new ObjectId($hex) : null;
}

/** Redirecionamento com encerramento imediato. */
function app_redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/** Mensagem flash (sucesso/erro) armazenada na sessão. */
function app_flash(string $mensagem, string $tipo = 'success'): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

/** Recupera e remove a mensagem flash atual. */
function app_flash_take(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return $flash;
}

/** Formata uma data YYYY-MM-DD para dd/mm/yyyy. */
function app_data_br(?string $valor): string
{
    $valor = app_texto($valor);
    if ($valor === '') {
        return '—';
    }

    $data = DateTime::createFromFormat('Y-m-d', $valor);

    return $data ? $data->format('d/m/Y') : app_e($valor);
}

/** Texto amigável para falhas de acesso ao banco. */
function app_erro_mensagem(Throwable $erro): string
{
    return 'Não foi possível concluir a operação. Verifique se o MongoDB está disponível. Detalhes: ' . $erro->getMessage();
}

/** Lista segura de IDs vindos de checkboxes (cursos_ids[]). */
function app_ids_post(string $chave): array
{
    $brutos = $_POST[$chave] ?? [];
    if (!is_array($brutos)) {
        return [];
    }

    $ids = [];
    foreach ($brutos as $bruto) {
        $oid = app_oid(app_texto($bruto));
        if ($oid !== null) {
            $ids[] = $oid;
        }
    }

    return array_values(array_unique(array_map(static fn (ObjectId $item): string => $item->__toString(), $ids)));
}
