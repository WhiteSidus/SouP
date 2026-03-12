<?php
// zpracovani.php

// Pokud na tuto stránku uživatel přesměruje formulář (metoda POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Získání textových dat odeslaných formulářem (s ošetřením, aby ignoroval prázdná data, pokud by nebyla)
    $adresa = trim($_POST['Adresa'] ?? '');
    $popis = trim($_POST['Popis'] ?? '');

    // Zpracování nahraného souboru - z pole $_FILES ('Soubor' odpovídá parametru name="")
    $soubor = $_FILES['Soubor'] ?? null;

    $zprava = "";

    // Složka, kam se budou ukládat fotky (pokud neexistuje, vytvoříme ji)
    $uploadSlozka = __DIR__ . '/uploads/';
    if (!file_exists($uploadSlozka)) {
        mkdir($uploadSlozka, 0777, true); // Vytvoří složku a udělí jí práva k zápisu
    }

    // Zkontrolujeme, zda se soubor nahrál do dočasné složky na serveru bez chyb (UPLOAD_ERR_OK)
    if ($soubor && $soubor['error'] === UPLOAD_ERR_OK) {
        
        // Získáme původní název souboru
        $jmenoSouboru = basename($soubor['name']);
        
        // Cílová cesta pro uložení (kvůli stejným názvům přidávám čas nahrání jako prefix)
        $cilovaCesta = $uploadSlozka . time() . '_' . $jmenoSouboru; 
        
        // Přesunutí fotky z dočasné složky XAMPP/MAMP do naší složky /uploads/
        if (move_uploaded_file($soubor['tmp_name'], $cilovaCesta)) {
            $zprava = "Soubor byl úspěšně uložen.";
            
            try {
    // 1. Vytvoření připojení k databázi přes PDO
    $pdo = new PDO("mysql:host=localhost;dbname=zavady_db;charset=utf8mb4", "root", "root");
    
    // Nastavení, aby PDO vyhazovalo výjimky při chybách
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Příprava SQL dotazu pro vložení dat
    $stmt = $pdo->prepare("INSERT INTO nahlaseni (fotka_cesta, adresa, popis) VALUES (:fotka, :adresa, :popis)");

    // 3. Provedení dotazu s našimi daty (zabrání to i tzv. SQL injection)
    $stmt->execute([
        ':fotka' => $cilovaCesta,
        ':adresa' => $adresa,
        ':popis' => $popis
    ]);

    $zprava .= " Data byla úspěšně uložena do databáze.";

} catch(PDOException $e) {
    $zprava .= " Chyba při ukládání do DB: " . $e->getMessage();
}

            
        } else {
            $zprava = "Při finálním ukládání souboru došlo k chybě.";
        }
    } else {
        $zprava = "Soubor se nepodařilo odeslat nebo žádný nebyl vybrán.";
    }
} else {
    // Pokud někdo otevře soubor napřímo zadáním URL, pouze ho tiše přesměrujeme zpět na index
    header("Location: index.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Potvrzení nahlášení</title>
</head>
<body>
    <header>
        <h1>Výsledek odeslání závady</h1>
    </header>
    <main>
        <h3>Stav: <?php echo htmlspecialchars($zprava); ?></h3>
        
        <p><strong>Adresa:</strong> <?php echo htmlspecialchars($adresa); ?></p>
        <p><strong>Popis:</strong> <?php echo htmlspecialchars($popis); ?></p>
        
        <br>
        <a href="index.html">← Zpět na hlavní stránku</a>
    </main>
</body>
</html>