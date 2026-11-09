<?php
// Se incluye al principio de cada vista protegida: sin sesión, vuelve al login.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION["id_usuario"])) {
    header("Location: login.html");
    exit;
}
