<!DOCTYPE html>
<html lang="es" data-theme="custom">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Inventario UTP — Sistema CRUD</title>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.10.2/dist/full.min.css" rel="stylesheet"/>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,400&display=swap" rel="stylesheet"/>
  <style>
    :root {
      --brand:       #1a3a5c;
      --brand-mid:   #2563a8;
      --accent:      #e8a020;
      --accent-lt:   #fef3db;
      --surface:     #f4f6f9;
      --card:        #ffffff;
      --text:        #0f1e2e;
      --muted:       #637898;
      --danger:      #c0392b;
      --success:     #1e7a4e;
    }
    *, *::before, *::after { box-sizing: border-box; }
    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--surface);
      color: var(--text);
      min-height: 100vh;
    }
    h1, h2, h3 { font-family: 'DM Serif Display', serif; }

    /* ── Sidebar ── */
    .sidebar {
      width: 260px;
      background: var(--brand);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      position: fixed;
      top: 0; left: 0; bottom: 0;
      z-index: 40;
      transition: transform .25s ease;
    }
    .sidebar-logo {
      padding: 2rem 1.5rem 1.5rem;
      border-bottom: 1px solid rgba(255,255,255,.1);
    }
    .sidebar-logo .tag {
      font-size: .65rem;
      letter-spacing: .12em;
      text-transform: uppercase;
      color: var(--accent);
      font-weight: 600;
      margin-bottom: .35rem;
    }
    .sidebar-logo h2 {
      color: #fff;
      font-size: 1.3rem;
      line-height: 1.2;
      margin: 0;
    }
    .nav-section {
      padding: .75rem 1rem .25rem;
      font-size: .65rem;
      letter-spacing: .12em;
      text-transform: uppercase;
      color: rgba(255,255,255,.35);
      font-weight: 600;
    }
    .nav-item {
      display: flex;
      align-items: center;
      gap: .75rem;
      padding: .65rem 1.5rem;
      color: rgba(255,255,255,.7);
      font-size: .88rem;
      font-weight: 500;
      cursor: pointer;
      border-left: 3px solid transparent;
      transition: all .15s;
      text-decoration: none;
    }
    .nav-item:hover { color: #fff; background: rgba(255,255,255,.07); }
    .nav-item.active {
      color: #fff;
      border-left-color: var(--accent);
      background: rgba(255,255,255,.1);
    }
    .nav-item svg { flex-shrink: 0; opacity: .8; }
    .nav-item.active svg { opacity: 1; }

    /* ── Main content ── */
    .main { margin-left: 260px; padding: 2rem 2.5rem; min-height: 100vh; }

    /* ── Topbar ── */
    .topbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 2rem;
    }
    .topbar h1 {
      font-size: 1.75rem;
      color: var(--brand);
      margin: 0;
    }
    .topbar p { color: var(--muted); font-size: .88rem; margin: .2rem 0 0; }

    /* ── Stat cards ── */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1.25rem;
      margin-bottom: 2rem;
    }
    .stat-card {
      background: var(--card);
      border-radius: 12px;
      padding: 1.25rem 1.5rem;
      border: 1px solid #e3e8ef;
      position: relative;
      overflow: hidden;
    }
    .stat-card::after {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 3px;
      background: var(--brand-mid);
      border-radius: 12px 12px 0 0;
    }
    .stat-card.accent::after { background: var(--accent); }
    .stat-card.success::after { background: var(--success); }
    .stat-card.danger::after  { background: var(--danger); }
    .stat-label { font-size: .75rem; text-transform: uppercase; letter-spacing: .08em; color: var(--muted); font-weight: 600; }
    .stat-value { font-family: 'DM Serif Display', serif; font-size: 2rem; color: var(--brand); line-height: 1.1; margin: .35rem 0 .2rem; }
    .stat-sub   { font-size: .78rem; color: var(--muted); }

    /* ── Table card ── */
    .table-card {
      background: var(--card);
      border-radius: 14px;
      border: 1px solid #e3e8ef;
      overflow: hidden;
      margin-bottom: 2rem;
    }
    .table-header {
      padding: 1.25rem 1.5rem;
      border-bottom: 1px solid #e3e8ef;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      flex-wrap: wrap;
    }
    .table-header h3 { margin: 0; font-size: 1.1rem; color: var(--brand); }
    .search-group {
      display: flex;
      gap: .75rem;
      align-items: center;
    }
    .search-input {
      border: 1.5px solid #d1dae8;
      border-radius: 8px;
      padding: .5rem .9rem;
      font-size: .85rem;
      font-family: inherit;
      outline: none;
      transition: border .15s;
      width: 220px;
      color: var(--text);
    }
    .search-input:focus { border-color: var(--brand-mid); }
    .select-filter {
      border: 1.5px solid #d1dae8;
      border-radius: 8px;
      padding: .5rem .9rem;
      font-size: .85rem;
      font-family: inherit;
      outline: none;
      color: var(--text);
      background: #fff;
      cursor: pointer;
    }
    .select-filter:focus { border-color: var(--brand-mid); }
    table { width: 100%; border-collapse: collapse; }
    thead tr { background: #f8fafc; }
    th {
      text-align: left;
      padding: .75rem 1.25rem;
      font-size: .72rem;
      text-transform: uppercase;
      letter-spacing: .09em;
      color: var(--muted);
      font-weight: 600;
      border-bottom: 1px solid #e3e8ef;
    }
    td {
      padding: .9rem 1.25rem;
      font-size: .87rem;
      border-bottom: 1px solid #f0f3f8;
      vertical-align: middle;
    }
    tr:last-child td { border-bottom: none; }
    tbody tr:hover { background: #f8fafc; }
    .badge-cat {
      display: inline-block;
      padding: .2rem .65rem;
      border-radius: 999px;
      font-size: .72rem;
      font-weight: 600;
      letter-spacing: .04em;
    }
    .cat-electronica  { background: #dbeafe; color: #1e40af; }
    .cat-mobiliario   { background: #d1fae5; color: #065f46; }
    .cat-papeleria    { background: #fef9c3; color: #854d0e; }
    .cat-laboratorio  { background: #ede9fe; color: #4c1d95; }
    .cat-otros        { background: #f1f5f9; color: #475569; }

    .stock-low  { color: var(--danger); font-weight: 600; }
    .stock-ok   { color: var(--success); font-weight: 600; }
    .action-btn {
      padding: .35rem .7rem;
      border-radius: 7px;
      border: none;
      font-size: .78rem;
      font-weight: 600;
      cursor: pointer;
      transition: opacity .15s;
      font-family: inherit;
    }
    .action-btn:hover { opacity: .82; }
    .btn-edit   { background: #dbeafe; color: #1d4ed8; }
    .btn-delete { background: #fee2e2; color: #b91c1c; }

    /* ── Buttons ── */
    .btn-primary {
      background: var(--brand);
      color: #fff;
      border: none;
      border-radius: 9px;
      padding: .6rem 1.25rem;
      font-size: .875rem;
      font-weight: 600;
      cursor: pointer;
      font-family: inherit;
      display: inline-flex;
      align-items: center;
      gap: .4rem;
      transition: background .15s;
    }
    .btn-primary:hover { background: var(--brand-mid); }
    .btn-outline {
      background: transparent;
      color: var(--brand);
      border: 1.5px solid var(--brand);
      border-radius: 9px;
      padding: .55rem 1.2rem;
      font-size: .875rem;
      font-weight: 600;
      cursor: pointer;
      font-family: inherit;
      display: inline-flex;
      align-items: center;
      gap: .4rem;
      transition: all .15s;
    }
    .btn-outline:hover { background: var(--brand); color: #fff; }

    /* ── Pagination ── */
    .pagination {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1rem 1.5rem;
      border-top: 1px solid #e3e8ef;
      font-size: .83rem;
      color: var(--muted);
    }
    .page-btns { display: flex; gap: .4rem; }
    .page-btn {
      width: 32px; height: 32px;
      border-radius: 7px;
      border: 1.5px solid #d1dae8;
      background: #fff;
      font-size: .83rem;
      font-weight: 600;
      cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      transition: all .15s;
    }
    .page-btn:hover { border-color: var(--brand-mid); color: var(--brand-mid); }
    .page-btn.active { background: var(--brand); color: #fff; border-color: var(--brand); }

    /* ── Modal ── */
    .modal-overlay {
      position: fixed; inset: 0;
      background: rgba(10,20,40,.5);
      backdrop-filter: blur(3px);
      z-index: 100;
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      pointer-events: none;
      transition: opacity .2s;
    }
    .modal-overlay.open { opacity: 1; pointer-events: all; }
    .modal-box {
      background: var(--card);
      border-radius: 16px;
      width: 520px;
      max-width: 95vw;
      box-shadow: 0 24px 60px rgba(0,0,0,.18);
      transform: translateY(16px);
      transition: transform .25s ease;
    }
    .modal-overlay.open .modal-box { transform: translateY(0); }
    .modal-head {
      padding: 1.5rem 1.75rem 1rem;
      border-bottom: 1px solid #e3e8ef;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .modal-head h3 { margin: 0; font-size: 1.2rem; color: var(--brand); }
    .modal-close {
      width: 32px; height: 32px;
      border-radius: 50%;
      border: none;
      background: #f0f3f8;
      cursor: pointer;
      font-size: 1.1rem;
      display: flex; align-items: center; justify-content: center;
      transition: background .15s;
    }
    .modal-close:hover { background: #e3e8ef; }
    .modal-body { padding: 1.25rem 1.75rem; }
    .modal-foot {
      padding: 1rem 1.75rem 1.5rem;
      display: flex;
      justify-content: flex-end;
      gap: .75rem;
    }
    .form-group { margin-bottom: 1.1rem; }
    .form-label {
      display: block;
      font-size: .78rem;
      font-weight: 600;
      letter-spacing: .05em;
      text-transform: uppercase;
      color: var(--muted);
      margin-bottom: .4rem;
    }
    .form-input, .form-select {
      width: 100%;
      border: 1.5px solid #d1dae8;
      border-radius: 9px;
      padding: .6rem .9rem;
      font-size: .9rem;
      font-family: inherit;
      color: var(--text);
      outline: none;
      transition: border .15s;
      background: #fff;
    }
    .form-input:focus, .form-select:focus { border-color: var(--brand-mid); box-shadow: 0 0 0 3px rgba(37,99,168,.1); }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .form-hint { font-size: .75rem; color: var(--muted); margin-top: .3rem; }

    /* ── Delete confirm ── */
    .delete-icon {
      width: 56px; height: 56px;
      border-radius: 50%;
      background: #fee2e2;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 1rem;
    }

    /* ── Notification toast ── */
    .toast-area {
      position: fixed;
      bottom: 1.5rem; right: 1.5rem;
      z-index: 200;
      display: flex;
      flex-direction: column;
      gap: .6rem;
    }
    .toast {
      background: #fff;
      border-radius: 10px;
      padding: .85rem 1.1rem;
      box-shadow: 0 8px 28px rgba(0,0,0,.14);
      display: flex;
      align-items: center;
      gap: .75rem;
      font-size: .85rem;
      font-weight: 500;
      min-width: 260px;
      border-left: 4px solid var(--success);
      animation: slideIn .3s ease;
    }
    @keyframes slideIn {
      from { transform: translateX(40px); opacity: 0; }
      to   { transform: translateX(0);   opacity: 1; }
    }

    /* ── Responsive ── */
    @media (max-width: 900px) {
      .sidebar { transform: translateX(-260px); }
      .sidebar.open { transform: translateX(0); }
      .main { margin-left: 0; padding: 1.25rem; }
      .stats-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 560px) {
      .stats-grid { grid-template-columns: 1fr; }
      .search-group { flex-direction: column; align-items: stretch; }
      .search-input { width: 100%; }
    }

    /* Scrollbar */
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #c5d0de; border-radius: 999px; }
  </style>
</head>
<body>

<!-- ══════════════ SIDEBAR ══════════════ -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="tag">Sistema Académico</div>
    <h2>Inventario<br>UTP</h2>
  </div>

  <nav style="flex:1; padding: 1rem 0; overflow-y: auto;">
    <div class="nav-section">Principal</div>
    <a class="nav-item active" href="index.html">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      Dashboard
    </a>
    <a class="nav-item" href="#" onclick="openModal('addModal')">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
      Registrar Producto
    </a>

    <div class="nav-section" style="margin-top:.5rem;">Reportes</div>
    <a class="nav-item" href="#">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      Estadísticas
    </a>
    <a class="nav-item" href="#">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
      Exportar Datos
    </a>

    <div class="nav-section" style="margin-top:.5rem;">Sistema</div>
    <a class="nav-item" href="#">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93A10 10 0 115 19.07"/></svg>
      Configuración
    </a>
  </nav>

  <div style="padding: 1rem 1.5rem; border-top: 1px solid rgba(255,255,255,.1);">
    <div style="display:flex; align-items:center; gap:.75rem;">
      <div style="width:34px;height:34px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:.85rem;">AD</div>
      <div>
        <div style="color:#fff;font-size:.83rem;font-weight:600;">Admin</div>
        <div style="color:rgba(255,255,255,.45);font-size:.72rem;">Administrador</div>
      </div>
    </div>
  </div>
</aside>

<!-- ══════════════ MAIN ══════════════ -->
<main class="main">

  <!-- Topbar -->
  <div class="topbar">
    <div>
      <h1>Dashboard</h1>
      <p>Sistema CRUD de Inventario Académico · <span id="fecha"></span></p>
    </div>
    <button class="btn-primary" onclick="openModal('addModal')">
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Nuevo Producto
    </button>
  </div>

  <!-- Stats -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-label">Total Productos</div>
      <div class="stat-value">248</div>
      <div class="stat-sub">En inventario activo</div>
    </div>
    <div class="stat-card accent">
      <div class="stat-label">Categorías</div>
      <div class="stat-value">5</div>
      <div class="stat-sub">Clasificaciones activas</div>
    </div>
    <div class="stat-card success">
      <div class="stat-label">Valor Total</div>
      <div class="stat-value">$12.4k</div>
      <div class="stat-sub">Precio estimado total</div>
    </div>
    <div class="stat-card danger">
      <div class="stat-label">Stock Bajo</div>
      <div class="stat-value">7</div>
      <div class="stat-sub">Requieren reposición</div>
    </div>
  </div>

  <!-- Table -->
  <div class="table-card">
    <div class="table-header">
      <h3>Listado de Productos</h3>
      <div class="search-group">
        <input class="search-input" type="text" placeholder="Buscar por nombre…"/>
        <select class="select-filter">
          <option value="">Todas las categorías</option>
          <option>Electrónica</option>
          <option>Mobiliario</option>
          <option>Papelería</option>
          <option>Laboratorio</option>
          <option>Otros</option>
        </select>
        <button class="btn-outline" style="padding:.5rem .85rem;">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
          Filtrar
        </button>
      </div>
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
          <!-- Row 1 -->
          <tr>
            <td><span style="font-family:monospace;color:var(--muted);font-size:.8rem;">#001</span></td>
            <td><strong>Laptop HP ProBook 440</strong></td>
            <td><span class="badge-cat cat-electronica">Electrónica</span></td>
            <td>$849.00</td>
            <td><span class="stock-ok">12</span></td>
            <td>
              <div style="display:flex;gap:.4rem;">
                <button class="action-btn btn-edit" onclick="openModal('editModal')">✏ Editar</button>
                <button class="action-btn btn-delete" onclick="openModal('deleteModal')">✕ Eliminar</button>
              </div>
            </td>
          </tr>
          <!-- Row 2 -->
          <tr>
            <td><span style="font-family:monospace;color:var(--muted);font-size:.8rem;">#002</span></td>
            <td><strong>Proyector Epson EB-X51</strong></td>
            <td><span class="badge-cat cat-electronica">Electrónica</span></td>
            <td>$375.00</td>
            <td><span class="stock-low">2</span></td>
            <td>
              <div style="display:flex;gap:.4rem;">
                <button class="action-btn btn-edit" onclick="openModal('editModal')">✏ Editar</button>
                <button class="action-btn btn-delete" onclick="openModal('deleteModal')">✕ Eliminar</button>
              </div>
            </td>
          </tr>
          <!-- Row 3 -->
          <tr>
            <td><span style="font-family:monospace;color:var(--muted);font-size:.8rem;">#003</span></td>
            <td><strong>Escritorio Ejecutivo Roble</strong></td>
            <td><span class="badge-cat cat-mobiliario">Mobiliario</span></td>
            <td>$210.00</td>
            <td><span class="stock-ok">8</span></td>
            <td>
              <div style="display:flex;gap:.4rem;">
                <button class="action-btn btn-edit" onclick="openModal('editModal')">✏ Editar</button>
                <button class="action-btn btn-delete" onclick="openModal('deleteModal')">✕ Eliminar</button>
              </div>
            </td>
          </tr>
          <!-- Row 4 -->
          <tr>
            <td><span style="font-family:monospace;color:var(--muted);font-size:.8rem;">#004</span></td>
            <td><strong>Resma Papel Bond A4</strong></td>
            <td><span class="badge-cat cat-papeleria">Papelería</span></td>
            <td>$4.50</td>
            <td><span class="stock-low">3</span></td>
            <td>
              <div style="display:flex;gap:.4rem;">
                <button class="action-btn btn-edit" onclick="openModal('editModal')">✏ Editar</button>
                <button class="action-btn btn-delete" onclick="openModal('deleteModal')">✕ Eliminar</button>
              </div>
            </td>
          </tr>
          <!-- Row 5 -->
          <tr>
            <td><span style="font-family:monospace;color:var(--muted);font-size:.8rem;">#005</span></td>
            <td><strong>Microscopio Binocular 40x</strong></td>
            <td><span class="badge-cat cat-laboratorio">Laboratorio</span></td>
            <td>$520.00</td>
            <td><span class="stock-ok">5</span></td>
            <td>
              <div style="display:flex;gap:.4rem;">
                <button class="action-btn btn-edit" onclick="openModal('editModal')">✏ Editar</button>
                <button class="action-btn btn-delete" onclick="openModal('deleteModal')">✕ Eliminar</button>
              </div>
            </td>
          </tr>
          <!-- Row 6 -->
          <tr>
            <td><span style="font-family:monospace;color:var(--muted);font-size:.8rem;">#006</span></td>
            <td><strong>Silla Ergonómica Mesh</strong></td>
            <td><span class="badge-cat cat-mobiliario">Mobiliario</span></td>
            <td>$140.00</td>
            <td><span class="stock-ok">20</span></td>
            <td>
              <div style="display:flex;gap:.4rem;">
                <button class="action-btn btn-edit" onclick="openModal('editModal')">✏ Editar</button>
                <button class="action-btn btn-delete" onclick="openModal('deleteModal')">✕ Eliminar</button>
              </div>
            </td>
          </tr>
          <!-- Row 7 -->
          <tr>
            <td><span style="font-family:monospace;color:var(--muted);font-size:.8rem;">#007</span></td>
            <td><strong>Teclado Mecánico Logitech</strong></td>
            <td><span class="badge-cat cat-electronica">Electrónica</span></td>
            <td>$89.00</td>
            <td><span class="stock-low">1</span></td>
            <td>
              <div style="display:flex;gap:.4rem;">
                <button class="action-btn btn-edit" onclick="openModal('editModal')">✏ Editar</button>
                <button class="action-btn btn-delete" onclick="openModal('deleteModal')">✕ Eliminar</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="pagination">
      <span>Mostrando <strong>1–7</strong> de <strong>248</strong> productos</span>
      <div class="page-btns">
        <button class="page-btn">‹</button>
        <button class="page-btn active">1</button>
        <button class="page-btn">2</button>
        <button class="page-btn">3</button>
        <span style="padding:0 .25rem;color:var(--muted);">…</span>
        <button class="page-btn">36</button>
        <button class="page-btn">›</button>
      </div>
    </div>
  </div>

</main>

<!-- ══════════════ MODAL: ADD ══════════════ -->
<div class="modal-overlay" id="addModal">
  <div class="modal-box">
    <div class="modal-head">
      <h3>Registrar Producto</h3>
      <button class="modal-close" onclick="closeModal('addModal')">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Nombre del producto</label>
        <input class="form-input" type="text" placeholder="Ej: Laptop HP ProBook 440"/>
      </div>
      <div class="form-group">
        <label class="form-label">Categoría</label>
        <select class="form-select">
          <option value="">Seleccione una categoría…</option>
          <option>Electrónica</option>
          <option>Mobiliario</option>
          <option>Papelería</option>
          <option>Laboratorio</option>
          <option>Otros</option>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Precio (USD)</label>
          <input class="form-input" type="number" step="0.01" min="0" placeholder="0.00"/>
          <div class="form-hint">Ingrese el precio unitario</div>
        </div>
        <div class="form-group">
          <label class="form-label">Cantidad</label>
          <input class="form-input" type="number" min="0" placeholder="0"/>
          <div class="form-hint">Unidades en inventario</div>
        </div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn-outline" onclick="closeModal('addModal')">Cancelar</button>
      <button class="btn-primary" onclick="closeModal('addModal'); showToast('Producto registrado correctamente.')">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
        Guardar Producto
      </button>
    </div>
  </div>
</div>

<!-- ══════════════ MODAL: EDIT ══════════════ -->
<div class="modal-overlay" id="editModal">
  <div class="modal-box">
    <div class="modal-head">
      <h3>Editar Producto</h3>
      <button class="modal-close" onclick="closeModal('editModal')">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Nombre del producto</label>
        <input class="form-input" type="text" value="Laptop HP ProBook 440"/>
      </div>
      <div class="form-group">
        <label class="form-label">Categoría</label>
        <select class="form-select">
          <option>Electrónica</option>
          <option>Mobiliario</option>
          <option>Papelería</option>
          <option>Laboratorio</option>
          <option>Otros</option>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Precio (USD)</label>
          <input class="form-input" type="number" step="0.01" value="849.00"/>
        </div>
        <div class="form-group">
          <label class="form-label">Cantidad</label>
          <input class="form-input" type="number" value="12"/>
        </div>
      </div>
      <div style="background:var(--accent-lt);border-radius:8px;padding:.75rem 1rem;font-size:.8rem;color:#7c5a00;display:flex;gap:.5rem;align-items:flex-start;">
        <span>⚠</span>
        <span>Los cambios actualizarán el registro en la base de datos <strong>inventario_utp</strong>.</span>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn-outline" onclick="closeModal('editModal')">Cancelar</button>
      <button class="btn-primary" onclick="closeModal('editModal'); showToast('Producto actualizado correctamente.')">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
        Actualizar
      </button>
    </div>
  </div>
</div>

<!-- ══════════════ MODAL: DELETE ══════════════ -->
<div class="modal-overlay" id="deleteModal">
  <div class="modal-box">
    <div class="modal-head">
      <h3>Eliminar Producto</h3>
      <button class="modal-close" onclick="closeModal('deleteModal')">✕</button>
    </div>
    <div class="modal-body" style="text-align:center; padding-top:1.5rem;">
      <div class="delete-icon">
        <svg width="24" height="24" fill="none" stroke="#b91c1c" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
      </div>
      <p style="font-size:1rem;font-weight:600;margin:0 0 .5rem;">¿Eliminar este producto?</p>
      <p style="color:var(--muted);font-size:.87rem;margin:0;">Se eliminará <strong>Laptop HP ProBook 440</strong> de la base de datos. Esta acción no se puede deshacer.</p>
    </div>
    <div class="modal-foot">
      <button class="btn-outline" onclick="closeModal('deleteModal')">Cancelar</button>
      <button style="background:var(--danger);color:#fff;border:none;border-radius:9px;padding:.6rem 1.25rem;font-size:.875rem;font-weight:600;cursor:pointer;font-family:inherit;" onclick="closeModal('deleteModal'); showToast('Producto eliminado.', 'danger')">
        Sí, eliminar
      </button>
    </div>
  </div>
</div>

<!-- ══════════════ TOAST AREA ══════════════ -->
<div class="toast-area" id="toastArea"></div>

<script>
  // Date
  const d = new Date();
  document.getElementById('fecha').textContent = d.toLocaleDateString('es-PA', {day:'2-digit',month:'long',year:'numeric'});

  // Modals
  function openModal(id) {
    document.getElementById(id).classList.add('open');
    document.body.style.overflow = 'hidden';
  }
  function closeModal(id) {
    document.getElementById(id).classList.remove('open');
    document.body.style.overflow = '';
  }
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
      if (e.target === overlay) closeModal(overlay.id);
    });
  });

  // Toast
  function showToast(msg, type = 'success') {
    const area = document.getElementById('toastArea');
    const t = document.createElement('div');
    t.className = 'toast';
    t.style.borderLeftColor = type === 'danger' ? 'var(--danger)' : 'var(--success)';
    t.innerHTML = `
      <span style="font-size:1.1rem;">${type === 'danger' ? '🗑' : '✅'}</span>
      <span>${msg}</span>`;
    area.appendChild(t);
    setTimeout(() => t.remove(), 3200);
  }
</script>
</body>
</html>