<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Repositories\CursoRepository;

$repo = new CursoRepository();
$erro = null;
$formErro = null;
$old = [];

try {
    $acao = app_post('acao');

    if ($acao === 'cadastrar') {
        $dados = [
            'nome' => app_post('nome'),
            'codigo' => app_post('codigo'),
            'carga_horaria' => app_post('carga_horaria'),
            'turno' => app_post('turno'),
            'coordenador' => app_post('coordenador'),
            'descricao' => app_post('descricao'),
        ];

        if ($dados['nome'] === '' || $dados['codigo'] === '') {
            throw new InvalidArgumentException('Informe ao menos nome e código do curso.');
        }
        if ($repo->existeCampo('codigo', $dados['codigo'])) {
            throw new InvalidArgumentException('Já existe um curso com este código.');
        }

        $repo->criar($dados);
        app_flash('Curso cadastrado com sucesso.');
        app_redirect('cursos.php');
    }

    if ($acao === 'excluir') {
        if (!$repo->excluir(app_post('id'))) {
            throw new InvalidArgumentException('Curso não encontrado.');
        }
        app_flash('Curso excluído e referências dos estudantes removidas.');
        app_redirect('cursos.php');
    }
} catch (InvalidArgumentException $e) {
    $formErro = $e->getMessage();
    $old = $_POST;
} catch (Throwable $e) {
    app_flash(app_erro_mensagem($e), 'error');
    app_redirect('cursos.php');
}

$id = app_texto($_GET['id'] ?? '');
$detalhe = null;
$cursos = [];

try {
    $cursos = $repo->listarComEstudantes();
    if ($id !== '') {
        $detalhe = $repo->buscarPorId($id);
        if ($detalhe === null) {
            app_flash('Curso não encontrado.', 'error');
            app_redirect('cursos.php');
        }
    }
} catch (Throwable $e) {
    $erro = app_erro_mensagem($e);
}

$titulo = $detalhe === null ? 'Cursos' : 'Detalhes do curso';
$aba = 'cursos';
$classeCampo = 'mt-1 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-100 placeholder-slate-600 outline-none transition focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500';
$classeLabel = 'block text-xs font-medium uppercase tracking-wide text-slate-400';

require __DIR__ . '/includes/header.php';
?>

<?php if ($erro !== null): ?>
    <div class="mb-6 rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-200" role="alert"><?= app_e($erro) ?></div>
<?php endif; ?>

