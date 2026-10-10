<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\QRCodeService;
use Illuminate\Support\Str;
use App\Models\Traits\BelongsToTenant;

class RestaurantTable extends Model
{
    use HasUuids, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'hotel_id',
        'table_number',
        'table_name',
        'capacity',
        'location',
        'section_id',
        'section',
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

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_OCCUPIED = 'occupied';
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_CLEANING = 'cleaning';
    public const STATUS_OUT_OF_SERVICE = 'out_of_service';

    protected static function booted()
    {
        static::creating(function ($table) {
            if (!$table->qr_token) {
                $table->qr_token = self::generateUniqueToken();
            }
        });

        static::created(function ($table) {
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

    public static function generateUniqueToken(): string
    {
        do {
            $token = strtoupper(Str::random(8));
        } while (self::where('qr_token', $token)->exists());

        return $token;
    }

    protected static function generateTableQRCode($tableId, $tableNumber, $qrToken, $baseUrl): string
    {
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

    public function orders()
    {
        return $this->hasMany(Order::class, 'table_id');
    }

    public function restaurantSection()
    {
        return $this->belongsTo(RestaurantSection::class, 'section_id');
    }

    public function sectionRel()
    {
        return $this->belongsTo(RestaurantSection::class, 'section_id');
    }

    public function getSectionNameAttribute(): string
    {
        return $this->restaurantSection?->name ?? $this->section ?? $this->location ?? 'Main Dining';
    }

    public function waiterAssignments()
    {
        return $this->hasMany(WaiterTableAssignment::class, 'table_id');
    }

    public function activeAssignments()
    {
        return $this->waiterAssignments()
            ->where('status', WaiterTableAssignment::STATUS_ACTIVE)
            ->whereDate('assignment_date', today());
    }

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

    public function getQRCodeUrlAttribute()
    {
        if (!$this->qr_image_path) {
            return null;
        }
        return url("storage/{$this->qr_image_path}");
    }

    public function regenerateQRCode($baseUrl = null): string
    {
        if (!$baseUrl) {
            $baseUrl = config('app.frontend_url', 'http://localhost:5173');
        }

        if ($this->qr_image_path) {
            $oldPath = storage_path("app/public/{$this->qr_image_path}");
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }

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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE)
                     ->where('is_active', true);
    }

    public static function seedDefaultTablesForHotel(?string $hotelId = null): void
    {
        $hotelIds = [];
        if ($hotelId) {
            $hotelIds[] = $hotelId;
        } else {
            $hotelIds = \App\Models\Hotel::pluck('id')->toArray();
        }

        if (empty($hotelIds)) {
            return;
        }

        $defaultTables = [

            ['table_number' => 'T01', 'table_name' => 'Window Table 1', 'capacity' => 2, 'location' => 'Main Dining'],
            ['table_number' => 'T02', 'table_name' => 'Window Table 2', 'capacity' => 2, 'location' => 'Main Dining'],
            ['table_number' => 'T03', 'table_name' => 'Corner Booth', 'capacity' => 4, 'location' => 'Main Dining'],
            ['table_number' => 'T04', 'table_name' => 'Center Table 1', 'capacity' => 4, 'location' => 'Main Dining'],
            ['table_number' => 'T05', 'table_name' => 'Center Table 2', 'capacity' => 4, 'location' => 'Main Dining'],
            ['table_number' => 'T06', 'table_name' => 'Large Family Table', 'capacity' => 6, 'location' => 'Main Dining'],
            ['table_number' => 'T07', 'table_name' => 'Main Dining Table 7', 'capacity' => 4, 'location' => 'Main Dining'],
            ['table_number' => 'T08', 'table_name' => 'Main Dining Table 8', 'capacity' => 4, 'location' => 'Main Dining'],

            ['table_number' => 'T09', 'table_name' => 'Terrace Table 1', 'capacity' => 2, 'location' => 'Terrace'],
            ['table_number' => 'T10', 'table_name' => 'Terrace Table 2', 'capacity' => 2, 'location' => 'Terrace'],
            ['table_number' => 'T11', 'table_name' => 'Terrace Booth', 'capacity' => 4, 'location' => 'Terrace'],
            ['table_number' => 'T12', 'table_name' => 'Terrace Large Table', 'capacity' => 6, 'location' => 'Terrace'],

            ['table_number' => 'V01', 'table_name' => 'VIP Private Room 1', 'capacity' => 8, 'location' => 'Private Dining'],
            ['table_number' => 'V02', 'table_name' => 'VIP Private Room 2', 'capacity' => 10, 'location' => 'Private Dining'],

            ['table_number' => 'B01', 'table_name' => 'Bar Table 1', 'capacity' => 2, 'location' => 'Bar'],
            ['table_number' => 'B02', 'table_name' => 'Bar Table 2', 'capacity' => 2, 'location' => 'Bar'],
            ['table_number' => 'B03', 'table_name' => 'Bar High Table', 'capacity' => 4, 'location' => 'Bar'],
        ];

        foreach ($hotelIds as $hId) {
            $hasTables = self::withoutTenant()->where('hotel_id', $hId)->exists();
            if (!$hasTables) {
                foreach ($defaultTables as $tableData) {
                    try {
                        self::withoutTenant()->create([
                            'hotel_id' => $hId,
                            'table_number' => $tableData['table_number'],
                            'table_name' => $tableData['table_name'],
                            'capacity' => $tableData['capacity'],
                            'location' => $tableData['location'],
                            'status' => self::STATUS_AVAILABLE,
                            'is_active' => true,
                        ]);
                    } catch (\Throwable $e) {
                        \Log::warning("seedDefaultTablesForHotel error for table {$tableData['table_number']}: " . $e->getMessage());
                    }
                }
            }
        }
    }
}

