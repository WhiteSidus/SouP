<?php
// api_zavady.php
header('Content-Type: application/json; charset=utf-8');

try {
    // Připojení k databázi přes PDO
    $pdo = new PDO("mysql:host=localhost;dbname=zavady_db;charset=utf8mb4", "root", "root");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vybrání všech hlášení
    // Vybíráme pouze ty záznamy, které mají vyplněné souřadnice (pro zobrazení na mapě)
    $stmt = $pdo->query("SELECT id, fotka_cesta, adresa, popis, lat, lng FROM nahlaseni WHERE lat IS NOT NULL AND lng IS NOT NULL");
    
    // Získání výsledků jako asociativní pole
    $zavady = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Vrácení výsledku ve formátu JSON
    echo json_encode($zavady);

} catch(PDOException $e) {
    // V případě chyby vrátíme error JSON
    http_response_code(500);
    echo json_encode(['error' => 'Chyba při načítání z databáze: ' . $e->getMessage()]);
}
?>
