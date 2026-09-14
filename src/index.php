<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

$host = 'db';
$dbname = 'db_arqueologia';
$user = 'user_arq';
$pass = 'pass_arq_123';

$errorMsg = '';
$pdo = null;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Solucionar automáticamente truncado de columnas de estado y tipos si es posible
    @$pdo->exec("ALTER TABLE piezas_arqueologicas MODIFY estado_conservacion VARCHAR(50) NULL");
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Descubrir columnas y tipos reales de la tabla de forma segura
$tableColsInfo = [];
$tableCols = [];
try {
    $colsResult = $pdo->query("DESCRIBE piezas_arqueologicas");
    while ($c = $colsResult->fetch(PDO::FETCH_ASSOC)) {
        $tableColsInfo[$c['Field']] = strtolower($c['Type']);
        $tableCols[] = $c['Field'];
    }
} catch (Exception $e) {}

function getVal($row, $keys, $default = '') {
    foreach ($keys as $k) {
        if (isset($row[$k])) return $row[$k];
    }
    return $default;
}

// Eliminar (Delete)
if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM piezas_arqueologicas WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        header("Location: index.php");
        exit();
    } catch (PDOException $e) {
        $errorMsg = "No se pudo eliminar el registro: " . $e->getMessage();
    }
}

// Cargar dato para editar
$editRow = [];
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM piezas_arqueologicas WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $fetched = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($fetched) $editRow = $fetched;
}

// Procesar Formulario (Create / Update dinámico adaptado a cualquier esquema)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $sitio = $_POST['sitio'] ?? '';
    $coordenadas = $_POST['coordenadas'] ?? '';
    $fecha = $_POST['fecha'] ?? '';
    $conservacion = $_POST['conservacion'] ?? 'Regular';
    $descripcion = $_POST['descripcion'] ?? '';
    $id = $_POST['id'] ?? '';
    $nowDateTime = date('Y-m-d H:i:s');

    $inputMap = [
        'nombre' => $nombre,
        'objeto' => $nombre,
        'nombre_tipo' => $nombre,
        'sitio' => $sitio,
        'ubicacion' => $sitio,
        'ubicacion_sitio' => $sitio,
        'coordenadas' => $coordenadas,
        'fecha' => $fecha,
        'fecha_hallazgo' => $fecha,
        'fecha_registro' => $nowDateTime,
        'created_at' => $nowDateTime,
        'conservacion' => $conservacion,
        'estado_conservacion' => $conservacion,
        'descripcion' => $descripcion
    ];

    try {
        if (!empty($id)) {
            $sets = [];
            $vals = [];
            foreach ($tableCols as $col) {
                if ($col === 'id') continue;
                $sets[] = "$col = ?";
                $val = $inputMap[$col] ?? '';
                $type = $tableColsInfo[$col] ?? '';
                // Manejar fechas vacías
                if ($val === '' && (strpos($type, 'date') !== false || strpos($type, 'time') !== false)) {
                    $val = null;
                }
                $vals[] = $val;
            }
            $vals[] = $id;
            if (!empty($sets)) {
                $sql = "UPDATE piezas_arqueologicas SET " . implode(', ', $sets) . " WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($vals);
            }
        } else {
            $cols = [];
            $placeholders = [];
            $vals = [];
            foreach ($tableCols as $col) {
                if ($col === 'id') continue;
                $cols[] = $col;
                $placeholders[] = '?';
                $val = $inputMap[$col] ?? '';
                $type = $tableColsInfo[$col] ?? '';
                // Manejar fechas vacías en INSERT
                if ($val === '' && (strpos($type, 'date') !== false || strpos($type, 'time') !== false)) {
                    if ($col === 'fecha_registro' || $col === 'created_at') {
                        $val = $nowDateTime;
                    } else {
                        $val = null;
                    }
                }
                $vals[] = $val;
            }
            if (!empty($cols)) {
                $sql = "INSERT INTO piezas_arqueologicas (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $placeholders) . ")";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($vals);
            }
        }
        header("Location: index.php");
        exit();
    } catch (PDOException $e) {
        $errorMsg = "Error al guardar en base de datos: " . $e->getMessage();
    }
}

