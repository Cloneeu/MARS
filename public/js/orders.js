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

// ------- RENDER ORDENES -------
function mostrarOrdenes() {
    ordersList.innerHTML = "";

    orders.forEach(order => {
        const div = document.createElement("div");
        div.className = "order-card";

        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5>Orden #${order.id}</h5>
                    <p class="text-muted">Fecha: ${order.fecha}</p>
                </div>
                <span class="fw-bold text-success">$${order.total}</span>
            </div>
        `;

        div.onclick = () => abrirDetalle(order);

        ordersList.appendChild(div);
    });
}

mostrarOrdenes();

// ------- ABRIR DETALLE -------
function abrirDetalle(order) {
    detalleInfo.innerHTML = `
        <h5>Orden #${order.id}</h5>
        <p>Fecha: ${order.fecha}</p>
        <p>Total: <strong>$${order.total}</strong></p>
    `;

    detalleProductos.innerHTML = "";

    order.productos.forEach(p => {
        const div = document.createElement("div");
        div.className = "product-item";

        div.innerHTML = `
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
