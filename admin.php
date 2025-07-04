<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Panel de Administración</title>
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet"
  />
  <style>
  :root {
    --fondo-principal: #f7f9fc;
    --color-primario: #4a90e2;
    --color-secundario: #50e3c2;
    --color-acentado: #f5a623;
    --color-suave: #f0f0f5;
    --texto-oscuro: #333;
  }

  body {
    margin: 0;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background-color: var(--fondo-principal);
    background-image: linear-gradient(120deg, #e0eafc, #cfdef3);
    font-family: 'Segoe UI', sans-serif;
  }

  header {
    background-color: white;
    padding: 1.2rem 2rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 4px solid var(--color-primario);
  }

  header h1 {
    font-size: 2rem;
    font-weight: 600;
    color: var(--color-primario);
    margin: 0;
  }

  .btn-header {
    margin-left: .75rem;
  }

  main {
    flex: 1;
    display: flex;
    justify-content: center;
    padding: 2rem;
  }

  .dashboard-grid {
    display: flex;
    flex-direction: column;
    gap: 2rem;
    width: 100%;
    max-width: 600px;
  }

  .dashboard-item {
    width: 100%;
  }

  .card-dashboard {
    border-radius: 1rem;
    background-color: white;
    padding: 2rem;
    box-shadow: 0 6px 16px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: transform 0.3s, box-shadow 0.3s;
    cursor: pointer;
    position: relative;
    overflow: hidden;
  }

  .card-dashboard:hover {
    transform: translateY(-6px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
  }

  .card-title {
    font-size: 1.8rem;
    font-weight: 600;
    color: var(--texto-oscuro);
    margin: 0;
  }

  /* nuevos fondos para dar distinción */
  .bg-clientes   { background-color: #e6f7ff; }
  .bg-puntos     { background-color: #d7fce8; }
  .bg-premios    { background-color: #f9f0ff; }
  .bg-beneficios { background-color: #fff5e6; }
</style>

</head>
<body>
  <?php
    session_start();
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
      header('Location: login.php');
      exit;
    }
  ?>
  <header>
    <h1>Administrador</h1>
    <div>
      <!-- <a href="perfil.php" class="btn btn-outline-primary btn-header">Perfil</a> -->
      <a href="logout.php" class="btn btn-danger btn-header">Cerrar Sesión</a>
    </div>
  </header>

  <main>
    <div class="dashboard-grid">
      <div class="dashboard-item">
        <a href="clientes.php" class="text-decoration-none">
          <div class="card card-dashboard bg-clientes">
            <h5 class="card-title">Clientes</h5>
          </div>
        </a>
      </div>
      <div class="dashboard-item">
        <a href="puntos.php" class="text-decoration-none">
          <div class="card card-dashboard bg-puntos">
            <h5 class="card-title">Puntos</h5>
          </div>
        </a>
      </div>
      <div class="dashboard-item">
        <a href="premios.php" class="text-decoration-none">
          <div class="card card-dashboard bg-premios">
            <h5 class="card-title">Premios</h5>
          </div>
        </a>
      </div>
      <div class="dashboard-item">
        <a href="beneficios.php" class="text-decoration-none">
          <div class="card card-dashboard bg-beneficios">
            <h5 class="card-title">Beneficios</h5>
          </div>
        </a>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
