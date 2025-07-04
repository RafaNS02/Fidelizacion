<?php
require 'firebase.php';

// Simular datos recibidos por POST
$data = [
    'nombre' => $_POST['nombre'] ?? 'Anónimo',
    'correo' => $_POST['correo'] ?? 'sin_correo@ejemplo.com'
];

// Guardar en la ruta "usuarios"
$newUser = $database->getReference('usuarios')->push($data);

echo json_encode([
    'status' => 'success',
    'id' => $newUser->getKey()
]);
