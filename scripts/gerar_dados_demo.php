<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Execução exclusiva pela linha de comando.');
}
if (!class_exists('SQLite3')) {
    fwrite(STDERR, "A extensão sqlite3 do PHP não está instalada.\n");
    exit(1);
}

$total = filter_var($argv[1] ?? '500', FILTER_VALIDATE_INT);
if ($total === false || $total < 1 || $total > 100000) {
    fwrite(STDERR, "Uso: php scripts/gerar_dados_demo.php [1..100000]\n");
    exit(1);
}

$root = dirname(__DIR__);
$basePath = $root . '/bd/tickets.db';
$dataPath = $root . '/data/demo_tickets.ndjson';
if (!is_dir($root . '/data')) {
    mkdir($root . '/data', 0775, true);
}
// Trabalha em arquivos temporários e só substitui o conjunto após gerar tudo.
$tmpBase = $root . '/bd/tickets.db.tmp';
$tmpData = $root . '/data/demo_tickets.ndjson.tmp';
@unlink($tmpBase);
@unlink($tmpData);
$db = new SQLite3($tmpBase);
$db->exec(file_get_contents($root . '/bd/schema.sql'));
$db->exec('BEGIN');

$tipos = [
    'Suporte técnico', 'Atendimento', 'Financeiro', 'Comercial',
];
$tipoStmt = $db->prepare('INSERT INTO Tipos_de_Servico (tipo_servico_id, nome) VALUES (:id, :nome)');
foreach ($tipos as $id => $nome) {
    $tipoStmt->bindValue(':id', $id + 1, SQLITE3_INTEGER);
    $tipoStmt->bindValue(':nome', $nome, SQLITE3_TEXT);
    $tipoStmt->execute();
}

$dataStmt = $db->prepare('INSERT INTO Data (data) VALUES (:data)');
$ticketStmt = $db->prepare('INSERT INTO Tickets (ticket_id, titulo, descricao, data_criacao_id, tipo_servico_id, prioridade, status_chamado) VALUES (:ticket_id, :titulo, :descricao, :data_id, :tipo_id, :prioridade, :status)');
$arquivo = fopen($tmpData, 'wb');
if ($arquivo === false) {
    fwrite(STDERR, "Não foi possível criar arquivo de dados de demonstração.\n");
    exit(1);
}

mt_srand(2026);
$inicio = strtotime('2020-01-01');
$fim = strtotime('2023-12-31');
$prioridades = ['Baixa', 'Média', 'Alta'];
$status = ['Aberto', 'Em andamento', 'Fechado'];
for ($i = 1; $i <= $total; $i++) {
    $dia = date('Y-m-d', mt_rand($inicio, $fim));
    $tipoId = mt_rand(1, count($tipos));
    $prioridade = $prioridades[mt_rand(0, 2)];
    $situacao = $status[mt_rand(0, 2)];
    $titulo = 'Ticket de demonstração #' . $i;
    $descricao = 'Registro sintético para teste de consulta #' . $i;
    $dataStmt->bindValue(':data', $dia, SQLITE3_TEXT);
    $dataStmt->execute();
    $dataId = $db->lastInsertRowID();
    foreach ([
        ':ticket_id' => [$i, SQLITE3_INTEGER],
        ':titulo' => [$titulo, SQLITE3_TEXT],
        ':descricao' => [$descricao, SQLITE3_TEXT],
        ':data_id' => [$dataId, SQLITE3_INTEGER],
        ':tipo_id' => [$tipoId, SQLITE3_INTEGER],
        ':prioridade' => [$prioridade, SQLITE3_TEXT],
        ':status' => [$situacao, SQLITE3_TEXT],
    ] as $key => [$value, $type]) {
        $ticketStmt->bindValue($key, $value, $type);
    }
    $ticketStmt->execute();

    $obj = [
        'ticket_id' => $i,
        'titulo' => $titulo,
        'descricao' => $descricao,
        'data_criacao_iso' => $dia,
        'data_criacao_id' => date('d/m/Y', strtotime($dia)),
        'tipo_servico_id' => $tipos[$tipoId - 1],
        'prioridade' => $prioridade,
        'status_chamado' => $situacao,
    ];
    fwrite($arquivo, json_encode($obj, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n");
}
$db->exec('COMMIT');
$db->close();
fclose($arquivo);
rename($tmpBase, $basePath);
rename($tmpData, $dataPath);
printf("Gerados %d tickets fictícios em SQLite e NDJSON.\n", $total);
