<?php
session_start();
// 1) Verificar sesión de cliente
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'cliente') {
    header('Location: login.php');
    exit;
}
$id_cliente = $_SESSION['user_id'];

// 2) Conexión a la BD
$host = 'localhost';
$db   = 'fidelizacion';
$user = 'root';
$pass = '';
$puerto = '3309';
$pdo  = new PDO("mysql:host=$host;port=$puerto;dbname=$db;charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// 3) Obtener puntos actuales
$stmt = $pdo->prepare("SELECT puntos_actuales FROM clientes WHERE id_cliente = ?");
$stmt->execute([$id_cliente]);
$puntos_actuales = (int)$stmt->fetchColumn();

// 4) Historial de bonificaciones (transacciones_puntos)
$stmt = $pdo->prepare("
    SELECT monto_compra, puntos_acreditados, fecha
      FROM transacciones_puntos
     WHERE id_cliente = ?
     ORDER BY fecha DESC
");
$stmt->execute([$id_cliente]);
$compras = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5) Historial de uso (redenciones)
$stmt = $pdo->prepare("
    SELECT p.nombre AS premio, r.puntos_usados, r.fecha
      FROM redenciones r
      JOIN premios p ON p.id_premio = r.id_premio
     WHERE r.id_cliente = ?
     ORDER BY r.fecha DESC
");
$stmt->execute([$id_cliente]);
$usos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Detalle de Puntos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
  body {
    background: linear-gradient(to right, #1f1c2c, #928dab);
    color: #eaeaea;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    padding-bottom: 3rem;
  }

  .header {
    margin: 2rem 0;
    text-align: center;
  }

  .header h1 {
    color: #00eaff;
    text-shadow: 0 0 10px #00eaff;
  }

  .header a {
    color: #f8f9fa;
    text-decoration: none;
    font-weight: bold;
    background-color: #2c2f48;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    display: inline-block;
    margin-top: 0.5rem;
    transition: all 0.3s ease-in-out;
  }

  .header a:hover {
    background-color: #00eaff;
    color: #000;
  }

  .card-puntos {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    color: #00ffd5;
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 20px;
    padding: 2rem;
    max-width: 450px;
    margin: 0 auto 2rem;
    text-align: center;
    box-shadow: 0 0 25px rgba(0, 255, 213, 0.2);
  }

  .card-puntos h3 {
    font-size: 3rem;
    margin: 0;
    text-shadow: 0 0 6px #00ffd5;
  }

  .card-puntos p {
    font-size: 1.2rem;
    letter-spacing: 1px;
    margin-bottom: 0.5rem;
  }

  .section-title {
    text-align: center;
    margin: 3rem 0 1rem;
    color: #ffffff;
    text-shadow: 0 0 4px rgba(255,255,255,0.3);
  }

  .table {
    background-color: rgba(255,255,255,0.03);
    color: #fff;
    border-radius: 10px;
    overflow: hidden;
  }

  .table thead {
    background-color: #0d1117;
    color: #00eaff;
    border-bottom: 2px solid #00eaff;
  }

  .table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(0, 255, 213, 0.05);
  }

  .table-striped tbody tr:nth-of-type(even) {
    background-color: rgba(255, 255, 255, 0.02);
  }

  .table td, .table th {
    vertical-align: middle;
    padding: 0.75rem;
  }

  .table td {
    color:rgb(0, 0, 0);
  }

  .table td:first-child,
  .table td:last-child {
    font-weight: bold;
  }

  .btn-back {
    display: block;
    margin: 2rem auto 0;
    padding: 0.75rem 2rem;
    border-radius: 30px;
    background-color: #00eaff;
    color: #000;
    text-decoration: none;
    font-weight: bold;
    box-shadow: 0 0 15px rgba(0, 255, 213, 0.4);
    transition: 0.3s ease-in-out;
  }

  .btn-back:hover {
    background-color: #00c2cc;
    color: #fff;
  }

  @media (max-width: 576px) {
    .card-puntos {
      padding: 1.5rem;
    }

    .card-puntos h3 {
      font-size: 2.2rem;
    }

    .section-title {
      font-size: 1.4rem;
    }
  }
</style>

</head>
<body>

<div class="container">
  <div class="header">
    <h1>Detalle de Puntos</h1>
    <a href="cliente.php">← Volver</a>
  </div>

  <!-- Puntos actuales -->
  <div class="card-puntos text-center">
    <p>Puntos disponibles</p>
    <h3><?= $puntos_actuales ?> pts</h3>
  </div>

  <!-- Historial de bonificaciones -->
  <h2 class="section-title">Historial de Bonificaciones</h2>
  <div class="table-responsive">
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Compra (MXN)</th>
          <th>Puntos Acreditados</th>
        </tr>
      </thead>
      <tbody>
      <?php if (count($compras)): ?>
        <?php foreach($compras as $c): ?>
          <tr>
            <td><?= date('d/m/Y H:i', strtotime($c['fecha'])) ?></td>
            <td>$<?= number_format($c['monto_compra'],2) ?></td>
            <td>+<?= $c['puntos_acreditados'] ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="3" class="text-center">No hay registros de compras.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Historial de uso de puntos -->
  <h2 class="section-title">Historial de Uso de Puntos</h2>
  <div class="table-responsive mb-4">
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Premio</th>
          <th>Puntos Usados</th>
        </tr>
      </thead>
      <tbody>
      <?php if (count($usos)): ?>
        <?php foreach($usos as $u): ?>
          <tr>
            <td><?= date('d/m/Y H:i', strtotime($u['fecha'])) ?></td>
            <td><?= htmlspecialchars($u['premio']) ?></td>
            <td>-<?= $u['puntos_usados'] ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="3" class="text-center">No has canjeado puntos aún.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

</body>
</html>
