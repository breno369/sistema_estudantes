<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Repositories\EstudanteRepository;

$repo = new EstudanteRepository();
$erro = null;
$formErro = null;
$old = [];

try {
    $acao = app_post('acao');

    if ($acao === 'cadastrar') {
        $dados = [
            'nome' => app_post('nome'),
            'cpf' => app_post('cpf'),
            'email' => app_post('email'),
            'telefone' => app_post('telefone'),
            'data_nascimento' => app_post('data_nascimento'),
            'matricula' => app_post('matricula'),
            'endereco' => [
                'cep' => app_post('cep'),
                'logradouro' => app_post('logradouro'),
                'numero' => app_post('numero'),
                'bairro' => app_post('bairro'),
                'cidade' => app_post('cidade'),
                'estado' => app_post('estado'),
            ],
        ];

        if ($dados['nome'] === '' || $dados['cpf'] === '' || $dados['matricula'] === '') {
            throw new InvalidArgumentException('Informe ao menos nome, CPF e matrícula.');
        }
        if ($dados['email'] !== '' && filter_var($dados['email'], FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Informe um e-mail válido.');
        }
        if ($repo->existeCampo('cpf', $dados['cpf'])) {
            throw new InvalidArgumentException('Já existe um estudante com este CPF.');
        }
        if ($repo->existeCampo('matricula', $dados['matricula'])) {
            throw new InvalidArgumentException('Já existe um estudante com esta matrícula.');
        }

        $repo->criar($dados);
        app_flash('Estudante cadastrado com sucesso.');
        app_redirect('estudantes.php');
    }

    if ($acao === 'excluir') {
        if (!$repo->excluir(app_post('id'))) {
            throw new InvalidArgumentException('Estudante não encontrado.');
        }
        app_flash('Estudante excluído e referências dos cursos removidas.');
        app_redirect('estudantes.php');
    }
} catch (InvalidArgumentException $e) {
    $formErro = $e->getMessage();
    $old = $_POST;
} catch (Throwable $e) {
    app_flash(app_erro_mensagem($e), 'error');
    app_redirect('estudantes.php');
}

$id = app_texto($_GET['id'] ?? '');
$detalhe = null;
$estudantes = [];

try {
    $estudantes = $repo->listarComCursos();
    if ($id !== '') {
        $detalhe = $repo->buscarPorId($id);
        if ($detalhe === null) {
            app_flash('Estudante não encontrado.', 'error');
            app_redirect('estudantes.php');
        }
    }
} catch (Throwable $e) {
    $erro = app_erro_mensagem($e);
}

$titulo = $detalhe === null ? 'Estudantes' : 'Detalhes do estudante';
$aba = 'estudantes';
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
                <p class="text-xs font-medium uppercase tracking-widest text-emerald-400">Matrícula <?= app_e(app_texto($detalhe['matricula'])) ?></p>
                <h2 class="mt-1 text-2xl font-semibold text-white"><?= app_e(app_texto($detalhe['nome'])) ?></h2>
            </div>
            <a href="estudantes.php" class="rounded-lg border border-slate-700 px-3 py-2 text-sm text-slate-300 transition hover:bg-slate-800">&larr; Voltar à listagem</a>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-5">
                <h3 class="mb-4 text-sm font-semibold text-emerald-300">Dados pessoais</h3>
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-slate-500">CPF</dt><dd class="text-slate-200"><?= app_e(app_texto($detalhe['cpf'])) ?></dd></div>
                    <div><dt class="text-slate-500">E-mail</dt><dd class="text-slate-200"><?= app_e(app_texto($detalhe['email'])) ?></dd></div>
                    <div><dt class="text-slate-500">Telefone</dt><dd class="text-slate-200"><?= app_e(app_texto($detalhe['telefone'])) ?></dd></div>
                    <div><dt class="text-slate-500">Nascimento</dt><dd class="text-slate-200"><?= app_e(app_data_br(app_texto($detalhe['data_nascimento']))) ?></dd></div>
                </dl>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-5">
                <h3 class="mb-4 text-sm font-semibold text-emerald-300">Endereço</h3>
                <dl class="space-y-3 text-sm">
                    <?php foreach (['cep' => 'CEP', 'logradouro' => 'Logradouro', 'numero' => 'Número', 'bairro' => 'Bairro', 'cidade' => 'Cidade', 'estado' => 'Estado'] as $chave => $rotulo): ?>
                        <div><dt class="text-slate-500"><?= app_e($rotulo) ?></dt><dd class="text-slate-200"><?= app_e(app_texto($detalhe['endereco'][$chave] ?? '')) ?></dd></div>
                    <?php endforeach; ?>
                </dl>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-5">
                <h3 class="mb-4 text-sm font-semibold text-emerald-300">Cursos matriculados</h3>
                <?php if (($detalhe['cursos'] ?? []) === []): ?>
                    <p class="text-sm text-slate-500">Nenhum curso vinculado. Use a tela de matrículas.</p>
                <?php else: ?>
                    <ul class="space-y-2 text-sm">
                        <?php foreach ($detalhe['cursos'] as $curso): ?>
                            <li class="rounded-lg bg-slate-900 px-3 py-2">
                                <span class="text-slate-200"><?= app_e(app_texto($curso['nome'])) ?></span>
                                <span class="ml-2 text-xs text-slate-500"><?= app_e(app_texto($curso['codigo'])) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<section class="mb-10 rounded-2xl border border-slate-800 bg-slate-900/50 p-6">
    <div class="mb-5">
        <h2 class="text-lg font-semibold text-white">Novo estudante</h2>
        <p class="text-sm text-slate-500">Campos obrigatórios: nome, CPF e matrícula.</p>
    </div>

    <?php if ($formErro !== null): ?>
        <div class="mb-5 rounded-lg border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-200" role="alert"><?= app_e($formErro) ?></div>
    <?php endif; ?>

    <form method="post" action="estudantes.php" class="space-y-6">
        <input type="hidden" name="acao" value="cadastrar">
        <fieldset>
            <legend class="mb-3 text-xs font-semibold uppercase tracking-widest text-slate-500">Dados principais</legend>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div><label class="<?= $classeLabel ?>" for="nome">Nome</label><input class="<?= $classeCampo ?>" id="nome" name="nome" required value="<?= app_e(app_texto($old['nome'] ?? '')) ?>" placeholder="Nome completo"></div>
                <div><label class="<?= $classeLabel ?>" for="cpf">CPF</label><input class="<?= $classeCampo ?>" id="cpf" name="cpf" required value="<?= app_e(app_texto($old['cpf'] ?? '')) ?>" placeholder="000.000.000-00"></div>
                <div><label class="<?= $classeLabel ?>" for="email">E-mail</label><input class="<?= $classeCampo ?>" id="email" name="email" type="email" value="<?= app_e(app_texto($old['email'] ?? '')) ?>" placeholder="nome@exemplo.com"></div>
                <div><label class="<?= $classeLabel ?>" for="telefone">Telefone</label><input class="<?= $classeCampo ?>" id="telefone" name="telefone" value="<?= app_e(app_texto($old['telefone'] ?? '')) ?>" placeholder="(00) 00000-0000"></div>
                <div><label class="<?= $classeLabel ?>" for="data_nascimento">Data de nascimento</label><input class="<?= $classeCampo ?>" id="data_nascimento" name="data_nascimento" type="date" value="<?= app_e(app_texto($old['data_nascimento'] ?? '')) ?>"></div>
                <div><label class="<?= $classeLabel ?>" for="matricula">Matrícula</label><input class="<?= $classeCampo ?>" id="matricula" name="matricula" required value="<?= app_e(app_texto($old['matricula'] ?? '')) ?>" placeholder="20260001"></div>
            </div>
        </fieldset>

        <fieldset>
            <legend class="mb-3 text-xs font-semibold uppercase tracking-widest text-slate-500">Endereço (subdocumento)</legend>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div><label class="<?= $classeLabel ?>" for="cep">CEP</label><input class="<?= $classeCampo ?>" id="cep" name="cep" value="<?= app_e(app_texto($old['cep'] ?? '')) ?>" placeholder="00000-000"></div>
                <div><label class="<?= $classeLabel ?>" for="logradouro">Logradouro</label><input class="<?= $classeCampo ?>" id="logradouro" name="logradouro" value="<?= app_e(app_texto($old['logradouro'] ?? '')) ?>" placeholder="Rua, avenida..."></div>
                <div><label class="<?= $classeLabel ?>" for="numero">Número</label><input class="<?= $classeCampo ?>" id="numero" name="numero" value="<?= app_e(app_texto($old['numero'] ?? '')) ?>" placeholder="123"></div>
                <div><label class="<?= $classeLabel ?>" for="bairro">Bairro</label><input class="<?= $classeCampo ?>" id="bairro" name="bairro" value="<?= app_e(app_texto($old['bairro'] ?? '')) ?>"></div>
                <div><label class="<?= $classeLabel ?>" for="cidade">Cidade</label><input class="<?= $classeCampo ?>" id="cidade" name="cidade" value="<?= app_e(app_texto($old['cidade'] ?? '')) ?>"></div>
                <div><label class="<?= $classeLabel ?>" for="estado">Estado</label><input class="<?= $classeCampo ?>" id="estado" name="estado" maxlength="2" value="<?= app_e(app_texto($old['estado'] ?? '')) ?>" placeholder="UF"></div>
            </div>
        </fieldset>

        <div class="flex justify-end">
            <button type="submit" class="rounded-lg bg-emerald-500 px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-emerald-400">Salvar estudante</button>
        </div>
    </form>
</section>

<section class="rounded-2xl border border-slate-800 bg-slate-900/50">
    <div class="flex items-center justify-between border-b border-slate-800 px-6 py-4">
        <h2 class="text-lg font-semibold text-white">Estudantes cadastrados</h2>
        <span class="rounded-full bg-slate-800 px-2.5 py-1 text-xs text-slate-400"><?= count($estudantes) ?></span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="bg-slate-900/80 text-xs uppercase tracking-wide text-slate-500">
                <tr><th class="px-6 py-3 font-medium">Nome</th><th class="px-6 py-3 font-medium">Matrícula</th><th class="px-6 py-3 font-medium">CPF</th><th class="px-6 py-3 font-medium">Cursos</th><th class="px-6 py-3 text-right font-medium">Ações</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                <?php if ($estudantes === []): ?>
                    <tr><td colspan="5" class="px-6 py-10 text-center text-slate-500">Nenhum estudante cadastrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($estudantes as $estudante): ?>
                        <tr class="transition hover:bg-slate-800/40">
                            <td class="px-6 py-4 font-medium text-slate-100"><?= app_e(app_texto($estudante['nome'])) ?></td>
                            <td class="px-6 py-4 text-slate-400"><?= app_e(app_texto($estudante['matricula'])) ?></td>
                            <td class="px-6 py-4 text-slate-400"><?= app_e(app_texto($estudante['cpf'])) ?></td>
                            <td class="px-6 py-4">
                                <?php if (($estudante['cursos'] ?? []) === []): ?>
                                    <span class="text-xs text-slate-600">Sem cursos</span>
                                <?php else: ?>
                                    <div class="flex flex-wrap gap-1.5">
                                        <?php foreach ($estudante['cursos'] as $curso): ?>
                                            <span class="rounded-md bg-emerald-500/10 px-2 py-1 text-xs text-emerald-300 ring-1 ring-emerald-500/20"><?= app_e(app_texto($curso['nome'])) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="estudantes.php?id=<?= app_e(app_id($estudante['_id'])) ?>" class="rounded-lg border border-slate-700 px-3 py-1.5 text-xs text-slate-300 transition hover:bg-slate-800">Ver</a>
                                    <form method="post" action="estudantes.php" onsubmit="return confirm('Excluir este estudante e remover suas matrículas?');">
                                        <input type="hidden" name="acao" value="excluir">
                                        <input type="hidden" name="id" value="<?= app_e(app_id($estudante['_id'])) ?>">
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
