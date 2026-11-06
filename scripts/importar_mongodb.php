<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Execução exclusiva pela linha de comando.');
}
if (!class_exists('MongoDB\\Driver\\Manager')) {
    fwrite(STDERR, "A extensão mongodb do PHP não está instalada.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/config.php';
$config = mongoConfig();
$caminho = dirname(__DIR__) . '/data/demo_tickets.ndjson';
if (!is_file($caminho)) {
    fwrite(STDERR, "Execute primeiro php scripts/gerar_dados_demo.php 500\n");
    exit(1);
}

try {
    $manager = new MongoDB\Driver\Manager($config['uri']);
    $namespace = $config['database'] . '.' . $config['collection'];
    $handle = fopen($caminho, 'rb');
    $total = 0;
    $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
    $pendentes = 0;
    while (($line = fgets($handle)) !== false) {
        $obj = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
        $bulk->update(['ticket_id' => $obj['ticket_id']], ['$set' => $obj], ['upsert' => true, 'multi' => false]);
        $total++;
        $pendentes++;
        if ($pendentes === 500) {
            $manager->executeBulkWrite($namespace, $bulk);
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            $pendentes = 0;
        }
    }
    if ($pendentes > 0) {
        $manager->executeBulkWrite($namespace, $bulk);
    }
    fclose($handle);
    printf("Importados/atualizados %d tickets fictícios em %s.\n", $total, $namespace);
} catch (Throwable $e) {
    fwrite(STDERR, "Falha na importação MongoDB: " . $e->getMessage() . "\n");
    exit(1);
}
