# Restaurant System Startup Guide

This guide explains how to start all required services for the restaurant system, including real-time order status updates via WebSocket.

## Prerequisites

- PHP 8.1 or higher
- Composer
- Node.js 16+ and npm
- MySQL/MariaDB database
- All dependencies installed (`composer install` and `npm install`)

## Required Services

The system requires **4 services** running simultaneously for full functionality:

### Terminal 1: Laravel API Server

The main backend API that handles all HTTP requests.

```powershell
cd d:\Restaurant_system2\server
php artisan serve --host=127.0.0.1 --port=8000
```

**Expected output:**
```
Laravel development server started: http://127.0.0.1:8000
```

**Keep this terminal running** - do not close it.

**Verify:** Open http://127.0.0.1:8000/api/categories in your browser - should return JSON data.

---

### Terminal 2: Laravel Reverb WebSocket Server

The WebSocket server that enables real-time updates for order status.

```powershell
cd d:\Restaurant_system2\server
php artisan reverb:start --host=0.0.0.0 --port=8080 --debug
```

**Expected output:**
```
  Reverb server started on 0.0.0.0:8080
  Application ID: restaurant-app
  
  ┌─────────────────────────────────────────┐
  │ Reverb server running on port 8080     │
  └─────────────────────────────────────────┘
```

**Keep this terminal running** - do not close it.

**Note:** The `--debug` flag provides detailed logging for troubleshooting WebSocket connections.

**Verify:** 
```powershell
netstat -an | Select-String "8080.*LISTENING"
```
Should show port 8080 is listening.

---

### Terminal 3: Laravel Queue Worker

⚠️ **CRITICAL FOR REAL-TIME UPDATES**

The queue worker processes background jobs, including broadcasting WebSocket events. **Without this, order status updates will NOT be sent to customers.**

```powershell
cd d:\Restaurant_system2\server
php artisan queue:work --queue=default --tries=3 --timeout=90 --verbose
```

**Expected output:**
```
[2024-01-15 10:30:00] Processing: App\Events\OrderStatusUpdated
[2024-01-15 10:30:00] Processed:  App\Events\OrderStatusUpdated
```

**Keep this terminal running** - do not close it.

**Important:** 
- This worker processes the `OrderStatusUpdated` event that broadcasts order status changes
- Without it, chef status updates (pending → preparing → ready → served) will NOT reach customers
- If you restart the Laravel server, you must also restart this worker

**Note about QUEUE_CONNECTION=sync:** If your `.env` file has `QUEUE_CONNECTION=sync`, events that implement `ShouldQueue` (like `OrderStatusUpdated`) are processed immediately in the same request rather than being queued. In sync mode, you don't need a separate queue worker process, but the queue system is still used - jobs just execute synchronously. Check your `.env` file:
```ini
QUEUE_CONNECTION=database  # Requires queue worker (events queued to database, processed async)
# or
QUEUE_CONNECTION=sync      # Does NOT require queue worker (events processed immediately)
```

---

### Terminal 4: Vue.js Development Server

The frontend application for customers, staff, and administrators.

```powershell
cd d:\Restaurant_system2\Client2\vue-project
npm run dev
```

**Expected output:**
```
  VITE v4.x.x  ready in XXXms

  ➜  Local:   http://localhost:5173/
  ➜  Network: use --host to expose
```

**Keep this terminal running** - do not close it.

**Verify:** Open http://localhost:5173 in your browser - should load the application.

---

## Quick Health Check

Run these commands to verify all services are running:

```powershell
# Check Laravel API (should return JSON)
Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/categories" -UseBasicParsing

# Check Reverb WebSocket (should show LISTENING on port 8080)
netstat -an | Select-String "8080.*LISTENING"

# Check Queue Worker (should show php artisan queue:work process)
Get-Process | Where-Object {$_.CommandLine -like "*queue:work*"}

# Check Vue Dev Server (should return HTML)
Invoke-WebRequest -Uri "http://localhost:5173" -UseBasicParsing
```

---

## Testing Real-Time Order Status Updates

1. **Place a test order:**
   - Open http://localhost:5173/qr/YOUR_QR_TOKEN
   - Add items to cart and place order
   - You'll be redirected to the order status page

2. **Verify WebSocket connection:**
   - Open browser DevTools (F12) → Console
   - You should see:
     ```
     [Echo] ✅ Connected to Reverb WebSocket server
     [useOrderStatus] ✅ Extracted hotel_id from API: {uuid}
     [useOrderStatus] 📡 Subscribing to channel: orders.{hotel-id}.{order-id}
     [Echo] ✅ Channel authorization successful: private-orders.{hotel-id}.{order-id}
     ```
   - The "Live" indicator (green dot) should be visible

3. **Test live updates:**
   - Open http://localhost:5173/kitchen in another tab/window
   - Log in as a chef/kitchen staff member
   - Find your test order (should show as "Pending")
   - Click "Start Preparing"
   - **Within 1-2 seconds**, the order status page should automatically update to "Preparing Your Order"
   - **No page refresh needed** - the update is instant via WebSocket

4. **Check queue worker terminal:**
   - You should see:
     ```
     Processing: App\Events\OrderStatusUpdated
     Processed:  App\Events\OrderStatusUpdated
     ```

