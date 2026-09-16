<?php
// ==========================================
// 1. CONEXIÓN Y TABLA (SQLite local)
// ==========================================
$pdo = new PDO('sqlite:database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$pdo->exec("CREATE TABLE IF NOT EXISTS usuarios_gestion (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    rol TEXT NOT NULL,
    estado TEXT NOT NULL DEFAULT 'Activo',
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// ==========================================
// 2. LÓGICA DE NEGOCIO (CRUD)
// ==========================================

// C: CREAR USUARIO
if (isset($_POST['crear'])) {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $rol = $_POST['rol'];
    $estado = $_POST['estado'];

    try {
        $stmt = $pdo->prepare("INSERT INTO usuarios_gestion (nombre, email, password, rol, estado) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $email, $password, $rol, $estado]);
    } catch (PDOException $e) {
        $error = "El correo electrónico ya está registrado.";
    }
    if (!isset($error)) { header("Location: user_management.php"); exit; }
}

// U: ACTUALIZAR USUARIO
if (isset($_POST['actualizar'])) {
    $id = $_POST['id'];
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $rol = $_POST['rol'];
    $estado = $_POST['estado'];

    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE usuarios_gestion SET nombre = ?, email = ?, password = ?, rol = ?, estado = ? WHERE id = ?");
        $stmt->execute([$nombre, $email, $password, $rol, $estado, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE usuarios_gestion SET nombre = ?, email = ?, rol = ?, estado = ? WHERE id = ?");
        $stmt->execute([$nombre, $email, $rol, $estado, $id]);
    }
    header("Location: user_management.php");
    exit;
}

// CAMBIAR ESTADO RÁPIDO (Activar / Desactivar)
if (isset($_GET['toggle_status'])) {
    $id = $_GET['toggle_status'];
    $nuevo_estado = $_GET['actual'] === 'Activo' ? 'Inactivo' : 'Activo';
    $stmt = $pdo->prepare("UPDATE usuarios_gestion SET estado = ? WHERE id = ?");
    $stmt->execute([$nuevo_estado, $id]);
    header("Location: user_management.php");
    exit;
}

// D: ELIMINAR USUARIO
if (isset($_GET['eliminar'])) {
    $stmt = $pdo->prepare("DELETE FROM usuarios_gestion WHERE id = ?");
    $stmt->execute([$_GET['eliminar']]);
    header("Location: user_management.php");
    exit;
}

// R: FILTROS Y BÚSQUEDA
$busqueda = $_GET['q'] ?? '';
$filtro_rol = $_GET['f_rol'] ?? '';
$filtro_estado = $_GET['f_estado'] ?? '';

$sql = "SELECT id, nombre, email, rol, estado, creado_en FROM usuarios_gestion WHERE (nombre LIKE ? OR email LIKE ?)";
$params = ["%$busqueda%", "%$busqueda%"];

if ($filtro_rol != '') {
    $sql .= " AND rol = ?";
    $params[] = $filtro_rol;
}
if ($filtro_estado != '') {
    $sql .= " AND estado = ?";
    $params[] = $filtro_estado;
}

$sql .= " ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();

// Cargar usuario para edición
$u_editar = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios_gestion WHERE id = ?");
    $stmt->execute([$_GET['editar']]);
    $u_editar = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management System</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 1000px; margin: 30px auto; padding: 0 20px; background: #f8f9fa; color: #333; }
        .card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.08); margin-bottom: 20px; }
        .grid-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; margin-bottom: 10px; }
        input, select { padding: 9px; border: 1px solid #ced4da; border-radius: 4px; width: 100%; box-sizing: border-box; }
        button { padding: 9px 18px; background: #0d6efd; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: 500; }
        button:hover { background: #0b5ed7; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.08); }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6; }
        th { background: #212529; color: white; }
        .badge { padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; }
        .bg-activo { background: #d1e7dd; color: #0f5132; }
        .bg-inactivo { background: #f8d7da; color: #842029; }
        .actions a { margin-right: 8px; text-decoration: none; color: #0d6efd; font-size: 0.9em; }
        .actions a.danger { color: #dc3545; }
        .error { color: #dc3545; margin-bottom: 10px; }
    </style>
</head>
<body>

    <h2>User Management System</h2>

    <?php if (isset($error)): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <!-- FORMULARIO CREAR / EDITAR -->
    <div class="card">
        <h3><?= $u_editar ? 'Editar Usuario #' . $u_editar['id'] : 'Registrar Nuevo Usuario' ?></h3>
        <form method="POST">
            <?php if ($u_editar): ?>
                <input type="hidden" name="id" value="<?= $u_editar['id'] ?>">
            <?php endif; ?>

            <div class="grid-form">
                <input type="text" name="nombre" placeholder="Nombre completo" value="<?= $u_editar['nombre'] ?? '' ?>" required>
                <input type="email" name="email" placeholder="Correo electrónico" value="<?= $u_editar['email'] ?? '' ?>" required>
                <input type="password" name="password" placeholder="<?= $u_editar ? 'Nueva Contraseña (opcional)' : 'Contraseña' ?>" <?= $u_editar ? '' : 'required' ?>>
                
                <select name="rol" required>
                    <option value="">-- Seleccionar Rol --</option>
                    <option value="Administrador" <?= (isset($u_editar) && $u_editar['rol'] == 'Administrador') ? 'selected' : '' ?>>Administrador</option>
                    <option value="Editor" <?= (isset($u_editar) && $u_editar['rol'] == 'Editor') ? 'selected' : '' ?>>Editor</option>
                    <option value="Usuario" <?= (isset($u_editar) && $u_editar['rol'] == 'Usuario') ? 'selected' : '' ?>>Usuario</option>
                </select>

                <select name="estado" required>
                    <option value="Activo" <?= (isset($u_editar) && $u_editar['estado'] == 'Activo') ? 'selected' : '' ?>>Activo</option>
                    <option value="Inactivo" <?= (isset($u_editar) && $u_editar['estado'] == 'Inactivo') ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>

            <?php if ($u_editar): ?>
                <button type="submit" name="actualizar">Guardar Cambios</button>
                <a href="user_management.php" style="margin-left: 10px;">Cancelar</a>
            <?php else: ?>
                <button type="submit" name="crear">Crear Usuario</button>
            <?php endif; ?>
        </form>
    </div>

    <!-- FILTROS Y BÚSQUEDA -->
    <div class="card">
        <form method="GET" class="grid-form" style="margin-bottom: 0;">
            <input type="text" name="q" placeholder="Buscar por nombre o email..." value="<?= htmlspecialchars($busqueda) ?>">
            <select name="f_rol">
                <option value="">Todos los Roles</option>
                <option value="Administrador" <?= $filtro_rol == 'Administrador' ? 'selected' : '' ?>>Administrador</option>
                <option value="Editor" <?= $filtro_rol == 'Editor' ? 'selected' : '' ?>>Editor</option>
                <option value="Usuario" <?= $filtro_rol == 'Usuario' ? 'selected' : '' ?>>Usuario</option>
            </select>
            <select name="f_estado">
                <option value="">Todos los Estados</option>
                <option value="Activo" <?= $filtro_estado == 'Activo' ? 'selected' : '' ?>>Activo</option>
                <option value="Inactivo" <?= $filtro_estado == 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
            </select>
            <button type="submit">Filtrar</button>
        </form>
    </div>

    <!-- TABLA DE USUARIOS -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>Registro</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($u['nombre']) ?></strong><br>
                        <small style="color: #6c757d;"><?= htmlspecialchars($u['email']) ?></small>
                    </td>
                    <td><?= $u['rol'] ?></td>
                    <td>
                        <span class="badge <?= $u['estado'] == 'Activo' ? 'bg-activo' : 'bg-inactivo' ?>">
                            <?= $u['estado'] ?>
                        </span>
                    </td>
                    <td><small><?= $u['creado_en'] ?></small></td>
                    <td class="actions">
                        <a href="user_management.php?editar=<?= $u['id'] ?>">Editar</a>
                        <a href="user_management.php?toggle_status=<?= $u['id'] ?>&actual=<?= $u['estado'] ?>">
                            <?= $u['estado'] == 'Activo' ? 'Desactivar' : 'Activar' ?>
                        </a>
                        <a href="user_management.php?eliminar=<?= $u['id'] ?>" class="danger" onclick="return confirm('¿Eliminar usuario definitivamente?')">Borrar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($usuarios)): ?>
                <tr><td colspan="6" style="text-align: center;">No se encontraron usuarios.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>