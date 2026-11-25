<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Repositories\CursoRepository;
use App\Repositories\EstudanteRepository;

$estudanteRepo = new EstudanteRepository();
$cursoRepo = new CursoRepository();
$erro = null;

try {
    if (app_post('acao') === 'salvar') {
        $estudanteId = app_post('estudante_id');
        $cursosIds = app_ids_post('cursos_ids');
        $estudanteRepo->sincronizarCursos($estudanteId, $cursosIds);
        app_flash('Matrículas atualizadas nas duas coleções.');
        app_redirect('matriculas.php?estudante_id=' . urlencode($estudanteId));
    }
} catch (Throwable $e) {
    app_flash(app_erro_mensagem($e), 'error');
    app_redirect('matriculas.php');
}

$estudantes = [];
$cursos = [];
$selecionado = null;
$estudanteId = app_texto($_GET['estudante_id'] ?? '');

try {
    $estudantes = $estudanteRepo->listarSimples();
    $cursos = $cursoRepo->listarSimples();

    if ($estudanteId === '' && $estudantes !== []) {
        $estudanteId = app_id($estudantes[0]['_id']);
    }
    if ($estudanteId !== '') {
        $selecionado = $estudanteRepo->buscarSimples($estudanteId);
    }
} catch (Throwable $e) {
    $erro = app_erro_mensagem($e);
}

$atuais = [];
foreach ($selecionado['cursos_ids'] ?? [] as $oid) {
    $atuais[app_id($oid)] = true;
}

$titulo = 'Matrículas';
$aba = 'matriculas';

require __DIR__ . '/includes/header.php';
?>

<?php if ($erro !== null): ?>
    <div class="mb-6 rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-200" role="alert"><?= app_e($erro) ?></div>
<?php endif; ?>

<section class="mb-8 rounded-2xl border border-slate-800 bg-slate-900/50 p-6">
    <div class="mb-5">
        <h2 class="text-lg font-semibold text-white">Vincular estudante e cursos</h2>
        <p class="text-sm text-slate-500">Marque os cursos desejados. O sistema usa <code class="rounded bg-slate-800 px-1 py-0.5 text-xs text-emerald-300">$addToSet</code> para vincular e <code class="rounded bg-slate-800 px-1 py-0.5 text-xs text-emerald-300">$pull</code> para desvincular, atualizando <code class="text-slate-400">estudantes.cursos_ids</code> e <code class="text-slate-400">cursos.estudantes_ids</code>.</p>
    </div>

    <?php if ($estudantes === [] || $cursos === []): ?>
        <div class="rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-200">
            Cadastre ao menos um estudante e um curso antes de matricular.
            <a class="underline" href="estudantes.php">Estudantes</a> · <a class="underline" href="cursos.php">Cursos</a>
        </div>
    <?php else: ?>
        <form method="post" action="matriculas.php" class="space-y-6">
            <input type="hidden" name="acao" value="salvar">
            <div>
                <label class="block text-xs font-medium uppercase tracking-wide text-slate-400" for="estudante_id">Estudante</label>
                <select class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2.5 text-sm text-slate-100 outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                        id="estudante_id" name="estudante_id"
                        onchange="location.href='matriculas.php?estudante_id=' + encodeURIComponent(this.value)">
                    <?php foreach ($estudantes as $estudante): ?>
                        <option value="<?= app_e(app_id($estudante['_id'])) ?>" <?= app_id($estudante['_id']) === $estudanteId ? 'selected' : '' ?>>
                            <?= app_e(app_texto($estudante['nome'])) ?> — <?= app_e(app_texto($estudante['matricula'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <fieldset>
                <legend class="mb-3 text-xs font-semibold uppercase tracking-widest text-slate-500">Cursos</legend>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <?php foreach ($cursos as $curso): ?>
                        <?php $cursoId = app_id($curso['_id']); ?>
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-800 bg-slate-950/60 p-4 transition hover:border-emerald-500/40 <?= isset($atuais[$cursoId]) ? 'ring-1 ring-emerald-500/30' : '' ?>">
                            <input type="checkbox" name="cursos_ids[]" value="<?= app_e($cursoId) ?>" <?= isset($atuais[$cursoId]) ? 'checked' : '' ?> class="mt-0.5 h-4 w-4 rounded border-slate-600 bg-slate-900 text-emerald-500 focus:ring-emerald-500">
                            <span>
                                <span class="block text-sm font-medium text-slate-100"><?= app_e(app_texto($curso['nome'])) ?></span>
                                <span class="block text-xs text-slate-500"><?= app_e(app_texto($curso['codigo'])) ?></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <div class="flex items-center justify-between gap-4">
                <p class="text-xs text-slate-500">Desmarque um curso para desvincular o estudante dele.</p>
                <button type="submit" class="rounded-lg bg-emerald-500 px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-emerald-400">Salvar matrículas</button>
            </div>
        </form>
    <?php endif; ?>
</section>

<div class="grid gap-6 lg:grid-cols-2">
    <section class="rounded-2xl border border-slate-800 bg-slate-900/50">
        <div class="border-b border-slate-800 px-6 py-4">
            <h2 class="text-base font-semibold text-white">Estudantes por curso</h2>
            <p class="text-xs text-slate-500">Consulta feita com $lookup em cursos.estudantes_ids</p>
        </div>
        <div class="divide-y divide-slate-800">
            <?php if ($estudantes === []): ?>
                <p class="px-6 py-6 text-sm text-slate-500">Nenhum estudante cadastrado.</p>
            <?php else: ?>
                <?php foreach ($estudanteRepo->listarComCursos() as $estudante): ?>
                    <div class="flex items-center justify-between gap-4 px-6 py-3 text-sm">
                        <span class="text-slate-200"><?= app_e(app_texto($estudante['nome'])) ?></span>
                        <span class="shrink-0 text-xs text-emerald-300"><?= count($estudante['cursos'] ?? []) ?> curso(s)</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-800 bg-slate-900/50">
        <div class="border-b border-slate-800 px-6 py-4">
            <h2 class="text-base font-semibold text-white">Cursos por estudante</h2>
            <p class="text-xs text-slate-500">Consulta feita com $lookup em estudantes.cursos_ids</p>
        </div>
        <div class="divide-y divide-slate-800">
            <?php if ($cursos === []): ?>
                <p class="px-6 py-6 text-sm text-slate-500">Nenhum curso cadastrado.</p>
            <?php else: ?>
                <?php foreach ($cursoRepo->listarComEstudantes() as $curso): ?>
                    <div class="flex items-center justify-between gap-4 px-6 py-3 text-sm">
                        <span class="text-slate-200"><?= app_e(app_texto($curso['nome'])) ?></span>
                        <span class="shrink-0 text-xs text-emerald-300"><?= count($curso['estudantes'] ?? []) ?> aluno(s)</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
