<?php
// Datos de conexión a la base de datos de TechInventory.
// Cada valor se puede sobrescribir con una variable de entorno (TI_DB_HOST, TI_DB_PORT, TI_DB_USER, TI_DB_PASS, TI_DB_NAME).

// Servidor de la base de datos
define("DB_HOST", getenv("TI_DB_HOST") ?: "localhost");
// Puerto de MySQL/MariaDB (XAMPP usa 3306 por defecto; 3307 si el 3306 está ocupado)
define("DB_PORT", (int)(getenv("TI_DB_PORT") ?: 3307));
// Nombre de la base de datos
define("DB_NAME", getenv("TI_DB_NAME") ?: "techinventory");
// Usuario
define("DB_USERNAME", getenv("TI_DB_USER") ?: "root");
// Contraseña (vacía por defecto en XAMPP)
define("DB_PASSWORD", getenv("TI_DB_PASS") !== false ? getenv("TI_DB_PASS") : "");
// Codificación de caracteres
define("DB_ENCODE", "utf8mb4");
