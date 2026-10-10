<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('name');
            $t->unsignedInteger('price_mxn');
            $t->json('features')->nullable();
            $t->timestamps();
        });

        Schema::create('plants', function (Blueprint $t) {
            $t->id();
            $t->foreignId('plan_id')->constrained();
            $t->string('name');
            $t->string('address')->nullable();
            $t->unsignedInteger('max_capacity')->default(200);
            $t->timestamps();
        });

        Schema::create('areas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('plant_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('risk_level')->default('bajo');
            $t->timestamps();
        });

        Schema::table('users', function (Blueprint $t) {
            $t->string('role')->default('guardia')->after('email'); // guardia, anfitrion, seguridad, admin
            $t->string('phone')->nullable();
            $t->foreignId('plant_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::create('visitors', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('company')->nullable();
            $t->string('id_document')->unique();
            $t->string('phone')->nullable();
            $t->string('email')->nullable();
            $t->boolean('is_blocked')->default(false);
            $t->string('block_reason')->nullable();
            $t->timestamps();
        });

        Schema::create('visits', function (Blueprint $t) {
            $t->id();
            $t->foreignId('plant_id')->constrained();
            $t->foreignId('visitor_id')->constrained()->cascadeOnDelete();
            $t->foreignId('host_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $t->string('reason')->nullable();
            $t->string('type')->default('visita'); // visita, proveedor, contratista
            $t->dateTime('scheduled_for');
            $t->string('status')->default('preregistrada'); // preregistrada, aprobada, en_planta, finalizada, cancelada
            $t->string('qr_token')->unique();
            $t->timestamps();
            $t->index(['plant_id', 'scheduled_for']);
        });

        Schema::create('badges', function (Blueprint $t) {
            $t->id();
            $t->foreignId('plant_id')->constrained()->cascadeOnDelete();
            $t->string('number');
            $t->string('status')->default('disponible'); // disponible, asignado, perdido
            $t->timestamps();
            $t->unique(['plant_id', 'number']);
        });

        Schema::create('access_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $t->foreignId('plant_id')->constrained();
            $t->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('badge_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('guard_in_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('guard_out_id')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('checked_in_at');
            $t->dateTime('checked_out_at')->nullable();   // NULL = la persona sigue dentro
            $t->string('entry_photo_path')->nullable();
            $t->string('vehicle_plate')->nullable();
            $t->string('vehicle_model')->nullable();
            $t->string('method')->default('manual');     // manual, qr, torniquete
            $t->timestamps();
            $t->index(['plant_id', 'checked_out_at']);    // acelera "quién está dentro"
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_logs');
        Schema::dropIfExists('badges');
        Schema::dropIfExists('visits');
        Schema::dropIfExists('visitors');
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('plant_id');
            $t->dropColumn(['role', 'phone']);
        });
        Schema::dropIfExists('areas');
        Schema::dropIfExists('plants');
        Schema::dropIfExists('plans');
    }
};