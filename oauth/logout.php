<?php
session_start();

// Détruire toutes les variables de session
$_SESSION = array();

// Détruire la session
session_destroy();

// Rediriger vers la page de connexion avec une animation
header('Location: login.php');
exit;
?> 