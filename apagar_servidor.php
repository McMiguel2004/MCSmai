<?php
include 'config.php';

// Recibir el ID del servidor a eliminar
$data = json_decode(file_get_contents("php://input"));
$servidorId = $conn->real_escape_string($data->servidorId);

// Obtener la ID del contenedor del servidor
$query = "SELECT container_id FROM servidores WHERE id = $servidorId";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    $containerId = $result->fetch_assoc()['container_id'];

    // Detener el contenedor Docker
    shell_exec("sudo docker stop $containerId");

    // Actualizar el estado del servidor en la base de datos
    $updateQuery = "UPDATE servidores SET estado = 'Detenido' WHERE id = $servidorId";
    if ($conn->query($updateQuery) === TRUE) {
        // Respondemos que se ha detenido correctamente
        echo json_encode(array("success" => true));
    } else {
        // Error al actualizar el estado
        echo json_encode(array("success" => false, "message" => "Error al actualizar el estado del servidor: " . $conn->error));
    }
} else {
    echo json_encode(array("success" => false, "message" => "Servidor no encontrado"));
}

$conn->close();
?>