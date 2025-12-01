   document.addEventListener("DOMContentLoaded", () => {
        const tabButtons = document.querySelectorAll(".tab-button");
        const tabPanels = document.querySelectorAll(".tab-panel");

        function switchTab(targetId) {
          tabPanels.forEach((panel) => {
            panel.classList.add("hidden");
            panel.classList.remove("active");
          });

          tabButtons.forEach((button) => {
            button.setAttribute("aria-selected", "false");
            button.classList.remove("border-primary", "text-primary");
            button.classList.add("border-transparent", "text-gray-500", "hover:text-gray-700");
          });

          const targetPanel = document.getElementById(`panel-${targetId}`);
          const targetButton = document.getElementById(`tab-${targetId}`);

          if (targetPanel && targetButton) {
            targetPanel.classList.remove("hidden");
            targetPanel.classList.add("active");
            targetButton.setAttribute("aria-selected", "true");
            targetButton.classList.add("border-primary", "text-primary");
            targetButton.classList.remove("border-transparent", "text-gray-500", "hover:text-gray-700");
          }
        }

        tabButtons.forEach((button) => {
          button.addEventListener("click", (event) => {
            const targetId = event.currentTarget.getAttribute("data-tab-target");
            switchTab(targetId);
          });
        });

        switchTab("product");
      });




const API_URL_GET_PRODUCTS = 'http://localhost:8081/mars/api/products.php';
const API_URL_ORDERS_GET = 'http://localhost:8081/mars/api/orders.php?action=list';

const UPLOADS_BASE_PATH = 'http://localhost:8081/mars/public/uploads/';

const tbodyProductos = document.querySelector("tbody");

// Modal
const modalActualizar = new bootstrap.Modal(document.getElementById("modalActualizarProducto"));
const form = document.getElementById("product-form");
const formMessage = document.getElementById("form-message");
// Producto actualmente seleccionado para actualizar

const modalDetalleOrden = new bootstrap.Modal(document.getElementById('modalDetalleOrden'));
const detalleInfo = document.getElementById('detalleInfo');
const detalleProductos = document.getElementById('detalleProductos');
let productoActual = null;
let productos = null;
const app = ()=>{

  traerProductos()
  traerOrdenes()
}



form.addEventListener("submit", async (e) => {
  e.preventDefault();

  // Validaciones simples
  const nombre = document.getElementById("product-nombre").value.trim();
  const descripcion = document.getElementById("product-descripcion").value.trim();
  const precio = document.getElementById("product-precio").value.trim();
  const stock = document.getElementById("product-stock").value.trim();
  const imagenInput = document.getElementById("product-imagen");

  if (!nombre || !descripcion || !precio || !stock) {
    formMessage.textContent = "Todos los campos son obligatorios.";
    formMessage.classList.remove("hidden");
    formMessage.classList.add("text-red-600");
    return;
  }

  if (!imagenInput.files.length) {
    formMessage.textContent = "Selecciona una imagen.";
    formMessage.classList.remove("hidden");
    formMessage.classList.add("text-red-600");
    return;
  }

  const imagenFile = imagenInput.files[0];

  // Validar que sea imagen
  if (!imagenFile.type.startsWith("image/")) {
    formMessage.textContent = "Solo se permiten archivos de imagen.";
    formMessage.classList.remove("hidden");
    formMessage.classList.add("text-red-600");
    return;
  }

  // Construir FormData para enviar al backend
  const formData = new FormData();
  formData.append("nombre", nombre);
  formData.append("descripcion", descripcion);
  formData.append("precio", precio);
  formData.append("stock", stock);
  formData.append("imagen", imagenFile);

  try {
    const response = await fetch(API_URL_GET_PRODUCTS, {
      method: "POST",
      body: formData
    });

    const result = await response.json();

    if (result.ok) {
      formMessage.textContent = result.message || "Producto creado exitosamente!";
      formMessage.classList.remove("hidden", "text-red-600");
      formMessage.classList.add("text-green-600");
      form.reset();
    } else {
      formMessage.textContent = result.message || "Error al crear producto.";
      formMessage.classList.remove("hidden");
      formMessage.classList.add("text-red-600");
    }

  } catch (error) {
    console.error(error);
    formMessage.textContent = "Error de red al enviar el producto.";
    formMessage.classList.remove("hidden");
    formMessage.classList.add("text-red-600");
  }
});

//{PARA PRODUCTOS}


