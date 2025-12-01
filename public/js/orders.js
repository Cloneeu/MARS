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
const API_URL_SET_REVIEW = ''
const API_URL_ME = 'http://localhost:8081/MARS/api/users.php?action=me';

// LISTA PRINCIPAL
const ordersList = document.getElementById("orders-list");

// MODAL DETALLE
const modalDetalle = new bootstrap.Modal(document.getElementById("modalDetalleOrden"));
const detalleInfo = document.getElementById("detalle-info");
const detalleProductos = document.getElementById("detalle-productos");
// MODAL RESEÑA
const modalResena = new bootstrap.Modal(document.getElementById("modalResena"));
const nombreProductoResena = document.getElementById("nombre-producto-resena");
let review = {};
let ratingSeleccionado = 0; // ⭐ rating global
let idProductoActual = 0;



document.addEventListener('DOMContentLoaded', async () => {
      
      const response = await getCurrentUser();
      console.log('Respuesta /me:', response);

    if (!response.ok) {
      // No hay token o es inválido
      alert('Debes iniciar sesión');
      location.href = 'login.html';
      return;
    }

    const usuario = response.usuario;
    console.log('Usuario actual:', usuario);
    console.log('Rol:', usuario.rol);

    


      app();




  });







//Funcion para el rating de las reseñas 




document.querySelectorAll("#rating-stars .star").forEach(star => {
    star.addEventListener("click", function () {
        ratingSeleccionado = this.dataset.value;

        // Actualizar visualmente
        document.querySelectorAll("#rating-stars .star").forEach(s => {
            s.classList.remove("selected");
        });

        for (let i = 0; i < ratingSeleccionado; i++) {
            document.querySelectorAll("#rating-stars .star")[i].classList.add("selected");
        }
    });
});





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
  //console.log(order)
    order.detalles.forEach(p => {
    //  console.log(p)
        const div = document.createElement("div");
         const imagenSrc = p.imagen 
        ? UPLOADS_BASE_PATH + p.imagen 
        : 'http://via.placeholder.com/200x150?text=Sin+Imagen';
        div.className = "product-item";

        div.innerHTML = `
    <img class="product-img" src="${imagenSrc}" alt="${p.nombre}">
            <span>${p.nombre}</span>
            <button class="rate-btn" onclick="abrirModalResena(${p.id_producto}, '${p.nombre}','${order.id_usuario}')">Reseñar</button>
        `;

        detalleProductos.appendChild(div);
    });

    modalDetalle.show();
}

// ------- ABRIR MODAL RESEÑA -------
function abrirModalResena(productId, nombre, idusuario) {

    review = {
        id_usuario: idusuario,
        id_producto: productId
    };
    idProductoActual = productId;

    nombreProductoResena.textContent = nombre;
    modalResena.show();
}
// ------- ENVIAR RESEÑA -------
document.getElementById("btnEnviarResena").addEventListener("click", async () => {
    const comentario = document.getElementById("textoResena").value.trim();

    if (comentario === "") {
        alert("Escribe una reseña.");
        return;
    }

    if (ratingSeleccionado === 0) {
        alert("Selecciona una calificación.");
        return;
    }

    // 🎯 Armamos el objeto EXACTO que tu API espera
    const dataParaAPI = {
    id_usuario: Number(review.id_usuario),
    id_producto: Number(review.id_producto),
    calificacion: Number(ratingSeleccionado),
    comentario: comentario
    };
    console.log("Objeto enviado al API:", dataParaAPI);
      const urlResenas = `http://localhost:8081/MARS/api/reviews.php`;
     try {
        const response = await fetch(urlResenas, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dataParaAPI)
        });

        const result = await response.json();

        if (response.ok) {
            alert("¡Gracias por tu reseña!");
            modalResena.hide();
        } else {
            // Manejo de errores específicos
            if (response.status === 409) {
                alert("Ya has dejado una reseña para este producto.");
            } else if (response.status === 422) {
                alert("Error: faltan datos obligatorios para enviar la reseña.");
            } else {
                alert(result.message || "Ocurrió un error al enviar la reseña.");
            }
        }

    } catch (error) {
        console.error("Error enviando la reseña:", error);
        alert("Error de conexión al enviar la reseña. Intenta de nuevo.");
    }
    // Aquí harías el fetch POST

    
});




function app(){
  mostrarOrdenes();

}



const getCurrentUser = async () => {
  try {
    const response = await fetch(API_URL_ME, {
      method: 'GET',
      headers: {
        'Accept': 'application/json'
      },
      credentials: 'include' // ▶️ manda la cookie "token"
    });

    return await response.json();

  } catch (error) {
    console.error("Error obteniendo usuario actual", error);
    return { ok: false };
  }
};