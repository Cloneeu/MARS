# MARS Watch Store - ⌚

Proyecto final de la UDA de aplicaciones de internet.

## Descripción

MARS Watch Store es una plataforma de venta de relojes en línea (Mini eCommerce). El proyecto incluye funcionalidades tanto para clientes como para administradores, permitiendo la gestión completa de productos, usuarios y pedidos.

## Características

### Para Clientes

- Catálogo de productos
- Registro e inicio de sesión de usuarios
- Carrito de compras
- Reseñas de productos

### Para Administradores

- Gestión de productos (CRUD)

## Tecnologías Utilizadas 

- **Frontend:** HTML, CSS y JavaScript 
- **Framework de CSS:** Bootstrap 
- **Backend:** PHP
- **Base de Datos:** MySQL

## Estructura del Proyecto - 📂

```
mars/
├── api/                # Endpoints de la API 
│   ├── _headers.php    # Headers CORS
│   ├── orders.php      # API de pedidos
│   ├── products.php    # API de productos
│   ├── reviews.php     # API de reseñas
│   └── users.php       # API de usuarios 
├── config/             # Configuración
│   ├── config.php      # Variables de entorno
│   └── database.php    # Conexión a la base de datos
├── helpers/            # Funciones auxiliares
│   └── send_json.php   # Helper para respuestas JSON
├── public/             # Archivos públicos
│   ├── css/
│   ├── js/
│   ├── index.html      # Página principal
│   ├── login.html      # Página de login
│   ├── register.html   # Página de registro
│   ├── cart.php        # Carrito de compras
│   ├── checkout.php    # Proceso de pago
│   └── product.php     # Detalle de producto
└── uploads/            # Imágenes de productos
```

---

#### Desarrollado con mucho ❤️ por:

- Alexandro Vega Ramírez
- XXXX
- XXXX
- XXXX