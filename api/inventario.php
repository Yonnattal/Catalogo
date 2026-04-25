<?php
session_start();
if (!isset($_SESSION['admin_auth'])) { header("Location: login.php"); exit(); }
include 'bd/conexion.php';

$productos = $conn->query("SELECT * FROM productos ORDER BY descripcion ASC");

// Cargar tipos únicos para el filtro y el datalist
$tipos_existentes = $conn->query("SELECT DISTINCT Tipo FROM productos WHERE Tipo IS NOT NULL AND Tipo <> '' ORDER BY Tipo ASC");
$tipos_array = [];
while($t = $tipos_existentes->fetch_assoc()){
    $tipos_array[] = $t['Tipo'];
}
// Reiniciamos el puntero para que el datalist del modal también pueda usarlo
$tipos_existentes->data_seek(0);



?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario | VenyPaga</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .modal { transition: opacity 0.25s ease; }
        body.modal-active { overflow: hidden; }
    </style>
</head>
<body class="bg-slate-100 min-h-screen">
    <nav class="bg-indigo-900 text-white p-4 sticky top-0 z-50 shadow-xl">
  
   
   <div class="max-w-6xl mx-auto flex flex-wrap justify-between items-center gap-4">
    <div class="flex gap-2 flex-1 md:flex-none">
          <div class="flex gap-4 items-center">
                <a href="admin.php" class="text-xs bg-indigo-800 p-2 rounded-lg font-bold">← VOLVER</a>
                <h1 class="text-xl font-black tracking-tighter">INVENTARIO</h1>
            </div>
        <input type="text" id="busqueda" onkeyup="filtrar()" placeholder="Buscar producto..." 
               class="w-full md:w-64 p-2 rounded-lg bg-indigo-800 text-white placeholder-indigo-300 border-none text-sm font-bold">
        
        <select id="filtroTipo" onchange="filtrar()" 
                class="bg-indigo-700 text-white p-2 rounded-lg border-none text-xs font-black uppercase tracking-wider">
            <option value="">TODOS LOS TIPOS</option>
            <?php foreach($tipos_array as $tipo): ?>
                <option value="<?php echo strtolower($tipo); ?>"><?php echo $tipo; ?></option>
            <?php endforeach; ?>
        </select>
    
    </div>
     <button onclick="abrirModal()" class="bg-green-500 px-4 py-2 rounded-lg font-bold text-sm shadow-lg">+ NUEVO</button>
</div>
   
   
    </nav>

    <main class="max-w-6xl mx-auto p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="contenedor">
        <?php while($p = $productos->fetch_assoc()): ?>
        <div class="card-prod bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden flex flex-col" 
        data-nombre="<?php echo strtolower($p['descripcion']); ?>" 
        data-tipo="<?php echo strtolower($p['Tipo']); ?>">
            <div class="relative h-48 bg-slate-200">
                <img src="img/<?php echo $p['foto']; ?>" class="w-full h-full object-cover">
                <div class="absolute top-2 left-2 bg-white/90 px-2 py-1 rounded-lg font-black text-indigo-600 shadow-sm border border-slate-100">
                  Unitario  $ <?php echo number_format($p['precio'], 0, ',', '.'); ?>
                </div>
                <div class="absolute top-2 right-2 bg-white/90 px-2 py-1 rounded-lg font-black text-indigo-600 shadow-sm border border-slate-100">
                 Mayor  $ <?php echo number_format($p['precio2'], 0, ',', '.'); ?>
                </div>
            </div>
            <div class="p-4">
                <h3 class="font-bold text-slate-800 leading-tight h-10 overflow-hidden"><?php echo $p['descripcion']; ?></h3>
         <div class="mt-2 mb-1">
            <span class="text-[10px] font-bold uppercase px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded-full border border-indigo-200">
                <i class="fas fa-tag mr-1"></i> <?php echo !empty($p['Venta']) ? $p['Venta'] : 'Detal'; ?>
            </span>
        </div>
                <div class="flex justify-between items-center mt-4">
                    <p class="text-[10px] text-indigo-600 font-bold uppercase"><?php echo $p['Tipo']; ?></p>
                    <span class="text-xs font-bold text-slate-400 uppercase">Stock: <b class="text-slate-700"><?php echo $p['disponible']; ?></b></span>
                    
                    <button onclick='abrirModalEditar(<?php echo json_encode($p); ?>)' class="text-xs bg-slate-900 text-white px-4 py-2 rounded-lg font-bold hover:bg-slate-700 transition">EDITAR</button>
                <button onclick="confirmarEliminar(<?php echo $p['idproducto']; ?>, '<?php echo addslashes($p['descripcion']); ?>')" 
                class="text-xs bg-red-100 text-red-600 px-3 py-2 rounded-lg font-bold hover:bg-red-600 hover:text-white transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </button>
                
                
                </div>
                
            </div>
        </div>
        <?php endwhile; ?>
    </main>
<div id="modalEliminar" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 z-[60]">
    <div class="bg-white p-8 rounded-3xl w-full max-w-sm shadow-2xl text-center">
        <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <h3 class="text-xl font-black text-slate-800 mb-2">¿Eliminar producto?</h3>
        <p id="nombreProdEliminar" class="text-sm text-slate-500 mb-6 font-medium"></p>
        
        <div class="flex gap-3">
            <button onclick="cerrarModalEliminar()" class="flex-1 bg-slate-100 text-slate-500 p-4 rounded-2xl font-black text-xs uppercase tracking-widest">Cancelar</button>
            <a id="linkEliminar" href="#" class="flex-1 bg-red-600 text-white p-4 rounded-2xl font-black text-xs uppercase tracking-widest shadow-lg shadow-red-200 text-center">Eliminar</a>
        </div>
    </div>
