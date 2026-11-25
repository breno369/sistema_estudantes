# Sistema Acadêmico (PHP puro + MongoDB)

Cadastro simples de estudantes e cursos, feito em PHP 8.1+, MongoDB (extensão `mongodb` + biblioteca oficial `mongodb/mongodb`) e Tailwind CSS via CDN.

## Requisitos

- PHP **8.1+** com a extensão `mongodb` habilitada
- Composer 2
- MongoDB local (Docker Compose) ou MongoDB Atlas

> A dependência `mongodb/mongodb` aceita as linhas `1.x` e `2.x` da biblioteca para acompanhar tanto a extensão `mongodb` 1.21+ quanto a 2.x. Também são aceitos `MONGODB_URI`/`MONGODB_DB` (usados por stacks Docker), com prioridade de `MONGO_URI`/`MONGO_DB`.

## Instalação e execução

```bash
# 1. dependências
composer install

# 2. configuração (MongoDB local já é o padrão)
cp .env.example .env

# 3. suba o MongoDB (opcional se você já tiver um servidor/Atlas)
docker compose up -d

# 4. sirva a aplicação
php -S localhost:8000 -t public
```

Acesse **http://localhost:8000**.

Para usar o Atlas, edite `.env`:

```env
MONGO_URI=mongodb+srv://usuario:senha@cluster.mongodb.net
MONGO_DB=sistema_estudantes
```

Variáveis de ambiente reais têm prioridade sobre o `.env`. Se nada for definido, usa-se `mongodb://localhost:27017` e o banco `sistema_estudantes`.

## Estrutura

```
composer.json                                   # dependências e autoload (PSR-4 + arquivos de config)
docker-compose.yml                              # MongoDB local para testes
.env.example                                    # modelo de configuração
config/database.php                             # conexão singleton + leitura de .env
src/helpers.php                                 # escape, validação de ObjectId, flash, redirecionamento
src/Repositories/EstudanteRepository.php        # CRUD de estudantes + vínculo N:N
src/Repositories/CursoRepository.php            # CRUD de cursos + limpeza de referências
public/index.php                                # dashboard/resumo
public/estudantes.php                           # formulário, listagem, detalhes, exclusão
public/cursos.php                               # formulário, listagem, detalhes, exclusão
public/matriculas.php                           # vinculação/desvinculação N:N
public/includes/header.php                      # layout + Tailwind CDN + navegação
public/includes/footer.php                      # fechamento do layout
COMMITS_PLAN.md                                 # plano de 8 commits para Dev 1 e Dev 2
```

## Modelo de dados (referências normalizadas)

Nenhum documento embute o outro. Cada lado guarda apenas `ObjectId`s:

```js
// estudantes
{
  _id: ObjectId(...),
  nome, cpf, email, telefone, data_nascimento, matricula,
  endereco: { cep, logradouro, numero, bairro, cidade, estado },
  cursos_ids: [ObjectId("..."), ObjectId("...")],
  criado_em: ISODate(...)
}

// cursos
{
  _id: ObjectId(...),
  nome, codigo, carga_horaria, turno, coordenador, descricao,
  estudantes_ids: [ObjectId("...")],
  criado_em: ISODate(...)
}
```

- **Matricular/desmatricular:** `tela de Matrículas` aplica `$addToSet`/`$pull` nas duas coleções na mesma ação, mantendo os arrays espelhados.
- **Leitura:** `$lookup` (aggregation pipeline) traz somente `nome`/`codigo`/`matricula` dos documentos relacionados para listagens e detalhes.
- **Exclusão:** antes de `deleteOne`, o repositório remove o `_id` excluído dos arrays da coleção oposta com `$pull`.

## Telas

| Rota | Finalidade |
| --- | --- |
| `index.php` | resumo, contadores e últimas movimentações |
| `estudantes.php` | cadastro (6 campos + 6 de endereço), listagem, detalhes, exclusão |
| `cursos.php` | cadastro (6 campos), listagem, detalhes, exclusão |
| `matriculas.php` | vincular/desvincular um estudante de vários cursos de uma vez |
