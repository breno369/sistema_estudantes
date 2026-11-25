<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use InvalidArgumentException;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;

/** Persistência de estudantes e da relationship N:N com cursos. */
final class EstudanteRepository
{
    /** @var array<string, string> */
    private const TYPEMAP = ['root' => 'array', 'document' => 'array', 'array' => 'array'];

    private Collection $estudantes;
    private Collection $cursos;

    public function __construct()
    {
        $banco = Database::db();
        $this->estudantes = $banco->selectCollection('estudantes');
        $this->cursos = $banco->selectCollection('cursos');
    }

    public function contar(): int
    {
        return $this->estudantes->countDocuments();
    }

    /** @param array<string, mixed> $dados */
    public function criar(array $dados): ObjectId
    {
        $documento = [
            'nome' => \app_texto($dados['nome'] ?? ''),
            'cpf' => \app_texto($dados['cpf'] ?? ''),
            'email' => \app_texto($dados['email'] ?? ''),
            'telefone' => \app_texto($dados['telefone'] ?? ''),
            'data_nascimento' => \app_texto($dados['data_nascimento'] ?? ''),
            'matricula' => \app_texto($dados['matricula'] ?? ''),
            'endereco' => [
                'cep' => \app_texto($dados['endereco']['cep'] ?? ''),
                'logradouro' => \app_texto($dados['endereco']['logradouro'] ?? ''),
                'numero' => \app_texto($dados['endereco']['numero'] ?? ''),
                'bairro' => \app_texto($dados['endereco']['bairro'] ?? ''),
                'cidade' => \app_texto($dados['endereco']['cidade'] ?? ''),
                'estado' => \app_texto($dados['endereco']['estado'] ?? ''),
            ],
            'cursos_ids' => [],
            'criado_em' => new UTCDateTime(),
        ];

        $id = $this->estudantes->insertOne($documento)->getInsertedId();
        if (!$id instanceof ObjectId) {
            throw new \RuntimeException('Não foi possível obter o ID do estudante criado.');
        }

        return $id;
    }

    /** Verifica duplicidade de CPF ou matrícula. */
    public function existeCampo(string $campo, string $valor): bool
    {
        if (!in_array($campo, ['cpf', 'matricula'], true) || $valor === '') {
            return false;
        }

        return $this->estudantes->countDocuments([$campo => $valor]) > 0;
    }

    /** Lista estudantes com os nomes dos cursos via $lookup. */
    public function listarComCursos(): array
    {
        return $this->estudantes->aggregate([
            ['$lookup' => [
                'from' => 'cursos',
                'localField' => 'cursos_ids',
                'foreignField' => '_id',
                'pipeline' => [['$project' => ['nome' => 1, 'codigo' => 1]]],
                'as' => 'cursos',
            ]],
            ['$sort' => ['nome' => 1]],
        ], ['typeMap' => self::TYPEMAP])->toArray();
    }

    /** Opções enxutas para o seletor de matrículas. */
    public function listarSimples(): array
    {
        return $this->estudantes->find(
            [],
            ['projection' => ['nome' => 1, 'matricula' => 1], 'sort' => ['nome' => 1], 'typeMap' => self::TYPEMAP]
        )->toArray();
    }

    /** Detalhes de um estudante com seus cursos via $lookup. */
    public function buscarPorId(string $id): ?array
    {
        $oid = \app_oid($id);
        if ($oid === null) {
            return null;
        }

        $documentos = $this->estudantes->aggregate([
            ['$match' => ['_id' => $oid]],
            ['$lookup' => [
                'from' => 'cursos',
                'localField' => 'cursos_ids',
                'foreignField' => '_id',
                'pipeline' => [['$project' => ['nome' => 1, 'codigo' => 1, 'turno' => 1]]],
                'as' => 'cursos',
            ]],
            ['$limit' => 1],
        ], ['typeMap' => self::TYPEMAP])->toArray();

        return $documentos[0] ?? null;
    }

    /** Documento cru (sem $lookup), usado para calcular a situação atual das matrículas. */
    public function buscarSimples(string $id): ?array
    {
        $oid = \app_oid($id);
        if ($oid === null) {
            return null;
        }

        return $this->estudantes->findOne(['_id' => $oid], ['typeMap' => self::TYPEMAP]);
    }

    /** Exclui o estudante e remove sua referência de todos os cursos. */
    public function excluir(string $id): bool
    {
        $oid = \app_oid($id);
        if ($oid === null) {
            return false;
        }

        $estudante = $this->estudantes->findOne(['_id' => $oid], ['typeMap' => self::TYPEMAP]);
        if ($estudante === null) {
            return false;
        }

        $cursosIds = $estudante['cursos_ids'] ?? [];
        if ($cursosIds !== []) {
            $this->cursos->updateMany(
                ['_id' => ['$in' => $cursosIds]],
                ['$pull' => ['estudantes_ids' => $oid]]
            );
        }

        return $this->estudantes->deleteOne(['_id' => $oid])->getDeletedCount() === 1;
    }

    /** Vincula estudante e curso nos dois documentos, sem duplicatas. */
    public function vincular(string $estudanteId, string $cursoId): void
    {
        $estudanteOid = \app_oid($estudanteId);
        $cursoOid = \app_oid($cursoId);
        if ($estudanteOid === null || $cursoOid === null) {
            throw new InvalidArgumentException('IDs inválidos para matrícula.');
        }

        $this->estudantes->updateOne(
            ['_id' => $estudanteOid],
            ['$addToSet' => ['cursos_ids' => $cursoOid]]
        );
        $this->cursos->updateOne(
            ['_id' => $cursoOid],
            ['$addToSet' => ['estudantes_ids' => $estudanteOid]]
        );
    }

    /** Desvincula estudante e curso nos dois documentos. */
    public function desvincular(string $estudanteId, string $cursoId): void
    {
        $estudanteOid = \app_oid($estudanteId);
        $cursoOid = \app_oid($cursoId);
        if ($estudanteOid === null || $cursoOid === null) {
            throw new InvalidArgumentException('IDs inválidos para matrícula.');
        }

        $this->estudantes->updateOne(
            ['_id' => $estudanteOid],
            ['$pull' => ['cursos_ids' => $cursoOid]]
        );
        $this->cursos->updateOne(
            ['_id' => $cursoOid],
            ['$pull' => ['estudantes_ids' => $estudanteOid]]
        );
    }

    /**
     * Ajusta as matrículas do estudante ao conjunto enviado pelo formulário:
     * adiciona o que é novo ($addToSet) e remove o que foi desmarcado ($pull).
     *
     * @param string[] $cursosIds
     */
    public function sincronizarCursos(string $estudanteId, array $cursosIds): void
    {
        $estudante = $this->buscarSimples($estudanteId);
        if ($estudante === null) {
            throw new InvalidArgumentException('Estudante não encontrado.');
        }

        $atuais = array_map(static fn (ObjectId $oid): string => $oid->__toString(), $estudante['cursos_ids'] ?? []);
        $desejados = array_values(array_unique($cursosIds));

        foreach (array_diff($desejados, $atuais) as $novo) {
            $this->vincular($estudanteId, $novo);
        }
        foreach (array_diff($atuais, $desejados) as $removido) {
            $this->desvincular($estudanteId, $removido);
        }
    }
}
