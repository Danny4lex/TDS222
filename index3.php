<?php
// Conexión y tabla automática de Usuarios
$pdo = new PDO('sqlite:database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    email TEXT NOT NULL,
    rol TEXT NOT NULL
)");

// C: CREATE
if (isset($_POST['crear'])) {
    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, rol) VALUES (?, ?, ?)");
    $stmt->execute([$_POST['nombre'], $_POST['email'], $_POST['rol']]);
    header("Location: index2.php");
    exit;
}

// U: UPDATE
if (isset($_POST['actualizar'])) {
    $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ?, rol = ? WHERE id = ?");
    $stmt->execute([$_POST['nombre'], $_POST['email'], $_POST['rol'], $_POST['id']]);
    header("Location: index2.php");
    exit;
}

// D: DELETE
if (isset($_GET['eliminar'])) {
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute([$_GET['eliminar']]);
    header("Location: index2.php");
    exit;
}

// R: READ
$usuarios = $pdo->query("SELECT * FROM usuarios")->fetchAll();

$usuario_editar = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$_GET['editar']]);
    $usuario_editar = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios (index2.php)</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 800px; margin: 30px auto; padding: 0 20px; background: #f4f4f9; }
        h2 { color: #333; }
        form { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        input, select { padding: 8px; margin-right: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; }
        button { padding: 8px 16px; background: #28a745; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #218838; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #28a745; color: white; }
        a { color: #007bff; text-decoration: none; }
        a.delete { color: #dc3545; }
    </style>
</head>
<body>

    <h2><?= $usuario_editar ? 'Editar Usuario' : 'Nuevo Usuario' ?></h2>

    <form method="POST">
        <?php if ($usuario_editar): ?>
            <input type="hidden" name="id" value="<?= $usuario_editar['id'] ?>">
        <?php endif; ?>

        <input type="text" name="nombre" placeholder="Nombre completo" value="<?= $usuario_editar['nombre'] ?? '' ?>" required>
        <input type="email" name="email" placeholder="Correo electrónico" value="<?= $usuario_editar['email'] ?? '' ?>" required>
        
        <select name="rol" required>
            <option value="">Seleccionar Rol</option>
            <option value="Admin" <?= (isset($usuario_editar) && $usuario_editar['rol'] == 'Admin') ? 'selected' : '' ?>>Admin</option>
            <option value="Usuario" <?= (isset($usuario_editar) && $usuario_editar['rol'] == 'Usuario') ? 'selected' : '' ?>>Usuario</option>
        </select>

        <?php if ($usuario_editar): ?>
            <button type="submit" name="actualizar">Actualizar</button>
            <a href="index2.php">Cancelar</a>
        <?php else: ?>
            <button type="submit" name="crear">Guardar</button>
        <?php endif; ?>
    </form>

    <h2>Lista de Usuarios</h2>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['nombre']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['rol']) ?></td>
                    <td>
                        <a href="index2.php?editar=<?= $u['id'] ?>">Editar</a> | 
                        <a href="index2.php?eliminar=<?= $u['id'] ?>" class="delete" onclick="return confirm('¿Eliminar usuario?')">Eliminar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($usuarios)): ?>
                <tr><td colspan="5" style="text-align: center;">No hay usuarios registrados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>