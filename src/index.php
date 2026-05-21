<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

session_start();

// ── Flash message ─────────────────────────────────────────────────────────────
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// ── DB Connection ─────────────────────────────────────────────────────────────
try {
    $pdo = getConnection();
} catch (Throwable $e) {
    die('
      <div style="font-family:sans-serif;padding:3rem;text-align:center;">
        <h2 style="color:#c0392b;">Error de conexión a la base de datos</h2>
        <p style="color:#555;">' . htmlspecialchars($e->getMessage()) . '</p>
      </div>');
}

// ── Filters & Pagination ──────────────────────────────────────────────────────
$perPage   = 10;
$page      = max(1, (int)($_GET['pagina'] ?? 1));
$offset    = ($page - 1) * $perPage;
$buscar    = trim($_GET['buscar']    ?? '');
$categoria = trim($_GET['categoria'] ?? '');

$where  = [];
$params = [];
if ($buscar !== '') {
    $where[]           = 'nombre ILIKE :buscar';
    $params[':buscar'] = '%' . $buscar . '%';
}
if ($categoria !== '') {
    $where[]              = 'categoria = :categoria';
    $params[':categoria'] = $categoria;
}
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Total para paginación
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM productos $whereSQL");
$stmtCount->execute($params);
$total      = (int)$stmtCount->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

// Listado de productos
$stmtProd = $pdo->prepare(
    "SELECT * FROM productos $whereSQL ORDER BY id ASC LIMIT :lim OFFSET :off"
);
foreach ($params as $k => $v) {
    $stmtProd->bindValue($k, $v);
}
$stmtProd->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmtProd->bindValue(':off', $offset,  PDO::PARAM_INT);
$stmtProd->execute();
$productos = $stmtProd->fetchAll();

// Estadísticas generales
$stats = $pdo->query(
    "SELECT
        COUNT(*)                                          AS total,
        COUNT(DISTINCT categoria)                         AS categorias,
        COALESCE(SUM(precio * cantidad), 0)               AS valor_total,
        SUM(CASE WHEN cantidad <= 3 THEN 1 ELSE 0 END)   AS stock_bajo
     FROM productos"
)->fetch();

// ── Producto a editar (pre-carga modal) ───────────────────────────────────────
$editProduct = null;
if (!empty($_GET['editar'])) {
    $s = $pdo->prepare('SELECT * FROM productos WHERE id = ?');
    $s->execute([(int)$_GET['editar']]);
    $editProduct = $s->fetch() ?: null;
}

// ── Producto a eliminar (pre-carga modal) ─────────────────────────────────────
$delProduct = null;
if (!empty($_GET['eliminar'])) {
    $s = $pdo->prepare('SELECT * FROM productos WHERE id = ?');
    $s->execute([(int)$_GET['eliminar']]);
    $delProduct = $s->fetch() ?: null;
}

// ── Helpers ───────────────────────────────────────────────────────────────────
$categorias = ['Electrónica', 'Mobiliario', 'Papelería', 'Laboratorio', 'Otros'];

$catClass = [
    'Electrónica' => 'cat-electronica',
    'Mobiliario'  => 'cat-mobiliario',
    'Papelería'   => 'cat-papeleria',
    'Laboratorio' => 'cat-laboratorio',
    'Otros'       => 'cat-otros',
];

function e(mixed $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function buildUrl(array $overrides = []): string
{
    $params = array_merge(
        array_filter([
            'buscar'    => $_GET['buscar']    ?? '',
            'categoria' => $_GET['categoria'] ?? '',
            'pagina'    => $_GET['pagina']    ?? '',
        ]),
        $overrides
    );
    return 'index.php' . ($params ? '?' . http_build_query($params) : '');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Inventario UTP — Sistema CRUD</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,400&display=swap" rel="stylesheet"/>
  <style>
    :root {
      --brand:     #1a3a5c;
      --brand-mid: #2563a8;
      --accent:    #e8a020;
      --accent-lt: #fef3db;
      --surface:   #f4f6f9;
      --card:      #ffffff;
      --text:      #0f1e2e;
      --muted:     #637898;
      --danger:    #c0392b;
      --success:   #1e7a4e;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--surface);
      color: var(--text);
      min-height: 100vh;
    }
    h1, h2, h3 { font-family: 'DM Serif Display', serif; }

    /* ── Layout ── */
    .wrapper { max-width: 1280px; margin: 0 auto; padding: 2rem 2.5rem; }

    /* ── Topbar ── */
    .topbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 2rem;
      gap: 1rem;
      flex-wrap: wrap;
    }
    .topbar-brand { display: flex; align-items: center; gap: 1rem; }
    .brand-badge {
      background: var(--brand);
      color: #fff;
      border-radius: 10px;
      padding: .5rem .85rem;
      font-size: .7rem;
      font-weight: 700;
      letter-spacing: .1em;
      text-transform: uppercase;
    }
    .topbar h1 { font-size: 1.9rem; color: var(--brand); line-height: 1; }
    .topbar p  { color: var(--muted); font-size: .85rem; margin-top: .2rem; }

    /* ── Stat cards ── */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1.25rem;
      margin-bottom: 2rem;
    }
    .stat-card {
      background: var(--card);
      border-radius: 14px;
      padding: 1.3rem 1.5rem;
      border: 1px solid #e3e8ef;
      position: relative;
      overflow: hidden;
    }
    .stat-card::after {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0; height: 3px;
      border-radius: 14px 14px 0 0;
      background: var(--brand-mid);
    }
    .stat-card.accent::after  { background: var(--accent);  }
    .stat-card.success::after { background: var(--success); }
    .stat-card.danger::after  { background: var(--danger);  }
    .stat-label { font-size: .72rem; text-transform: uppercase; letter-spacing: .09em; color: var(--muted); font-weight: 600; }
    .stat-value { font-family: 'DM Serif Display', serif; font-size: 2.1rem; color: var(--brand); line-height: 1.1; margin: .35rem 0 .2rem; }
    .stat-sub   { font-size: .77rem; color: var(--muted); }

    /* ── Table card ── */
    .table-card { background: var(--card); border-radius: 14px; border: 1px solid #e3e8ef; overflow: hidden; margin-bottom: 2rem; }
    .table-header {
      padding: 1.25rem 1.5rem;
      border-bottom: 1px solid #e3e8ef;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      flex-wrap: wrap;
    }
    .table-header h3 { font-size: 1.1rem; color: var(--brand); }
    .search-group  { display: flex; gap: .65rem; align-items: center; flex-wrap: wrap; }
    .search-input  {
      border: 1.5px solid #d1dae8; border-radius: 8px;
      padding: .5rem .9rem; font-size: .85rem; font-family: inherit;
      outline: none; width: 210px; color: var(--text);
      transition: border .15s;
    }
    .search-input:focus { border-color: var(--brand-mid); }
    .select-filter {
      border: 1.5px solid #d1dae8; border-radius: 8px;
      padding: .5rem .9rem; font-size: .85rem; font-family: inherit;
      color: var(--text); background: #fff; cursor: pointer; outline: none;
    }
    .select-filter:focus { border-color: var(--brand-mid); }

    table { width: 100%; border-collapse: collapse; }
    thead tr { background: #f8fafc; }
    th {
      text-align: left; padding: .75rem 1.25rem;
      font-size: .7rem; text-transform: uppercase; letter-spacing: .09em;
      color: var(--muted); font-weight: 600; border-bottom: 1px solid #e3e8ef;
    }
    td {
      padding: .9rem 1.25rem; font-size: .87rem;
      border-bottom: 1px solid #f0f3f8; vertical-align: middle;
    }
    tr:last-child td { border-bottom: none; }
    tbody tr:hover { background: #f8fafc; }

    .badge-cat {
      display: inline-block; padding: .2rem .65rem;
      border-radius: 999px; font-size: .72rem; font-weight: 600; letter-spacing: .04em;
    }
    .cat-electronica { background: #dbeafe; color: #1e40af; }
    .cat-mobiliario  { background: #d1fae5; color: #065f46; }
    .cat-papeleria   { background: #fef9c3; color: #854d0e; }
    .cat-laboratorio { background: #ede9fe; color: #4c1d95; }
    .cat-otros       { background: #f1f5f9; color: #475569; }

    .stock-low { color: var(--danger);  font-weight: 700; }
    .stock-ok  { color: var(--success); font-weight: 700; }

    .action-btn {
      padding: .35rem .7rem; border-radius: 7px; border: none;
      font-size: .78rem; font-weight: 600; cursor: pointer;
      font-family: inherit; transition: opacity .15s; text-decoration: none;
      display: inline-flex; align-items: center; gap: .3rem;
    }
    .action-btn:hover { opacity: .8; }
    .btn-edit   { background: #dbeafe; color: #1d4ed8; }
    .btn-delete { background: #fee2e2; color: #b91c1c; }

    /* ── Buttons ── */
    .btn-primary {
      background: var(--brand); color: #fff; border: none;
      border-radius: 9px; padding: .6rem 1.25rem;
      font-size: .875rem; font-weight: 600; cursor: pointer;
      font-family: inherit; display: inline-flex; align-items: center;
      gap: .45rem; transition: background .15s; text-decoration: none;
    }
    .btn-primary:hover { background: var(--brand-mid); }
    .btn-outline {
      background: transparent; color: var(--brand);
      border: 1.5px solid var(--brand); border-radius: 9px;
      padding: .55rem 1.1rem; font-size: .875rem; font-weight: 600;
      cursor: pointer; font-family: inherit; display: inline-flex;
      align-items: center; gap: .4rem; transition: all .15s; text-decoration: none;
    }
    .btn-outline:hover { background: var(--brand); color: #fff; }
    .btn-danger {
      background: var(--danger); color: #fff; border: none;
      border-radius: 9px; padding: .6rem 1.25rem;
      font-size: .875rem; font-weight: 600; cursor: pointer; font-family: inherit;
    }
    .btn-filter {
      background: transparent; color: var(--brand);
      border: 1.5px solid #d1dae8; border-radius: 8px;
      padding: .48rem .85rem; font-size: .85rem; font-weight: 600;
      cursor: pointer; font-family: inherit; transition: all .15s;
      display: inline-flex; align-items: center; gap: .35rem;
    }
    .btn-filter:hover { border-color: var(--brand); }

    /* ── Pagination ── */
    .pagination {
      display: flex; align-items: center; justify-content: space-between;
      padding: 1rem 1.5rem; border-top: 1px solid #e3e8ef;
      font-size: .83rem; color: var(--muted); flex-wrap: wrap; gap: .75rem;
    }
    .page-btns { display: flex; gap: .4rem; }
    .page-btn {
      width: 32px; height: 32px; border-radius: 7px;
      border: 1.5px solid #d1dae8; background: #fff;
      font-size: .83rem; font-weight: 600; cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      transition: all .15s; text-decoration: none; color: var(--text);
    }
    .page-btn:hover { border-color: var(--brand-mid); color: var(--brand-mid); }
    .page-btn.active { background: var(--brand); color: #fff; border-color: var(--brand); pointer-events: none; }
    .page-btn.disabled { opacity: .4; pointer-events: none; }

    /* ── Modal ── */
    .modal-overlay {
      position: fixed; inset: 0;
      background: rgba(10,20,40,.5); backdrop-filter: blur(3px);
      z-index: 100; display: flex; align-items: center; justify-content: center;
      opacity: 0; pointer-events: none; transition: opacity .2s;
    }
    .modal-overlay.open { opacity: 1; pointer-events: all; }
    .modal-box {
      background: var(--card); border-radius: 16px; width: 520px; max-width: 95vw;
      box-shadow: 0 24px 60px rgba(0,0,0,.18);
      transform: translateY(16px); transition: transform .25s ease;
    }
    .modal-overlay.open .modal-box { transform: translateY(0); }
    .modal-head {
      padding: 1.5rem 1.75rem 1rem; border-bottom: 1px solid #e3e8ef;
      display: flex; align-items: center; justify-content: space-between;
    }
    .modal-head h3 { font-size: 1.2rem; color: var(--brand); }
    .modal-close {
      width: 32px; height: 32px; border-radius: 50%; border: none;
      background: #f0f3f8; cursor: pointer; font-size: 1rem;
      display: flex; align-items: center; justify-content: center; transition: background .15s;
    }
    .modal-close:hover { background: #e3e8ef; }
    .modal-body { padding: 1.25rem 1.75rem; }
    .modal-foot { padding: 1rem 1.75rem 1.5rem; display: flex; justify-content: flex-end; gap: .75rem; }

    .form-group  { margin-bottom: 1.1rem; }
    .form-label  {
      display: block; font-size: .75rem; font-weight: 600;
      letter-spacing: .05em; text-transform: uppercase;
      color: var(--muted); margin-bottom: .4rem;
    }
    .form-input, .form-select {
      width: 100%; border: 1.5px solid #d1dae8; border-radius: 9px;
      padding: .6rem .9rem; font-size: .9rem; font-family: inherit;
      color: var(--text); outline: none; transition: border .15s, box-shadow .15s;
      background: #fff;
    }
    .form-input:focus, .form-select:focus {
      border-color: var(--brand-mid);
      box-shadow: 0 0 0 3px rgba(37,99,168,.1);
    }
    .form-row  { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .form-hint { font-size: .74rem; color: var(--muted); margin-top: .3rem; }
    .form-warn {
      background: var(--accent-lt); border-radius: 8px;
      padding: .75rem 1rem; font-size: .8rem; color: #7c5a00;
      display: flex; gap: .5rem; align-items: flex-start; margin-top: .5rem;
    }

    /* ── Delete modal ── */
    .delete-icon {
      width: 56px; height: 56px; border-radius: 50%; background: #fee2e2;
      display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;
    }

    /* ── Flash / Alert ── */
    .flash-bar {
      border-radius: 10px; padding: .85rem 1.25rem;
      display: flex; align-items: center; gap: .75rem;
      font-size: .87rem; font-weight: 500; margin-bottom: 1.5rem;
      border-left: 4px solid;
    }
    .flash-success { background: #f0fdf4; border-color: var(--success); color: #14532d; }
    .flash-danger  { background: #fef2f2; border-color: var(--danger);  color: #7f1d1d; }
    .flash-error   { background: #fef2f2; border-color: var(--danger);  color: #7f1d1d; }
    .flash-dismiss {
      margin-left: auto; background: none; border: none; cursor: pointer;
      font-size: 1rem; color: inherit; opacity: .6; padding: 0 .25rem;
    }

    /* ── Empty state ── */
    .empty-state { text-align: center; padding: 3rem 1rem; color: var(--muted); }
    .empty-state svg { opacity: .3; margin-bottom: .75rem; }
    .empty-state p { font-size: .9rem; }

    /* ── Responsive ── */
    @media (max-width: 900px) {
      .wrapper { padding: 1.25rem; }
      .stats-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 560px) {
      .stats-grid { grid-template-columns: 1fr; }
      .search-group { flex-direction: column; align-items: stretch; }
      .search-input { width: 100%; }
      .form-row { grid-template-columns: 1fr; }
    }

    /* ── Scrollbar ── */
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #c5d0de; border-radius: 999px; }
  </style>
</head>
<body>

<div class="wrapper">

  <!-- ── Topbar ── -->
  <div class="topbar">
    <div>
      <div class="topbar-brand">
        <span class="brand-badge">UTP</span>
        <h1>Inventario</h1>
      </div>
      <p>Sistema CRUD de Inventario Académico &middot;
        <?= (new DateTime())->format('d') ?> de
        <?= strftime_es((new DateTime())->format('n')) ?> de
        <?= (new DateTime())->format('Y') ?>
      </p>
    </div>
    <button class="btn-primary" onclick="openModal('addModal')">
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      Nuevo Producto
    </button>
  </div>

  <?php if ($flash): ?>
  <div class="flash-bar flash-<?= e($flash['type']) ?>" id="flashBar">
    <span>
      <?php if ($flash['type'] === 'success'): ?>✅<?php elseif ($flash['type'] === 'danger'): ?>🗑<?php else: ?>⚠️<?php endif; ?>
    </span>
    <span><?= $flash['msg'] ?></span>
    <button class="flash-dismiss" onclick="document.getElementById('flashBar').remove()">✕</button>
  </div>
  <?php endif; ?>

  <!-- ── Stats ── -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-label">Total Productos</div>
      <div class="stat-value"><?= number_format((int)$stats['total']) ?></div>
      <div class="stat-sub">En inventario activo</div>
    </div>
    <div class="stat-card accent">
      <div class="stat-label">Categorías</div>
      <div class="stat-value"><?= (int)$stats['categorias'] ?></div>
      <div class="stat-sub">Clasificaciones activas</div>
    </div>
    <div class="stat-card success">
      <div class="stat-label">Valor Total</div>
      <div class="stat-value">$<?= number_format((float)$stats['valor_total'], 0) ?></div>
      <div class="stat-sub">Precio estimado total</div>
    </div>
    <div class="stat-card danger">
      <div class="stat-label">Stock Bajo</div>
      <div class="stat-value"><?= (int)$stats['stock_bajo'] ?></div>
      <div class="stat-sub">Requieren reposición (&le;3)</div>
    </div>
  </div>

  <!-- ── Table ── -->
  <div class="table-card">
    <div class="table-header">
      <h3>Listado de Productos</h3>
      <form method="GET" action="index.php" class="search-group">
        <input
          class="search-input"
          type="text"
          name="buscar"
          placeholder="Buscar por nombre…"
          value="<?= e($buscar) ?>"
        />
        <select class="select-filter" name="categoria">
          <option value="">Todas las categorías</option>
          <?php foreach ($categorias as $cat): ?>
          <option value="<?= e($cat) ?>"<?= $categoria === $cat ? ' selected' : '' ?>><?= e($cat) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn-filter" type="submit">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
          </svg>
          Filtrar
        </button>
        <?php if ($buscar || $categoria): ?>
        <a href="index.php" class="btn-filter" style="border-color:var(--danger);color:var(--danger);">✕ Limpiar</a>
        <?php endif; ?>
      </form>
    </div>

    <div style="overflow-x:auto;">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Categoría</th>
            <th>Precio (USD)</th>
            <th>Cantidad</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($productos)): ?>
          <tr>
            <td colspan="6">
              <div class="empty-state">
                <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                  <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>
                </svg>
                <p>No se encontraron productos<?= $buscar || $categoria ? ' con los filtros aplicados' : '' ?>.</p>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($productos as $p): ?>
          <tr>
            <td><span style="font-family:monospace;color:var(--muted);font-size:.8rem;">#<?= str_pad((string)$p['id'], 3, '0', STR_PAD_LEFT) ?></span></td>
            <td><strong><?= e($p['nombre']) ?></strong></td>
            <td>
              <span class="badge-cat <?= e($catClass[$p['categoria']] ?? 'cat-otros') ?>">
                <?= e($p['categoria']) ?>
              </span>
            </td>
            <td>$<?= number_format((float)$p['precio'], 2) ?></td>
            <td>
              <span class="<?= (int)$p['cantidad'] <= 3 ? 'stock-low' : 'stock-ok' ?>">
                <?= (int)$p['cantidad'] ?>
              </span>
            </td>
            <td>
              <div style="display:flex;gap:.4rem;">
                <a class="action-btn btn-edit" href="<?= buildUrl(['editar' => $p['id'], 'pagina' => $page]) ?>">
                  <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  Editar
                </a>
                <a class="action-btn btn-delete" href="<?= buildUrl(['eliminar' => $p['id'], 'pagina' => $page]) ?>">
                  <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
                  Eliminar
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="pagination">
      <span>
        Mostrando <strong><?= $total ? $offset + 1 : 0 ?>–<?= min($offset + $perPage, $total) ?></strong>
        de <strong><?= $total ?></strong> producto<?= $total !== 1 ? 's' : '' ?>
      </span>
      <?php if ($totalPages > 1): ?>
      <div class="page-btns">
        <a class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>"
           href="<?= buildUrl(['pagina' => $page - 1]) ?>">‹</a>
        <?php
        $range = pagRange($page, $totalPages);
        $prev  = null;
        foreach ($range as $p):
          if ($prev !== null && $p - $prev > 1):
        ?>
        <span style="padding:0 .25rem;color:var(--muted);line-height:32px;">…</span>
        <?php
          endif;
          $prev = $p;
        ?>
        <a class="page-btn <?= $p === $page ? 'active' : '' ?>"
           href="<?= buildUrl(['pagina' => $p]) ?>"><?= $p ?></a>
        <?php endforeach; ?>
        <a class="page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>"
           href="<?= buildUrl(['pagina' => $page + 1]) ?>">›</a>
      </div>
      <?php endif; ?>
    </div>
  </div>

</div><!-- /wrapper -->

<!-- ══════════════ MODAL: ADD ══════════════ -->
<div class="modal-overlay" id="addModal">
  <div class="modal-box">
    <div class="modal-head">
      <h3>Registrar Producto</h3>
      <button class="modal-close" onclick="closeModal('addModal')">✕</button>
    </div>
    <form method="POST" action="actions.php">
      <input type="hidden" name="action" value="create"/>
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label" for="add_nombre">Nombre del producto</label>
          <input class="form-input" id="add_nombre" type="text" name="nombre"
                 placeholder="Ej: Laptop HP ProBook 440" required maxlength="100"/>
        </div>
        <div class="form-group">
          <label class="form-label" for="add_categoria">Categoría</label>
          <select class="form-select" id="add_categoria" name="categoria" required>
            <option value="">Seleccione una categoría…</option>
            <?php foreach ($categorias as $cat): ?>
            <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="add_precio">Precio (USD)</label>
            <input class="form-input" id="add_precio" type="number"
                   step="0.01" min="0" name="precio" placeholder="0.00" required/>
            <div class="form-hint">Precio unitario</div>
          </div>
          <div class="form-group">
            <label class="form-label" for="add_cantidad">Cantidad</label>
            <input class="form-input" id="add_cantidad" type="number"
                   min="0" name="cantidad" placeholder="0" required/>
            <div class="form-hint">Unidades en inventario</div>
          </div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn-outline" onclick="closeModal('addModal')">Cancelar</button>
        <button type="submit" class="btn-primary">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
          Guardar Producto
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ══════════════ MODAL: EDIT ══════════════ -->
<?php if ($editProduct): ?>
<div class="modal-overlay" id="editModal">
  <div class="modal-box">
    <div class="modal-head">
      <h3>Editar Producto</h3>
      <a class="modal-close" href="<?= buildUrl(['pagina' => $page]) ?>">✕</a>
    </div>
    <form method="POST" action="actions.php">
      <input type="hidden" name="action" value="update"/>
      <input type="hidden" name="id" value="<?= (int)$editProduct['id'] ?>"/>
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label" for="edit_nombre">Nombre del producto</label>
          <input class="form-input" id="edit_nombre" type="text" name="nombre"
                 value="<?= e($editProduct['nombre']) ?>" required maxlength="100"/>
        </div>
        <div class="form-group">
          <label class="form-label" for="edit_categoria">Categoría</label>
          <select class="form-select" id="edit_categoria" name="categoria" required>
            <?php foreach ($categorias as $cat): ?>
            <option value="<?= e($cat) ?>"<?= $editProduct['categoria'] === $cat ? ' selected' : '' ?>>
              <?= e($cat) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="edit_precio">Precio (USD)</label>
            <input class="form-input" id="edit_precio" type="number"
                   step="0.01" min="0" name="precio"
                   value="<?= number_format((float)$editProduct['precio'], 2, '.', '') ?>" required/>
          </div>
          <div class="form-group">
            <label class="form-label" for="edit_cantidad">Cantidad</label>
            <input class="form-input" id="edit_cantidad" type="number"
                   min="0" name="cantidad" value="<?= (int)$editProduct['cantidad'] ?>" required/>
          </div>
        </div>
        <div class="form-warn">
          <span>⚠</span>
          <span>Los cambios actualizarán el registro en la base de datos <strong>inventario_utp</strong>.</span>
        </div>
      </div>
      <div class="modal-foot">
        <a class="btn-outline" href="<?= buildUrl(['pagina' => $page]) ?>">Cancelar</a>
        <button type="submit" class="btn-primary">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
          Actualizar
        </button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- ══════════════ MODAL: DELETE ══════════════ -->
<?php if ($delProduct): ?>
<div class="modal-overlay" id="deleteModal">
  <div class="modal-box">
    <div class="modal-head">
      <h3>Eliminar Producto</h3>
      <a class="modal-close" href="<?= buildUrl(['pagina' => $page]) ?>">✕</a>
    </div>
    <form method="POST" action="actions.php">
      <input type="hidden" name="action" value="delete"/>
      <input type="hidden" name="id" value="<?= (int)$delProduct['id'] ?>"/>
      <div class="modal-body" style="text-align:center;padding-top:1.5rem;">
        <div class="delete-icon">
          <svg width="24" height="24" fill="none" stroke="#b91c1c" stroke-width="2" viewBox="0 0 24 24">
            <polyline points="3 6 5 6 21 6"/>
            <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
            <path d="M10 11v6M14 11v6M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
          </svg>
        </div>
        <p style="font-size:1rem;font-weight:600;margin-bottom:.5rem;">¿Eliminar este producto?</p>
        <p style="color:var(--muted);font-size:.87rem;">
          Se eliminará <strong><?= e($delProduct['nombre']) ?></strong> de la base de datos.<br>
          Esta acción <strong>no se puede deshacer</strong>.
        </p>
      </div>
      <div class="modal-foot">
        <a class="btn-outline" href="<?= buildUrl(['pagina' => $page]) ?>">Cancelar</a>
        <button type="submit" class="btn-danger">Sí, eliminar</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
  function openModal(id) {
    document.getElementById(id)?.classList.add('open');
    document.body.style.overflow = 'hidden';
  }
  function closeModal(id) {
    document.getElementById(id)?.classList.remove('open');
    document.body.style.overflow = '';
  }
  document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); });
  });

  // Auto-open modals set by PHP (edit / delete)
  <?php if ($editProduct): ?>
  openModal('editModal');
  <?php endif; ?>
  <?php if ($delProduct): ?>
  openModal('deleteModal');
  <?php endif; ?>

  // Auto-dismiss flash after 4 s
  setTimeout(() => document.getElementById('flashBar')?.remove(), 4000);
</script>
</body>
</html>
<?php
// ── Helpers PHP al final para no contaminar el HTML ───────────────────────────

/**
 * Genera el rango de páginas visible en la paginación.
 * Siempre muestra primera, última y las 2 páginas adyacentes a la actual.
 */
function pagRange(int $current, int $total): array
{
    $pages = [];
    for ($i = 1; $i <= $total; $i++) {
        if ($i === 1 || $i === $total || abs($i - $current) <= 1) {
            $pages[] = $i;
        }
    }
    return array_unique($pages);
}

/**
 * Nombre del mes en español sin strftime (deprecado en PHP 8.1).
 */
function strftime_es(string $month): string
{
    return [
        1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',
        5=>'mayo',6=>'junio',7=>'julio',8=>'agosto',
        9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre',
    ][(int)$month];
}
?>