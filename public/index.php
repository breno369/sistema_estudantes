<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Repositories\CursoRepository;
use App\Repositories\EstudanteRepository;

$estudanteRepo = new EstudanteRepository();
$cursoRepo = new CursoRepository();

$erro = null;
$totalEstudantes = 0;
$totalCursos = 0;
$estudantes = [];
$cursos = [];

try {
    $totalEstudantes = $estudanteRepo->contar();
    $totalCursos = $cursoRepo->contar();
    $estudantes = array_slice($estudanteRepo->listarComCursos(), 0, 5);
    $cursos = array_slice($cursoRepo->listarComEstudantes(), 0, 5);
} catch (Throwable $e) {
    $erro = app_erro_mensagem($e);
}

$titulo = 'Dashboard';
$aba = 'inicio';

require __DIR__ . '/includes/header.php';
?>

<?php if ($erro !== null): ?>
    <div class="mb-6 rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-200" role="alert"><?= app_e($erro) ?></div>
<?php endif; ?>

<section class="mb-8 rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-900 to-slate-950 p-6 sm:p-8">
    <p class="text-xs font-semibold uppercase tracking-widest text-emerald-400">Visão geral</p>
    <h2 class="mt-2 max-w-2xl text-2xl font-semibold tracking-tight text-white sm:text-3xl">Cadastro acadêmico com relacionamento N:N normalizado</h2>
    <p class="mt-3 max-w-2xl text-sm leading-relaxed text-slate-400">Estudantes e cursos são documentos independentes. As matrículas vivem em arrays de ObjectId nas duas coleções, sem duplicar documentos: integridade mantida com <code class="text-emerald-300">$addToSet</code>, <code class="text-emerald-300">$pull</code> e <code class="text-emerald-300">$lookup</code>.</p>
    <div class="mt-6 flex flex-wrap gap-3">
        <a href="estudantes.php" class="rounded-lg bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-emerald-400">Gerenciar estudantes</a>
        <a href="cursos.php" class="rounded-lg border border-slate-700 px-4 py-2.5 text-sm font-medium text-slate-200 transition hover:bg-slate-800">Gerenciar cursos</a>
        <a href="matriculas.php" class="rounded-lg border border-slate-700 px-4 py-2.5 text-sm font-medium text-slate-200 transition hover:bg-slate-800">Fazer matrículas</a>
    </div>
</section>

<section class="mb-8 grid gap-4 sm:grid-cols-3">
    <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-5">
        <p class="text-xs uppercase tracking-wide text-slate-500">Estudantes</p>
        <p class="mt-2 text-3xl font-semibold text-white"><?= $totalEstudantes ?></p>
        <p class="mt-1 text-xs text-slate-500">Documentos em <code>estudantes</code></p>
    </div>
    <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-5">
        <p class="text-xs uppercase tracking-wide text-slate-500">Cursos</p>
        <p class="mt-2 text-3xl font-semibold text-white"><?= $totalCursos ?></p>
        <p class="mt-1 text-xs text-slate-500">Documentos em <code>cursos</code></p>
    </div>
    <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/5 p-5">
        <p class="text-xs uppercase tracking-wide text-emerald-400/80">Modelo</p>
        <p class="mt-2 text-xl font-semibold text-emerald-200">N : N</p>
        <p class="mt-1 text-xs text-emerald-300/60">Referências bidirecionais</p>
    </div>
</section>

<div class="grid gap-6 lg:grid-cols-2">
    <section class="rounded-2xl border border-slate-800 bg-slate-900/50">
        <div class="flex items-center justify-between border-b border-slate-800 px-6 py-4">
            <h2 class="text-base font-semibold text-white">Estudantes recentes</h2>
            <a class="text-xs text-emerald-400 hover:underline" href="estudantes.php">Ver todos &rarr;</a>
        </div>
        <div class="divide-y divide-slate-800">
            <?php if ($estudantes === []): ?>
                <p class="px-6 py-6 text-sm text-slate-500">Nenhum estudante cadastrado.</p>
            <?php else: ?>
                <?php foreach ($estudantes as $estudante): ?>
                    <div class="flex items-center justify-between gap-4 px-6 py-3 text-sm">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-slate-100"><?= app_e(app_texto($estudante['nome'])) ?></p>
                            <p class="truncate text-xs text-slate-500"><?= app_e(app_texto($estudante['matricula'])) ?></p>
                        </div>
                        <span class="shrink-0 rounded-md bg-slate-800 px-2 py-1 text-xs text-slate-300"><?= count($estudante['cursos'] ?? []) ?> curso(s)</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-800 bg-slate-900/50">
        <div class="flex items-center justify-between border-b border-slate-800 px-6 py-4">
            <h2 class="text-base font-semibold text-white">Cursos recentes</h2>
            <a class="text-xs text-emerald-400 hover:underline" href="cursos.php">Ver todos &rarr;</a>
        </div>
        <div class="divide-y divide-slate-800">
            <?php if ($cursos === []): ?>
                <p class="px-6 py-6 text-sm text-slate-500">Nenhum curso cadastrado.</p>
            <?php else: ?>
                <?php foreach ($cursos as $curso): ?>
                    <div class="flex items-center justify-between gap-4 px-6 py-3 text-sm">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-slate-100"><?= app_e(app_texto($curso['nome'])) ?></p>
                            <p class="truncate text-xs text-slate-500"><?= app_e(app_texto($curso['codigo'])) ?></p>
                        </div>
                        <span class="shrink-0 rounded-md bg-emerald-500/10 px-2 py-1 text-xs text-emerald-300"><?= count($curso['estudantes'] ?? []) ?> aluno(s)</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
