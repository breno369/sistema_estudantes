# Plano de commits — Sistema Acadêmico

Todos os arquivos já estão no working directory. **Nenhum `git commit` ou `git push` foi executado.** Este plano divide a entrega em 8 commits lógicos e equilibrados, alternando responsabilidades entre Dev 1 (infraestrutura, conexão MongoDB, módulo de estudantes) e Dev 2 (layout base, módulo de cursos, matrículas N:N e documentação).

> Intervalos de linhas conferidos com os arquivos finais (`wc -l`). Os números de linha são inclusivos e refletem a versão final de cada arquivo — como cada arquivo é introduzido em um único commit, as faixas indicam os blocos técnicos dentro dele.

---

## Commit 1 — Dev 1 (Infraestrutura)

**Mensagem:** `chore(infra): configura composer, ambiente e container MongoDB local`

**Comandos exatos:**

```bash
git add composer.json .env.example docker-compose.yml
git commit -m "chore(infra): configura composer, ambiente e container MongoDB local"
```

**Detalhamento técnico:**

| Arquivo | Linhas | O que foi feito |
| --- | --- | --- |
| `composer.json` | 1 a 5 | Metadados do projeto: nome, descrição, tipo `project` e licença MIT. |
| `composer.json` | 6 a 10 | Requisitos: PHP >= 8.1, extensão `mongodb` e `mongodb/mongodb` (`^1.19 \|\| ^2.0`, cobrindo extensão 1.21+ e 2.x). |
| `composer.json` | 11 a 23 | Autoload PSR-4 `App\ => src/`, carga automática de `src/helpers.php` e `config/database.php`, e `sort-packages`. |
| `.env.example` | 1 a 5 | Modelo de configuração com `MONGO_URI` (local/Atlas) e `MONGO_DB`. |
| `docker-compose.yml` | 1 a 14 | Serviço `mongo` com imagem `mongo:8.0`, porta `27017`, volume persistente `mongo_data` e `restart: unless-stopped`. |

---

## Commit 2 — Dev 2 (Layout base)

**Mensagem:** `feat(layout): cria helpers de seguranca e layout dark com Tailwind CDN`

**Comandos exatos:**

```bash
git add src/helpers.php public/includes/header.php public/includes/footer.php
git commit -m "feat(layout): cria helpers de seguranca e layout dark com Tailwind CDN"
```

**Detalhamento técnico:**

| Arquivo | Linhas | O que foi feito |
| --- | --- | --- |
| `src/helpers.php` | 1 a 10 | `declare(strict_types=1)`, import de `ObjectId` e inicialização de sessão (necessária para flash messages). |
| `src/helpers.php` | 11 a 28 | `app_texto()`, `app_e()` (escape HTML com `ENT_QUOTES`) e `app_post()` para leitura segura de formulários. |
| `src/helpers.php` | 29 a 51 | `app_id()`, `app_oid()` (valida ObjectId hexadecimal de 24 chars) e `app_redirect()` (retorno `never`). |
| `src/helpers.php` | 52 a 85 | `app_flash()`, `app_flash_take()`, `app_data_br()` e `app_erro_mensagem()`: feedback e formatação. |
| `src/helpers.php` | 86 a 103 | `app_ids_post()`: normaliza, valida e deduplica o array `cursos_ids[]` enviado por checkboxes. |
| `public/includes/header.php` | 1 a 15 | Parâmetros `$titulo`/`$aba`, consumo do flash e definição dos 4 itens de navegação com ícones SVG. |
| `public/includes/header.php` | 16 a 31 | `<head>` com Tailwind CDN, fonte Inter, ajustes de tema escuro e abertura do layout responsivo. |
| `public/includes/header.php` | 32 a 52 | Sidebar: logo, menu responsivo e destaque da aba ativa conforme `$aba`. |
| `public/includes/header.php` | 53 a 69 | Topbar com título, badge MongoDB e `<main>` com alerta de flash (sucesso/erro). |
| `public/includes/footer.php` | 1 a 8 | Rodapé, fechamento de `<main>`, do flex container, do `<body>` e do `<html>`. |

---

## Commit 3 — Dev 1 (Conexão MongoDB)

**Mensagem:** `feat(db): implementa conexao singleton com leitura de .env`

**Comandos exatos:**

```bash
git add config/database.php
git commit -m "feat(db): implementa conexao singleton com leitura de .env"
```

**Detalhamento técnico:**

| Arquivo | Linhas | O que foi feito |
| --- | --- | --- |
| `config/database.php` | 1 a 10 | Namespace `App\Config`, imports de `MongoDB\Client` e `MongoDB\Database as MongoDatabase`. |
| `config/database.php` | 11 a 15 | Classe final `Database` com `$client` estático (singleton). |
| `config/database.php` | 16 a 33 | `uri()` e `nome()`: priorizam `MONGO_URI`/`MONGO_DB`, aceitam `MONGODB_URI`/`MONGODB_DB` de stacks Docker e usam fallback `mongodb://localhost:27017` / `sistema_estudantes`. |
| `config/database.php` | 34 a 43 | `db()`: cria o `Client` uma única vez com `serverSelectionTimeoutMS => 5000` e retorna o banco selecionado. |
| `config/database.php` | 44 a 77 | `carregarEnv()`: parser simples de `.env` (ignora comentários, remove aspas, não sobrescreve variáveis existentes). |

