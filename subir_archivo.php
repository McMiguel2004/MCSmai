<?php
include 'config.php';


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
   if (isset($_FILES['archivo']) && isset($_POST['servidorId'])) {
       $archivo = $_FILES['archivo'];
       $servidorId = $_POST['servidorId'];


       // Verificar si hay algún error al subir el archivo
       if ($archivo['error'] !== UPLOAD_ERR_OK) {
           echo json_encode(['success' => false, 'message' => 'Error al subir el archivo: ' . obtenerMensajeError($archivo['error'])]);
           exit();
       }


       // Obtener el container_id y software del servidor desde la base de datos
        $query = "SELECT container_id, software FROM servidores WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $servidorId);
        $stmt->execute();
        $stmt->bind_result($container_id, $software);
        $stmt->fetch();
        $stmt->close();


if ($container_id) {
   // Directorio en el servidor donde se guardarán los archivos de manera permanente
   if ($software === 'Forge') {
       $directorioDestino = "/home/usuario/mods";
   } else if ($software === 'Spigot' || $software === 'Bukkit') {
       $directorioDestino = "/home/usuario/plugins";
   } else {
       // Si el software no es ni Forge ni Spigot, puedes manejar el error como desees
       echo json_encode(['success' => false, 'message' => 'Software del servidor no compatible']);
       exit();
   }


   // Verificar si el directorio de destino existe, si no, crearlo
   if (!file_exists($directorioDestino)) {
       mkdir($directorioDestino, 0777, true);
   }


   // Nombre del archivo en el servidor
   $nombreArchivoServidor = $directorioDestino . "/" . basename($archivo['name']);


   // Mover el archivo al directorio de destino en el servidor
   if (move_uploaded_file($archivo['tmp_name'], $nombreArchivoServidor)) {
       // Obtener la ID del contenedor y el nombre del archivo
       if ($software === 'Forge' ) {
           $directorioDestinoContenedor = "/data/mods/";
       } else if ($software === 'Spigot' || $software === 'Bukkit') {
           $directorioDestinoContenedor = "/data/plugins/";
       }
       $comando = "sudo docker cp \"$nombreArchivoServidor\" \"$container_id:$directorioDestinoContenedor\"";
      
       // Ejecutar el comando
       $output = [];
       $return_var = null;
       exec($comando, $output, $return_var);


       // Eliminar el archivo del directorio de destino en el servidor
       unlink($nombreArchivoServidor);


       if ($return_var === 0) {
           // Respuesta de éxito
           echo json_encode(['success' => true, 'message' => 'Archivo subido correctamente. PORFAVOR reinicie el servidor para que terminen de subir los archivos']);
       } else {
           // Respuesta de error
           echo json_encode(['success' => false, 'message' => 'Error al enviar el archivo al contenedor']);
       }
   } else {
       // Error al mover el archivo
       echo json_encode(['success' => false, 'message' => 'Error al subir el archivo al servidor']);
   }
} else {
   echo json_encode(['success' => false, 'message' => 'Servidor no encontrado']);
}


   } else {
       echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
   }
} else {
   echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}


function obtenerMensajeError($codigo) {
   switch ($codigo) {
       case UPLOAD_ERR_INI_SIZE:
           return 'El archivo subido excede la directiva upload_max_filesize en php.ini';
       case UPLOAD_ERR_FORM_SIZE:
           return 'El archivo subido excede la directiva MAX_FILE_SIZE especificada en el formulario HTML';
       case UPLOAD_ERR_PARTIAL:
           return 'El archivo subido solo se ha subido parcialmente';
       case UPLOAD_ERR_NO_FILE:
           return 'No se ha subido ningún archivo';
       case UPLOAD_ERR_NO_TMP_DIR:
           return 'Falta la carpeta temporal';
       case UPLOAD_ERR_CANT_WRITE:
           return 'No se pudo escribir el archivo en el disco';
       case UPLOAD_ERR_EXTENSION:
           return 'Una extensión de PHP detuvo la subida del archivo';
       default:
           return 'Error desconocido al subir el archivo';
   }
}
?>

