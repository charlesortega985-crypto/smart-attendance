<?php
// Kunin ang Environment Variables mula sa Vercel Dashboard
$host = getenv('SUPABASE_DB_HOST') ?: 'db.fqzchoftygzyqwguxyzu.supabase.co';
$db   = getenv('SUPABASE_DB_NAME') ?: 'postgres';
$user = getenv('SUPABASE_DB_USER') ?: 'postgres';
$pass = getenv('SUPABASE_DB_PASS');
$port = "5432"; // Explicit TCP Port para sa Supabase

try {
    // Siguraduhing may host at port sa DSN para pilitin ang TCP network socket
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;";
    
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
?>
