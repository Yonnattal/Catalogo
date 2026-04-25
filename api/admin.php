<?php
session_start();
ob_start();

// 1. SEGURIDAD: Control de acceso
if (!isset($_SESSION['admin_auth']) || $_SESSION['admin_auth'] !== true) {
    header("Location: login.php");
    exit();
}

include 'bd/conexion.php';

// 2. LÓGICA DE GESTIÓN (Despachar / Cancelar)
if (isset($_GET['accion']) && isset($_GET['id'])) {
    $idp = intval($_GET['id']);
    
    if ($_GET['accion'] == 'cancelar') {
        // Devolver stock antes de cancelar
        $stmtDetalle = $conn->prepare("SELECT idproducto, cantidad FROM detalle_pedidos WHERE idpedido = ?");
        $stmtDetalle->bind_param("i", $idp);
        $stmtDetalle->execute();
        $resDetalle = $stmtDetalle->get_result();
        
        while ($d = $resDetalle->fetch_assoc()) {
            $stmtStock = $conn->prepare("UPDATE productos SET disponible = disponible + ? WHERE idproducto = ?");
            $stmtStock->bind_param("ii", $d['cantidad'], $d['idproducto']);
            $stmtStock->execute();
        }
        $conn->query("UPDATE pedidos SET estado = 'Cancelado' WHERE idpedido = $idp");
    } elseif ($_GET['accion'] == 'despachar') {
        $conn->query("UPDATE pedidos SET estado = 'Despachado' WHERE idpedido = $idp");
    }
    header("Location: admin.php");
    exit();
}

// 3. CONSULTA DE REPORTES (Estadísticas)
$mes = date('m');
$anio = date('Y');
$hoy = date('Y-m-d');

// Ventas del mes
$res_mes = $conn->query("SELECT SUM(total) as total FROM pedidos WHERE MONTH(fecha) = '$mes' AND YEAR(fecha) = '$anio' AND estado = 'Despachado'");
$total_mes = $res_mes->fetch_assoc()['total'] ?? 0;

// Pedidos pendientes de hoy
$res_pend = $conn->query("SELECT COUNT(*) as cuenta FROM pedidos WHERE DATE(fecha) = '$hoy' AND estado = 'Pendiente'");
$pendientes_hoy = $res_pend->fetch_assoc()['cuenta'] ?? 0;

// Top Productos
$top_productos = $conn->query("SELECT p.descripcion, SUM(d.cantidad) as cant FROM detalle_pedidos d JOIN productos p ON d.idproducto = p.idproducto JOIN pedidos ped ON d.idpedido = ped.idpedido WHERE ped.estado = 'Despachado' GROUP BY d.idproducto ORDER BY cant DESC LIMIT 3");

// 4. CONSULTA AJAX PARA DETALLE (Si se solicita)
if (isset($_GET['get_detalle'])) {
    $idp = intval($_GET['get_detalle']);
    $res = $conn->query("SELECT d.*, p.descripcion FROM detalle_pedidos d JOIN productos p ON d.idproducto = p.idproducto WHERE d.idpedido = $idp");
    $items = [];
    while($row = $res->fetch_assoc()) { $items[] = $row; }
    header('Content-Type: application/json');
    echo json_encode($items);
    exit;
}