<?php if ($detalhe !== null): ?>
    <section class="mb-8 rounded-2xl border border-slate-800 bg-slate-900/50 p-6">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-medium uppercase tracking-widest text-emerald-400">Código <?= app_e(app_texto($detalhe['codigo'])) ?></p>
                <h2 class="mt-1 text-2xl font-semibold text-white"><?= app_e(app_texto($detalhe['nome'])) ?></h2>
            </div>
            <a href="cursos.php" class="rounded-lg border border-slate-700 px-3 py-2 text-sm text-slate-300 transition hover:bg-slate-800">&larr; Voltar à listagem</a>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-5">
                <h3 class="mb-4 text-sm font-semibold text-emerald-300">Dados do curso</h3>
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-slate-500">Carga horária</dt><dd class="text-slate-200"><?= app_e(app_texto($detalhe['carga_horaria'])) ?> h</dd></div>
                    <div><dt class="text-slate-500">Turno</dt><dd class="text-slate-200"><?= app_e(app_texto($detalhe['turno'])) ?></dd></div>
                    <div><dt class="text-slate-500">Coordenador</dt><dd class="text-slate-200"><?= app_e(app_texto($detalhe['coordenador'])) ?></dd></div>
                </dl>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-5 lg:col-span-2">
                <h3 class="mb-4 text-sm font-semibold text-emerald-300">Descrição</h3>
                <p class="text-sm leading-relaxed text-slate-300"><?= nl2br(app_e(app_texto($detalhe['descricao']))) ?></p>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-5 lg:col-span-3">
                <h3 class="mb-4 text-sm font-semibold text-emerald-300">Estudantes matriculados (<?= count($detalhe['estudantes'] ?? []) ?>)</h3>
                <?php if (($detalhe['estudantes'] ?? []) === []): ?>
                    <p class="text-sm text-slate-500">Nenhum estudante vinculado. Use a tela de matrículas.</p>
                <?php else: ?>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <?php foreach ($detalhe['estudantes'] as $estudante): ?>
                            <div class="rounded-lg bg-slate-900 px-3 py-2 text-sm">
                                <span class="text-slate-200"><?= app_e(app_texto($estudante['nome'])) ?></span>
                                <span class="ml-2 text-xs text-slate-500"><?= app_e(app_texto($estudante['matricula'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<section class="mb-10 rounded-2xl border border-slate-800 bg-slate-900/50 p-6">
    <div class="mb-5">
        <h2 class="text-lg font-semibold text-white">Novo curso</h2>
        <p class="text-sm text-slate-500">Campos obrigatórios: nome e código.</p>
    </div>

    <?php if ($formErro !== null): ?>
        <div class="mb-5 rounded-lg border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-200" role="alert"><?= app_e($formErro) ?></div>
    <?php endif; ?>

    <form method="post" action="cursos.php" class="space-y-6">
        <input type="hidden" name="acao" value="cadastrar">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div><label class="<?= $classeLabel ?>" for="nome">Nome</label><input class="<?= $classeCampo ?>" id="nome" name="nome" required value="<?= app_e(app_texto($old['nome'] ?? '')) ?>" placeholder="Ex.: Ciência da Computação"></div>
            <div><label class="<?= $classeLabel ?>" for="codigo">Código</label><input class="<?= $classeCampo ?>" id="codigo" name="codigo" required value="<?= app_e(app_texto($old['codigo'] ?? '')) ?>" placeholder="CC-001"></div>
            <div><label class="<?= $classeLabel ?>" for="carga_horaria">Carga horária (h)</label><input class="<?= $classeCampo ?>" id="carga_horaria" name="carga_horaria" type="number" min="1" value="<?= app_e(app_texto($old['carga_horaria'] ?? '')) ?>" placeholder="360"></div>
            <div>
                <label class="<?= $classeLabel ?>" for="turno">Turno</label>
                <select class="<?= $classeCampo ?>" id="turno" name="turno">
                    <?php foreach (['Manhã', 'Tarde', 'Noite', 'Integral'] as $turno): ?>
                        <option value="<?= app_e($turno) ?>" <?= app_texto($old['turno'] ?? '') === $turno ? 'selected' : '' ?>><?= app_e($turno) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label class="<?= $classeLabel ?>" for="coordenador">Coordenador</label><input class="<?= $classeCampo ?>" id="coordenador" name="coordenador" value="<?= app_e(app_texto($old['coordenador'] ?? '')) ?>" placeholder="Nome do coordenador"></div>
            <div class="sm:col-span-2 lg:col-span-3"><label class="<?= $classeLabel ?>" for="descricao">Descrição</label><textarea class="<?= $classeCampo ?>" id="descricao" name="descricao" rows="3" placeholder="Ementa ou observações"><?= app_e(app_texto($old['descricao'] ?? '')) ?></textarea></div>
        </div>
        <div class="flex justify-end">
            <button type="submit" class="rounded-lg bg-emerald-500 px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-emerald-400">Salvar curso</button>
        </div>
    </form>
</section>

<section class="rounded-2xl border border-slate-800 bg-slate-900/50">
    <div class="flex items-center justify-between border-b border-slate-800 px-6 py-4">
        <h2 class="text-lg font-semibold text-white">Cursos cadastrados</h2>
        <span class="rounded-full bg-slate-800 px-2.5 py-1 text-xs text-slate-400"><?= count($cursos) ?></span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[760px] text-left text-sm">
            <thead class="bg-slate-900/80 text-xs uppercase tracking-wide text-slate-500">
                <tr><th class="px-6 py-3 font-medium">Curso</th><th class="px-6 py-3 font-medium">Código</th><th class="px-6 py-3 font-medium">Turno</th><th class="px-6 py-3 font-medium">Estudantes</th><th class="px-6 py-3 text-right font-medium">Ações</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                <?php if ($cursos === []): ?>
                    <tr><td colspan="5" class="px-6 py-10 text-center text-slate-500">Nenhum curso cadastrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($cursos as $curso): ?>
                        <tr class="transition hover:bg-slate-800/40">
                            <td class="px-6 py-4 font-medium text-slate-100"><?= app_e(app_texto($curso['nome'])) ?></td>
                            <td class="px-6 py-4 text-slate-400"><?= app_e(app_texto($curso['codigo'])) ?></td>
                            <td class="px-6 py-4 text-slate-400"><?= app_e(app_texto($curso['turno'])) ?></td>
                            <td class="px-6 py-4">
                                <span class="rounded-md bg-emerald-500/10 px-2 py-1 text-xs text-emerald-300 ring-1 ring-emerald-500/20"><?= count($curso['estudantes'] ?? []) ?> matriculado(s)</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="cursos.php?id=<?= app_e(app_id($curso['_id'])) ?>" class="rounded-lg border border-slate-700 px-3 py-1.5 text-xs text-slate-300 transition hover:bg-slate-800">Ver</a>
                                    <form method="post" action="cursos.php" onsubmit="return confirm('Excluir este curso e remover suas matrículas?');">
                                        <input type="hidden" name="acao" value="excluir">
                                        <input type="hidden" name="id" value="<?= app_e(app_id($curso['_id'])) ?>">
                                        <button type="submit" class="rounded-lg border border-rose-500/30 px-3 py-1.5 text-xs text-rose-300 transition hover:bg-rose-500/10">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