</div>
    <div id="modalProducto" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <form action="procesar_producto.php" method="POST" enctype="multipart/form-data" class="bg-white p-8 rounded-3xl w-full max-w-md shadow-2xl">
            <h2 id="modalTitulo" class="text-2xl font-black mb-6 text-slate-800 italic">NUEVO PRODUCTO</h2>
            
            <input type="hidden" name="idproducto" id="edit_id">
            
            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Nombre / Descripción</label>
            <input type="text" name="descripcion" id="edit_desc" class="w-full p-3 mb-4 border border-slate-200 rounded-xl outline-none focus:border-indigo-500" required>
            
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Precio Unit. ($)</label>
                    <input type="number" name="precio" id="edit_precio" step="0.01" class="w-full p-3 border border-slate-200 rounded-xl outline-none" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Precio Mayor ($)</label>
                    <input type="number" step="0.01" name="precio2" id="edit_precio2" class="w-full p-2 border rounded" required>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Existencia (Stock)</label>
                    <input type="number" name="disponible" id="edit_stock" class="w-full p-3 border border-slate-200 rounded-xl outline-none" required>
                </div>
                 <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Und Minima de Venta</label>
                    <input type="number" name="Minimo" id="edit_Minimo" class="w-full p-3 border border-slate-200 rounded-xl outline-none" required>
                </div>
            </div>
            <div>
    <label class="block text-[10px] font-black text-slate-400 mb-1">TIPO / CATEGORÍA</label>
    <input type="text" name="tipo" id="edit_tipo" list="lista_tipos" placeholder="Seleccione o escriba un tipo..." 
           class="w-full p-4 bg-slate-100 rounded-2xl border-none focus:ring-2 focus:ring-indigo-500 font-bold text-sm">
    <datalist id="lista_tipos">
        <?php while($row_t = $tipos_existentes->fetch_assoc()): ?>
            <option value="<?php echo $row_t['Tipo']; ?>">
        <?php endwhile; ?>
    </datalist>
</div>
<div class="mb-4">
    <label class="block text-gray-700 text-sm font-bold mb-2">Tipo de Venta</label>
    <select id="edit_venta" name="venta" class="w-full p-2 border rounded bg-white">
        <option value="Detal">Detal</option>
       
         <option value="Por Encargo">Por Encargo</option>
    </select>
</div>
            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Imagen (Opcional si editas)</label>
            <input type="file" name="foto" accept="image/*" class="w-full mb-6 text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-black file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">

            <div class="flex gap-3">
                <button type="button" onclick="cerrarModal()" class="flex-1 bg-slate-100 text-slate-500 p-4 rounded-2xl font-black text-xs uppercase tracking-widest">Cancelar</button>
                <button type="submit" class="flex-1 bg-indigo-600 text-white p-4 rounded-2xl font-black text-xs uppercase tracking-widest shadow-lg shadow-indigo-200">Guardar</button>
            </div>
        </form>
    </div>

    <script>
    
    function confirmarEliminar(id, nombre) {
    document.getElementById('nombreProdEliminar').innerText = nombre;
    document.getElementById('linkEliminar').href = "procesar_producto.php?eliminar=" + id;
    document.getElementById('modalEliminar').classList.remove('hidden');
}

function cerrarModalEliminar() {
    document.getElementById('modalEliminar').classList.add('hidden');
}

function abrirModal() {
    document.getElementById('modalTitulo').innerText = "NUEVO PRODUCTO";
    document.getElementById('edit_id').value = "";
    document.getElementById('edit_tipo').value = ""; // Limpiar tipo
    // ... resto de tus limpiezas
    document.getElementById('modalProducto').classList.remove('hidden');
}

function abrirModalEditar(p) {
    document.getElementById('modalTitulo').innerText = "EDITAR PRODUCTO";
    document.getElementById('edit_id').value = p.idproducto;
    document.getElementById('edit_desc').value = p.descripcion;
    document.getElementById('edit_precio').value = p.precio;
    document.getElementById('edit_precio2').value = p.precio2;
    document.getElementById('edit_stock').value = p.disponible;
    document.getElementById('edit_Minimo').value = p.Minimo;
    document.getElementById('edit_tipo').value = p.Tipo;
    
    // ESTA LÍNEA ES LA QUE CARGA EL VALOR PARA EL BACKEND
  
    document.getElementById('edit_venta').value = p.Venta; // Esto "setea" el combo antes de guardar
    
    document.getElementById('modalProducto').classList.remove('hidden');
}

function abrirModalNuevo() {
    document.getElementById('modalTitulo').innerText = "NUEVO PRODUCTO";
    document.getElementById('edit_id').value = "";
    document.getElementById('edit_desc').value = "";
    document.getElementById('edit_precio').value = "";
    document.getElementById('edit_precio2').value = "";
    document.getElementById('edit_stock').value = "";
    document.getElementById('edit_Minimo').value = "";
    document.getElementById('edit_tipo').value = "";
    document.getElementById('edit_venta').value = ""; // Limpiar combo venta
    document.getElementById('modalProducto').classList.remove('hidden');
}

        function cerrarModal() {
            document.getElementById('modalProducto').classList.add('hidden');
        }

     function filtrar() {
    let bus = document.getElementById('busqueda').value.toLowerCase();
    let tipoSel = document.getElementById('filtroTipo').value.toLowerCase();
    
    document.querySelectorAll('.card-prod').forEach(card => {
        let nombreMatch = card.dataset.nombre.includes(bus);
        let tipoMatch = (tipoSel === "") || (card.dataset.tipo === tipoSel);
        
        if (nombreMatch && tipoMatch) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}
    </script>
</body>
</html>