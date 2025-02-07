<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isVeg = $_POST['isVeg'] ?? '';
    $price = $_POST['price'] ?? 0;
    $tastes = json_decode($_POST['tastes'], true) ?? [];
    $sortBy = $_POST['sortBy'] ?? 'price';

    $csvFile = 'food_data.csv';
    $foodItems = [];

    if (($handle = fopen($csvFile, 'r')) !== false) {
        // Skip the first row if it's a header
        fgetcsv($handle);

        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            $foodItems[] = [
                'type' => $data[0],
                'taste' => $data[1],
                'price' => (int)$data[2],
                'rating' => (int)$data[3],
                'name' => $data[4],
                'location' => $data[5],
                'image' => $data[6],
                'description' => $data[7] ?? '',  // New description column
            ];
        }
        fclose($handle);
    }

    $filteredFoodItems = array_filter($foodItems, function($item) use ($isVeg, $price, $tastes) {
        $matchesVeg = empty($isVeg) || strtolower($item['type']) === strtolower($isVeg);
        $matchesPrice = $item['price'] <= $price;
        $matchesTaste = empty($tastes) || array_reduce($tastes, function($carry, $taste) use ($item) {
            return $carry || stripos($item['taste'], $taste) !== false;
        }, false);

        return $matchesVeg && $matchesPrice && $matchesTaste;
    });

    usort($filteredFoodItems, function($a, $b) use ($sortBy) {
        if (strpos($sortBy, 'desc') !== false) {
            return $b[str_replace('-desc', '', $sortBy)] <=> $a[str_replace('-desc', '', $sortBy)];
        }
        return $a[$sortBy] <=> $b[$sortBy];
    });

    header('Content-Type: application/json');
    echo json_encode($filteredFoodItems);
} else {
    header('Content-Type: application/json');
    echo json_encode([]);
}
?>