// 5. LISTADO GENERAL DE PEDIDOS
$pedidos = $conn->query("SELECT * FROM pedidos ORDER BY idpedido DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin | VenyPaga</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen">

    <nav class="bg-indigo-900 text-white p-4 shadow-xl sticky top-0 z-40">
        <div class="max-w-6xl mx-auto flex justify-between items-center">
            <div class="flex gap-6 items-center">
                <h1 class="font-black text-xl tracking-tighter">VENYPAGA ADMIN</h1>
                <a href="inventario.php" class="text-xs bg-indigo-700 hover:bg-indigo-600 px-3 py-2 rounded-lg font-bold transition">GESTIONAR INVENTARIO</a>
            </div>
            <a href="logout.php" class="text-xs bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg font-bold transition">SALIR</a>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-2xl shadow-sm border-l-4 border-indigo-600">
            <p class="text-[10px] font-black text-slate-400 uppercase">Ventas Mes (Despachado)</p>
            <h2 class="text-3xl font-black text-slate-800">$ <?php echo number_format($total_mes, 0, ',', '.'); ?></h2>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-sm border-l-4 border-amber-500">
            <p class="text-[10px] font-black text-slate-400 uppercase">Pendientes Hoy</p>
            <h2 class="text-3xl font-black text-slate-800"><?php echo $pendientes_hoy; ?></h2>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-sm border-l-4 border-emerald-500">
            <p class="text-[10px] font-black text-slate-400 uppercase">Más Vendidos</p>
            <div class="mt-1">
                <?php while($t = $top_productos->fetch_assoc()): ?>
                    <p class="text-[10px] text-slate-500 truncate"><b><?php echo $t['cant']; ?>x</b> <?php echo $t['descripcion']; ?></p>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <main class="max-w-6xl mx-auto px-6 pb-12">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr class="text-slate-400 text-[10px] uppercase font-black">
                        <th class="p-4">ID / Detalle</th>
                        <th class="p-4">Fecha</th>
                        <th class="p-4">Total</th>
                        <th class="p-4">Estado</th>
                        <th class="p-4 text-center">Gestión</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php while($p = $pedidos->fetch_assoc()): ?>
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="p-4">
                            <button onclick="verDetalle(<?php echo $p['idpedido']; ?>)" class="text-indigo-600 font-bold hover:underline">
                                #<?php echo $p['idpedido']; ?> <span class="text-[10px] ml-1">🔍</span>
                            </button>
                        </td>
                        <td class="p-4 text-xs text-slate-500"><?php echo date('d/m H:i', strtotime($p['fecha'])); ?></td>
                        <td class="p-4 font-black text-slate-700">$ <?php echo number_format($p['total'], 0, ',', '.'); ?></td>
                        <td class="p-4">
                            <?php 
                                $statusClass = ($p['estado'] == 'Pendiente') ? 'bg-amber-100 text-amber-700' : ($p['estado'] == 'Despachado' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700');
                            ?>
                            <span class="px-2 py-1 rounded-md text-[10px] font-black uppercase <?php echo $statusClass; ?>">
                                <?php echo $p['estado']; ?>
                            </span>
                        </td>
                        <td class="p-4 flex justify-center gap-2">
                            <?php if($p['estado'] == 'Pendiente'): ?>
                                <a href="?accion=despachar&id=<?php echo $p['idpedido']; ?>" class="bg-green-600 text-white px-3 py-1.5 rounded-lg text-[10px] font-bold shadow-sm">LISTO</a>
                                <a href="?accion=cancelar&id=<?php echo $p['idpedido']; ?>" onclick="return confirm('¿Anular pedido?')" class="bg-slate-100 text-slate-400 px-3 py-1.5 rounded-lg text-[10px] font-bold">X</a>
                            <?php else: ?>
                                <span class="text-[10px] text-slate-300 italic">Completado</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div id="modalDetalle" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-100">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-black text-slate-800">Pedido <span id="numPedido" class="text-indigo-600"></span></h2>
                <button onclick="cerrarModal()" class="text-slate-400 text-2xl">&times;</button>
            </div>
            <div id="listaProductos" class="space-y-4 max-h-96 overflow-y-auto pr-2">
                </div>
            <button onclick="cerrarModal()" class="w-full mt-8 bg-slate-900 text-white py-4 rounded-2xl font-black text-sm tracking-widest uppercase shadow-lg">Cerrar</button>
        </div>
    </div>

    <script>
        function verDetalle(id) {
            const modal = document.getElementById('modalDetalle');
            const lista = document.getElementById('listaProductos');
            document.getElementById('numPedido').innerText = '#' + id;
            lista.innerHTML = '<p class="text-center py-4 text-slate-400 animate-pulse">Cargando productos...</p>';
            modal.classList.remove('hidden');

            fetch('admin.php?get_detalle=' + id)
                .then(res => res.json())
                .then(data => {
                    lista.innerHTML = '';
                    data.forEach(item => {
                        lista.innerHTML += `
                            <div class="flex justify-between items-center bg-slate-50 p-3 rounded-xl border border-slate-100">
                                <div class="flex flex-col">
                                    <span class="text-xs font-black text-slate-800">${item.descripcion}</span>
                                    <span class="text-[10px] text-slate-400">${item.cantidad} unidad(es)</span>
                                </div>
                                <span class="font-bold text-indigo-600 text-sm">$ ${parseFloat(item.precio).toLocaleString('es-ES')}</span>
                            </div>
                        `;
                    });
                });
        }

        function cerrarModal() {
            document.getElementById('modalDetalle').classList.add('hidden');
        }
    </script>
</body>
</html>