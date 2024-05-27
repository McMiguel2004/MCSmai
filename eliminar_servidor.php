<?php
include 'config.php';

// Recibir el ID del servidor a eliminar
$data = json_decode(file_get_contents("php://input"));
$servidorId = $conn->real_escape_string($data->servidorId);

// Obtener la ID del contenedor del servidor y la ID del usuario
$query = "SELECT container_id, id_usuario FROM servidores WHERE id = $servidorId";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $containerId = $row['container_id'];
    $idUsuario = $row['id_usuario'];

    // Eliminar la entrada en la tabla 'server_properties' asociada al servidor
    $delete_properties_query = "DELETE FROM server_properties WHERE servidor_id = $servidorId";
    if ($conn->query($delete_properties_query) === FALSE) {
        echo json_encode(array("success" => false, "message" => "Error al eliminar las propiedades del servidor"));
        exit();
    }

    // Detener y eliminar el contenedor Docker
    shell_exec("sudo docker rm $containerId");

    // Eliminar el servidor de la base de datos
    $delete_server_query = "DELETE FROM servidores WHERE id = $servidorId";
    if ($conn->query($delete_server_query) === TRUE) {
        // Obtener la cantidad actual de servidores del usuario
        $query_servidores = "SELECT COUNT(id) AS cantidad FROM servidores WHERE id_usuario = $idUsuario";
        $result_servidores = $conn->query($query_servidores);
        $row = $result_servidores->fetch_assoc();
        $cantidad_servidores = $row['cantidad'];

        // Actualizar el contador de servidores del usuario
        $query_update = "UPDATE usuarios SET servidores_creados = $cantidad_servidores WHERE id = $idUsuario";
        if ($conn->query($query_update) === FALSE) {
            echo json_encode(array("success" => false, "message" => "Error al actualizar el contador de servidores creados"));
            exit();
        }

        echo json_encode(array("success" => true));
    } else {
        echo json_encode(array("success" => false, "message" => "Error al eliminar el servidor"));
    }
} else {
    echo json_encode(array("success" => false, "message" => "Servidor no encontrado"));
}

$conn->close();
?>
