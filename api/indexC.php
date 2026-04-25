<?php
include './bd/conexion.php'; 

// 1. Consulta principal (Asegúrate de que 'precio' y 'Minimo' existan en tu tabla)
$res = $conn->query("SELECT idproducto, descripcion, precio, disponible, Minimo, foto, Tipo,Venta FROM productos WHERE disponible >= 0 and precio > 0 ORDER BY Tipo ASC, descripcion ASC");

$productosAgrupados = [];
while($row = $res->fetch_assoc()){ 
    $categoria = !empty($row['Tipo']) ? $row['Tipo'] : 'Otros';
    $productosAgrupados[$categoria][] = $row; 
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>VenyPaga | Clientes</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap');
        body { font-family: 'Inter', sans-serif; background: #f8fafc; margin: 0; }
        .sticky-header { position: sticky; top: 0; z-index: 50; backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.9); }
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 100; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .swal2-popup { border-radius: 1.5rem !important; }
    </style>
</head>
<body>

    <header class="sticky-header border-b border-gray-200 shadow-sm px-4 py-4">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
            <img class="h-16 w-auto" src="img/Logo_Market.JPG" alt="Logo">
            <div class="relative w-full md:w-1/2">
                <input type="text" id="inputBusqueda" placeholder="¿Qué estás buscando hoy?..." 
                       class="w-full pl-10 pr-4 py-2.5 rounded-full border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none shadow-sm">
            </div>
            <button onclick="verNota()" class="bg-blue-600 text-white px-8 py-2.5 rounded-full font-bold hover:bg-blue-700 transition shadow-lg relative">
                🛒 Mi Pedido
                <span id="contadorNota" class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] w-6 h-6 flex items-center justify-center rounded-full border-2 border-white font-black">0</span>
            </button>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8">
        <?php foreach($productosAgrupados as $categoria => $items): ?>
            <div class="mb-12 contenedor-categoria">
                <div class="flex items-center mb-8">
                    <h2 class="text-xl font-extrabold text-gray-800 uppercase tracking-tighter bg-white pr-4">
                        <?php echo htmlspecialchars($categoria); ?>
                    </h2>
                    <div class="flex-grow h-px bg-gray-200"></div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-8">
                    <?php foreach($items as $prod): ?>
                        <div class="producto-item bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden flex flex-col transition hover:shadow-md" 
                             data-nombre="<?php echo strtolower($prod['descripcion']); ?>">
                            
                            <div class="relative aspect-square overflow-hidden bg-gray-50">
                                <img src="img/<?php echo $prod['foto']; ?>" class="w-full h-full object-cover">
                              <?php if($prod['Venta'] <> "Detal"): ?>
                               <div class="absolute bottom-2 right-2 bg-indigo-600 text-white px-2 py-1 rounded-lg font-bold text-[10px] shadow-lg">
                                <?php echo $prod['Venta']; ?> 
                               </div>
                                <?php endif; ?>
                            </div>

                            <div class="p-5 flex-grow flex flex-col">
                                <h3 class="text-sm font-bold text-gray-700 line-clamp-2 h-10"><?php echo $prod['descripcion']; ?></h3>
                                
                                <div class="mt-4 flex flex-col">
                                    <span class="text-xl font-black text-blue-600">$<?php echo number_format($prod['precio'], 2); ?></span>
                                    <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Stock: <?php echo $prod['disponible']; ?> disponible</span>
                                </div>
                                
                                <?php if ($prod['disponible'] > 0): ?>
                                    <div class="mt-4 flex items-center justify-between bg-slate-50 p-2 rounded-2xl border border-slate-100">
                                        <button onclick="cambiarCantFicha(<?php echo $prod['idproducto']; ?>, -1)" 
                                                class="w-9 h-9 flex items-center justify-center bg-white rounded-xl shadow-sm text-blue-600 font-black border border-slate-200 hover:bg-blue-50">-</button>
                                        
                                        <input type="number" id="cant_<?php echo $prod['idproducto']; ?>" 
                                               value="1" 
                                               min="1" 
                                               max="<?php echo $prod['disponible']; ?>" 
                                               class="w-10 text-center bg-transparent font-black text-sm outline-none" readonly>
                                        
                                        <button onclick="cambiarCantFicha(<?php echo $prod['idproducto']; ?>, 1)" 
                                                class="w-9 h-9 flex items-center justify-center bg-white rounded-xl shadow-sm text-blue-600 font-black border border-slate-200 hover:bg-blue-50">+</button>
                                    </div>

                                    <button onclick='capturarYAgregar(<?php echo json_encode($prod); ?>)' 
                                            class="mt-4 w-full bg-blue-600 text-white py-3.5 rounded-2xl font-bold text-xs hover:bg-green-600 transition-all shadow-md active:scale-95">
                                        AGREGAR AL PEDIDO
                                    </button>
                                <?php else: ?>
                                    <div class="mt-4 py-3 bg-red-50 border border-red-100 rounded-2xl text-center">
                                        <span class="text-red-500 font-black text-[10px] uppercase tracking-widest">PRODUCTO AGOTADO</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </main>

    <div id="modalNota" class="modal px-4">
        <div class="bg-white w-full max-w-lg rounded-[2.5rem] overflow-hidden shadow-2xl flex flex-col max-h-[85vh]">
            <div class="p-8 border-b border-gray-100 flex justify-between items-center bg-slate-50">
                <div>
                    <h2 class="text-2xl font-black text-gray-800">Mi Pedido</h2>
                    <p class="text-xs text-gray-400 font-bold uppercase tracking-widest">Resumen de selección</p>
                </div>
                <button onclick="cerrarNota()" class="bg-white p-2 rounded-full shadow-sm text-gray-400 hover:text-red-500 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div id="listaNota" class="p-8 overflow-y-auto flex-grow space-y-5"></div>

            <div class="p-8 border-t border-gray-100 bg-white">
                <div class="flex justify-between items-end mb-8">
                    <span class="text-gray-400 font-black text-xs uppercase tracking-tighter">Total Neto</span>
                    <span id="totalNota" class="text-3xl font-black text-blue-600 leading-none">$0.00</span>
                </div>
                <button onclick="enviarWhatsApp()" id="btnEnviar" 
                        class="w-full bg-green-500 text-white py-5 rounded-[1.5rem] font-black text-lg shadow-xl hover:bg-green-600 transition-all flex items-center justify-center gap-3">
                    CONFIRMAR PEDIDO
                </button>
            </div>
        </div>
    </div>

    <script>
        let nota = JSON.parse(localStorage.getItem('nota')) || [];

        function cambiarCantFicha(id, delta) {
            const input = document.getElementById('cant_' + id);
            const min = parseInt(input.getAttribute('min'));
            const max = parseInt(input.getAttribute('max'));
            let valor = parseInt(input.value) + delta;
            if (valor >= min && valor <= max) input.value = valor;
        }

        function capturarYAgregar(p) {
            const input = document.getElementById('cant_' + p.idproducto);
            const cantidad = parseInt(input.value);
            const existe = nota.find(item => item.idproducto === p.idproducto);

            if (existe) {
                existe.cantidad += cantidad;
            } else {
                nota.push({ ...p, cantidad: cantidad });
            }
            
            actualizarContador();
            
            Swal.fire({
                title: '¡Añadido!',
                text: `${p.descripcion} se sumó a la nota.`,
                icon: 'success',
                timer: 1000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        }

        function actualizarContador() {
            const total = nota.reduce((acc, item) => acc + item.cantidad, 0);
            document.getElementById('contadorNota').innerText = total;
            localStorage.setItem('nota', JSON.stringify(nota));
        }

        function verNota() {
            const lista = document.getElementById('listaNota');
            const totalE = document.getElementById('totalNota');
            lista.innerHTML = '';
            let total = 0;

            if (nota.length === 0) {
                lista.innerHTML = `<div class="text-center py-10">
                    <p class="text-gray-300 font-black text-sm uppercase">La nota está vacía</p>
                </div>`;
            } else {
                nota.forEach((p, index) => {
                    const subtotal = parseFloat(p.precio) * p.cantidad;
                    total += subtotal;
                    lista.innerHTML += `
                        <div class="flex items-center justify-between group">
                            <div class="flex-grow">
                                <p class="font-black text-gray-800 text-sm leading-tight uppercase">${p.descripcion}</p>
                                <p class="text-blue-600 font-black text-xs mt-1">${p.cantidad} UND x $${parseFloat(p.precio).toFixed(2)}</p>
                            </div>
                            <button onclick="eliminarDelPedido(${index})" class="ml-4 p-2 text-gray-300 hover:text-red-500 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                    `;
                });
            }
            totalE.innerText = `$${total.toLocaleString('es-ES', {minimumFractionDigits: 2})}`;
            document.getElementById('modalNota').classList.add('active');
        }

        function eliminarDelPedido(index) {
            nota.splice(index, 1);
            actualizarContador();
            verNota();
        }

        function cerrarNota() {
            document.getElementById('modalNota').classList.remove('active');
        }

        function enviarWhatsApp() {
            if (nota.length === 0) return;

            const btn = document.getElementById('btnEnviar');
            const originalText = btn.innerText;
            btn.disabled = true;
            btn.innerText = "PROCESANDO STOCK...";

            const total = nota.reduce((s, p) => s + (parseFloat(p.precio) * p.cantidad), 0);

            // Fetch a la base de datos (Esto debe ejecutar el descuento de stock en el servidor)
            fetch('guardar_pedido.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ carrito: nota, total: total })
            })
            .then(res => res.json())
            .then(data => {
                if(data.ok) {
                    let msg = `PEDIDO N° ${data.idpedido}\n`;
                    msg += `━━━━━━━━━━━━━━━━━━\n\n`;
                    nota.forEach(p => {
                        msg += `*${p.cantidad}x* ${p.descripcion.toUpperCase()}\n`;
                        msg += `Subtotal: $ ${(parseFloat(p.precio) * p.cantidad).toLocaleString('es-ES')}\n\n`;
                    });
                    msg += `━━━━━━━━━━━━━━━━━━\n`;
                    msg += `*TOTAL A PAGAR: $ ${total.toLocaleString('es-ES', {minimumFractionDigits: 2})}*`;
                    
                    const telefono = "573208634210";
                    window.open(`https://wa.me/${telefono}?text=${encodeURIComponent(msg)}`, '_blank');

                    Swal.fire({
                        title: '¡Éxito!',
                        text: 'Pedido enviado, Gracias por su compra',
                        icon: 'success',
                        confirmButtonText: 'Genial',
                        confirmButtonColor: '#2563eb'
                    });

                    nota = [];
                    actualizarContador();
                    cerrarNota();
                } else {
                    Swal.fire('Error', data.msg || 'No se pudo procesar', 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error de red', 'No hay conexión con el servidor', 'error');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerText = originalText;
            });
        }

     // Buscador dinámico optimizado
document.getElementById('inputBusqueda').addEventListener('input', function(e) {
    const busqueda = e.target.value.toLowerCase();
    
    // 1. Filtrar los productos individualmente
    document.querySelectorAll('.producto-item').forEach(card => {
        const nombre = card.getAttribute('data-nombre');
        // Si el nombre incluye la búsqueda, se muestra, si no, se oculta
        card.style.display = nombre.includes(busqueda) ? 'flex' : 'none';
    });

    // 2. Ocultar categorías que se quedaron sin productos visibles
    document.querySelectorAll('.contenedor-categoria').forEach(seccion => {
        // Buscamos si dentro de esta sección hay al menos un producto visible
        const tieneProductosVisibles = Array.from(seccion.querySelectorAll('.producto-item'))
            .some(card => card.style.display !== 'none');

        // Si no hay productos, ocultamos el título y la sección completa
        seccion.style.display = tieneProductosVisibles ? 'block' : 'none';
    });
});

        actualizarContador();
    </script>
</body>
</html>
