<?php

/** @var string $titulo */
/** @var string $aba */
$titulo = $titulo ?? 'Sistema Acadêmico';
$aba = $aba ?? '';
$flash = app_flash_take();

$nav = [
    ['chave' => 'inicio', 'rotulo' => 'Dashboard', 'href' => 'index.php', 'icone' => 'M3 12l9-9 9 9M5 10v10h14V10'],
    ['chave' => 'estudantes', 'rotulo' => 'Estudantes', 'href' => 'estudantes.php', 'icone' => 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM22 21v-2a4 4 0 00-3-3.87'],
    ['chave' => 'cursos', 'rotulo' => 'Cursos', 'href' => 'cursos.php', 'icone' => 'M4 19.5A2.5 2.5 0 016.5 17H20M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z'],
    ['chave' => 'matriculas', 'rotulo' => 'Matrículas', 'href' => 'matriculas.php', 'icone' => 'M9 12l2 2 4-4M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= app_e($titulo) ?> · Sistema Acadêmico</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
        [type='date']::-webkit-calendar-picker-indicator { filter: invert(1); opacity: .6; }
    </style>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
<div class="min-h-screen lg:flex">
    <aside class="border-b border-slate-800 bg-slate-900/70 lg:w-64 lg:shrink-0 lg:border-b-0 lg:border-r">
        <div class="flex items-center gap-3 px-6 py-5">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500/15 text-emerald-400 ring-1 ring-emerald-500/30">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zM12 14l9 5-9 5-9-5 9-5z"/></svg>
            </div>
            <div>
                <p class="text-sm font-semibold tracking-tight text-white">Acadêmico</p>
                <p class="text-xs text-slate-500">Gestão de matrículas</p>
            </div>
        </div>
        <nav class="flex gap-1 overflow-x-auto px-3 pb-4 lg:flex-col lg:overflow-visible lg:px-3 lg:pb-0">
            <?php foreach ($nav as $item): ?>
                <a href="<?= app_e($item['href']) ?>"
                   class="flex items-center gap-3 whitespace-nowrap rounded-lg px-3 py-2.5 text-sm font-medium transition <?= $aba === $item['chave'] ? 'bg-emerald-500/10 text-emerald-300 ring-1 ring-emerald-500/20' : 'text-slate-400 hover:bg-slate-800/70 hover:text-slate-100' ?>">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="<?= app_e($item['icone']) ?>"/></svg>
                    <?= app_e($item['rotulo']) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="flex items-center justify-between border-b border-slate-800 bg-slate-900/40 px-4 py-4 sm:px-6 lg:px-8">
            <div>
                <h1 class="text-lg font-semibold tracking-tight text-white sm:text-xl"><?= app_e($titulo) ?></h1>
                <p class="text-xs text-slate-500">PHP 8.1+ · MongoDB · referências normalizadas N:N</p>
            </div>
            <span class="hidden items-center gap-2 rounded-full border border-emerald-500/20 bg-emerald-500/5 px-3 py-1.5 text-xs font-medium text-emerald-300 sm:flex">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> MongoDB
            </span>
        </header>

        <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
            <?php if ($flash !== null): ?>
                <div class="mb-6 rounded-xl border px-4 py-3 text-sm <?= $flash['tipo'] === 'error' ? 'border-rose-500/30 bg-rose-500/10 text-rose-200' : 'border-emerald-500/30 bg-emerald-500/10 text-emerald-200' ?>" role="alert">
                    <?= app_e($flash['mensagem']) ?>
                </div>
            <?php endif; ?>
