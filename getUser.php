<?php
require 'firebase.php';

$usuarios = $database->getReference('usuarios')->getValue();

echo json_encode($usuarios);
