<?php 
session_start();
require_once '../components/valida-sessao.php';
require_once '../conecta.php';
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Location: login');
    exit();
}
// --- Backup configuration (in-file, no extra files) ---
define('BACKUP_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'backups');
// filename generation now uses DateTime::format directly in the generate action
define('BACKUP_ALLOWED_ROLES', ['Gerente', 'Tecnico', 'Técnico']);

// Ensure backups directory exists and is writable. Try to create and fix perms when possible.
if (!is_dir(BACKUP_DIR)) {
    if (!mkdir(BACKUP_DIR, 0755, true)) {
        error_log('Falha ao criar pasta de backups: ' . BACKUP_DIR);
    }
}
// attempt to ensure writable
if (!is_writable(BACKUP_DIR)) {
    // try to chmod (may not work on Windows)
    @chmod(BACKUP_DIR, 0755);
    if (!is_writable(BACKUP_DIR)) {
        error_log('Pasta de backups não está gravável: ' . BACKUP_DIR);
    }
}

// Error handling: log to backups/backup_errors.log and convert warnings to exceptions
ini_set('display_errors', '0');
error_reporting(E_ALL);
$errorLog = BACKUP_DIR . DIRECTORY_SEPARATOR . 'backup_errors.log';
ini_set('log_errors', '1');
ini_set('error_log', $errorLog);

set_error_handler(function($severity, $message, $file, $line) {
    // Convert warnings/notices to ErrorException so they are caught
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Ensure any accidental output doesn't break JSON responses
ob_start();

function send_json_and_exit($payload, $http = 200) {
    http_response_code($http);
    // discard any buffered output
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

if (!isset($_SESSION['tipo']) || !in_array($_SESSION['tipo'], BACKUP_ALLOWED_ROLES)) {
    header('Location: vendas');
    exit();
}

// --- Request handlers for AJAX/actions (generate, list, restore, download) ---
if ((isset($_GET['action']) && $_GET['action']) || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']))) {
    $action = $_REQUEST['action'] ?? '';
    header('Content-Type: application/json; charset=utf-8');

    // double-check role
    $userTipo = $_SESSION['tipo'] ?? '';
    if (!in_array($userTipo, BACKUP_ALLOWED_ROLES)) {
        send_json_and_exit(['ok' => false, 'msg' => 'Acesso negado'], 403);
    }

    // helper: dump DB using PDO
    function generate_dump($pdo) {
        // determine current database
        $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
        $dump = "-- Dump criado em " . date('Y-m-d H:i:s') . "\n";
        $dump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        // 1) Export base tables (TABLE_TYPE = 'BASE TABLE') with data
        $stmtTables = $pdo->prepare("SELECT TABLE_NAME FROM information_schema.tables WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'");
        $stmtTables->execute([$dbName]);
        $tables = $stmtTables->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $row = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
            // ensure DROP before CREATE to make restore idempotent
            $dump .= "-- Estrutura da tabela `{$table}`\n";
            $dump .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $dump .= $row['Create Table'] . ";\n\n";

            // dump rows
            $stmt = $pdo->query("SELECT * FROM `{$table}`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($rows) > 0) {
                $dump .= "-- Dados da tabela `{$table}`\n";
                $cols = array_map(function($c){ return "`$c`"; }, array_keys($rows[0]));
                $colList = implode(', ', $cols);
                foreach ($rows as $r) {
                    $vals = array_map(function($v) use ($pdo){
                        if (is_null($v)) return 'NULL';
                        return $pdo->quote($v);
                    }, array_values($r));
                    $dump .= "INSERT INTO `{$table}` ({$colList}) VALUES (" . implode(', ', $vals) . ");\n";
                }
                $dump .= "\n";
            }
        }

        // 2) Export views separately (no INSERTs) using SHOW CREATE VIEW
        $stmtViews = $pdo->prepare("SELECT TABLE_NAME FROM information_schema.tables WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'VIEW'");
        $stmtViews->execute([$dbName]);
        $views = $stmtViews->fetchAll(PDO::FETCH_COLUMN);
        foreach ($views as $view) {
            $row = $pdo->query("SHOW CREATE VIEW `{$view}`")->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row['Create View'])) {
                $dump .= "-- Estrutura da view `{$view}`\n";
                $dump .= "DROP VIEW IF EXISTS `{$view}`;\n";
                $dump .= $row['Create View'] . ";\n\n";
            }
        }

        $dump .= "SET FOREIGN_KEY_CHECKS=1;\n";
        return $dump;
    }

    try {
        if ($action === 'list') {
            $files = [];
            foreach (glob(BACKUP_DIR . DIRECTORY_SEPARATOR . '*.sql') as $f) {
                $files[] = [
                    'name' => basename($f),
                    'path' => $f,
                    'size' => filesize($f),
                    'mtime' => filemtime($f)
                ];
            }
            usort($files, function($a,$b){ return $b['mtime'] - $a['mtime']; });
            send_json_and_exit(['ok' => true, 'files' => $files]);
        }

        if ($action === 'generate') {
            $dump = generate_dump($pdo);
            // Use DateTime::format instead of deprecated strftime
            $filename = 'backup_' . (new DateTime())->format('Ymd_His') . '.sql';
            $path = BACKUP_DIR . DIRECTORY_SEPARATOR . $filename;
            $written = file_put_contents($path, $dump);
            if ($written === false) {
                error_log('Falha ao gravar backup em: ' . $path);
                send_json_and_exit(['ok' => false, 'msg' => 'Falha ao gravar arquivo de backup'], 500);
            }
            send_json_and_exit(['ok' => true, 'file' => basename($path), 'path' => $path]);
        }

        if ($action === 'restore') {
            // Accept uploaded file or existing filename
            if (isset($_FILES['sqlfile']) && $_FILES['sqlfile']['error'] === UPLOAD_ERR_OK) {
                $tmp = $_FILES['sqlfile']['tmp_name'];
                $content = file_get_contents($tmp);
            } elseif (!empty($_POST['file'])) {
                $file = basename($_POST['file']);
                $path = BACKUP_DIR . DIRECTORY_SEPARATOR . $file;
                if (!file_exists($path)) {
                    send_json_and_exit(['ok' => false, 'msg' => 'Arquivo não encontrado'], 400);
                }
                $content = file_get_contents($path);
            } else {
                send_json_and_exit(['ok' => false, 'msg' => 'Nenhum arquivo informado'], 400);
            }

            // Execute via mysqli multi_query
            $dbHost = 'localhost';
            $dbName = 'sorveteria_db';
            $dbUser = $username ?? 'root';
            $dbPass = $password ?? '';
            $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
            if ($mysqli->connect_errno) {
                error_log('MySQL connect error: ' . $mysqli->connect_error);
                send_json_and_exit(['ok' => false, 'msg' => $mysqli->connect_error], 500);
            }

            // Optionally reset DB before restore to avoid conflicts
            $mode = $_POST['mode'] ?? 'reset'; // 'reset' or 'merge'
            if ($mode === 'reset') {
                // drop triggers
                $triggers = $mysqli->query("SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = '" . $mysqli->real_escape_string($dbName) . "'");
                if ($triggers) {
                    while ($row = $triggers->fetch_assoc()) {
                        $tname = $row['TRIGGER_NAME'];
                        @$mysqli->query("DROP TRIGGER IF EXISTS `" . $mysqli->real_escape_string($tname) . "`");
                    }
                }

                // drop views
                $views = $mysqli->query("SELECT TABLE_NAME FROM information_schema.VIEWS WHERE TABLE_SCHEMA = '" . $mysqli->real_escape_string($dbName) . "'");
                $dropViews = [];
                if ($views) {
                    while ($row = $views->fetch_assoc()) {
                        $dropViews[] = "`" . $mysqli->real_escape_string($row['TABLE_NAME']) . "`";
                    }
                    if (count($dropViews)) {
                        @$mysqli->query("DROP VIEW IF EXISTS " . implode(',', $dropViews));
                    }
                }

                // drop routines (procedures/functions)
                $routines = $mysqli->query("SELECT ROUTINE_NAME, ROUTINE_TYPE FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = '" . $mysqli->real_escape_string($dbName) . "'");
                if ($routines) {
                    while ($row = $routines->fetch_assoc()) {
                        $rname = $row['ROUTINE_NAME'];
                        $rtype = strtoupper($row['ROUTINE_TYPE']);
                        if ($rtype === 'PROCEDURE') {
                            @$mysqli->query("DROP PROCEDURE IF EXISTS `" . $mysqli->real_escape_string($rname) . "`");
                        } else {
                            @$mysqli->query("DROP FUNCTION IF EXISTS `" . $mysqli->real_escape_string($rname) . "`");
                        }
                    }
                }

                // drop all tables
                $tables = $mysqli->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = '" . $mysqli->real_escape_string($dbName) . "'");
                $dropTables = [];
                if ($tables) {
                    while ($row = $tables->fetch_assoc()) {
                        $dropTables[] = "`" . $mysqli->real_escape_string($row['TABLE_NAME']) . "`";
                    }
                    if (count($dropTables)) {
                        // disable fk checks
                        @$mysqli->query('SET FOREIGN_KEY_CHECKS=0');
                        @$mysqli->query("DROP TABLE IF EXISTS " . implode(',', $dropTables));
                        @$mysqli->query('SET FOREIGN_KEY_CHECKS=1');
                    }
                }

                // log reset action
                error_log('DB reset performed before restore by user: ' . ($_SESSION['usuario_id'] ?? 'unknown'));
            }

            // Remove DELIMITER markers left by some dumps
            $content = preg_replace('/DELIMITER\s+\$\$\s*/i', "", $content);
            $content = str_replace('$$', ';', $content);

            // Defensive: remove INSERTs for objects that don't have a CREATE TABLE in this dump
            // This prevents attempting to INSERT into views or other objects that were exported incorrectly
            $createTables = [];
            if (preg_match_all('/CREATE\s+TABLE\s+`([^`]+)`/i', $content, $m)) {
                $createTables = $m[1];
            }

            $lines = preg_split("/\r\n|\n|\r/", $content);
            $filtered = [];
            for ($i = 0; $i < count($lines); $i++) {
                $line = $lines[$i];
                // detect comment lines like: -- Dados da tabela `name`
                if (preg_match('/^\s*--\s*Dados da tabela\s*`([^`]+)`/i', $line, $cm)) {
                    $name = $cm[1];
                    if (!in_array($name, $createTables, true)) {
                        // skip this comment and skip following INSERT lines for this object
                        // advance i while next lines start with INSERT INTO `name`
                        while (isset($lines[$i+1]) && preg_match('/^\s*INSERT\s+INTO\s+`' . preg_quote($name, '/') . '`/i', $lines[$i+1])) {
                            $i++; // skip that insert line
                        }
                        continue; // skip the comment
                    }
                }

                // detect standalone INSERT into object not present in createTables
                if (preg_match('/^\s*INSERT\s+INTO\s+`([^`]+)`/i', $line, $ins)) {
                    $iname = $ins[1];
                    if (!in_array($iname, $createTables, true)) {
                        // skip this insert line
                        continue;
                    }
                }

                $filtered[] = $line;
            }

            $content = implode("\n", $filtered);

            if ($mysqli->multi_query($content)) {
                do {
                    if ($result = $mysqli->store_result()) {
                        $result->free();
                    }
                } while ($mysqli->more_results() && $mysqli->next_result());
                send_json_and_exit(['ok' => true]);
            } else {
                error_log('MySQL multi_query error: ' . $mysqli->error);
                send_json_and_exit(['ok' => false, 'msg' => $mysqli->error], 500);
            }
            // send_json_and_exit already exited
        }

    } catch (Exception $e) {
        // Log and return JSON error
        error_log('Backup handler exception: ' . $e->getMessage());
        send_json_and_exit(['ok' => false, 'msg' => $e->getMessage()], 500);
    }
}

// Download handler (separate GET param)
if (isset($_GET['download'])) {
    $file = basename($_GET['download']);
    $path = BACKUP_DIR . DIRECTORY_SEPARATOR . $file;
    if (!file_exists($path)) {
        http_response_code(404);
        echo 'Arquivo não encontrado';
        exit;
    }
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $file . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Gela-Gela | Backup</title>
    <link rel="icon" type="image/png" href="https://img.icons8.com/ios-filled/50/ff4d7d/ice-cream-bowl.png">

    <link rel="stylesheet" href="ASSETS/CSS/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>

    <div class="layout">

        <?php require_once '../components/sidebar.php'; ?>

        <main class="content">

            <header class="topbar">
                <button class="menu-btn" onclick="toggleSidebar()">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <h1>Backup do Sistema</h1>
                <?php
   require_once '../components/user-menu.php';
 ?>


            </header>

            <section class="main">

                <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
                    <p><strong>Último backup:</strong> <span id="ultimo-backup">--</span></p>

                    <button class="btn" onclick="fazerBackup(this)">
                        <i class="fa fa-database"></i> Gerar Backup
                    </button>
                </div>

                <div class="box">
                    <table>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Tipo</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>

                        <tbody id="tabela-backup">
                        <!-- Lista dinâmica gerada via AJAX -->
                        </tbody>
                    </table>
                </div>

            </section>
        </main>
    </div>

        <script src="ASSETS/JS/sidebar.js"></script>
    <script src="ASSETS/JS/user-menu.js"></script>
    <script>
        async function atualizarLista() {
            const res = await fetch('backup.php?action=list', { credentials: 'same-origin' });
            const data = await res.json();
            const tabela = document.getElementById('tabela-backup');
            tabela.innerHTML = '';
            if (!data.ok) return;
            let ultimo = null;
            data.files.forEach(f => {
                const d = new Date(f.mtime * 1000);
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${d.toLocaleString('pt-BR')}</td>
                    <td><span class="tag-resumo">Automático</span></td>
                    <td><span class="badge ok">Disponível</span></td>
                    <td>
                        <a class="btn-icon" href="backup.php?download=${encodeURIComponent(f.name)}"><i class="fa fa-download"></i></a>
                        <button class="btn-icon" onclick="restaurar('${encodeURIComponent(f.name)}')"><i class="fa fa-undo"></i></button>
                    </td>
                `;
                tabela.appendChild(row);
                if (!ultimo) ultimo = d;
            });
            const ultimoBackup = document.getElementById('ultimo-backup');
            ultimoBackup.innerText = ultimo ? ultimo.toLocaleString('pt-BR') : '--';
        }

        async function fazerBackup(btn) {
            if (!confirm('Gerar backup agora?')) return;
            btn.disabled = true;
            try {
                const res = await fetch('backup.php?action=generate', { method: 'POST', credentials: 'same-origin' });
                const data = await res.json();
                if (data.ok) {
                    alert('Backup criado: ' + data.file);
                    atualizarLista();
                } else {
                    alert('Erro: ' + (data.msg || ''));
                }
            } catch (e) {
                alert('Erro ao gerar backup');
            } finally {
                btn.disabled = false;
            }
        }

        async function restaurar(filename) {
            filename = decodeURIComponent(filename);
            if (!confirm('Restaurar o banco a partir de ' + filename + '? Isso pode sobrescrever dados.')) return;
            try {
                const form = new FormData();
                form.append('file', filename);
                // Pergunta se quer resetar (dropar tabelas) antes da restauração
                const reset = confirm('Deseja resetar (dropar) todas as tabelas antes de restaurar? Recomendada para evitar conflitos.');
                form.append('mode', reset ? 'reset' : 'merge');
                const res = await fetch('backup.php?action=restore', { method: 'POST', body: form, credentials: 'same-origin' });
                const data = await res.json();
                if (data.ok) {
                    alert('Restauração concluída com sucesso.');
                } else {
                    alert('Erro: ' + (data.msg || ''));
                }
            } catch (e) {
                alert('Erro na restauração');
            }
        }

        // lista ao carregar
        atualizarLista();
    </script>

</body>

</html>