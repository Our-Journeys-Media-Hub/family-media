<?php
try {
  $pdo = new PDO('mysql:host=localhost;dbname=demo_auth;charset=utf8mb4', 'journeys', 'SehrStark!123');
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  echo "OK: DB-Verbindung steht.";
} catch (Throwable $e) {
  echo "FEHLER: ".$e->getMessage();
}