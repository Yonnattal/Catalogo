<?php
// 1. Forzar la visualización de errores para saber qué falla
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start(); // <--- AGREGA ESTA LÍNEA AL PRINCIPIO

session_start();
include 'bd/conexion.php'; // Verifica que esta ruta sea correcta

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = $_POST['usuario'];
    $pass = $_POST['password'];

    // 2. Consulta preparada para evitar errores de caracteres
    $stmt = $conn->prepare("SELECT id, usuario, password FROM usuarios WHERE usuario = ?");
    if (!$stmt) {
        die("Error en la preparación de la consulta: " . $conn->error);
    }
    
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // 3. Verificación de contraseña (ajusta 'admin123' por la tuya si no usas hash)
        if ($pass === "admin123") { 
            $_SESSION['admin_auth'] = true;
            $_SESSION['usuario_nombre'] = $row['usuario'];
            
            // Redirección forzada
            header("Location: admin.php");
            exit();
        } else {
            $error = "Contraseña incorrecta.";
        }
    } else {
        $error = "El usuario no existe.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso Admin | VenyPaga</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-2xl shadow-2xl w-96">
        <h2 class="text-2xl font-bold text-center mb-6 text-slate-800">Administracion de Pedidos</h2>
        
        <?php if(isset($error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-3 mb-4 text-sm">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="mb-4">
                <label class="block text-slate-700 text-sm font-bold mb-2">Usuario</label>
                <input type="text" name="usuario" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none" required>
            </div>
            <div class="mb-6">
                <label class="block text-slate-700 text-sm font-bold mb-2">Contraseña</label>
                <input type="password" name="password" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none" required>
            </div>
            <button type="submit" class="w-full bg-indigo-600 text-white p-3 rounded-lg font-bold hover:bg-indigo-700 transition shadow-lg">
                INGRESAR AL SISTEMA
            </button>
        </form>
    </div>
</body>
</html>