<?php
session_start();
// 1) Verificar sesión de cliente
// Ahora
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
$pdo  = new PDO("mysql:host=$host;dbname=$db;port=$puerto;charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// 3) Procesar creación de tarjeta (solo desde el modal 2)
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'create_card') {
    $telefono_input = trim($_POST['telefono']);

    // 3.1) Validar teléfono contra la BD
    $stmt = $pdo->prepare("SELECT telefono FROM clientes WHERE id_cliente = ?");
    $stmt->execute([$id_cliente]);
    $telefono_bd = $stmt->fetchColumn();

    if ($telefono_input !== $telefono_bd) {
        $error = "El número de teléfono no coincide.";
    } else {
        // 3.2) Generar número de tarjeta único
        do {
            $numero = '';
            for ($i = 0; $i < 16; $i++) {
                $numero .= rand(0, 9);
            }
            $chk = $pdo->prepare("SELECT 1 FROM tarjetas WHERE numero = ?");
            $chk->execute([$numero]);
        } while ($chk->fetch());

        // Generar CVV único
        do {
            $cvv = str_pad((string)rand(0, 999), 3, '0', STR_PAD_LEFT);
            $chk = $pdo->prepare("SELECT 1 FROM tarjetas WHERE cvv = ?");
            $chk->execute([$cvv]);
        } while ($chk->fetch());

        // Fecha de vencimiento a +4 años
        $fecha_vto = date('Y-m-d', strtotime('+4 years'));

        // 3.3) Insertar en tarjetas y actualizar al cliente
        $pdo->beginTransaction();
        $pdo->prepare("
            INSERT INTO tarjetas (id_cliente, numero, fecha_vencimiento, cvv)
            VALUES (?, ?, ?, ?)
        ")->execute([$id_cliente, $numero, $fecha_vto, $cvv]);

        $pdo->prepare("
            UPDATE clientes
               SET tarjeta_digital = 'si'
             WHERE id_cliente = ?
        ")->execute([$id_cliente]);
        $pdo->commit();

        header('Location: cliente.php');
        exit;
    }
}

// 4) Obtener datos del cliente y tarjeta
$stmt = $pdo->prepare("
    SELECT
      c.nombre,
      c.apellidos,
      c.puntos_actuales,
      c.tarjeta_digital,
      t.numero,
      t.fecha_vencimiento,
      t.cvv
    FROM clientes c
    LEFT JOIN tarjetas t ON t.id_cliente = c.id_cliente
    WHERE c.id_cliente = ?
");
$stmt->execute([$id_cliente]);
$u = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Mi Cuenta - Fidelización</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(to right, #0f2027, #203a43, #2c5364);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: #eaeaea;
    }

    h2, h4 {
      color: #00ffd5;
    }

    .card-virtual {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(8px);
      border-radius: 20px;
      padding: 2rem;
      max-width: 400px;
      margin: auto;
      box-shadow: 0 0 25px rgba(0, 255, 213, 0.2);
      position: relative;
    }

    .card-number {
      font-size: 1.6rem;
      letter-spacing: 3px;
      margin: 1.5rem 0;
      color: #00ffd5;
      text-shadow: 0 0 5px #00ffd5;
    }

    .section {
      text-align: center;
      margin: 3rem 0;
    }

    .btn {
      border-radius: 30px;
      font-weight: bold;
      padding: 0.6rem 1.5rem;
    }

    .btn-outline-primary {
      color: #00ffd5;
      border-color: #00ffd5;
    }

    .btn-outline-primary:hover {
      background-color: #00ffd5;
      color: #000;
    }

    .btn-outline-success {
      color: #6cff7a;
      border-color: #6cff7a;
    }

    .btn-outline-success:hover {
      background-color: #6cff7a;
      color: #000;
    }

    .btn-outline-warning {
      color: #f7ce46;
      border-color: #f7ce46;
    }

    .btn-outline-warning:hover {
      background-color: #f7ce46;
      color: #000;
    }

    .modal-content {
      background-color: #1f2b37;
      color: #fff;
      border-radius: 16px;
    }

    .modal-header, .modal-footer {
      border: none;
    }

    .form-control {
      background-color: #2c3e50;
      color: #fff;
      border: none;
      border-radius: 10px;
    }

    .btn-primary {
      background-color: #00ffd5;
      color: #000;
      border: none;
    }

    .btn-secondary {
      background-color: #e74c3c;
      color: white;
      border: none;
    }

    .btn-light {
      background-color: #fff;
      color: #000;
    }

    img {
      border-radius: 8px;
    }
</style>
</head>
<body>

<div class="container py-4">

  <?php if ($u['tarjeta_digital'] !== 'si'): ?>
    <!-- Modal 1: Confirmar creación de tarjeta -->
    <div class="modal" id="modalConfirm" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Crear Tarjeta Digital</h5>
          </div>
          <div class="modal-body">
            <p>Para usar la aplicación necesitas crear tu tarjeta digital.</p>
          </div>
          <div class="modal-footer">
            <button type="button" id="btnAccept" class="btn btn-primary">Aceptar</button>
            <a href="logout.php" class="btn btn-secondary">Rechazar</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal 2: Formulario de teléfono -->
    <div class="modal" id="modalPhone" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <form method="POST">
            <input type="hidden" name="form_type" value="create_card">
            <div class="modal-header">
              <h5 class="modal-title">Verificar Teléfono</h5>
            </div>
            <div class="modal-body">
              <p>Ingresa tu teléfono registrado:</p>
              <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
              <?php endif; ?>
              <input type="text" name="telefono" class="form-control" required>
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn btn-primary">Crear Tarjeta</button>
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            </div>
          </form>
        </div>
      </div>
    </div>

  <?php else: ?>
    <!-- UI principal -->
    <div class="text-center mb-4">
      <h2>Bienvenido, <?= htmlspecialchars($u['nombre'].' '.$u['apellidos']) ?></h2>
    </div>

    <!-- Tarjeta virtual -->
    <div class="card-virtual mb-4">
      <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/16/Former_Visa_%28company%29_logo.svg/2560px-Former_Visa_%28company%29_logo.svg.png" alt="Logo" 
           class="position-absolute" style="top:1rem; right:1rem; width:50px">
      <div class="card-number"><?= chunk_split($u['numero'], 4, ' ') ?></div>
      <button class="btn btn-light btn-sm position-absolute"
              style="bottom:1rem; right:1rem;"
              data-bs-toggle="modal" data-bs-target="#detallesCard">
        Detalles
      </button>
    </div>

    <!-- Modal: detalles de tarjeta -->
    <div class="modal fade" id="detallesCard" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Detalles de tu Tarjeta</h5>
          </div>
          <div class="modal-body text-center">
            <p><strong>Número:</strong> <?= chunk_split($u['numero'],4,' ') ?></p>
            <p><strong>Vence:</strong> <?= date('m/Y', strtotime($u['fecha_vencimiento'])) ?></p>
            <p><strong>CVV:</strong> <?= htmlspecialchars($u['cvv']) ?></p>
          </div>
          <div class="modal-footer">
            <button class="btn btn-primary" data-bs-dismiss="modal">Vale</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Secciones -->
    <div class="section">
      <h4>Puntos Acumulados</h4>
      <p class="display-5"><?= $u['puntos_actuales'] ?> pts</p>
      <a href="punto_detalle.php" class="btn btn-outline-primary">Ver Detalles</a>
    </div>
    <div class="section">
      <h4>Premios</h4>
      <img src="https://www.tecnocarreteras.es/wp-content/uploads/sites/2/2018/12/Premios-y-reconocimientos.jpg" alt="Premios" class="mb-2" style="border-radius: 20px;">
      <br>
      <a href="premios_usuario.php" class="btn btn-outline-success">Ir a Premios</a>
    </div>
    <div class="section">
      <h4>Beneficios</h4>
      <img src="https://www.observatoriorh.com/wp-content/uploads/2016/10/beneficios-sociales.jpg" alt="Beneficios" class="mb-2" style="border-radius: 20px;">
      <br>
      <a href="beneficio_usuario.php" class="btn btn-outline-warning">Ir a Beneficios</a>
    </div>
  <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const hasCard = <?= json_encode($u['tarjeta_digital'] === 'si') ?>;
  if (!hasCard) {
    // Mostrar primero el modal de confirmación
    const modalConfirm = new bootstrap.Modal(document.getElementById('modalConfirm'), {
      backdrop: 'static',
      keyboard: false
    });
    modalConfirm.show();

    document.getElementById('btnAccept').addEventListener('click', () => {
      modalConfirm.hide();
      const modalPhone = new bootstrap.Modal(document.getElementById('modalPhone'), {
        backdrop: 'static',
        keyboard: false
      });
      modalPhone.show();
    });
  }
});
</script>
</body>
</html>
