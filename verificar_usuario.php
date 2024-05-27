<?php
include 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $correo = $_POST['correo'];
    $nombre = $_POST['nombre'];

    // Verificar si el correo es de Gmail
    if (substr($correo, -10) !== '@gmail.com') {
        echo json_encode(['status' => 'invalid_email']);
        exit();
    }

    $sql = "SELECT * FROM usuarios WHERE correo = ? OR nombre = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $correo, $nombre);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['status' => 'exists']);
    } else {
        echo json_encode(['status' => 'success']);
    }

    $stmt->close();
}
?>