const API_URL = 'http://localhost:8888/api/products.php'; 
const UPLOADS_BASE_PATH = 'http://localhost:8888/uploads/'; 


document.addEventListener('DOMContentLoaded', () => {
    obtenerYMostrarProductos();
});

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

        // Tu API de PHP usa 'send_json', que ya envía un status code, 
        // pero es buena práctica verificar si el fetch fue exitoso (status 200-299)
        if (!response.ok) {
            // Si hay un error 404, 500, etc., lanza un error
            throw new Error(`Error en la petición: HTTP ${response.status}`);
        }

        const productos = await response.json();

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
    card.className = 'producto-card'; // Clase definida en products.css

    // Determina la fuente de la imagen. 
    // Si la propiedad 'imagen' existe, usa la ruta completa. Si no, usa una imagen por defecto.
    const imagenSrc = producto.imagen 
        ? UPLOADS_BASE_PATH + producto.imagen 
        : 'http://via.placeholder.com/200x150?text=Sin+Imagen'; // Imagen de placeholder

    // Formatear el precio a dos decimales
    const precioFormateado = parseFloat(producto.precio).toFixed(2); 

    // Usamos Template Literals (backticks ``) para crear la estructura interna
    card.innerHTML = `
        <img src="${imagenSrc}" alt="${producto.nombre}">
        
        <p class="text-muted mb-1" style="font-size: 0.8em;">ID: ${producto.id_producto}</p>
        
        <h3 class="mb-2">${producto.nombre}</h3>
        
        <p class="text-muted">${producto.descripcion.substring(0, 40)}...</p> 
        
        <div class="precio">$${precioFormateado}</div>
        
        <button class="btn btn-sm btn-danger mt-2 w-100">
            <i class="bi bi-cart-plus"></i> Añadir al Carrito
        </button>
    `;

    return card;
}