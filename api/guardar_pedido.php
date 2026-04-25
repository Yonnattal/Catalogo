<?php
include 'bd/conexion.php';
header('Content-Type: application/json');

// Recibir los datos enviados por el catálogo
$datos = json_decode(file_get_contents('php://input'), true);

if (!$datos || empty($datos['carrito'])) {
    echo json_encode(['ok' => false, 'msg' => 'Carrito vacío']);
    exit;
}

$carrito = $datos['carrito'];
$total = $datos['total'];

// Iniciamos una transacción para que si algo falla, no se guarde nada a medias
$conn->begin_transaction();

try {
    // 1. Insertar el pedido principal
    // Nota: 'Finalizado' es el estado inicial (puedes usar 'Pendiente')
    $stmt = $conn->prepare("INSERT INTO pedidos (fecha, total, estado) VALUES (NOW(), ?, 'Pendiente')");
    $stmt->bind_param("d", $total);
    $stmt->execute();
    $idPedido = $conn->insert_id;

    // 2. Insertar cada producto del carrito y actualizar stock
    foreach ($carrito as $item) {
        $idProd = $item['idproducto'];
        $cant   = $item['cantidad'];
        $precio = $item['precio'];

        // Guardar detalle del pedido
        $stmtDetalle = $conn->prepare("INSERT INTO detalle_pedidos (idpedido, idproducto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
        $stmtDetalle->bind_param("iiid", $idPedido, $idProd, $cant, $precio);
        $stmtDetalle->execute();

        // RESTRAR STOCK de la tabla productos
        $stmtStock = $conn->prepare("UPDATE productos SET disponible = disponible - ? WHERE idproducto = ?");
        $stmtStock->bind_param("ii", $cant, $idProd);
        $stmtStock->execute();
    }

    $conn->commit();
    echo json_encode(['ok' => true, 'idpedido' => $idPedido]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
?>