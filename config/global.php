<?php
// Datos de conexión a la base de datos de TechInventory

// Servidor de la base de datos
define("DB_HOST", getenv("TI_DB_HOST") ?: "localhost");

// Puerto de MySQL
define("DB_PORT", (int)(getenv("TI_DB_PORT") ?: 3307));

// Nombre de la base de datos
define("DB_NAME", getenv("TI_DB_NAME") ?: "techinventory");

// Usuario
define("DB_USERNAME", getenv("TI_DB_USER") ?: "root");

// Contraseña
define("DB_PASSWORD", getenv("TI_DB_PASS") !== false ? getenv("TI_DB_PASS") : "");

// Codificación de caracteres
define("DB_ENCODE", "utf8mb4");
