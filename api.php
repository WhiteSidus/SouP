<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Nastavení MAMP databáze
$host = 'localhost';
$user = 'root';
$pass = 'root'; // Výchozí heslo MAMP na Windows/Mac je 'root'
$dbname = 'zavady_db';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Nelze se připojit k databázi ' . $dbname . ': ' . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? '';
// Přečteme JSON data, pokud dorazila, jinak $_POST
$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

if ($action === 'register') {
    $name = $data['name'] ?? ''; // Můžeme si ponechat pro případné jiné využití, ale do DB se neukládá
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    
    if (!$email || !$password) {
        echo json_encode(['status' => 'error', 'message' => 'Vyplňte e-mail a heslo.']);
        exit;
    }
    
    // Hashování hesla - bezpečnější přístup
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        // Tabulka 'registrovany' má sloupce 'Email', 'heslo'
        $stmt = $pdo->prepare("INSERT INTO registrovany (Email, heslo) VALUES (?, ?)");
        $stmt->execute([$email, $hash]);
        echo json_encode(['status' => 'success', 'message' => 'Registrace proběhla úspěšně.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Chyba databáze (možná email už existuje): ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'login') {
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM registrovany WHERE Email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $hesloZDb = $user['heslo'] ?? $user['password'] ?? '';
        $isPasswordValid = false;
        
        if ($user) {
            // Kontrola pro zahešované heslo i heslo v čistém textu, pokud by tam bylo z dřívějška
            if (password_verify($password, $hesloZDb) || $password === $hesloZDb) {
                $isPasswordValid = true;
            }
        }
        
        if ($isPasswordValid) {
            $_SESSION['user_id'] = $user['ID'] ?? $user['id'] ?? $user['Email'];
            // V databázi není uloženo jméno, takže použijeme část e-mailu před znakem @ jako jméno
            $displayName = explode('@', $user['Email'])[0];
            $_SESSION['user_name'] = $displayName;
            echo json_encode(['status' => 'success', 'message' => 'Přihlášení úspěšné.', 'name' => $displayName]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Špatný e-mail nebo heslo.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Chyba databáze (nesedí sloupce/tabulka): ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'report') {
    $place = $data['place'] ?? '';
    $title = $data['title'] ?? '';
    $description = $data['description'] ?? '';
    $date = date('d. m. Y');
    
    try {
        // Očekáváme tabulku 'nahlaseni' se sloupci 'nazev', 'misto', 'popis', 'stav', 'datum'
        $stmt = $pdo->prepare("INSERT INTO nahlaseni (nazev, misto, popis, stav, datum) VALUES (?, ?, ?, 'Nové', ?)");
        $stmt->execute([$title, $place, $description, $date]);
        echo json_encode(['status' => 'success', 'message' => 'Hlášení bylo odesláno.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Chyba databáze (nesedí sloupce/tabulka): ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'getReports') {
    try {
        // Načte všechna hlášení od nejnovějšího (předpokládáme primární klíč id)
        // Pokud id neexistuje, smažte "ORDER BY id DESC" z příkazu
        $stmt = $pdo->query("SELECT * FROM nahlaseni ORDER BY id DESC");
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $out = [];
        foreach($reports as $r) {
            $out[] = [
                'title' => $r['nazev'] ?? $r['title'] ?? 'Neznámý název',
                'place' => $r['misto'] ?? $r['place'] ?? 'Neznámé místo',
                'status' => $r['stav'] ?? $r['status'] ?? 'Nové',
                'date' => $r['datum'] ?? $r['date'] ?? ''
            ];
        }
        echo json_encode(['status' => 'success', 'data' => $out]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Chyba databáze (nesedí sloupce/tabulka): ' . $e->getMessage(), 'data' => []]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Neznámá akce.']);