---

## Commit 4 — Dev 2 (Módulo de Cursos)

**Mensagem:** `feat(cursos): implementa repositorio e formulario de cadastro`

**Comandos exatos:**

```bash
git add src/Repositories/CursoRepository.php public/cursos.php
git commit -m "feat(cursos): implementa repositorio e formulario de cadastro"
```

**Detalhamento técnico:**

| Arquivo | Linhas | O que foi feito |
| --- | --- | --- |
| `src/Repositories/CursoRepository.php` | 1 a 12 | Namespace, imports, `typeMap` para arrays PHP e propriedades `$cursos`/`$estudantes`. |
| `src/Repositories/CursoRepository.php` | 13 a 27 | Construtor: seleção das coleções via `Database::db()`. |
| `src/Repositories/CursoRepository.php` | 28 a 65 | `contar()`, `criar()` (6 campos + `estudantes_ids` + `criado_em`) e `existeCampo('codigo')` para duplicidade. |
| `src/Repositories/CursoRepository.php` | 66 a 89 | `listarComEstudantes()` e `listarSimples()`: `$lookup` e projeção leve para seletores. |
| `src/Repositories/CursoRepository.php` | 90 a 112 | `buscarPorId()` com `$match` + `$lookup` + `$limit` para a tela de detalhes. |
| `src/Repositories/CursoRepository.php` | 113 a 135 | `excluir()`: `$pull` do `_id` em `estudantes.cursos_ids` antes de `deleteOne()`. |
| `public/cursos.php` | 1 a 16 | Bootstrap, instância do repositório e variáveis de estado (`$erro`, `$formErro`, `$old`). |
| `public/cursos.php` | 17 a 45 | POST `cadastrar` (6 campos, validação de obrigatórios/código duplicado) e POST `excluir` com PRG. |
| `public/cursos.php` | 46 a 78 | Tratamento de `InvalidArgumentException`/`Throwable`, carga da listagem e do detalhe, variáveis de layout e include do header. |
| `public/cursos.php` | 79 a 124 | Tela de detalhes: dados do curso, descrição e estudantes trazidos por `$lookup`. |
| `public/cursos.php` | 125 a 157 | Formulário de cadastro com os 6 campos (select de turno e textarea de descrição). |
| `public/cursos.php` | 158 a 198 | Tabela de cursos com contagem de matriculados, botões Ver/Excluir (POST com `confirm`) e include do footer. |

---

## Commit 5 — Dev 1 (Módulo de Estudantes)

**Mensagem:** `feat(estudantes): implementa repositorio e formulario de cadastro`

**Comandos exatos:**

```bash
git add src/Repositories/EstudanteRepository.php public/estudantes.php
git commit -m "feat(estudantes): implementa repositorio e formulario de cadastro"
```

**Detalhamento técnico:**

| Arquivo | Linhas | O que foi feito |
| --- | --- | --- |
| `src/Repositories/EstudanteRepository.php` | 1 a 15 | Namespace, imports, `typeMap`, propriedades `$estudantes`/`$cursos` e docblock do repositório. |
| `src/Repositories/EstudanteRepository.php` | 16 a 28 | Construtor com as duas coleções (necessárias para manter as referências espelhadas). |
| `src/Repositories/EstudanteRepository.php` | 29 a 74 | `contar()`, `criar()` (6 campos + subdocumento `endereco` com 6 campos + `cursos_ids` + `criado_em`) e checagem de CPF/matrícula. |
| `src/Repositories/EstudanteRepository.php` | 75 a 132 | `listarComCursos()`, `listarSimples()`, `buscarPorId()` (com `$lookup`) e `buscarSimples()` para o estado atual das matrículas. |
| `src/Repositories/EstudanteRepository.php` | 133 a 156 | `excluir()`: `$pull` em `cursos.estudantes_ids` para cada curso vinculado e `deleteOne()`. |
| `src/Repositories/EstudanteRepository.php` | 157 a 175 | `vincular()` com `$addToSet` nas duas coleções, validando ambos os ObjectIds. |
| `src/Repositories/EstudanteRepository.php` | 176 a 199 | `desvincular()` com `$pull` nas duas coleções. |
| `src/Repositories/EstudanteRepository.php` | 200 a 217 | `sincronizarCursos()`: calcula `array_diff` entre matriculas atuais e desejadas, aplicando vínculos/desvínculos. |
| `public/estudantes.php` | 1 a 16 | Bootstrap, repositório e variáveis de estado do formulário. |
| `public/estudantes.php` | 17 a 59 | POST `cadastrar` (6 campos + 6 de endereço, e-mail, duplicidades) e POST `excluir` com redirecionamento. |
| `public/estudantes.php` | 60 a 92 | Captura de erros, carga de listagem/detalhe, variáveis de layout e include do header. |
| `public/estudantes.php` | 93 a 143 | Alerta de erro de banco e tela de detalhes: dados pessoais, endereço e cursos via `$lookup`. |
| `public/estudantes.php` | 144 a 185 | Formulário com os 6 campos principais e os 6 campos do subdocumento `endereco`. |
| `public/estudantes.php` | 186 a 234 | Tabela com cursos por estudante, ações Ver/Excluir (POST com `confirm`) e include do footer. |

