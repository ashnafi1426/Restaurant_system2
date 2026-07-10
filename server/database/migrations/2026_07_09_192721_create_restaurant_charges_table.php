<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('restaurant_charges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('reservation_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignUuid('order_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();
            $table->decimal('amount', 10, 2);

            $table->string('description');
            $table->enum('status', [
                'unpaid',
                'paid',
                'cancelled',

            ])->default('unpaid');
            $table->timestamp('paid_at')->nullable();

            $table->string('payment_reference')->nullable();
            $table->timestamps();

            $table->softDeletes();
            $table->index('reservation_id');

            $table->index('status');

            $table->index('created_at');

        });
    }
    public function down(): void
    {
        Schema::dropIfExists('restaurant_charges');
    }
};