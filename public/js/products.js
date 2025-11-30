const API_URL = 'http://localhost:8081/mars/api/products.php';
const UPLOADS_BASE_PATH = 'http://localhost:8081/mars/public/uploads/';

const API_URL_ME = 'http://localhost:8081/MARS/api/users.php?action=me';




document.addEventListener('DOMContentLoaded', async () => {
    
    const response = await getCurrentUser();
     console.log('Respuesta /me:', response);

  if (!response.ok) {
    // No hay token o es inválido
    alert('Debes iniciar sesión');
   // location.href = 'login.html';
    return;
  }

  const usuario = response.usuario;
  console.log('Usuario actual:', usuario);
  console.log('Rol:', usuario.rol);

  if (usuario.rol !== 'admin') {
    alert('No tienes permiso para ver esta página');
    //location.href = 'dashboard.html';
  }
    
    
    
    obtenerYMostrarProductos();




});
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
/**
 * Función principal para obtener los datos de la API y dibujar el HTML.
 */
async function obtenerYMostrarProductos() {
    const contenedor = document.getElementById('productos-contenedor');
    contenedor.innerHTML = '<p class="text-center">Cargando productos...</p>'; // Mensaje de carga

    try {
        const response = await fetch(API_URL, {
            method: 'GET',
            headers: {
                // Indica que esperamos una respuesta JSON
                'Accept': 'application/json' 
            }
        });

        console.log(response)

        // Tu API de PHP usa 'send_json', que ya envía un status code, 
        // pero es buena práctica verificar si el fetch fue exitoso (status 200-299)
        if (!response.ok) {
            // Si hay un error 404, 500, etc., lanza un error
            throw new Error(`Error en la petición: HTTP ${response.status}`);
        }

        const productos = await response.json();
        console.log(productos)
        // Limpiar el mensaje de carga antes de inyectar productos
        contenedor.innerHTML = ''; 

        if (productos.length === 0) {
            contenedor.innerHTML = '<p class="text-center">No se encontraron productos disponibles.</p>';
            return;
        }

        // Recorrer el arreglo de productos y crear su tarjeta
        productos.forEach(producto => {
            const card = crearCardProducto(producto);
            contenedor.appendChild(card);
        });

    } catch (error) {
        console.error('Fallo al obtener los productos:', error);
        contenedor.innerHTML = `<p class="text-center text-danger">Error al cargar los productos: ${error.message}</p>`;
    }
}

/**
 * Función auxiliar para crear el elemento HTML (card) de un producto.
 * @param {object} producto - El objeto de producto de la API.
 * @returns {HTMLElement} La tarjeta de producto completa con estilos de Bootstrap.
 */
function crearCardProducto(producto) {
    const card = document.createElement('div');
    card.className = 'producto-card';

    const imagenSrc = producto.imagen 
        ? UPLOADS_BASE_PATH + producto.imagen 
        : 'http://via.placeholder.com/200x150?text=Sin+Imagen';

    const precioFormateado = parseFloat(producto.precio).toFixed(2);

    card.innerHTML = `
        <img src="${imagenSrc}" alt="${producto.nombre}">
        
        <p class="text-muted mb-1" style="font-size: 0.8em;">ID: ${producto.id_producto}</p>
        
        <h3 class="mb-2">${producto.nombre}</h3>
        
        <p class="text-muted">${producto.descripcion.substring(0, 40)}...</p> 
        
        <div class="precio">$${precioFormateado}</div>

        <a href="#" class="ver-resenas d-block mt-2 text-primary" 
           data-id="${producto.id_producto}">
           Ver reseñas
        </a>

        <button class="btn btn-sm btn-danger mt-2 w-100 btn-add-cart">
            <i class="bi bi-cart-plus"></i> Añadir al Carrito
        </button>
    `;

    // Botón carrito
    card.querySelector(".btn-add-cart").addEventListener("click", () => {
        agregarAlCarrito(producto);
    });

    // Botón reseñas
    card.querySelector(".ver-resenas").addEventListener("click", (e) => {
        e.preventDefault();
        abrirModalResenas(producto.id_producto);
    });

    return card;
}

function agregarAlCarrito(producto) {
    let carrito = JSON.parse(localStorage.getItem("carrito")) || [];

    // Buscar si ya existe
    const existe = carrito.find(item => item.id_producto == producto.id_producto);

    if (existe) {
        existe.cantidad++;
    } else {
        carrito.push({
            ...producto,
            cantidad: 1
        });
    }

    localStorage.setItem("carrito", JSON.stringify(carrito));
    alert(`Producto agregado: ${producto.nombre}`);
}



async function abrirModalResenas(idProducto) {
    const contenedor = document.getElementById("contenedor-resenas");
    contenedor.innerHTML = `<p class="text-muted">Cargando reseñas...</p>`;
    console.log(idProducto)
    // ⚠️ Ajusta tu URL real
      const urlResenas = `http://localhost:8081/MARS/api/reviews.php?id_producto=${idProducto}`;

      try {
          const response = await fetch(urlResenas);
          const data = await response.json();
          console.log(data)

          if (!data.ok || data.resenas.length === 0) {
              contenedor.innerHTML = `<p class="text-muted">No hay reseñas aún.</p>`;
          } else {
              let html = `
                  <table class="table table-striped text-center">
                      <thead class="table-dark">
                          <tr>
                              <th>Calificación</th>
                              <th>Comentario</th>
                              <th>Fecha</th>
                          </tr>
                      </thead>
                      <tbody>
              `;

              data.resenas.forEach(r => {
                  html += `
                      <tr>
                          <td>${r.calificacion} ⭐</td>
                          <td>${r.comentario}</td>
                          <td>${r.fecha}</td>
                      </tr>
                  `;
              });

              html += `</tbody></table>`;
              contenedor.innerHTML = html;
          }

      } catch (err) {
          contenedor.innerHTML = `<p class="text-danger">Error cargando reseñas.</p>`;
      }

    // // Mostrar modal
     const modal = new bootstrap.Modal(document.getElementById("modalResenas"));
     modal.show();
}