// Listar registros
$piezas = [];
try {
    $stmt = $pdo->query("SELECT * FROM piezas_arqueologicas ORDER BY id DESC");
    $piezas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

$editId = getVal($editRow, ['id']);
$editNombre = getVal($editRow, ['nombre', 'objeto', 'nombre_tipo']);
$editSitio = getVal($editRow, ['sitio', 'ubicacion', 'ubicacion_sitio']);
$pCoord = getVal($p, ['coordenadas']);
$editFecha = getVal($editRow, ['fecha_hallazgo', 'fecha']);
$editCons = getVal($editRow, ['conservacion', 'estado_conservacion'], 'Regular');
$editDesc = getVal($editRow, ['descripcion']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patrimonio Cultural - Registro Arqueológico</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0f172a; --primary-accent: #3b82f6; --secondary: #475569;
            --bg-body: #f8fafc; --card-bg: #ffffff; --border-color: #e2e8f0;
            --text-main: #1e293b; --text-muted: #64748b;
            --badge-excelente-bg: #dcfce7; --badge-excelente-text: #166534;
            --badge-regular-bg: #fef9c3; --badge-regular-text: #854d0e;
            --badge-frag-bg: #fee2e2; --badge-frag-text: #991b1b;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-body); color: var(--text-main); line-height: 1.5; padding: 2rem 1rem; }
        .container { max-width: 1200px; margin: 0 auto; }
        header { margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--border-color); padding-bottom: 1rem; flex-wrap: wrap; gap: 1rem; }
        header h1 { font-size: 1.75rem; font-weight: 700; color: var(--primary); }
        header span { font-size: 0.875rem; color: var(--text-muted); background: #f1f5f9; padding: 0.35rem 0.75rem; border-radius: 999px; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.875rem; }
        .dashboard-grid { display: grid; grid-template-columns: 380px 1fr; gap: 2rem; }
        @media (max-width: 968px) { .dashboard-grid { grid-template-columns: 1fr; } }
        .card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); overflow: hidden; }
        .card h2 { font-size: 1.125rem; font-weight: 600; margin-bottom: 1.25rem; color: var(--primary); }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.35rem; }
        .form-control { width: 100%; padding: 0.65rem 0.75rem; font-size: 0.875rem; border: 1px solid var(--border-color); border-radius: 8px; background: #fff; color: var(--text-main); }
        .form-control:focus { outline: none; border-color: var(--primary-accent); box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); }
        textarea.form-control { resize: vertical; min-height: 80px; }
        .btn-group-form { display: flex; gap: 0.5rem; margin-top: 1.25rem; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem; padding: 0.65rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 8px; text-decoration: none; cursor: pointer; border: none; }
        .btn-primary { background: var(--primary); color: white; width: 100%; }
        .btn-primary:hover { background: #1e293b; }
        .btn-cancel { background: #f1f5f9; color: var(--text-muted); }
        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 600px; }
        th { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); padding: 0.75rem 1rem; background: #f8fafc; border-bottom: 1px solid var(--border-color); }
        td { padding: 1rem; font-size: 0.875rem; border-bottom: 1px solid var(--border-color); vertical-align: top; }
        .obj-title { font-weight: 600; color: var(--text-main); }
        .obj-desc, .coord-box { font-size: 0.75rem; color: var(--text-muted); }
        .coord-box { font-family: monospace; }
        .badge { display: inline-flex; padding: 0.25rem 0.65rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; }
        .badge-Excelente { background: var(--badge-excelente-bg); color: var(--badge-excelente-text); }
        .badge-Regular { background: var(--badge-regular-bg); color: var(--badge-regular-text); }
        .badge-Fragmentado { background: var(--badge-frag-bg); color: var(--badge-frag-text); }
        .actions { display: flex; gap: 0.4rem; }
        .action-link { font-size: 0.75rem; font-weight: 500; padding: 0.35rem 0.65rem; border-radius: 6px; text-decoration: none; }
        .action-edit { background: #eff6ff; color: #1d4ed8; }
        .action-delete { background: #fef2f2; color: #b91c1c; }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Patrimonio Cultural</h1>
            <span>Gestión de Hallazgos Arqueológicos</span>
        </header>

        <?php if(!empty($errorMsg)): ?>
            <div class="alert-error">⚠️ <?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <div class="card">
                <h2><?= !empty($editId) ? '✏️ Editar Pieza #' . htmlspecialchars($editId) : '➕ Registrar Pieza' ?></h2>
                <form method="POST" action="index.php">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($editId) ?>">
                    <div class="form-group">
                        <label>Tipo de Objeto</label>
                        <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($editNombre) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Sitio de Hallazgo / Ubicación</label>
                        <input type="text" name="sitio" class="form-control" value="<?= htmlspecialchars($editSitio) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Coordenadas (Lat, Lon)</label>
                        <input type="text" name="coordenadas" class="form-control" value="<?= htmlspecialchars($editCoord) ?>">
                    </div>
                    <div class="form-group">
                        <label>Fecha de Hallazgo</label>
                        <input type="date" name="fecha" class="form-control" value="<?= htmlspecialchars($editFecha) ?>">
                    </div>
                    <div class="form-group">
                        <label>Conservación</label>
                        <select name="conservacion" class="form-control">
                            <option value="Excelente" <?= ($editCons == 'Excelente') ? 'selected' : '' ?>>Excelente</option>
                            <option value="Regular" <?= ($editCons == 'Regular') ? 'selected' : '' ?>>Regular</option>
                            <option value="Fragmentado" <?= ($editCons == 'Fragmentado') ? 'selected' : '' ?>>Fragmentado</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Descripción / Observaciones</label>
                        <textarea name="descripcion" class="form-control"><?= htmlspecialchars($editDesc) ?></textarea>
                    </div>
                    <div class="btn-group-form">
                        <?php if(!empty($editId)): ?>
                            <a href="index.php" class="btn btn-cancel">Cancelar</a>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary"><?= !empty($editId) ? 'Guardar Cambios' : 'Registrar Hallazgo' ?></button>
                    </div>
                </form>
            </div>

            <div class="card">
                <h2>📦 Listado de Piezas Registradas (<?= count($piezas) ?>)</h2>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Objeto / Descripción</th>
                                <th>Ubicación</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($piezas)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">No hay piezas registradas aún.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($piezas as $p): 
                                    $pId = getVal($p, ['id']);
                                    $pNom = getVal($p, ['nombre', 'objeto', 'nombre_tipo']);
                                    $pDesc = getVal($p, ['descripcion']);
                                    $pSitio = getVal($p, ['sitio', 'ubicacion', 'ubicacion_sitio']);
                                    $pCoord = getVal($p, ['coordenadas']);
                                    $pFecha = getVal($p, ['fecha_hallazgo', 'fecha']);
                                    $pCons = getVal($p, ['conservacion', 'estado_conservacion'], 'Regular');
                                ?>
                                <tr>
                                    <td style="font-weight: 600; color: var(--text-muted);">#<?= htmlspecialchars($pId) ?></td>
                                    <td>
                                        <div class="obj-title"><?= htmlspecialchars($pNom) ?></div>
                                        <div class="obj-desc"><?= htmlspecialchars($pDesc) ?></div>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($pSitio) ?></div>
                                        <div class="coord-box"><?= htmlspecialchars($pCoord) ?></div>
                                    </td>
                                    <td style="white-space: nowrap;"><?= htmlspecialchars($pFecha) ?></td>
                                    <td>
                                        <span class="badge badge-<?= htmlspecialchars($pCons) ?>"><?= htmlspecialchars($pCons) ?></span>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="index.php?edit=<?= $pId ?>" class="action-link action-edit">Editar</a>
                                            <a href="index.php?delete=<?= $pId ?>" class="action-link action-delete" onclick="return confirm('¿Seguro que deseas eliminar este registro?');">Eliminar</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>