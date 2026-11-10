<?php
// Supabase Connection Pooling Host & Port para sa Vercel IPv4 compatibility
$host = getenv('SUPABASE_DB_HOST') ?: 'aws-0-ap-southeast-1.pooler.supabase.com'; // O ang Pooler Host mula sa Supabase Dashboard
$db   = getenv('SUPABASE_DB_NAME') ?: 'postgres';
$user = getenv('SUPABASE_DB_USER') ?: 'postgres.fqzchoftygzyqwguxyzu'; // Direct reference format sa pooler
$pass = getenv('SUPABASE_DB_PASS');
$port = "6543"; // Connection Pooler Port (IPv4 compatible)

try {
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
