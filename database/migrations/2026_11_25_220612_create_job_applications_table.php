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
        // Applications sent from the Developer Careers page.
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->string('role', 80); // a slug from App\Support\Careers
            $table->string('role_title'); // as it was when they applied
            $table->string('name', 120);
            $table->string('email', 190);
            $table->string('phone', 40)->nullable();
            $table->string('location', 120)->nullable();
            $table->string('portfolio_url', 500)->nullable();
            $table->string('cv_path')->nullable(); // on the local (private) disk
            $table->string('cv_name')->nullable();
            $table->text('message')->nullable();
            $table->string('status', 20)->default('new');
            $table->text('notes')->nullable(); // the team's, never shown to the applicant
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
