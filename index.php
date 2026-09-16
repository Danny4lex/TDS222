<?php
// ==========================================
// 1. CONEXIÓN A BASE DE DATOS
// ==========================================
// Usamos SQLite en un archivo local para ejecutar sin configurar MySQL.
// Si prefieres MySQL, reemplaza esta línea por:
// $pdo = new PDO('mysql:host=localhost;dbname=demo_crud;charset=utf8', 'root', '');
$pdo = new PDO('sqlite:database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Crear tabla automáticamente si no existe
$pdo->exec("CREATE TABLE IF NOT EXISTS productos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    precio REAL NOT NULL
)");

// ==========================================
// 2. LÓGICA DEL CRUD (POST / GET)
// ==========================================

// C: CREATE (Insertar)
if (isset($_POST['crear'])) {
    $stmt = $pdo->prepare("INSERT INTO productos (nombre, precio) VALUES (?, ?)");
    $stmt->execute([$_POST['nombre'], $_POST['precio']]);
    header("Location: index.php");
    exit;
}

// U: UPDATE (Actualizar)
if (isset($_POST['actualizar'])) {
    $stmt = $pdo->prepare("UPDATE productos SET nombre = ?, precio = ? WHERE id = ?");
    $stmt->execute([$_POST['nombre'], $_POST['precio'], $_POST['id']]);
    header("Location: index.php");
    exit;
}

// D: DELETE (Eliminar)
if (isset($_GET['eliminar'])) {
    $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
    $stmt->execute([$_GET['eliminar']]);
    header("Location: index.php");
    exit;
}

// R: READ (Cargar datos para la tabla)
$productos = $pdo->query("SELECT * FROM productos")->fetchAll();

// Cargar registro específico si se dio clic en "Editar"
$producto_editar = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
    $stmt->execute([$_GET['editar']]);
    $producto_editar = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD PHP Todo en Uno</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 800px; margin: 30px auto; padding: 0 20px; background: #f4f4f9; }
        h2 { color: #333; }
        form { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        input { padding: 8px; margin-right: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; }
        button { padding: 8px 16px; background: #007bff; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #007bff; color: white; }
        a { color: #007bff; text-decoration: none; }
        a.delete { color: #dc3545; }
    </style>
</head>
<body>

    <h2><?= $producto_editar ? 'Editar Producto' : 'Nuevo Producto' ?></h2>

    <!-- Formulario para Crear / Editar -->
    <form method="POST">
        <?php if ($producto_editar): ?>
            <input type="hidden" name="id" value="<?= $producto_editar['id'] ?>">
        <?php endif; ?>

        <input type="text" name="nombre" placeholder="Nombre" value="<?= $producto_editar['nombre'] ?? '' ?>" required>
        <input type="number" step="0.01" name="precio" placeholder="Precio" value="<?= $producto_editar['precio'] ?? '' ?>" required>

        <?php if ($producto_editar): ?>
            <button type="submit" name="actualizar">Actualizar</button>
            <a href="index.php">Cancelar</a>
        <?php else: ?>
            <button type="submit" name="crear">Guardar</button>
        <?php endif; ?>
    </form>

    <h2>Inventario</h2>

    <!-- Tabla para Mostrar / Acciones -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Precio</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($productos as $p): ?>
                <tr>
                    <td><?= $p['id'] ?></td>
                    <td><?= htmlspecialchars($p['nombre']) ?></td>
                    <td>$<?= number_format($p['precio'], 2) ?></td>
                    <td>
                        <a href="index.php?editar=<?= $p['id'] ?>">Editar</a> | 
                        <a href="index.php?eliminar=<?= $p['id'] ?>" class="delete" onclick="return confirm('¿Eliminar registro?')">Eliminar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($productos)): ?>
                <tr><td colspan="4" style="text-align: center;">No hay registros disponibles.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>