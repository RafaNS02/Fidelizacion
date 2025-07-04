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

// 1) Obtener datos de cliente y tarjeta
$stmt = $pdo->prepare("
  SELECT c.puntos_actuales, t.numero
    FROM clientes c
    LEFT JOIN tarjetas t ON t.id_cliente = c.id_cliente
   WHERE c.id_cliente = ?
");
$stmt->execute([$id_cliente]);
$u = $stmt->fetch(PDO::FETCH_ASSOC);

// 2) Procesar compra de premio
$message = "";
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='buy') {
    $id_premio = (int)$_POST['id_premio'];

    // 2.1) Info del premio
    $p = $pdo->prepare("SELECT puntos_requeridos, stock FROM premios WHERE id_premio = ? AND activo = 1");
    $p->execute([$id_premio]);
    $premio = $p->fetch(PDO::FETCH_ASSOC);

    if (!$premio) {
        $message = "Premio no encontrado.";
    } elseif ($u['puntos_actuales'] < $premio['puntos_requeridos']) {
        $message = "No tienes suficientes puntos.";
    } elseif ($premio['stock'] < 1) {
        $message = "Lo sentimos, este premio está agotado.";
    } else {
        // 2.2) Transacción: insertar redención, actualizar puntos y stock
        $pdo->beginTransaction();
        $puntos_restantes = $u['puntos_actuales'] - $premio['puntos_requeridos'];
        $inst = $pdo->prepare("
          INSERT INTO redenciones (id_cliente,id_premio,puntos_usados,puntos_restantes)
          VALUES (?,?,?,?)
        ");
        $inst->execute([$id_cliente, $id_premio, $premio['puntos_requeridos'],$puntos_restantes]);

        $upd1 = $pdo->prepare("
          UPDATE clientes
             SET puntos_actuales = puntos_actuales - ?
           WHERE id_cliente = ?
        ");
        $upd1->execute([$premio['puntos_requeridos'], $id_cliente]);

        $upd2 = $pdo->prepare("
          UPDATE premios
             SET stock = stock - 1
           WHERE id_premio = ?
        ");
        $upd2->execute([$id_premio]);

        $pdo->commit();
        // recarga datos de puntos
        $u['puntos_actuales'] -= $premio['puntos_requeridos'];
        $message = "¡Has canjeado el premio correctamente!";
    }
}

// 3) Obtener lista de premios activos
$stmt = $pdo->query("
  SELECT id_premio, nombre, descripcion, puntos_requeridos, stock, imagen
    FROM premios
   WHERE activo = 1
   ORDER BY puntos_requeridos ASC
");
$premios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Premios</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet">
<style>
  body {
    background: linear-gradient(to right, #1e3c72, #2a5298);
    font-family: 'Segoe UI', sans-serif;
    color: #f0f0f0;
  }

  .header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 2rem 0 1rem;
  }

  .header a {
    color: #00f7ff;
    font-weight: bold;
    text-decoration: none;
    font-size: 1.1rem;
    transition: 0.3s;
  }

  .header a:hover {
    color: #ffffff;
  }

  h2 {
    color: #00f7ff;
    text-shadow: 0 0 5px #00f7ff;
  }

  .card-mini {
    background: #00f7ff;
    color: #000;
    padding: 1rem 1.5rem;
    border-radius: 14px;
    box-shadow: 0 0 15px rgba(0, 247, 255, 0.4);
    font-weight: bold;
    text-align: center;
  }

  .grid {
    display: grid;
    gap: 2rem;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    margin-bottom: 3rem;
  }

  .premio-card {
    border-radius: 16px;
    overflow: hidden;
    background: #101c3a;
    box-shadow: 0 0 12px rgba(0, 255, 213, 0.1);
    transition: transform 0.2s ease-in-out;
  }

  .premio-card:hover {
    transform: scale(1.02);
    box-shadow: 0 0 20px rgba(0, 255, 213, 0.25);
  }

  .premio-card img {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-bottom: 2px solid #00f7ff;
  }

  .premio-body {
    padding: 1rem;
  }

  .premio-name {
    font-size: 1.2rem;
    font-weight: bold;
    color: #00f7ff;
    margin-bottom: 0.5rem;
  }

  .premio-pts {
    font-weight: 600;
    margin-bottom: 1rem;
    color: #ffffff;
  }

  .btn-outline-primary {
    background-color: transparent;
    border: 2px solid #00f7ff;
    color: #00f7ff;
    font-weight: bold;
    border-radius: 10px;
    transition: 0.3s;
  }

  .btn-outline-primary:hover {
    background-color: #00f7ff;
    color: #000;
  }

  .btn-outline-primary:disabled {
    background-color: #444;
    color: #aaa;
    border-color: #444;
    cursor: not-allowed;
  }

  .alert-info {
    background-color: #00f7ff;
    border: none;
    color: #000;
    font-weight: bold;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0,247,255,0.3);
    margin-bottom: 1.5rem;
  }
</style>
</head>
<body>
      <div class="header">
    <a href="cliente.php">← Volver</a>
  </div>
<div class="container">
  <div class="header">
    <h2>Premios Disponibles</h2>
    <div class="card-mini">
      <small>Tarjeta nº</small><br>
      <?= chunk_split($u['numero']??'************0000',4,' ') ?>
      <br><small><?= $u['puntos_actuales'] ?> pts</small>
    </div>
  </div>

  <?php if($message): ?>
    <div class="alert alert-info text-center"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <div class="grid">
    <?php foreach($premios as $p): ?>
      <div class="premio-card">
        <img src="<?= htmlspecialchars($p['imagen']?:'https://via.placeholder.com/300x140?text=Premio') ?>"
             alt="<?= htmlspecialchars($p['nombre']) ?>">
        <div class="premio-body">
          <div class="premio-name"><?= htmlspecialchars($p['nombre']) ?></div>
          <div class="premio-pts"><?= $p['puntos_requeridos'] ?> pts</div>
          <form method="POST">
            <input type="hidden" name="action" value="buy">
            <input type="hidden" name="id_premio" value="<?= $p['id_premio'] ?>">
            <button class="btn btn-outline-primary w-100"
                    <?= $u['puntos_actuales'] < $p['puntos_requeridos'] || $p['stock']<1 
                        ? 'disabled' : '' ?>>
              <?= $p['stock']<1 ? 'Agotado' : 'Comprar' ?>
            </button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

</div>

</body>
</html>
