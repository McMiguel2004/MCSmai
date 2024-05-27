<?php
include 'config.php';

// Recibir el ID del servidor a iniciar
$data = json_decode(file_get_contents("php://input"));
$servidorId = $conn->real_escape_string($data->servidorId);

// Obtener el servidor de la base de datos
$query = "SELECT * FROM servidores WHERE id = $servidorId";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    $servidor = $result->fetch_assoc();

    // Verificar si el contenedor ya ha sido creado
    if (empty($servidor['container_id'])) {
        // Generar un puerto único para el servidor (por ejemplo, basado en el ID)
        $puerto = 25565 + $servidorId;

        // Obtener las propiedades avanzadas del servidor
        $queryProperties = "SELECT * FROM server_properties WHERE servidor_id = $servidorId";
        $resultProperties = $conn->query($queryProperties);
        
        $maxPlayers = 20;  // valor por defecto
        $difficulty = 'easy';  // valor por defecto
        
        if ($resultProperties->num_rows > 0) {
            $properties = $resultProperties->fetch_assoc();
            $maxPlayers = $properties['max_players'];
            $difficulty = $properties['difficulty'];
        }

        // Construir el comando Docker para crear el contenedor
        $comando = "sudo docker run -d -it -p $puerto:25565 -e EULA=TRUE -e ONLINE_MODE=FALSE -e ICON=https://github.com/hammad2003/smai/blob/master/Img/MacacoSmai.png?raw=true -e VERSION={$servidor['version']}";
        
        // Añadir las variables de entorno avanzadas si difieren de los valores por defecto
        if ($maxPlayers != 20) {
            $comando .= " -e MAX_PLAYERS=$maxPlayers";
        }
        if ($difficulty != 'easy') {
            $comando .= " -e DIFFICULTY=$difficulty";
        }

        // Añadir el tipo de servidor si corresponde
        if ($servidor['software'] === 'Forge') {
            $comando .= " -e TYPE=FORGE";
        } elseif ($servidor['software'] === 'Spigot') {
            $comando .= " -e TYPE=SPIGOT";
        } elseif ($servidor['software'] === 'Bukkit') {
            $comando .= " -e TYPE=BUKKIT";
        }

        $comando .= " itzg/minecraft-server";

        // Ejecutar el comando y obtener la ID del contenedor
        $containerId = trim(shell_exec($comando));

        if ($containerId) {
            // Obtener la dirección IP de la máquina host
            $ip_address = trim(shell_exec("hostname -I | cut -d' ' -f1"));

            // Guardar la ID del contenedor y actualizar el estado en la base de datos
            $updateQuery = "UPDATE servidores SET container_id = '$containerId', ip_address = '$ip_address', puerto = '$puerto', estado = 'Activo' WHERE id = $servidorId";
            if ($conn->query($updateQuery) === TRUE) {
                echo json_encode(array("success" => true));
            } else {
                echo json_encode(array("success" => false, "message" => "Error al actualizar la base de datos"));
            }
        } else {
            echo json_encode(array("success" => false, "message" => "Error al iniciar el servidor Docker"));
        }
    } else {
        // Contenedor ya existe, usar docker start
        $containerId = $servidor['container_id'];
        $comando = "sudo docker start $containerId";
        $resultado = shell_exec($comando);

        if (strpos($resultado, $containerId) !== false) {
            // Actualizar el estado del servidor en la base de datos
            $updateQuery = "UPDATE servidores SET estado = 'Activo' WHERE id = $servidorId";

            if ($conn->query($updateQuery) === TRUE) {
                echo json_encode(array("success" => true));
            } else {
                echo json_encode(array("success" => false, "message" => "Error al actualizar la base de datos"));
            }
        } else {
            echo json_encode(array("success" => false, "message" => "Error al iniciar el contenedor Docker"));
        }
    }
} else {
    echo json_encode(array("success" => false, "message" => "Servidor no encontrado"));
}

$conn->close();
?>
