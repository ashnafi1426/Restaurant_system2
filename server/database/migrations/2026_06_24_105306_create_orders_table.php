<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();

      $table->string('order_number')->unique();

$table->foreignUuid('reservation_id')->constrained();

$table->foreignUuid('guest_id')->constrained();

$table->foreignUuid('room_id')->constrained();

$table->timestamp('order_time')->useCurrent();

$table->enum('status',[
    'pending',
    'preparing',
    'ready',
    'served',
    'cancelled'
])->default('pending');

$table->decimal('subtotal',10,2)->default(0);

$table->decimal('tax',10,2)->default(0);

$table->decimal('discount',10,2)->default(0);

$table->decimal('total',10,2)->default(0);

$table->enum('payment_type',[
    'room_charge',
    'cash',
    'card'
])->default('room_charge');

$table->text('notes')->nullable();

$table->timestamp('served_at')->nullable();

$table->timestamp('cancelled_at')->nullable();

$table->timestamps();

$table->softDeletes();

$table->index('reservation_id');

$table->index('guest_id');

$table->index('room_id');

$table->index('status');

$table->index('order_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
