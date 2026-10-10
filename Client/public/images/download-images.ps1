# Download all images for the restaurant hotel system
# Run this script from the images folder

$images = @{
    "hero/hero.jpg" = "https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1920"
    
    "rooms/deluxe.jpg" = "https://images.unsplash.com/photo-1590490360182-c33d57733427?w=1200"
    "rooms/suite.jpg" = "https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=1200"
    "rooms/family.jpg" = "https://images.unsplash.com/photo-1578683010236-d716f9a3f461?w=1200"
    
    "facilities/restaurant.jpg" = "https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1200"
    "facilities/pool.jpg" = "https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?w=1200"
    "facilities/spa.jpg" = "https://images.unsplash.com/photo-1544161515-4ab6ce6db874?w=1200"
    "facilities/gym.jpg" = "https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=1200"
    "facilities/conference.jpg" = "https://images.unsplash.com/photo-1511578314322-379afb476865?w=1200"
    "facilities/garden.jpg" = "https://images.unsplash.com/photo-1506744038136-46273834b3fb?w=1200"
    "facilities/lobby.jpg" = "https://images.unsplash.com/photo-1445019980597-93fa8acb246c?w=1200"
    
    "gallery/gallery1.jpg" = "https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1200"
    "gallery/gallery2.jpg" = "https://images.unsplash.com/photo-1590490360182-c33d57733427?w=1200"
    "gallery/gallery3.jpg" = "https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=1200"
    "gallery/gallery4.jpg" = "https://images.unsplash.com/photo-1578683010236-d716f9a3f461?w=1200"
    "gallery/gallery5.jpg" = "https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1200"
    "gallery/gallery6.jpg" = "https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?w=1200"
    
    "team/manager.jpg" = "https://images.unsplash.com/photo-1560250097-0b93528c311a?w=600"
    "team/chef.jpg" = "https://images.unsplash.com/photo-1583394293214-28ded15ee548?w=600"
    "team/reception.jpg" = "https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=600"
    "team/relations.jpg" = "https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=600"
    
    "testimonials/guest1.jpg" = "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=500"
    "testimonials/guest2.jpg" = "https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=500"
    "testimonials/guest3.jpg" = "https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=500"
    
    "food/burger.jpg" = "https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=1200"
    "food/pizza.jpg" = "https://images.unsplash.com/photo-1604068549290-dea0e4a305ca?w=1200"
    "food/coffee.jpg" = "https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=1200"
    "food/dessert.jpg" = "https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=1200"
}

Write-Host "Starting image downloads..." -ForegroundColor Green
$downloadedCount = 0
$failedCount = 0

foreach ($item in $images.GetEnumerator()) {
    $filePath = $item.Key
    $url = $item.Value
    
    try {
        Write-Host "Downloading: $filePath" -ForegroundColor Cyan
        Invoke-WebRequest -Uri $url -OutFile $filePath -ErrorAction Stop
        Write-Host "Downloaded: $filePath" -ForegroundColor Green
        $downloadedCount++
    }
    catch {
        Write-Host "Failed to download: $filePath" -ForegroundColor Red
        $failedCount++
    }
    
    Start-Sleep -Milliseconds 500
}

Write-Host "`nDownload Summary:" -ForegroundColor Yellow
Write-Host "Downloaded: $downloadedCount" -ForegroundColor Green
Write-Host "Failed: $failedCount" -ForegroundColor Red
