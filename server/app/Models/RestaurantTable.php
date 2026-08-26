<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\QRCodeService;
use Illuminate\Support\Str;

class RestaurantTable extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'table_number',
        'table_name',
        'capacity',
        'location',
        'status',
        'is_active',
        'qr_token',
        'qr_image_path',
        'qr_generated_at',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
        'qr_generated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Status Constants
    |--------------------------------------------------------------------------
    */
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_OCCUPIED = 'occupied';
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_CLEANING = 'cleaning';
    public const STATUS_OUT_OF_SERVICE = 'out_of_service';

    /**
     * Boot model to auto-generate QR token and code when creating
     */
    protected static function booted()
    {
        static::creating(function ($table) {
            // Generate random 8-character token if not set
            if (!$table->qr_token) {
                $table->qr_token = self::generateUniqueToken();
            }
        });

        static::created(function ($table) {
            // Generate QR code image after table is created
            try {
                $qrImagePath = self::generateTableQRCode(
                    $table->id,
                    $table->table_number,
                    $table->qr_token,
                    config('app.frontend_url', 'http://localhost:5173')
                );
                
                $table->update([
                    'qr_image_path' => $qrImagePath,
                    'qr_generated_at' => now(),
                ]);
                
                \Log::info('QR Code Generated for Restaurant Table', [
                    'table_id' => $table->id,
                    'table_number' => $table->table_number,
                    'token' => $table->qr_token,
                    'image_path' => $qrImagePath,
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to generate QR code for restaurant table', [
                    'table_id' => $table->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * Generate unique 8-character token
     */
    public static function generateUniqueToken(): string
    {
        do {
            $token = strtoupper(Str::random(8));
        } while (self::where('qr_token', $token)->exists());
        
        return $token;
    }

    /**
     * Generate QR code specifically for restaurant tables
     */
    protected static function generateTableQRCode($tableId, $tableNumber, $qrToken, $baseUrl): string
    {
        // URL pattern: /restaurant-order/{token} to distinguish from room orders
        $url = "{$baseUrl}/restaurant-order/{$qrToken}";
        
        $storageDir = storage_path('app/public/qr-codes/tables');
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }
        
        $filename = "table_{$tableNumber}.png";
        $filePath = $storageDir . '/' . $filename;
        
        try {
            $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                ->size(300)
                ->errorCorrection('H')
                ->generate($url);
            
            file_put_contents($filePath, $qrCode);
            
            \Log::info('Table QR Code saved successfully', [
                'table_id' => $tableId,
                'path' => $filePath,
                'size' => filesize($filePath),
            ]);
            
        } catch (\Exception $generationError) {
            // Fallback to online API
            \Log::warning('Local QR generation failed for table, trying API', [
                'error' => $generationError->getMessage(),
            ]);
            
            $qrApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($url);
            $qrImage = @file_get_contents($qrApiUrl);
            
            if ($qrImage === false) {
                throw new \Exception('Both local and API QR code generation failed for table');
            }
            
            file_put_contents($filePath, $qrImage);
        }
        
        return "qr-codes/tables/{$filename}";
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get orders associated with this table
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'table_id');
    }

    /**
     * Get waiter assignments for this table
     */
    public function waiterAssignments()
    {
        return $this->hasMany(WaiterTableAssignment::class, 'table_id');
    }

    /**
     * Get active waiter assignments for this table
     */
    public function activeAssignments()
    {
        return $this->waiterAssignments()
            ->where('status', WaiterTableAssignment::STATUS_ACTIVE)
            ->whereDate('assignment_date', today());
    }

    /*
    |--------------------------------------------------------------------------
    | Status Helper Methods
    |--------------------------------------------------------------------------
    */

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE && $this->is_active;
    }

    public function isOccupied(): bool
    {
        return $this->status === self::STATUS_OCCUPIED;
    }

    public function isReserved(): bool
    {
        return $this->status === self::STATUS_RESERVED;
    }

    public function isInMaintenance(): bool
    {
        return $this->status === self::STATUS_CLEANING || $this->status === self::STATUS_OUT_OF_SERVICE;
    }

    /*
    |--------------------------------------------------------------------------
    | Utility Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get full QR code image URL
     */
    public function getQRCodeUrlAttribute()
    {
        if (!$this->qr_image_path) {
            return null;
        }
        return url("storage/{$this->qr_image_path}");
    }

    /**
     * Regenerate QR code for this table
     */
    public function regenerateQRCode($baseUrl = null): string
    {
        if (!$baseUrl) {
            $baseUrl = config('app.frontend_url', 'http://localhost:5173');
        }

        // Delete old QR code if exists
        if ($this->qr_image_path) {
            $oldPath = storage_path("app/public/{$this->qr_image_path}");
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }

        // Generate new QR code
        $newPath = self::generateTableQRCode(
            $this->id,
            $this->table_number,
            $this->qr_token,
            $baseUrl
        );

        $this->update([
            'qr_image_path' => $newPath,
            'qr_generated_at' => now(),
        ]);

        return $newPath;
    }

    /**
     * Scope to search tables by multiple criteria
     */
    public function scopeSearch($query, $searchTerm)
    {
        if (!$searchTerm) {
            return $query;
        }

        return $query->where(function ($q) use ($searchTerm) {
            $q->whereRaw('LOWER(table_number) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
              ->orWhereRaw('LOWER(table_name) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
              ->orWhereRaw('LOWER(location) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
              ->orWhereRaw('LOWER(status) LIKE ?', ['%' . strtolower($searchTerm) . '%']);
        });
    }

    /**
     * Scope for active tables only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for available tables (available status and active)
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE)
                     ->where('is_active', true);
    }
}
