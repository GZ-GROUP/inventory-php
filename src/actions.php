<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

session_start();

/**
 * Redirige al dashboard con un mensaje flash.
 */
function redirect(string $msg, string $type = 'success', string $extra = ''): never
{
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
    header('Location: index.php' . ($extra ? "?$extra" : ''));
    exit;
}

/**
 * Valida y sanea los campos del formulario de producto.
 * Devuelve un array con los datos o lanza un error flash.
 */
function parseProductForm(): array
{
    $nombre    = trim($_POST['nombre']    ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $precio    = trim($_POST['precio']    ?? '');
    $cantidad  = trim($_POST['cantidad']  ?? '');

    if ($nombre === '' || $categoria === '') {
        redirect('El nombre y la categoría son obligatorios.', 'error');
    }
    if (!is_numeric($precio) || (float)$precio < 0) {
        redirect('El precio debe ser un número válido mayor o igual a 0.', 'error');
    }
    if (!ctype_digit($cantidad) && !(is_numeric($cantidad) && (int)$cantidad >= 0)) {
        redirect('La cantidad debe ser un entero mayor o igual a 0.', 'error');
    }

    return [
        'nombre'    => htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'),
        'categoria' => $categoria,
        'precio'    => round((float)$precio, 2),
        'cantidad'  => (int)$cantidad,
    ];
}

// ── Verificar método ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('Acceso no permitido.', 'error');
}

$action = trim($_POST['action'] ?? '');

try {
    $pdo = getConnection();

    // ── CREATE ────────────────────────────────────────────────────────────────
    if ($action === 'create') {
        $data = parseProductForm();
        $stmt = $pdo->prepare(
            'INSERT INTO productos (nombre, categoria, precio, cantidad)
             VALUES (:nombre, :categoria, :precio, :cantidad)'
        );
        $stmt->execute($data);
        redirect('Producto <strong>' . $data['nombre'] . '</strong> registrado correctamente.');
    }

    // ── UPDATE ────────────────────────────────────────────────────────────────
    elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            redirect('ID de producto inválido.', 'error');
        }
        $data       = parseProductForm();
        $data['id'] = $id;
        $stmt = $pdo->prepare(
            'UPDATE productos
             SET nombre = :nombre, categoria = :categoria,
                 precio = :precio, cantidad = :cantidad
             WHERE id = :id'
        );
        $stmt->execute($data);
        if ($stmt->rowCount() === 0) {
            redirect('No se encontró el producto a actualizar.', 'error');
        }
        redirect('Producto <strong>' . $data['nombre'] . '</strong> actualizado correctamente.');
    }

    // ── DELETE ────────────────────────────────────────────────────────────────
    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            redirect('ID de producto inválido.', 'error');
        }
        // Recuperar nombre antes de borrar para el mensaje
        $stmtNombre = $pdo->prepare('SELECT nombre FROM productos WHERE id = ?');
        $stmtNombre->execute([$id]);
        $prod = $stmtNombre->fetch();
        if (!$prod) {
            redirect('El producto no existe.', 'error');
        }
        $pdo->prepare('DELETE FROM productos WHERE id = ?')->execute([$id]);
        redirect('Producto <strong>' . htmlspecialchars($prod['nombre']) . '</strong> eliminado.', 'danger');
    }

    else {
        redirect('Acción desconocida.', 'error');
    }
} catch (PDOException $e) {
    redirect('Error de base de datos: ' . htmlspecialchars($e->getMessage()), 'error');
}