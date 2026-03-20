<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Record;
use App\Models\Template;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    private array $permissions = [
        'view_clients', 'create_clients', 'edit_clients', 'delete_clients',
        'view_records', 'create_records', 'edit_records', 'delete_records',
        'manage_templates', 'manage_users',
    ];

    public function run(): void
    {
        // 1. Permissions
        foreach ($this->permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'sanctum']);
        }

        // 2. Roles
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        $admin->syncPermissions($this->permissions);

        $clinician = Role::firstOrCreate(['name' => 'clinician', 'guard_name' => 'sanctum']);
        $clinician->syncPermissions([
            'view_clients', 'create_clients', 'edit_clients',
            'view_records', 'create_records',
        ]);

        $viewer = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'sanctum']);
        $viewer->syncPermissions(['view_clients', 'view_records']);

        // 3. Demo Tenant
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'demo-physio-clinic'],
            [
                'name'        => 'Demo Physio Clinic',
                'clinic_type' => 'physio',
                'is_active'   => true,
                'settings'    => [
                    'timezone'      => 'Africa/Lagos',
                    'date_format'   => 'DD/MM/YYYY',
                    'primary_color' => '#2563EB',
                ],
            ]
        );

        // 4. Users
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@demo.com'],
            ['tenant_id' => $tenant->id, 'name' => 'Admin User',
                'password' => Hash::make('password'), 'is_active' => true]
        );
        $adminUser->assignRole($admin);

        $clinicianUser = User::firstOrCreate(
            ['email' => 'clinician@demo.com'],
            ['tenant_id' => $tenant->id, 'name' => 'Dr. Jane Smith',
                'password' => Hash::make('password'), 'is_active' => true]
        );
        $clinicianUser->assignRole($clinician);

        // 5. Physiotherapy Assessment Template
        Template::firstOrCreate(
            ['tenant_id' => $tenant->id, 'key' => 'physio_assessment', 'version' => 1],
            [
                'name'        => 'Physiotherapy Assessment',
                'description' => 'Standard initial and follow-up physiotherapy assessment.',
                'is_active'   => true,
                'created_by'  => $adminUser->id,
                'schema'      => [
                    'fields' => [
                        ['name' => 'pain_level',      'label' => 'Pain Level (1–10)',         'type' => 'number',   'required' => true,  'validation' => ['min' => 1,  'max' => 10]],
                        ['name' => 'joint_tested',    'label' => 'Joint Tested',              'type' => 'select',   'required' => true,  'options' => ['Knee','Shoulder','Hip','Ankle','Elbow','Wrist','Spine']],
                        ['name' => 'range_of_motion', 'label' => 'Range of Motion (degrees)', 'type' => 'number',   'required' => false, 'validation' => ['min' => 0,  'max' => 360]],
                        ['name' => 'muscle_strength', 'label' => 'Muscle Strength (0–5 MRC)', 'type' => 'number',   'required' => false, 'validation' => ['min' => 0,  'max' => 5]],
                        ['name' => 'outcome',         'label' => 'Session Outcome',           'type' => 'select',   'required' => true,  'options' => ['improved','unchanged','worse']],
                        ['name' => 'exercises_given', 'label' => 'Exercises Prescribed',      'type' => 'textarea', 'required' => false],
                    ],
                ],
            ]
        );

        // 5b. Daily Living Log Template
        Template::firstOrCreate(
            ['tenant_id' => $tenant->id, 'key' => 'daily_living_log', 'version' => 1],
            [
                'name'        => 'Daily Living Log',
                'description' => 'Tracks ADLs, mood and medication adherence.',
                'is_active'   => true,
                'created_by'  => $adminUser->id,
                'schema'      => [
                    'fields' => [
                        ['name' => 'mood',              'label' => 'Mood',                   'type' => 'select',  'required' => true,  'options' => ['very_good','good','neutral','bad','very_bad']],
                        ['name' => 'medication_taken',  'label' => 'Medication Taken?',      'type' => 'boolean', 'required' => true],
                        ['name' => 'dietary_intake',    'label' => 'Dietary Intake Notes',   'type' => 'textarea','required' => false],
                        ['name' => 'mobility_score',    'label' => 'Mobility Score (1–5)',   'type' => 'number',  'required' => true,  'validation' => ['min' => 1, 'max' => 5]],
                    ],
                ],
            ]
        );

        // 6. Sample client + record
        $client = Client::firstOrCreate(
            ['email' => 'john.doe@example.com', 'tenant_id' => $tenant->id],
            ['tenant_id' => $tenant->id, 'first_name' => 'John', 'last_name' => 'Doe',
                'phone' => '+2348012345678', 'gender' => 'male', 'status' => 'active',
                'created_by' => $adminUser->id]
        );

        Record::firstOrCreate(
            ['client_id' => $client->id, 'template_key' => 'physio_assessment',
                'template_version' => 1, 'tenant_id' => $tenant->id],
            [
                'data' => [
                    'pain_level' => 6, 'joint_tested' => 'Knee',
                    'range_of_motion' => 95, 'muscle_strength' => 3,
                    'outcome' => 'improved',
                    'exercises_given' => 'Quad sets, SLR x 3 sets of 15 reps.',
                ],
                'notes'            => 'Patient reports improvement since last visit.',
                'status'           => 'submitted',
                'recorded_at'      => now(),
                'created_by'       => $clinicianUser->id,
            ]
        );

        $this->command->info('✅  Seeding complete.');
        $this->command->info('   Admin     → admin@demo.com / password');
        $this->command->info('   Clinician → clinician@demo.com / password');
    }
}
