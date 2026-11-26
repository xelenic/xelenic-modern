<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Clients' cloud services, resold from InterServer: a VPS, web hosting,
 * a domain or a Rapid Deploy dedicated server. Each starts as an order the
 * team approves; approving buys it on InterServer (paid from the prepay
 * balance), and from then on it mirrors the InterServer service.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cloud_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('product', 20);
            $table->string('status', 20)->default('requested');
            // Hostname or domain, and a one-line description of the plan.
            $table->string('name');
            $table->string('plan');
            // What the client chose, as InterServer's order fields.
            $table->json('config');
            // A root password the client chose, kept only until it's ordered.
            $table->text('secret')->nullable();

            // Billing: InterServer's cost and the client's price, per period.
            $table->string('currency', 3)->default('USD');
            $table->unsignedSmallInteger('period')->default(1);
            $table->decimal('cost', 10, 2);
            $table->decimal('price', 10, 2);
            $table->decimal('monthly_price', 10, 2);
            $table->json('quote')->nullable();

            // On InterServer, once ordered.
            $table->uuid('interserver_uuid')->nullable()->index();
            $table->json('interserver_invoices')->nullable();
            $table->json('remote')->nullable();
            $table->string('remote_status', 40)->nullable();
            $table->timestamp('next_due_at')->nullable();
            $table->timestamp('synced_at')->nullable();

            $table->text('note')->nullable();
            $table->text('failure')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('cancel_requested_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cloud_services');
    }
};
