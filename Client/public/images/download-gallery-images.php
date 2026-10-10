<?php
/**
 * Gallery Image Downloader - Improved Version
 * Downloads images from Unsplash and saves them to /gallery folder
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(60);

// Configuration
$galleryPath = __DIR__ . '/gallery/';
$baseUrl = 'https://images.unsplash.com/photo-';

// Ensure gallery directory exists
if (!is_dir($galleryPath)) {
    mkdir($galleryPath, 0755, true);
}

// Image definitions with VERIFIED Unsplash IDs
$images = [
    // Rooms Category
    [
        'id' => 'room-1',
        'name' => 'luxury-suite.jpg',
        'unsplash_id' => '1631049307264-da0ec9d70304',
        'category' => 'Rooms',
        'title' => 'Luxury Suite Room'
    ],
    [
        'id' => 'room-2',
        'name' => 'deluxe-double.jpg',
        'unsplash_id' => '1566073771259-6a8506099945',
        'category' => 'Rooms',
        'title' => 'Deluxe Double Room'
    ],
    [
        'id' => 'room-3',
        'name' => 'executive-suite.jpg',
        'unsplash_id' => '1570129477492-45a003537e1f',
        'category' => 'Rooms',
        'title' => 'Executive Master Suite'
    ],
    // Facilities Category
    [
        'id' => 'facility-1',
        'name' => 'swimming-pool.jpg',
        'unsplash_id' => '1576610616656-d3aa5d1f4fabc',
        'category' => 'Facilities',
        'title' => 'Olympic Swimming Pool'
    ],
    [
        'id' => 'facility-2',
        'name' => 'spa-center.jpg',
        'unsplash_id' => '1544367567-0d6fcffe7f4f',
        'category' => 'Facilities',
        'title' => 'Spa & Wellness Center'
    ],
    [
        'id' => 'facility-3',
        'name' => 'elegant-lobby.jpg',
        'unsplash_id' => '1560448204-e02f11c3d0e2',
        'category' => 'Facilities',
        'title' => 'Elegant Lobby'
    ],
    [
        'id' => 'facility-4',
        'name' => 'conference-hall.jpg',
        'unsplash_id' => '1519904981063-b0cf448d479e',
        'category' => 'Facilities',
        'title' => 'Conference Hall'
    ],
    [
        'id' => 'facility-5',
        'name' => 'fitness-center.jpg',
        'unsplash_id' => '1534438327276-14e5300c3a48',
        'category' => 'Facilities',
        'title' => 'Fitness Center'
    ],
    // Restaurant Category
    [
        'id' => 'restaurant-1',
        'name' => 'fine-dining.jpg',
        'unsplash_id' => '1504674900769-8a8a3f3d7131',
        'category' => 'Restaurant',
        'title' => 'Fine Dining Restaurant'
    ],
    [
        'id' => 'restaurant-2',
        'name' => 'restaurant-interior.jpg',
        'unsplash_id' => '1517248135467-4d71bcdd2d59',
        'category' => 'Restaurant',
        'title' => 'Elegant Restaurant Interior'
    ],
    // Outdoor Category
    [
        'id' => 'outdoor-1',
        'name' => 'landscape-garden.jpg',
        'unsplash_id' => '1552462881-23dde8faf51f',
        'category' => 'Outdoor',
        'title' => 'Beautiful Landscape Garden'
    ],
    [
        'id' => 'outdoor-2',
        'name' => 'terrace-seating.jpg',
        'unsplash_id' => '1519904981063-b0cf448d479e',
        'category' => 'Outdoor',
        'title' => 'Outdoor Terrace Seating'
    ]
];

$results = [
    'success' => [],
    'failed' => [],
    'skipped' => []
];

echo "<pre style='font-family: monospace; background: #f4f4f4; padding: 20px; border-radius: 5px;'>";
echo "=== Unsplash Gallery Image Downloader (Improved) ===\n\n";

foreach ($images as $image) {
    $filePath = $galleryPath . $image['name'];
    
    // Skip if file already exists
    if (file_exists($filePath)) {
        $size = filesize($filePath);
        $sizeKb = round($size / 1024, 2);
        echo "[SKIP] {$image['name']} - Already exists ({$sizeKb} KB)\n";
        $results['skipped'][] = $image['name'];
        continue;
    }
    
    // Build Unsplash URL
    $url = $baseUrl . $image['unsplash_id'] . '?w=1000&h=800&fit=crop&q=80';
    
    echo "[DOWNLOAD] {$image['title']}\n";
    echo "  → Filename: {$image['name']}\n";
    
    // Download with cURL for better compatibility
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);
    
    $imageData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode === 200 && $imageData) {
        if (file_put_contents($filePath, $imageData)) {
            $size = filesize($filePath);
            $sizeKb = round($size / 1024, 2);
            echo "  ✓ SUCCESS: Saved ({$sizeKb} KB)\n\n";
            $results['success'][] = $image['name'];
        } else {
            echo "  ✗ FAILED: Could not write file to disk\n\n";
            $results['failed'][] = $image['name'];
        }
    } else {
        echo "  ✗ FAILED: HTTP $httpCode - $error\n\n";
        $results['failed'][] = $image['name'];
    }
    
    // Small delay to avoid rate limiting
    usleep(500000);
}

echo "\n=== Download Summary ===\n";
echo "✓ Successful: " . count($results['success']) . "/12\n";
echo "✗ Failed: " . count($results['failed']) . "/12\n";
echo "⊘ Skipped: " . count($results['skipped']) . "/12\n";

if (!empty($results['failed'])) {
    echo "\nFailed images (you may need to download manually):\n";
    foreach ($results['failed'] as $failed) {
        echo "  - $failed\n";
    }
}

echo "\n=== Copy-Paste Code for Gallary.vue ===\n";
echo "Replace the galleryItems in Gallary.vue with:\n\n";
?>

const galleryItems = ref([
  // === ROOMS CATEGORY ===
  {
    id: 1,
    title: 'Luxury Suite Room',
    category: 'Rooms',
    src: '/images/gallery/luxury-suite.jpg',
  },
  {
    id: 2,
    title: 'Deluxe Double Room',
    category: 'Rooms',
    src: '/images/gallery/deluxe-double.jpg',
  },
  {
    id: 3,
    title: 'Executive Master Suite',
    category: 'Rooms',
    src: '/images/gallery/executive-suite.jpg',
  },
  
  // === FACILITIES CATEGORY ===
  {
    id: 4,
    title: 'Olympic Swimming Pool',
    category: 'Facilities',
    src: '/images/gallery/swimming-pool.jpg',
  },
  {
    id: 5,
    title: 'Spa & Wellness Center',
    category: 'Facilities',
    src: '/images/gallery/spa-center.jpg',
  },
  {
    id: 6,
    title: 'Elegant Lobby',
    category: 'Facilities',
    src: '/images/gallery/elegant-lobby.jpg',
  },
  {
    id: 7,
    title: 'Conference Hall',
    category: 'Facilities',
    src: '/images/gallery/conference-hall.jpg',
  },
  {
    id: 8,
    title: 'Fitness Center',
    category: 'Facilities',
    src: '/images/gallery/fitness-center.jpg',
  },
  
  // === RESTAURANT CATEGORY ===
  {
    id: 9,
    title: 'Fine Dining Restaurant',
    category: 'Restaurant',
    src: '/images/gallery/fine-dining.jpg',
  },
  {
    id: 10,
    title: 'Elegant Restaurant Interior',
    category: 'Restaurant',
    src: '/images/gallery/restaurant-interior.jpg',
  },
  
  // === OUTDOOR CATEGORY ===
  {
    id: 11,
    title: 'Beautiful Landscape Garden',
    category: 'Outdoor',
    src: '/images/gallery/landscape-garden.jpg',
  },
  {
    id: 12,
    title: 'Outdoor Terrace Seating',
    category: 'Outdoor',
    src: '/images/gallery/terrace-seating.jpg',
  },
])

<?php
echo "</pre>";
?>
