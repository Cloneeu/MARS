// Simulación de órdenes (reemplaza con tu API o localStorage)
let orders = JSON.parse(localStorage.getItem("ordenes")) || [
    {
        id: 1,
        fecha: "2025-11-30",
        total: 450,
        productos: [
            { id: 11, nombre: "Reloj Negro" },
            { id: 12, nombre: "Reloj Plata" }
        ]
    }
];



const API_URL_ORDERS_GET = 'http://localhost:8081/mars/api/orders.php?action=my-orders';
const UPLOADS_BASE_PATH = 'http://localhost:8081/mars/public/uploads/';


// LISTA PRINCIPAL
const ordersList = document.getElementById("orders-list");

// MODAL DETALLE
const modalDetalle = new bootstrap.Modal(document.getElementById("modalDetalleOrden"));
const detalleInfo = document.getElementById("detalle-info");
const detalleProductos = document.getElementById("detalle-productos");

// MODAL RESEÑA
const modalResena = new bootstrap.Modal(document.getElementById("modalResena"));
const nombreProductoResena = document.getElementById("nombre-producto-resena");
let idProductoActual = null;



app();




// ------- RENDER ORDENES -------
async function mostrarOrdenes() {
    ordersList.innerHTML = "";

    try {
        const response = await fetch(API_URL_ORDERS_GET, {
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        });

        if (!response.ok) {
            throw new Error(`Error en la petición: HTTP ${response.status}`);
        }

        const data = await response.json();
        console.log("DATA RECIBIDA:", data);

        // NORMALIZAR RESPUESTA
        if (Array.isArray(data)) {
            orders = data;
        } else if (Array.isArray(data.orders)) {
            orders = data.orders;
        } else if (Array.isArray(data.data)) {
            orders = data.data;
        } else {
            throw new Error("La API no devolvió un array de órdenes");
        }

        // RENDERIZAR
        orders.forEach(order => {
            const div = document.createElement("div");
            div.className = "order-card";

            div.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5>Orden #${order.id_orden}</h5>
                        <p class="text-muted">Fecha: ${order.created_at}</p>
                    </div>
                    <span class="fw-bold text-success">$${order.total}</span>
                </div>
            `;

            div.onclick = () => abrirDetalle(order);

            ordersList.appendChild(div);
        });

    } catch (error) {
        console.error("Fallo al obtener las ordenes:", error);
        ordersList.innerHTML = `
            <p class="text-center text-danger">
                Error al cargar las órdenes: ${error.message}
            </p>
        `;
    }
}


// ------- ABRIR DETALLE -------
function abrirDetalle(order) {
 
    detalleInfo.innerHTML = `
    
        <h5>Orden #${order.id_orden}</h5>
        <p>Fecha: ${order.created_at}</p>
        <p>Total: <strong>$${order.total}</strong></p>
    `;

    detalleProductos.innerHTML = "";
  console.log(order)
    order.detalles.forEach(p => {
        const div = document.createElement("div");
         const imagenSrc = p.imagen 
        ? UPLOADS_BASE_PATH + p.imagen 
        : 'http://via.placeholder.com/200x150?text=Sin+Imagen';
        div.className = "product-item";

        div.innerHTML = `
    <img class="product-img" src="${imagenSrc}" alt="${p.nombre}">
            <span>${p.nombre}</span>
            <button class="rate-btn" onclick="abrirModalResena(${p.id}, '${p.nombre}')">Reseñar</button>
        `;

        detalleProductos.appendChild(div);
    });

    modalDetalle.show();
}

// ------- ABRIR MODAL RESEÑA -------
function abrirModalResena(productId, nombre) {
    idProductoActual = productId;
    nombreProductoResena.textContent = nombre;
    modalResena.show();
}

// ------- ENVIAR RESEÑA -------
document.getElementById("btnEnviarResena").addEventListener("click", () => {
    const texto = document.getElementById("textoResena").value;

    if (texto.trim() === "") {
        alert("Escribe una reseña.");
        return;
    }

    // Aquí enviarias a tu API
    console.log("Reseña enviada:", {
        productoId: idProductoActual,
        texto: texto
    });

    alert("Gracias por tu reseña!");
    modalResena.hide();
});




function app(){
  mostrarOrdenes();

}