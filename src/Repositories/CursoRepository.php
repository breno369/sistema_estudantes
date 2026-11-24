<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;

/** Persistência de cursos e consultas normalizadas de seus estudantes. */
final class CursoRepository
{
    /** @var array<string, string> */
    private const TYPEMAP = ['root' => 'array', 'document' => 'array', 'array' => 'array'];

    private Collection $cursos;
    private Collection $estudantes;

    public function __construct()
    {
        $banco = Database::db();
        $this->cursos = $banco->selectCollection('cursos');
        $this->estudantes = $banco->selectCollection('estudantes');
    }

    public function contar(): int
    {
        return $this->cursos->countDocuments();
    }

    /** @param array<string, mixed> $dados */
    public function criar(array $dados): ObjectId
    {
        $documento = [
            'nome' => \app_texto($dados['nome'] ?? ''),
            'codigo' => \app_texto($dados['codigo'] ?? ''),
            'carga_horaria' => \app_texto($dados['carga_horaria'] ?? ''),
            'turno' => \app_texto($dados['turno'] ?? ''),
            'coordenador' => \app_texto($dados['coordenador'] ?? ''),
            'descricao' => \app_texto($dados['descricao'] ?? ''),
            'estudantes_ids' => [],
            'criado_em' => new UTCDateTime(),
        ];

        $id = $this->cursos->insertOne($documento)->getInsertedId();
        if (!$id instanceof ObjectId) {
            throw new \RuntimeException('Não foi possível obter o ID do curso criado.');
        }

        return $id;
    }

    /** Verifica duplicidade de código do curso. */
    public function existeCampo(string $campo, string $valor): bool
    {
        if ($campo !== 'codigo' || $valor === '') {
            return false;
        }

        return $this->cursos->countDocuments([$campo => $valor]) > 0;
    }

    /** Lista cursos com os nomes dos estudantes via $lookup. */
    public function listarComEstudantes(): array
    {
        return $this->cursos->aggregate([
            ['$lookup' => [
                'from' => 'estudantes',
                'localField' => 'estudantes_ids',
                'foreignField' => '_id',
                'pipeline' => [['$project' => ['nome' => 1, 'matricula' => 1]]],
                'as' => 'estudantes',
            ]],
            ['$sort' => ['nome' => 1]],
        ], ['typeMap' => self::TYPEMAP])->toArray();
    }

    /** Opções enxutas para o seletor de matrículas. */
    public function listarSimples(): array
    {
        return $this->cursos->find(
            [],
            ['projection' => ['nome' => 1, 'codigo' => 1], 'sort' => ['nome' => 1], 'typeMap' => self::TYPEMAP]
        )->toArray();
    }

    /** Detalhes de um curso com seus estudantes via $lookup. */
    public function buscarPorId(string $id): ?array
    {
        $oid = \app_oid($id);
        if ($oid === null) {
            return null;
        }

        $documentos = $this->cursos->aggregate([
            ['$match' => ['_id' => $oid]],
            ['$lookup' => [
                'from' => 'estudantes',
                'localField' => 'estudantes_ids',
                'foreignField' => '_id',
                'pipeline' => [['$project' => ['nome' => 1, 'matricula' => 1, 'email' => 1]]],
                'as' => 'estudantes',
            ]],
            ['$limit' => 1],
        ], ['typeMap' => self::TYPEMAP])->toArray();

        return $documentos[0] ?? null;
    }

    /** Exclui o curso e remove sua referência de todos os estudantes. */
    public function excluir(string $id): bool
    {
        $oid = \app_oid($id);
        if ($oid === null) {
            return false;
        }

        $curso = $this->cursos->findOne(['_id' => $oid], ['typeMap' => self::TYPEMAP]);
        if ($curso === null) {
            return false;
        }

        $estudantesIds = $curso['estudantes_ids'] ?? [];
        if ($estudantesIds !== []) {
            $this->estudantes->updateMany(
                ['_id' => ['$in' => $estudantesIds]],
                ['$pull' => ['cursos_ids' => $oid]]
            );
        }

        return $this->cursos->deleteOne(['_id' => $oid])->getDeletedCount() === 1;
    }
}
