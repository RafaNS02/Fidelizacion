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


// Obtener todos los beneficios activos
$stmt = $pdo->query("SELECT id_beneficio, empresa, imagen FROM beneficios WHERE activo = 1");
$beneficios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Beneficios Disponibles</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
  body {
    background: linear-gradient(to right, #141e30, #243b55);
    font-family: 'Segoe UI', sans-serif;
    color: #f8f9fa;
    margin: 0;
    padding: 0;
  }

  .header {
    display: flex;
    align-items: center;
    padding: 1.5rem 2rem 0;
  }

  .header a {
    color: #00f7ff;
    font-weight: 600;
    text-decoration: none;
    font-size: 1.1rem;
    transition: color 0.3s ease;
  }

  .header a:hover {
    color: #ffffff;
  }

  h2 {
    text-align: center;
    color: #00f7ff;
    margin: 2rem 0 1rem;
    text-shadow: 0 0 8px #00f7ff;
  }

  .container {
    max-width: 1200px;
    margin: 0 auto;
  }

  .row {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    justify-content: center;
  }

  .card-beneficio {
    background: #1b2735;
    border: none;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 0 15px rgba(0, 247, 255, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    width: 100%;
    max-width: 280px;
    cursor: pointer;
  }

  .card-beneficio:hover {
    transform: translateY(-8px);
    box-shadow: 0 0 20px rgba(0, 247, 255, 0.3);
  }

  .card-beneficio img {
    width: 100%;
    height: 180px;
    object-fit: cover;
    border-bottom: 3px solid #00f7ff;
  }

  .card-title {
    color: #00f7ff;
    font-size: 1.1rem;
    font-weight: bold;
    margin-top: 0.75rem;
  }

  .card-body {
    padding: 1rem;
    background-color: transparent;
    text-align: center;
  }

  @media (max-width: 768px) {
    .card-beneficio {
      max-width: 100%;
    }
  }
</style>

</head>
<body>
      <div class="header">
    <a href="cliente.php">← Volver</a>
  </div>
  <div class="container py-4">
    <h2 class="mb-4 text-center">Beneficios para Ti</h2>
    <div class="row g-3">
      <?php foreach($beneficios as $b): ?>
        <div class="col-sm-6 col-md-4 col-lg-3">
          <div class="card card-beneficio" onclick="location.href='beneficio_usudeta.php?id=<?= $b['id_beneficio'] ?>'">
            <img src="<?= htmlspecialchars($b['imagen']) ?>" class="card-img-top" alt="<?= htmlspecialchars($b['empresa']) ?>">
            <div class="card-body text-center">
              <h5 class="card-title mb-0"><?= htmlspecialchars($b['empresa']) ?></h5>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</body>
</html>