---

## Troubleshooting

### Problem: Order status doesn't update live

**Check:**
1. ✅ Is the queue worker running? (Terminal 3)
   ```powershell
   Get-Process | Where-Object {$_.CommandLine -like "*queue:work*"}
   ```

2. ✅ Is the Reverb WebSocket server running? (Terminal 2)
   ```powershell
   netstat -an | Select-String "8080.*LISTENING"
   ```

3. ✅ Does the browser console show WebSocket connected?
   - Open DevTools (F12) → Console
   - Look for `[Echo] ✅ Connected to Reverb WebSocket server`

4. ✅ Is the hotel_id extracted correctly?
   - Check console for: `[useOrderStatus] ✅ Extracted hotel_id from API: {uuid}`
   - If hotel_id is missing or empty, the channel subscription will fail

5. ✅ Is the channel subscription successful?
   - Check console for: `[Echo] ✅ Channel authorization successful`
   - If authorization fails (403), check that the order belongs to the correct hotel

### Problem: 403 Forbidden when loading order status

**Causes:**
- Missing or invalid `qr_token` in localStorage
- Order doesn't exist
- QR token doesn't match the order's room/table

**Fix:**
- Ensure you accessed the order status page via the QR menu flow (scan QR → place order → view status)
- Check browser console for error details
- Verify `qr_token` is in localStorage: `localStorage.getItem('guest_qr_token')`

### Problem: Queue worker not processing events

**Causes:**
- Queue worker is not running
- Database queue table is full or corrupted

**Fix:**
1. Restart the queue worker (Terminal 3):
   ```powershell
   # Press Ctrl+C to stop the current worker, then:
   cd d:\Restaurant_system2\server
   php artisan queue:work --queue=default --tries=3 --timeout=90 --verbose
   ```

2. Clear failed jobs:
   ```powershell
   cd d:\Restaurant_system2\server
   php artisan queue:flush
   ```

3. Check the jobs table:
   ```powershell
   cd d:\Restaurant_system2\server
   php artisan tinker
   # Then in tinker:
   DB::table('jobs')->count()
   DB::table('failed_jobs')->count()
   ```

---

## Environment Configuration

### Backend (.env)

Ensure these settings are configured in `d:\Restaurant_system2\server\.env`:

```ini
# Queue Configuration
QUEUE_CONNECTION=database

# Broadcasting Configuration
BROADCAST_DRIVER=reverb

# Reverb Configuration
REVERB_APP_ID=restaurant-app
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
```

### Frontend (.env)

Ensure these settings are configured in `d:\Restaurant_system2\Client2\vue-project\.env`:

```ini
VITE_API_BASE_URL=http://127.0.0.1:8000

# Reverb WebSocket Configuration
VITE_REVERB_APP_KEY=your-app-key
VITE_REVERB_HOST=127.0.0.1
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http
```

**Important:** The `VITE_REVERB_APP_KEY` must match the `REVERB_APP_KEY` in the backend `.env`.

---

## Production Deployment

For production deployment:

1. **Use a process manager** for the queue worker:
   - **Linux:** `supervisor` or `systemd`
   - **Windows:** `nssm` (Non-Sucking Service Manager)

   Example supervisor config:
   ```ini
   [program:restaurant-queue-worker]
   process_name=%(program_name)s_%(process_num)02d
   command=php /path/to/artisan queue:work --queue=default --tries=3 --timeout=90
   autostart=true
   autorestart=true
   user=www-data
   numprocs=2
   redirect_stderr=true
   stdout_logfile=/var/log/restaurant-queue-worker.log
   ```

2. **Use a proper WebSocket server:**
   - Deploy Reverb behind a reverse proxy (nginx/Apache)
   - Use SSL/TLS for secure WebSocket connections (wss://)
   - Set `REVERB_SCHEME=https` in production

3. **Frontend:**
   - Build the Vue app: `npm run build`
   - Serve the `dist` folder via nginx/Apache
   - Update `VITE_API_BASE_URL` to your production API URL

---

## Development Tips

- **Hot Reload:** The Vue dev server supports hot module replacement - changes to Vue files reload automatically
- **Queue Worker Reload:** If you change event handlers or listeners, restart the queue worker to pick up changes
- **Database Migrations:** Run `php artisan migrate` after pulling updates
- **Clear Cache:** Run `php artisan optimize:clear` if you encounter unexpected behavior

---

## Summary: Startup Checklist

- [ ] Terminal 1: `php artisan serve` (Laravel API)
- [ ] Terminal 2: `php artisan reverb:start --debug` (WebSocket server)
- [ ] Terminal 3: `php artisan queue:work --verbose` (Queue worker)
- [ ] Terminal 4: `npm run dev` (Vue frontend)
- [ ] Verify all services are running (see Quick Health Check)
- [ ] Test order status updates (see Testing section)

**All 4 terminals must remain open for the system to work correctly.**

---

## Additional Resources

- Laravel Broadcasting: https://laravel.com/docs/broadcasting
- Laravel Reverb: https://laravel.com/docs/reverb
- Laravel Echo: https://laravel.com/docs/broadcasting#client-side-installation
- Vue.js: https://vuejs.org/guide/
