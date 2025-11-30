const UPLOADS_BASE_PATH = 'http://localhost:8081/mars/public/uploads/';
const API_URL = 'http://localhost:8081/mars/api/orders.php?action=create';





// Obtener carrito del localStorage
let carrito = JSON.parse(localStorage.getItem("carrito")) || [];

// Elemento donde mostraremos los productos
const cartList = document.getElementById("cart-list");

// Renderizar carrito
function mostrarCarrito() {
    cartList.innerHTML = "";

    if (carrito.length === 0) {
        cartList.innerHTML = `
            <p class="text-center text-muted">Tu carrito está vacío 🛒</p>
        `;
        return;
    }

    carrito.forEach((item, index) => {
        const div = document.createElement("div");
        div.className = "cart-item";
     const imagenSrc = item.imagen 
        ? UPLOADS_BASE_PATH + item.imagen 
        : 'http://via.placeholder.com/200x150?text=Sin+Imagen';
        div.innerHTML = `
            <div class="d-flex align-items-center">
                <img src="${imagenSrc}" alt="${item.nombre}">
                <div>
                    <h5>${item.nombre}</h5>
                    <p class="text-danger fw-bold">$${item.precio}</p>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <button class="qty-btn" onclick="cambiarCantidad(${index}, -1)">-</button>
                <span>${item.cantidad}</span>
                <button class="qty-btn" onclick="cambiarCantidad(${index}, 1)">+</button>
            </div>

            <button class="delete-btn" onclick="eliminarItem(${index})">
                <i class="fas fa-trash"></i>
            </button>
        `;

        cartList.appendChild(div);
    });
}

mostrarCarrito();

// Cambiar cantidad
function cambiarCantidad(index, amount) {
    carrito[index].cantidad += amount;

    if (carrito[index].cantidad <= 0) {
        carrito.splice(index, 1);
    }

    localStorage.setItem("carrito", JSON.stringify(carrito));
    mostrarCarrito();
}

// Eliminar producto
function eliminarItem(index) {
    carrito.splice(index, 1);
    localStorage.setItem("carrito", JSON.stringify(carrito));
    mostrarCarrito();
}

// Cerrar orden
document.getElementById("cerrarOrden").addEventListener("click", () => {
    if (carrito.length === 0) {
        alert("No tienes productos en el carrito");
        return;
    }

    alert("¡Orden cerrada con éxito!");
    localStorage.removeItem("carrito");
    location.reload();
});