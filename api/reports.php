<?php
$host = 'localhost'; 
$db = 'civiceye_db'; // EXACTLY MATCHING YOUR DB!
$user = 'root'; 
$pass = '';

try { $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass); } 
catch (PDOException $e) { die("DB Error: " . $e->getMessage()); }

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

// GET: Fetch all reports, organized by hottest upvotes first!
if ($method === 'GET') {
    $stmt = $pdo->query("SELECT * FROM reports ORDER BY upvotes DESC, id DESC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}

// POST: New Report (Now accepts the Email)
elseif ($method === 'POST') {
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];
    $category = $_POST['category'];
    $desc = $_POST['description'];
    $email = isset($_POST['email']) ? $_POST['email'] : null;
    $imagePath = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imagePath = 'uploads/' . uniqid() . '.' . $ext;
        if (!is_dir('../uploads')) mkdir('../uploads', 0777, true);
        move_uploaded_file($_FILES['image']['tmp_name'], '../' . $imagePath);
    }

    $stmt = $pdo->prepare("INSERT INTO reports (lat, lng, category, description, image_path, email) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$lat, $lng, $category, $desc, $imagePath, $email])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}

// PUT: Resolve Issue OR Add Upvotes!
elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    // 🔥 THE UPVOTE ENGINE 🔥
    if (isset($data['action']) && $data['action'] === 'upvote') {
        $pdo->prepare("UPDATE reports SET upvotes = upvotes + 1 WHERE id = ?")->execute([$data['id']]);
        echo json_encode(['success' => true]);
        exit;
    }

    // ✅ THE RESOLVE ENGINE
    if (isset($data['action']) && $data['action'] === 'resolve') {
        $pdo->prepare("UPDATE reports SET status = 'resolved' WHERE id = ?")->execute([$data['id']]);
        echo json_encode(['success' => true]);
        exit;
    }
}

// DELETE: The Nuke Button
elseif ($method === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (isset($data['action']) && $data['action'] === 'reset') {
        $pdo->exec("DELETE FROM reports"); 
        $pdo->exec("ALTER TABLE reports AUTO_INCREMENT = 1");
        echo json_encode(['success' => true]);
    }
}
?>