const traerProductos = async() => {
  try {
     const response = await fetch(API_URL_GET_PRODUCTS, {
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        });

        if (!response.ok) {
            throw new Error(`Error en la petición: HTTP ${response.status}`);
        }

        productos = await response.json();

        console.log(response)
        console.log(productos)

      
         tbodyProductos.innerHTML = ""; // limpiar tabla

    productos.forEach(p => {
        const tr = document.createElement("tr");
        tr.className = "hover:bg-gray-50";

        tr.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${p.id_producto}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${p.nombre}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${p.descripcion}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-bold">$${p.precio}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${p.stock}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <button class="btn btn-sm btn-primary me-2" onclick="abrirModalActualizar(${p.id_producto})">Actualizar</button>
                <button class="btn btn-sm btn-danger" onclick="borrarProducto(${p.id_producto})">Borrar</button>
            </td>
        `;

        tbodyProductos.appendChild(tr);
    });
  } catch (error) {
    console.log('error' ,error)
  }
}


// Abrir modal con los datos del producto
function abrirModalActualizar(id) {
    // buscar producto en la lista
    productoActual = productos.find(p => p.id_producto === id);

    if (!productoActual) return;

    // rellenar campos
    document.getElementById("updateNombre").value = productoActual.nombre;
    document.getElementById("updateDescripcion").value = productoActual.descripcion;
    document.getElementById("updatePrecio").value = productoActual.precio;
    document.getElementById("updateStock").value = productoActual.stock;
   // document.getElementById("updateImagen").value = productoActual.imagen;

    modalActualizar.show();
}

// Guardar cambios
document.getElementById("btnGuardarActualizacion").addEventListener("click", async() => {
    if (!productoActual) return;

    productoActual.nombre = document.getElementById("updateNombre").value;
    productoActual.descripcion = document.getElementById("updateDescripcion").value;
    productoActual.precio = document.getElementById("updatePrecio").value;
    productoActual.stock = document.getElementById("updateStock").value;
 //   productoActual.imagen = document.getElementById("updateImagen").value;


    try {
      const response = await fetch(API_URL_GET_PRODUCTS+'?id='+productoActual.id_producto, {
            method: 'PUT',
            headers: { 'Accept': 'application/json' },
              body: JSON.stringify(productoActual)
        });

        if (!response.ok) {
            throw new Error(`Error en la petición: HTTP ${response.status}`);
        }
        console.log(response)
    } catch (error) {
      console.log('error ACtializando',error)
    }
    console.log("Producto actualizado:", productoActual);
    modalActualizar.hide();

    // Re-renderizar tabla
    traerProductos(productos);
});

// Borrar producto
async function borrarProducto(id) {
     const confirmacion = confirm("¿Estás seguro que quieres eliminar este producto?");
    if (!confirmacion) return; // Si cancela, no hace nada

    try {
        const response = await fetch(`http://localhost:8081/MARS/api/products.php?id=${id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json'
            }
        });

        const result = await response.json();

        if (response.ok) {
            alert("Producto eliminado correctamente.");
            // Opcional: refrescar la lista de productos
            traerProductos();
        } else {
            // Manejo de errores según status code
            alert(result.message || "No se pudo eliminar el producto.");
        }

    } catch (error) {
        console.error("Error al eliminar:", error);
        alert("Ocurrió un error al intentar eliminar el producto.");
    }
}

// {PARA ORDENES }

const tbodyOrdenes = document.querySelector("#panel-orders tbody"); // tbody de la tabla de órdenes

const traerOrdenes = async () => {
    tbodyOrdenes.innerHTML = ""; // limpiar tabla

    try {
        const response = await fetch(API_URL_ORDERS_GET, {
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        });

        if (!response.ok) {
            throw new Error(`Error en la petición: HTTP ${response.status}`);
        }

        const data = await response.json();
        console.log("Órdenes recibidas:", data);

        // Normalizar la respuesta
        let ordenesArray;
        if (Array.isArray(data)) {
            ordenesArray = data;
        } else if (Array.isArray(data.orders)) {
            ordenesArray = data.orders;
        } else if (Array.isArray(data.data)) {
            ordenesArray = data.data;
        } else {
            throw new Error("La API no devolvió un array de órdenes");
        }

        // Renderizar cada orden en un <tr>
        ordenesArray.forEach(o => {
            const tr = document.createElement("tr");
            tr.className = "hover:bg-gray-50";

            tr.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${o.id_orden}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${o.nombre + o.apellido}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${o.created_at}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-bold">$${o.total}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${o.estado === 'Completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                        ${o.estado}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <button class="btn btn-sm btn-primary me-2" onclick='abrirDetalleOrden(${JSON.stringify(o)})'">Detalle</button>
                </td>
            `;

            tbodyOrdenes.appendChild(tr);
        });

    } catch (error) {
        console.error("Error al obtener órdenes:", error);
        tbodyOrdenes.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-red-500 py-4">
                    Error al cargar las órdenes: ${error.message}
                </td>
            </tr>
        `;
    }
};


function abrirDetalleOrden(order) {
    // Información general
    console.log(order)
    detalleInfo.innerHTML = `
        <p><strong>Orden #:</strong> ${order.id_orden}</p>
        <p><strong>Cliente:</strong> ${order.nombre + order.apellido}</p>
        <p><strong>Fecha:</strong> ${order.created_at}</p>
        <p><strong>Total:</strong> $${order.total}</p>
        <p><strong>Estado:</strong> ${order.estado}</p>
    `;

    // Limpiar lista de productos
    detalleProductos.innerHTML = "";

    // Recorrer los productos de la orden
    if (Array.isArray(order.detalles) && order.detalles.length > 0) {
        order.detalles.forEach(p => {
            const div = document.createElement("div");
            div.className = "d-flex justify-content-between align-items-center mb-2 p-2 border rounded";

            div.innerHTML = `
                <div>
                    <strong>${p.nombre}</strong> - $${p.precio_unitario} <br>
                    <strong>Cantidad </strong> - ${p.cantidad} <br>
                  
                </div>
                
            `;

            detalleProductos.appendChild(div);
        });
    } else {
        detalleProductos.innerHTML = `<p class="text-muted text-center">No hay productos en esta orden.</p>`;
    }

    modalDetalleOrden.show();
}


app();
