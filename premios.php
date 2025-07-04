<?php
session_start();
// Verificar rol admin
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Conexión a BD
$pdo = new PDO(
    "mysql:host=localhost;port=3309;dbname=fidelizacion;charset=utf8mb4",
    "root", "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$action = $_GET['action'] ?? 'list';

// Función auxiliar para procesar subida a carpeta img/
function handleUpload($fieldName, &$error) {
    if (empty($_FILES[$fieldName]['name']) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        // Nada que subir
        return null;
    }

    $tmp  = $_FILES[$fieldName]['tmp_name'];
    $name = basename($_FILES[$fieldName]['name']);
    $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif'];
    if (!in_array($ext, $allowed)) {
        $error = "Formato de imagen no válido. Solo JPG, PNG o GIF.";
        return null;
    }

    // Carpeta img/
    $uploadDir = __DIR__ . '/img/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            $error = "No se pudo crear carpeta img/.";
            return null;
        }
    }

    // Nombre único
    $newName = uniqid('premio_', true) . "." . $ext;
    $dest = $uploadDir . $newName;
    if (!move_uploaded_file($tmp, $dest)) {
        $error = "Error al mover el archivo a $dest.";
        return null;
    }

    return "img/{$newName}";
}

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = '';
    if ($_POST['form_type'] === 'save_premio') {
        $id    = $_POST['id'] ?? null;
        $nombre= $_POST['nombre'];
        $descr = $_POST['descripcion'];
        $pts   = (int)$_POST['puntos_requeridos'];
        $stk   = (int)$_POST['stock'];
        $act   = isset($_POST['activo']) ? 1 : 0;

        // Subida de imagen si se seleccionó
        $imgPath = null;
        if (!empty($_FILES['imagen']['name'])) {
            $imgPath = handleUpload('imagen', $error);
            if ($error) {
                $_SESSION['error'] = $error;
                header("Location: premios.php?action=" . ($id ? "edit&id={$id}" : "add"));
                exit;
            }
        }

        if ($id) {
            // UPDATE
            if ($imgPath) {
                $stmt = $pdo->prepare("
                  UPDATE premios
                     SET nombre=?, descripcion=?, puntos_requeridos=?, stock=?, activo=?, imagen=?
                   WHERE id_premio=?
                ");
                $stmt->execute([$nombre, $descr, $pts, $stk, $act, $imgPath, $id]);
            } else {
                $stmt = $pdo->prepare("
                  UPDATE premios
                     SET nombre=?, descripcion=?, puntos_requeridos=?, stock=?, activo=?
                   WHERE id_premio=?
                ");
                $stmt->execute([$nombre, $descr, $pts, $stk, $act, $id]);
            }
        } else {
            // INSERT
            $stmt = $pdo->prepare("
              INSERT INTO premios (nombre, descripcion, puntos_requeridos, stock, activo, imagen)
              VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nombre, $descr, $pts, $stk, $act, $imgPath]);
        }

        header('Location: premios.php');
        exit;
    }

    if ($_POST['form_type'] === 'delete_premio') {
        $id = $_POST['id'];
        $pdo->prepare("DELETE FROM premios WHERE id_premio = ?")
            ->execute([$id]);
        header('Location: premios.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Premios - Admin</title>
  <style>
/* Fondo y tipografía */
body {
  background: linear-gradient(to right, #141e30, #243b55);
  color: #eaeaea;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  margin: 0;
  padding: 0;
}

/* Contenedor principal centrado */
.container {
  max-width: 1200px;
  margin: 2rem auto;
  padding: 2rem;
  background: rgba(255, 255, 255, 0.02);
  border-radius: 16px;
  box-shadow: 0 0 30px rgba(0, 255, 213, 0.06);
}

/* Encabezado */
.header {
  padding-bottom: 1rem;
  margin-bottom: 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid rgba(255,255,255,0.1);
}

.header a {
  font-size: 1.5rem;
  color: #00f7ff;
  text-decoration: none;
  font-weight: bold;
  transition: 0.3s;
}
.header a:hover {
  color: #fff;
}

h2, h4 {
  color: #00f7ff;
  text-shadow: 0 0 5px #00f7ff;
  margin: 0;
}

/* Botones */
.btn {
  display: inline-block;
  padding: 0.5rem 1rem;
  font-weight: bold;
  border-radius: 8px;
  cursor: pointer;
  text-align: center;
  text-decoration: none;
  transition: background 0.3s;
  margin: 0.25rem;
}

.btn-primary {
  background-color: #00f7ff;
  border: none;
  color: #000;
}
.btn-primary:hover {
  background-color: #00cdd7;
  color: #fff;
}

.btn-secondary {
  background-color: #444;
  color: #fff;
  border: none;
}
.btn-secondary:hover {
  background-color: #333;
}

.btn-warning {
  background-color: #ffc107;
  color: #000;
  border: none;
}
.btn-warning:hover {
  background-color: #e0a800;
}

.btn-danger {
  background-color: #ff4c4c;
  color: #fff;
  border: none;
}
.btn-danger:hover {
  background-color: #d43f3f;
}

.btn-sm {
  font-size: 0.85rem;
  padding: 0.4rem 0.7rem;
}

/* Tarjetas */
.card {
  background-color: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 16px;
  padding: 2rem;
  box-shadow: 0 0 25px rgba(0, 255, 213, 0.08);
  margin-bottom: 2rem;
}

/* Tabla */
.table {
  width: 100%;
  background-color: rgba(20, 30, 48, 0.95);
  border-radius: 16px;
  border-collapse: separate;
  border-spacing: 0;
  overflow: hidden;
  box-shadow: 0 0 20px rgba(0, 255, 213, 0.07);
  color: #e0f7fa;
  border: 1px solid rgba(0, 247, 255, 0.2);
  margin-bottom: 2rem;
}

.table thead {
  background-color: #00f7ff;
  color: #000;
}

.table thead th {
  font-weight: bold;
  text-align: left;
  padding: 1rem;
}

.table tbody td {
  padding: 1rem;
  color: #f0f0f0;
  border-top: 1px solid rgba(255,255,255,0.1);
}

.table-striped tbody tr:nth-of-type(odd) {
  background-color: rgba(0, 247, 255, 0.05);
}

.table-striped tbody tr:nth-of-type(even) {
  background-color: rgba(255, 255, 255, 0.03);
}

.table tbody tr:hover {
  background-color: rgba(0, 247, 255, 0.15);
  transform: scale(1.005);
  transition: all 0.2s ease-in-out;
}

/* Formularios */
.form-control {
  background-color: #1f2a38;
  color: #fff;
  border: none;
  border-radius: 10px;
  padding: 0.5rem 0.75rem;
  width: 100%;
  transition: box-shadow 0.2s;
}

.form-control:focus {
  outline: none;
  box-shadow: 0 0 0 2px #00f7ff;
}

.form-label {
  font-weight: 500;
  color: #c5e8ff;
  margin-bottom: 0.5rem;
  display: block;
}

.form-check-label {
  color: #fff;
}

.form-check-input {
  background-color: #1f2a38;
  border: 1px solid #00f7ff;
}
.form-check-input:checked {
  background-color: #00f7ff;
  border-color: #00f7ff;
}

/* Imagen miniatura */
.thumb {
  width: 100px;
  height: 100px;
  object-fit: cover;
  border-radius: 10px;
  box-shadow: 0 0 10px rgba(0,255,213,0.2);
}

/* Alertas */
.alert-danger {
  background-color: #ff4c4c;
  border: none;
  color: #fff;
  padding: 1rem;
  border-radius: 10px;
  margin-bottom: 1rem;
}

/* Botón de regreso */
.btn-back {
  background-color: #00f7ff;
  color: #000;
  border-radius: 20px;
  padding: 0.5rem 1.5rem;
  text-decoration: none;
  font-weight: bold;
  display: inline-block;
  margin-top: 1rem;
  box-shadow: 0 0 10px rgba(0, 255, 213, 0.4);
  transition: 0.3s ease-in-out;
}
.btn-back:hover {
  background-color: #00cdd7;
  color: #fff;
}

/* Utilidades */
.text-center {
  text-align: center;
}
.mt-3 {
  margin-top: 1rem;
}
.mb-3 {
  margin-bottom: 1rem;
}

/* Responsive tabla */
@media screen and (max-width: 768px) {
  .table thead {
    display: none;
  }

  .table, .table tbody, .table tr, .table td {
    display: block;
    width: 100%;
  }

  .table tr {
    margin-bottom: 1rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    padding: 0.5rem;
    border-radius: 10px;
    background-color: rgba(20, 30, 48, 0.85);
  }

  .table td {
    text-align: right;
    padding-left: 50%;
    position: relative;
  }

  .table td::before {
    content: attr(data-label);
    position: absolute;
    left: 1rem;
    width: 45%;
    text-align: left;
    font-weight: bold;
    color: #00f7ff;
  }
}
</style>

</head>
<body>
<div class="container">

  <div class="header">
    <a href="admin.php">&larr;</a>
    <h2>Módulo de Premios</h2>
  </div>

  <?php if(isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
    <?php unset($_SESSION['error']); endif; ?>

  <?php if ($action === 'list'): ?>
    <a href="premios.php?action=add" class="btn btn-primary mb-3">+ Nuevo Premio</a>
    <table class="table table-striped card">
      <thead>
        <tr>
          <th>#</th><th>Imagen</th><th>Nombre</th>
          <th>Puntos</th><th>Stock</th><th>Activo</th><th>Acciones</th>
        </tr>
      </thead>
      <tbody>
      <?php
      $stmt = $pdo->query("SELECT * FROM premios ORDER BY nombre");
      while($p = $stmt->fetch()):
      ?>
        <tr>
          <td><?= $p['id_premio'] ?></td>
          <td>
            <?php if($p['imagen']): ?>
              <img src="<?= htmlspecialchars($p['imagen']) ?>" class="thumb" alt="">
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars($p['nombre']) ?></td>
          <td><?= $p['puntos_requeridos'] ?></td>
          <td><?= $p['stock'] ?></td>
          <td><?= $p['activo'] ? 'Sí':'No' ?></td>
          <td>
            <a href="premios.php?action=edit&id=<?= $p['id_premio'] ?>"
               class="btn btn-sm btn-warning">Editar</a>
            <form method="POST" style="display:inline-block;"
                  onsubmit="return confirm('¿Eliminar este premio?');">
              <input type="hidden" name="form_type" value="delete_premio">
              <input type="hidden" name="id" value="<?= $p['id_premio'] ?>">
              <button class="btn btn-sm btn-danger">Borrar</button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>

  <?php elseif ($action==='add' || $action==='edit'):
      $id = $_GET['id'] ?? null;
      if ($id) {
          $stmt = $pdo->prepare("SELECT * FROM premios WHERE id_premio = ?");
          $stmt->execute([$id]);
          $p = $stmt->fetch();
      }
  ?>
    <div class="card p-4 mt-4">
      <h4><?= $id ? 'Editar' : 'Nuevo' ?> Premio</h4>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="form_type" value="save_premio">
        <?php if($id): ?>
          <input type="hidden" name="id" value="<?= $id ?>">
        <?php endif; ?>

        <div class="mb-3">
          <label class="form-label">Nombre</label>
          <input type="text" name="nombre" class="form-control"
                 value="<?= $p['nombre'] ?? '' ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Descripción</label>
          <textarea name="descripcion" class="form-control" rows="3"><?= $p['descripcion'] ?? '' ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Puntos Requeridos</label>
          <input type="number" name="puntos_requeridos" class="form-control"
                 value="<?= $p['puntos_requeridos'] ?? 0 ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Stock</label>
          <input type="number" name="stock" class="form-control"
                 value="<?= $p['stock'] ?? 0 ?>" required>
        </div>
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" id="activo"
                 name="activo" <?= (!isset($p) || $p['activo']) ? 'checked' : '' ?>>
          <label class="form-check-label" for="activo">Activo</label>
        </div>

        <div class="mb-3 form-img">
          <label class="form-label">Imagen <?= $id ? '(opcional para reemplazar)' : '' ?></label><br>
          <?php if($id && !empty($p['imagen'])): ?>
            <img src="<?= htmlspecialchars($p['imagen']) ?>" class="thumb mb-2" alt=""><br>
          <?php endif; ?>
          <input type="file" name="imagen" accept="image/*" class="form-control">
        </div>

        <button class="btn btn-primary"><?= $id ? 'Actualizar' : 'Guardar' ?></button>
        <a href="premios.php" class="btn btn-secondary">Cancelar</a>
      </form>
    </div>
  <?php endif; ?>

</div>
</body>
</html>
