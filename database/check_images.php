<?php
require dirname(__DIR__) . '/app/Core/Database.php';

$pdo = App\Core\Database::connection();

$rows = $pdo->query("
  SELECT p.id, p.name, p.slug, pi.path
  FROM products p
  LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
  WHERE p.active = 1 AND p.name LIKE 'Bali%'
  ORDER BY p.name
  LIMIT 30
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $r) {
    echo $r['name'] . ' | ' . ($r['path'] ?? 'NULL') . PHP_EOL;
}

echo "--- duplicate primary paths ---" . PHP_EOL;
$u = $pdo->query("
  SELECT pi.path, COUNT(*) c, GROUP_CONCAT(p.name, ' | ') names
  FROM products p
  JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
  WHERE p.active = 1 AND p.name LIKE 'Bali%'
  GROUP BY pi.path
  HAVING c > 1
  ORDER BY c DESC
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($u as $r) {
    echo $r['c'] . 'x ' . $r['path'] . ' => ' . $r['names'] . PHP_EOL;
}
echo 'dup groups: ' . count($u) . PHP_EOL;

echo "--- products missing image ---" . PHP_EOL;
$m = $pdo->query("
  SELECT p.id, p.name
  FROM products p
  LEFT JOIN product_images pi ON pi.product_id = p.id
  WHERE p.active = 1 AND pi.id IS NULL
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($m as $r) echo $r['id'] . ' ' . $r['name'] . PHP_EOL;
echo 'missing: ' . count($m) . PHP_EOL;

echo "--- sample img src from catalog HTML ---" . PHP_EOL;
$html = @file_get_contents('http://localhost:8080/categoria/colecao-bali');
if ($html) {
    preg_match_all('/product-card[\s\S]*?<img src="([^"]+)"/', $html, $m2);
    $srcs = array_slice($m2[1] ?? [], 0, 12);
    foreach ($srcs as $s) echo $s . PHP_EOL;
    echo 'unique among first 12: ' . count(array_unique($srcs)) . PHP_EOL;
}
