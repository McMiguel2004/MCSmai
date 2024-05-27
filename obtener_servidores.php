<?php
session_start();
include 'config.php';

// Obtener el ID de usuario de la sesión actual
$idUsuario = $_SESSION['id_usuario'];

// Consulta SQL para obtener los servidores del usuario actual
$sql = "SELECT * FROM servidores WHERE id_usuario = $idUsuario";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $servidores = array();

    while ($row = $result->fetch_assoc()) {
        $servidores[] = $row;
    }

    // Devolver la respuesta como JSON
    echo json_encode($servidores);
} else {
    // Devolver un arreglo vacío si el usuario no tiene servidores
    echo json_encode([]);
}

// Cierra la conexión a la base de datos
$conn->close();
?>