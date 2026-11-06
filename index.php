<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TicketFlow | Comparação SQL e NoSQL</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css">
</head>
<body class="bg-light">
<main class="container py-5">
    <div class="mb-4">
        <h1 class="h3 mb-2">TicketFlow</h1>
        <p class="text-muted mb-0">Comparação de consultas a tickets: SQLite (SQL) e MongoDB (NoSQL)</p>
    </div>
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form id="consultaForm" novalidate>
                <div class="form-row align-items-end">
                    <div class="form-group col-md-3">
                        <label for="dt_inicial">Data inicial</label>
                        <input type="date" class="form-control" name="dt_inicial" id="dt_inicial">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="dt_final">Data final</label>
                        <input type="date" class="form-control" name="dt_final" id="dt_final">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="banco">Fonte de dados</label>
                        <select class="form-control" name="banco" id="banco">
                            <option value="sqlite">SQLite (SQL)</option>
                            <option value="mongo">MongoDB (NoSQL)</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <div class="custom-control custom-checkbox mb-2">
                            <input type="checkbox" class="custom-control-input" id="todos" name="todos" checked>
                            <label class="custom-control-label" for="todos">Todos os registros</label>
                        </div>
                        <button type="submit" class="btn btn-primary" id="btConsultar">Consultar</button>
                        <button type="reset" class="btn btn-outline-secondary">Limpar</button>
                    </div>
                </div>
            </form>
            <div id="mensagemErro" class="alert alert-danger d-none" role="alert"></div>
            <p id="tempoCarregamento" class="small text-muted mb-0" aria-live="polite">Selecione a consulta para visualizar resultados.</p>
        </div>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabela" class="table table-striped table-bordered w-100">
                    <thead>
                    <tr>
                        <th>ID</th><th>Título</th><th>Descrição</th><th>Data de criação</th>
                        <th>Tipo de serviço</th><th>Prioridade</th><th>Status</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</main>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap4.min.js"></script>
<script src="consulta.js"></script>
</body>
</html>
