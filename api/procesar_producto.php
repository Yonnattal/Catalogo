<?php
// 1. Mostrar errores por si algo falla internamente
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start(); 
session_start();
if (!isset($_SESSION['admin_auth'])) {
    die("Acceso denegado");
}

include 'bd/conexion.php';

if (isset($_GET['eliminar'])) {
    $id_eliminar = intval($_GET['eliminar']);
    
    // Opcional: Obtener nombre de la foto para borrar el archivo del servidor
    $res = $conn->query("SELECT foto FROM productos WHERE idproducto = $id_eliminar");
    if($f = $res->fetch_assoc()){
        if($f['foto'] != "default.jpg" && file_exists("img/" . $f['foto'])){
            unlink("img/" . $f['foto']);
        }
    }

    $stmt = $conn->prepare("DELETE FROM productos WHERE idproducto = ?");
    $stmt->bind_param("i", $id_eliminar);
    
    if ($stmt->execute()) {
        header("Location: inventario.php?status=deleted");
    } else {
        echo "Error al eliminar: " . $conn->error;
    }
    exit();
}
// Verificamos que lleguen los datos básicos
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $id         = isset($_POST['idproducto']) ? intval($_POST['idproducto']) : 0;
    $desc       = $_POST['descripcion'];
    $precio     = floatval($_POST['precio']);
    $precio2     = floatval($_POST['precio2']);
    $stock      = intval($_POST['disponible']);
    $Minimo      = intval($_POST['Minimo']);
    $tipo       = $_POST['tipo']; // Capturamos el tipo del combo
    $venta      = $_POST['venta']; // <--- NUEVO CAMPO CAPTURADO (Mayor/Detal)
    $nombreFoto = "";

    // Manejo de la foto (solo si se subió una nueva)
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $nombreFoto = time() . "_" . basename($_FILES['foto']['name']);
        if (!move_uploaded_file($_FILES['foto']['tmp_name'], "img/" . $nombreFoto)) {
            die("Error al subir la imagen. Revisa los permisos de la carpeta /img/");
        }
    }

    if ($id > 0) {
        // MODO EDICIÓN
        if ($nombreFoto != "") {
            // Actualiza incluyendo foto, tipo y venta
            $stmt = $conn->prepare("UPDATE productos SET descripcion=?, disponible=?,Minimo=?, precio=?,precio2=?, foto=?, Tipo=?, Venta=? WHERE idproducto=?");
            $stmt->bind_param("siiddsssi", $desc, $stock, $Minimo,$precio,$precio2, $nombreFoto, $tipo, $venta, $id);
        } else {
            // Actualiza sin tocar la foto, pero incluye tipo y venta
            $stmt = $conn->prepare("UPDATE productos SET descripcion=?, disponible=?,Minimo=?, precio=?,precio2=?, Tipo=?, Venta=? WHERE idproducto=?");
            $stmt->bind_param("siiddssi", $desc, $stock,$Minimo, $precio,$precio2, $tipo, $venta, $id);
            
            // Si actualizas SIN foto:

        }
    } else {
        // MODO NUEVO PRODUCTO
        if ($nombreFoto == "") { $nombreFoto = "default.jpg"; }
        
        // Insertamos incluyendo los nuevos campos: Tipo y venta
        $stmt = $conn->prepare("INSERT INTO productos (descripcion, disponible,Minimo, precio,precio2, foto, idAgencia, Tipo, Venta) VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)");
        $stmt->bind_param("siiddsss", $desc, $stock,$Minimo, $precio,$precio2, $nombreFoto, $tipo, $venta);
    }

    if ($stmt->execute()) {
        header("Location: inventario.php?status=success");
        exit();
    } else {
        echo "Error al procesar el producto: " . $conn->error;
    }
}
?>