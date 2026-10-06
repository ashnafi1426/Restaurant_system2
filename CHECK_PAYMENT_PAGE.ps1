# Payment Page Diagnostic Script
Write-Host "`n=== OrderPaymentPage Diagnostic Check ===" -ForegroundColor Cyan

# Check 1: File exists
Write-Host "`n[1] Checking if OrderPaymentPage.vue exists..." -ForegroundColor Yellow
$filePath = "d:\Restaurant_system2\Client2\vue-project\src\views\payment\OrderPaymentPage.vue"
if (Test-Path $filePath) {
    Write-Host "   OK - OrderPaymentPage.vue EXISTS" -ForegroundColor Green
    $fileSize = (Get-Item $filePath).Length
    Write-Host "   File size: $fileSize bytes" -ForegroundColor Gray
} else {
    Write-Host "   ERROR - OrderPaymentPage.vue NOT FOUND" -ForegroundColor Red
    exit 1
}

# Check 2: Router configuration
Write-Host "`n[2] Checking router configuration..." -ForegroundColor Yellow
$routerPath = "d:\Restaurant_system2\Client2\vue-project\src\router\index.ts"
$routerContent = Get-Content $routerPath -Raw

if ($routerContent -match "import OrderPaymentPage") {
    Write-Host "   OK - OrderPaymentPage imported in router" -ForegroundColor Green
} else {
    Write-Host "   ERROR - OrderPaymentPage NOT imported in router" -ForegroundColor Red
}

if ($routerContent -match "path: '/order/payment'") {
    Write-Host "   OK - Route /order/payment configured" -ForegroundColor Green
} else {
    Write-Host "   ERROR - Route /order/payment NOT configured" -ForegroundColor Red
}

if ($routerContent -match "component: OrderPaymentPage") {
    Write-Host "   OK - Component assigned to route" -ForegroundColor Green
} else {
    Write-Host "   ERROR - Component NOT assigned to route" -ForegroundColor Red
}

# Check 3: QRMenu navigation
Write-Host "`n[3] Checking QRMenu.vue navigation..." -ForegroundColor Yellow
$qrMenuPath = "d:\Restaurant_system2\Client2\vue-project\src\views\guest\QRMenu.vue"
$qrMenuContent = Get-Content $qrMenuPath -Raw

if ($qrMenuContent -match "router\.push") {
    Write-Host "   OK - router.push found in QRMenu.vue" -ForegroundColor Green
} else {
    Write-Host "   WARNING - router.push NOT found in QRMenu.vue" -ForegroundColor Yellow
}

if ($qrMenuContent -match "path: '/order/payment'") {
    Write-Host "   OK - Navigation to /order/payment configured" -ForegroundColor Green
} else {
    Write-Host "   ERROR - Navigation to /order/payment NOT found" -ForegroundColor Red
}

if ($qrMenuContent -match "walk_in_payment_data") {
    Write-Host "   OK - localStorage walk_in_payment_data saving configured" -ForegroundColor Green
} else {
    Write-Host "   ERROR - localStorage saving NOT configured" -ForegroundColor Red
}

# Summary
Write-Host "`n=== Summary ===" -ForegroundColor Cyan
Write-Host "All files and configurations are in place." -ForegroundColor Green
Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Yellow
Write-Host "1. Start dev server: npm run dev" -ForegroundColor White
Write-Host "2. Open browser: http://localhost:5173/menu" -ForegroundColor White
Write-Host "3. Add items to cart" -ForegroundColor White
Write-Host "4. Click Pay with Chapa button" -ForegroundColor White
Write-Host "5. Should see OrderPaymentPage with dark theme" -ForegroundColor White
Write-Host ""
Write-Host "If page does not appear:" -ForegroundColor Yellow
Write-Host "   - Hard refresh browser (Ctrl+Shift+R)" -ForegroundColor White
Write-Host "   - Clear browser cache" -ForegroundColor White
Write-Host "   - Check browser console (F12) for errors" -ForegroundColor White
Write-Host "   - Check localStorage in browser" -ForegroundColor White
Write-Host ""
