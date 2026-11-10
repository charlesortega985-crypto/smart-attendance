<?php
// Supabase Connection Settings - Sydney Region Pooler
$host = getenv('SUPABASE_DB_HOST') ?: 'aws-0-ap-southeast-2.pooler.supabase.com';
$db   = getenv('SUPABASE_DB_NAME') ?: 'postgres';
$user = getenv('SUPABASE_DB_USER') ?: 'postgres.fqzchoftygzyqwguxyzu';
$pass = getenv('SUPABASE_DB_PASS');
$port = "6543"; // Connection Pooler Port para sa Vercel

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require;";
    
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
?>
