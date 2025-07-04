<?php
session_start();
// 1) Verificar sesión de cliente
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'cliente') {
    header('Location: login.php');
    exit;
}
$id_cliente = $_SESSION['user_id'];

// Conexión
$pdo = new PDO(
    "mysql:host=localhost;port=3309;dbname=fidelizacion;charset=utf8mb4",
    "root","", [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]
);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("
    SELECT empresa, descripcion, descuento, vigente_desde, vigente_hasta, imagen
      FROM beneficios
     WHERE id_beneficio = ? AND activo = 1
");
$stmt->execute([$id]);
$b = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$b) {
    echo "<p class='text-center mt-5'>Beneficio no encontrado o ya no está disponible.</p>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Detalle del Beneficio</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
  body {
    background: linear-gradient(to right, #141e30, #243b55);
    font-family: 'Segoe UI', sans-serif;
    color: #f1f1f1;
    margin: 0;
    padding: 0;
  }

  .beneficio-header {
    background: #00f7ff;
    color: #141e30;
    padding: 2.5rem 1rem;
    border-radius: 0 0 20px 20px;
    text-align: center;
    box-shadow: 0 5px 20px rgba(0, 247, 255, 0.2);
  }

  .beneficio-header img {
    max-width: 140px;
    border: 5px solid #141e30;
    border-radius: 12px;
    margin-bottom: 1rem;
    box-shadow: 0 0 12px rgba(0, 0, 0, 0.4);
  }

  .beneficio-header h1 {
    font-size: 2rem;
    font-weight: bold;
    margin: 0;
    text-shadow: 0 0 5px rgba(0,0,0,0.3);
  }

  .detalle {
    max-width: 750px;
    margin: 3rem auto;
    background: rgba(255, 255, 255, 0.05);
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 0 15px rgba(0, 247, 255, 0.05);
  }

  .detalle dt {
    font-weight: 600;
    color: #00f7ff;
  }

  .detalle dd {
    margin-bottom: 1.2rem;
    color: #e3e3e3;
  }

  .btn-outline-primary {
    border-color: #00f7ff;
    color: #00f7ff;
    font-weight: 600;
    transition: all 0.3s ease;
  }

  .btn-outline-primary:hover {
    background-color: #00f7ff;
    color: #141e30;
    box-shadow: 0 0 10px #00f7ff;
  }

  @media (max-width: 576px) {
    .beneficio-header h1 {
      font-size: 1.5rem;
    }
    .detalle {
      margin: 2rem 1rem;
    }
  }
</style>

</head>
<body>
  <div class="beneficio-header">
    <img src="<?= htmlspecialchars($b['imagen']) ?>" alt="<?= htmlspecialchars($b['empresa']) ?>">
    <h1><?= htmlspecialchars($b['empresa']) ?></h1>
  </div>

  <div class="detalle container">
    <dl class="row">
      <dt class="col-sm-3">Descripción:</dt>
      <dd class="col-sm-9"><?= nl2br(htmlspecialchars($b['descripcion'])) ?></dd>

      <dt class="col-sm-3">Descuento:</dt>
      <dd class="col-sm-9"><?= htmlspecialchars($b['descuento']) ?></dd>

      <dt class="col-sm-3">Vigencia:</dt>
      <dd class="col-sm-9">
        Desde <?= date('d/m/Y', strtotime($b['vigente_desde'])) ?>
        hasta <?= date('d/m/Y', strtotime($b['vigente_hasta'])) ?>
      </dd>
    </dl>
    <div class="text-center mt-4">
      <a href="beneficio_usuario.php" class="btn btn-outline-primary">← Volver a Beneficios</a>
    </div>
  </div>
</body>
</html>
