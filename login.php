<?php
session_start();
// Configuración de la conexión a la base de datos
$host = 'localhost';
$db   = 'fidelizacion';
$user = 'root';
$pass = ''; // Ajusta según tu configuración
$puerto = '3309';
$dsn  = "mysql:host=$host;port=$puerto;dbname=$db;charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

$error = '';
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $telefono = trim($_POST['telefono']);
    $password = trim($_POST['password']);

    if (empty($telefono) || empty($password)) {
        $error = 'Ingresa teléfono y contraseña.';
    } else {
        // Intentar login como admin (comparación en texto plano)
        $stmt = $pdo->prepare('SELECT id_admin AS id, nombre, password_hash FROM admin WHERE telefono = ?');
        $stmt->execute([$telefono]);
        $admin = $stmt->fetch();
        if ($admin && $password === $admin['password_hash']) {
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['nombre']  = $admin['nombre'];
            $_SESSION['rol']     = 'admin';
            echo "<script>
                    alert('Bienvenido, {$admin['nombre']}');
                    window.location.href='admin.php';
                  </script>";
            exit;
        }

        // Intentar login como cliente (comparación en texto plano)
        $stmt = $pdo->prepare('SELECT id_cliente AS id, nombre, password_hash FROM clientes WHERE telefono = ?');
        $stmt->execute([$telefono]);
        $cliente = $stmt->fetch();
        if ($cliente && $password === $cliente['password_hash']) {
            $_SESSION['user_id'] = $cliente['id'];
            $_SESSION['nombre']  = $cliente['nombre'];
            $_SESSION['rol']     = 'cliente';
            echo "<script>
                    alert('Bienvenido, {$cliente['nombre']}');
                    window.location.href='cliente.php';
                  </script>";
            exit;
        }

        // Credenciales inválidas
        $error = 'Teléfono o contraseña incorrectos.';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Programa de Fidelización</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
    body {
        height: 100vh;
        margin: 0;
        padding: 0;
        background: url('https://png.pngtree.com/background/20230425/original/pngtree-colorful-watercolor-dots-background-wallpaper-free-download-picture-image_2473451.jpg') no-repeat center center fixed;
        background-size: cover;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #fff;
    }

    .overlay {
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.6);
        z-index: 0;
    }

    .login-form {
        width: 100%;
        max-width: 400px;
        padding: 30px;
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(15px);
        box-shadow: 0 0 20px rgba(0, 255, 213, 0.2);
        z-index: 1;
        position: relative;
    }

    .card-title {
        font-size: 1.8rem;
        text-align: center;
        color: #00ffd5;
        margin-bottom: 20px;
    }

    .form-control {
        background-color: rgba(255, 255, 255, 0.07);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 8px;
    }

    .form-control::placeholder {
        color: #ccc;
    }

    label {
        font-weight: 500;
        color: #eee;
    }

    .btn-primary {
        background-color: #00ffd5;
        color: #000;
        border: none;
        font-weight: bold;
        transition: background-color 0.3s ease;
    }

    .btn-primary:hover {
        background-color: #00bfa6;
    }

    .error-alert {
        background-color: rgba(231, 76, 60, 0.9);
        color: #fff;
        padding: 10px;
        border-radius: 6px;
        text-align: center;
        margin-bottom: 15px;
        font-size: 0.95rem;
    }

    @media (max-width: 480px) {
        .login-form {
            padding: 20px;
            margin: 0 10px;
        }
    }
</style>
</head>
<body>
    <div class="overlay"></div>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="login-form">
                    <h3 class="text-center card-title">Iniciar sesión</h3>
                    <?php if (!empty($error)): ?>
                        <div class="error-alert"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label>Teléfono</label>
                            <input type="text" name="telefono" class="form-control" pattern="\d{10,15}" title="Sólo números, entre 10 y 15 dígitos" required>
                        </div>
                        <div class="mb-3">
                            <label>Contraseña</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Ingresar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
