<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['data' => [], 'error' => 'Método não permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/config.php';

function validarData(string $valor): bool
{
    $data = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
    return $data !== false && $data->format('Y-m-d') === $valor;
}

try {
    $banco = (string) ($_POST['banco'] ?? '');
    $todos = (string) ($_POST['todos'] ?? '0') === '1';
    $inicio = (string) ($_POST['dt_inicial'] ?? '');
    $fim = (string) ($_POST['dt_final'] ?? '');
    if (!in_array($banco, ['sqlite', 'mongo'], true)) {
        throw new InvalidArgumentException('Selecione uma fonte de dados válida.');
    }
    if (!$todos && (!validarData($inicio) || !validarData($fim) || $inicio > $fim)) {
        throw new InvalidArgumentException('Informe um intervalo de datas válido.');
    }

    $start = hrtime(true);
    $tickets = [];

    if ($banco === 'sqlite') {
        if (!class_exists('SQLite3')) {
            throw new RuntimeException('A extensão sqlite3 do PHP não está disponível.');
        }
        $arquivo = __DIR__ . '/bd/tickets.db';
        if (!is_file($arquivo)) {
            throw new RuntimeException('Base de demonstração ausente. Execute o gerador indicado no README.');
        }
        $db = new SQLite3($arquivo, SQLITE3_OPEN_READONLY);
        $sql = 'SELECT t.ticket_id, t.titulo, t.descricao, d.data AS data_criacao_iso, '
            . "strftime('%d/%m/%Y', d.data) AS data_criacao_id, "
            . 's.nome AS tipo_servico_id, t.prioridade, t.status_chamado '
            . 'FROM Tickets t JOIN Data d ON d.data_id = t.data_criacao_id '
            . 'JOIN Tipos_de_Servico s ON s.tipo_servico_id = t.tipo_servico_id';
        if (!$todos) {
            $sql .= ' WHERE d.data BETWEEN :inicio AND :fim';
        }
        $sql .= ' ORDER BY d.data DESC, t.ticket_id DESC';
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('Não foi possível preparar a consulta SQL.');
        }
        if (!$todos) {
            $stmt->bindValue(':inicio', $inicio, SQLITE3_TEXT);
            $stmt->bindValue(':fim', $fim, SQLITE3_TEXT);
        }
        $result = $stmt->execute();
        if (!$result) {
            throw new RuntimeException('Falha na consulta SQL.');
        }
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $tickets[] = $row;
        }
        $db->close();
    } else {
        if (!class_exists('MongoDB\\Driver\\Manager')) {
            throw new RuntimeException('A extensão mongodb do PHP não está disponível.');
        }
        $config = mongoConfig();
        $manager = new MongoDB\Driver\Manager($config['uri']);
        $filtro = $todos ? [] : ['data_criacao_iso' => ['$gte' => $inicio, '$lte' => $fim]];
        $query = new MongoDB\Driver\Query($filtro, ['sort' => ['data_criacao_iso' => -1, 'ticket_id' => -1]]);
        $cursor = $manager->executeQuery($config['database'] . '.' . $config['collection'], $query);
        foreach ($cursor as $document) {
            $ticket = (array) $document;
            unset($ticket['_id']);
            $tickets[] = $ticket;
        }
    }
    echo json_encode([
        'data' => $tickets,
        'execution_time_ms' => round((hrtime(true) - $start) / 1000000, 2),
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode(['data' => [], 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('[TicketFlow] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'data' => [],
        'error' => 'Falha na consulta. Verifique as extensões, a conexão e os dados de demonstração.',
    ], JSON_UNESCAPED_UNICODE);
}
