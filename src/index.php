<?php
$host = 'db';
$dbname = 'db_arqueologia';
$user = 'user_arq';
$pass = 'pass_arq_123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Eliminar registro
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM piezas_arqueologicas WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: index.php");
    exit();
}

// Registrar o actualizar
$editRow = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['id'])) {
        // Update
        $stmt = $pdo->prepare("UPDATE piezas_arqueologicas SET nombre_tipo=?, ubicacion_sitio=?, coordenadas=?, fecha_hallazgo=?, descripcion=?, estado_conservacion=? WHERE id=?");
        $stmt->execute([
            $_POST['nombre_tipo'], $_POST['ubicacion_sitio'], $_POST['coordenadas'],
            $_POST['fecha_hallazgo'], $_POST['descripcion'], $_POST['estado_conservacion'], $_POST['id']
        ]);
    } else {
        // Create
        $stmt = $pdo->prepare("INSERT INTO piezas_arqueologicas (nombre_tipo, ubicacion_sitio, coordenadas, fecha_hallazgo, descripcion, estado_conservacion) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['nombre_tipo'], $_POST['ubicacion_sitio'], $_POST['coordenadas'],
            $_POST['fecha_hallazgo'], $_POST['descripcion'], $_POST['estado_conservacion']
        ]);
    }
    header("Location: index.php");
    exit();
}

// Cargar para editar
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM piezas_arqueologicas WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editRow = $stmt->fetch(PDO::FETCH_ASSOC);
}

$piezas = $pdo->query("SELECT * FROM piezas_arqueologicas ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Hallazgos Arqueológicos</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f4f6f8; }
        .container { max-width: 1000px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        form { display: grid; gap: 10px; margin-bottom: 20px; background: #eef2f5; padding: 15px; border-radius: 6px; }
        input, textarea, select { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        button { background: #0056b3; color: white; border: none; padding: 10px; border-radius: 4px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 14px; }
        th { background: #0056b3; color: white; }
        a { text-decoration: none; padding: 4px 8px; border-radius: 3px; font-size: 12px; }
        .btn-edit { background: #f0ad4e; color: white; }
        .btn-delete { background: #d9534f; color: white; }
    </style>
</head>
<body>
<div class="container">
    <h2>Patrimonio Cultural - Registro de Hallazgos Arqueológicos</h2>
    
    <form method="POST">
        <input type="hidden" name="id" value="<?= $editRow['id'] ?? '' ?>">
        <input type="text" name="nombre_tipo" placeholder="Nombre / Tipo de objeto (ej. Vasija)" required value="<?= $editRow['nombre_tipo'] ?? '' ?>">
        <input type="text" name="ubicacion_sitio" placeholder="Nombre del sitio" required value="<?= $editRow['ubicacion_sitio'] ?? '' ?>">
        <input type="text" name="coordenadas" placeholder="Coordenadas (Lat, Lon)" required value="<?= $editRow['coordenadas'] ?? '' ?>">
        <input type="date" name="fecha_hallazgo" required value="<?= $editRow['fecha_hallazgo'] ?? '' ?>">
        <select name="estado_conservacion" required>
            <option value="Excelente" <?= (isset($editRow['estado_conservacion']) && $editRow['estado_conservacion']=='Excelente')?'selected':'' ?>>Excelente</option>
            <option value="Regular" <?= (isset($editRow['estado_conservacion']) && $editRow['estado_conservacion']=='Regular')?'selected':'' ?>>Regular</option>
            <option value="Fragmentado" <?= (isset($editrow['estado_conservacion']) && $editrow['estado_conservacion']=='Fragmentado')?'selected':'' ?>>Fragmentado</option>
        </select>
        <textarea name="descripcion" placeholder="Descripción de la pieza" required><%= $editRow['descripcion'] ?? '' ?></textarea>
        <button type="submit"><?= isset($editRow['id']) ? 'Actualizar Pieza' : 'Registrar Pieza' ?></button>
    </form>

    <h3>Listado de Piezas Registradas</h3>
    <table>
        <tr>
            <th>ID</th>
            <th>Objeto</th>
            <th>Sitio / Coordenadas</th>
            <th>Fecha</th>
            <th>Conservación</th>
            <th>Acciones</th>
        </tr>
        <?php foreach ($piezas as $p): ?>
        <tr>
            <td><?= $p['id'] ?></td>
            <td><strong><?= htmlspecialchars($p['nombre_tipo']) ?></strong><br><small><?= htmlspecialchars($p['descripcion']) ?></small></td>
            <td><?= htmlspecialchars($p['ubicacion_sitio']) ?><br><small><?= htmlspecialchars($p['coordenadas']) ?></small></td>
            <td><?= $p['fecha_hallazgo'] ?></td>
            <td><?= $p['estado_conservacion'] ?></td>
            <td>
                <a class="btn-edit" href="?edit=<?= $p['id'] ?>">Editar</a>
                <a class="btn-delete" href="?delete=<?= $p['id'] ?>" onclick="return confirm('¿Eliminar registro?');">Eliminar</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>