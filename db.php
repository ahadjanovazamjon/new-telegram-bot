<?php
require_once __DIR__ . '/config.php';

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_FILE);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
    return $pdo;
}

function initDB() {
    $db = getDB();
    
    // Create tables
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        telegram_id INTEGER UNIQUE,
        full_name TEXT,
        phone TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT,
        slug TEXT UNIQUE
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        category_id INTEGER,
        name TEXT,
        description TEXT,
        price REAL,
        image_url TEXT,
        FOREIGN KEY (category_id) REFERENCES categories(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS rooms (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT,
        capacity TEXT,
        hourly_price REAL,
        description TEXT,
        image_url TEXT,
        is_available INTEGER DEFAULT 1
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        type TEXT,
        room_id INTEGER,
        total_price REAL,
        status TEXT DEFAULT 'Kutmoqda',
        address_lat_long TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER,
        product_id INTEGER,
        quantity INTEGER,
        price REAL,
        FOREIGN KEY (order_id) REFERENCES orders(id),
        FOREIGN KEY (product_id) REFERENCES products(id)
    )");

    // Seed dummy data if empty
    $stmt = $db->query("SELECT COUNT(*) as count FROM categories");
    if ($stmt->fetch()['count'] == 0) {
        $db->exec("INSERT INTO categories (name, slug) VALUES 
            ('Taomlar', 'taomlar'),
            ('Kaboblar', 'kaboblar'),
            ('Ichimliklar', 'ichimliklar')
        ");

        $db->exec("INSERT INTO products (category_id, name, description, price, image_url) VALUES
            (1, 'To''y Osh', 'Maxsus Toshkent oshi, mol go''shti bilan', 45000, 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=500&q=80'),
            (1, 'Manti', 'Go''shtli va piyozli manti (5 dona)', 35000, 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?auto=format&fit=crop&w=500&q=80'),
            (2, 'Qiyma Kabob', 'Shirador va yumshoq mol go''shti', 18000, 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?auto=format&fit=crop&w=500&q=80'),
            (2, 'Jaz Kabob', 'Tandirda pishirilgan qo''y go''shti', 22000, 'https://images.unsplash.com/photo-1603360946369-dc9bb6258143?auto=format&fit=crop&w=500&q=80'),
            (3, 'Kuk choy', 'Ko''k choy limoni bilan', 5000, 'https://images.unsplash.com/photo-1576092768241-dec231879fc3?auto=format&fit=crop&w=500&q=80')
        ");

        $db->exec("INSERT INTO rooms (name, capacity, hourly_price, description, image_url) VALUES
            ('VIP Xona', '8-10 kishilik', 100000, 'Konditsioner, Smart TV, PS5 bor', 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=500&q=80'),
            ('Milliy Tapchan', '4-6 kishilik', 50000, 'Ochiq havodagi shinam tapchan', 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=500&q=80')
        ");
    }
}
initDB();
?>
