<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Badge;
use App\Models\Plan;
use App\Models\Plant;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use App\Services\AccessService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(AccessService $access): void
    {
        // Planes (MXN / mes)
        Plan::insert([
            ['slug' => 'basico', 'name' => 'Básico', 'price_mxn' => 3500, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'estandar', 'name' => 'Estándar', 'price_mxn' => 7500, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'enterprise', 'name' => 'Enterprise', 'price_mxn' => 14000, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $plant = Plant::create([
            'plan_id' => Plan::where('slug', 'estandar')->value('id'),
            'name' => 'Industrias del Bajío - Planta Querétaro',
            'address' => 'Querétaro, Qro.',
            'max_capacity' => 120,
        ]);

        $areas = collect(['Producción', 'Almacén', 'Oficinas', 'Mantenimiento', 'Comedor'])
            ->map(fn ($n) => Area::create(['plant_id' => $plant->id, 'name' => $n]));

        foreach (range(1, 20) as $i) {
            Badge::create(['plant_id' => $plant->id, 'number' => sprintf('V-%02d', $i)]);
        }

        // Usuarios (contraseña: password)
        $users = [
            ['Guardia Caseta', 'guardia@bajio.test', 'guardia'],
            ['Ana Torres (Anfitriona)', 'anfitrion@bajio.test', 'anfitrion'],
            ['Luis Ramírez (Seguridad)', 'seguridad@bajio.test', 'seguridad'],
            ['Admin Bajío', 'admin@bajio.test', 'admin'],
        ];
        foreach ($users as [$name, $email, $role]) {
            $user = User::create([
                'name' => $name, 'email' => $email, 'role' => $role,
                'plant_id' => $plant->id, 'password' => 'password',
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $guard = User::where('role', 'guardia')->first();
        $host = User::where('role', 'anfitrion')->first();

        // Visitas esperadas hoy
        $data = [
            ['Carlos Mendoza', 'Aceros del Centro', 'INE-1001', 'proveedor'],
            ['Laura Ríos', 'Seguros Atlas', 'INE-1002', 'visita'],
            ['Jorge Salinas', 'Mantenimiento Industrial MX', 'INE-1003', 'contratista'],
            ['Marta Vega', 'Logística Express', 'INE-1004', 'proveedor'],
            ['Pedro Núñez', 'Autopartes del Norte', 'INE-1005', 'visita'],
        ];
        $visits = [];
        foreach ($data as $i => [$name, $company, $doc, $type]) {
            $visitor = Visitor::create(['name' => $name, 'company' => $company, 'id_document' => $doc]);
            $visits[] = Visit::create([
                'plant_id' => $plant->id, 'visitor_id' => $visitor->id, 'host_id' => $host->id,
                'area_id' => $areas[$i % $areas->count()]->id, 'type' => $type,
                'reason' => 'Reunión programada', 'scheduled_for' => now()->setTime(9 + $i, 0),
                'status' => 'preregistrada', 'qr_token' => Str::random(40),
            ]);
        }

        // Persona bloqueada, para demostrar la alerta
        $blocked = Visitor::create([
            'name' => 'Ricardo Bloqueado', 'company' => 'Sin empresa', 'id_document' => 'INE-9999',
            'is_blocked' => true, 'block_reason' => 'Incidente previo de seguridad',
        ]);
        Visit::create([
            'plant_id' => $plant->id, 'visitor_id' => $blocked->id, 'host_id' => $host->id,
            'type' => 'visita', 'scheduled_for' => now()->setTime(14, 0),
            'status' => 'preregistrada', 'qr_token' => Str::random(40),
        ]);

        // Dos personas ya dentro de la planta
        $access->checkInVisit($visits[0], ['vehicle_plate' => 'QRO-123-A'], $guard);
        $access->checkInVisit($visits[2], [], $guard);
    }
}