---

## Commit 6 — Dev 2 (Matrículas N:N)

**Mensagem:** `feat(matriculas): implementa vinculacao bidirecional com addToSet e pull`

**Comandos exatos:**

```bash
git add public/matriculas.php
git commit -m "feat(matriculas): implementa vinculacao bidirecional com addToSet e pull"
```

**Detalhamento técnico:**

| Arquivo | Linhas | O que foi feito |
| --- | --- | --- |
| `public/matriculas.php` | 1 a 13 | Bootstrap, instância de `EstudanteRepository` e `CursoRepository`. |
| `public/matriculas.php` | 14 a 26 | POST `salvar`: lê `estudante_id` e `cursos_ids[]`, chama `sincronizarCursos()`, grava flash e redireciona mantendo o estudante selecionado. |
| `public/matriculas.php` | 27 a 56 | Carrega estudantes/cursos para os seletores, resolve o ID da URL (ou o primeiro), obtém as matrículas atuais e monta o mapa `$atuais`; inclui o header. |
| `public/matriculas.php` | 57 a 111 | Formulário de matrícula: seletor de estudante (troca via `onchange`), grade de checkboxes marcados conforme `$atuais` e botão de salvar. |
| `public/matriculas.php` | 112 a 150 | Dois painéis de consulta com `$lookup`: estudantes x cursos e cursos x estudantes. |
| `public/matriculas.php` | 151 a 152 | Include do footer e fechamento do HTML. |

---

## Commit 7 — Dev 1 (Dashboard)

**Mensagem:** `feat(dashboard): adiciona resumo com contadores e ultimos cadastros`

**Comandos exatos:**

```bash
git add public/index.php
git commit -m "feat(dashboard): adiciona resumo com contadores e ultimos cadastros"
```

**Detalhamento técnico:**

| Arquivo | Linhas | O que foi feito |
| --- | --- | --- |
| `public/index.php` | 1 a 10 | Bootstrap, repositórios e `use` das classes. |
| `public/index.php` | 11 a 31 | `contar()` nas duas coleções e `array_slice` das listagens com relacionamentos; captura de erro de banco. |
| `public/index.php` | 32 a 48 | Include do header, alerta de erro e cartão de apresentação do modelo N:N com atalhos de navegação. |
| `public/index.php` | 49 a 66 | Cards com total de estudantes, total de cursos e o selo do modelo `N : N`. |
| `public/index.php` | 67 a 113 | Painéis de estudantes e cursos recentes (com contagem de relacionamentos) e include do footer. |

---

## Commit 8 — Dev 2 (Documentação)

**Mensagem:** `docs: adiciona README com a documentação do projeto`

**Comandos exatos:**

```bash
git add README.md COMMITS_PLAN.md
git commit -m "docs: adiciona README com a documentação do projeto"
```

**Detalhamento técnico:**

| Arquivo | Linhas | O que foi feito |
| --- | --- | --- |
| `README.md` | 1 a 12 | Apresentação, requisitos (PHP 8.1+, extensão `mongodb`, Composer), compatibilidade das versões da biblioteca e aliases `MONGODB_URI`/`MONGODB_DB`. |
| `README.md` | 13 a 39 | Instalação passo a passo (`composer install`, `.env`, `docker compose up -d`, `php -S`) e configuração para Atlas. |
| `README.md` | 40 a 58 | Árvore de diretórios com a responsabilidade de cada arquivo. |
| `README.md` | 59 a 94 | Modelo de dados com os dois documentos de exemplo, regras de `$addToSet`/`$pull`, `$lookup` e limpeza na exclusão; tabela de rotas. |
| `README.md` | 95 a 108 | Segurança/tratamento de erros, validação executada (`php -l` + teste contra MongoDB real) e ponteiro para este plano. |

---

## Resumo da divisão

| Dev | Commits | Área |
| --- | --- | --- |
| Dev 1 | 1, 3, 5, 7 | Infraestrutura, conexão MongoDB, Estudantes, Dashboard |
| Dev 2 | 2, 4, 6, 8 | Layout base, Cursos, Matrículas N:N, Documentação |

> Ordem sugerida: 1 → 2 → 3 → 4 → 5 → 6 → 7 → 8. Como os módulos de estudantes/cursos compartilham a camada de conexão e o layout, os commits 1 a 3 devem ser aplicados antes dos demais para manter a aplicação executável em cada ponto.
