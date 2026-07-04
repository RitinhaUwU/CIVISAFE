<?php

namespace Database\Seeders;

use App\Enums\RolesEnum;
use App\Models\Donations\DistributionContent;
use App\Models\Donations\DonationAudit;
use App\Models\Donations\DonationContent;
use App\Models\Donations\DonationDistribution;
use App\Models\Donations\DonationLog;
use App\Models\Donations\DonationStock;
use App\Models\Entity;
use App\Models\Facility;
use App\Models\Incident;
use App\Models\IncidentParty;
use App\Models\IncidentPCO;
use App\Models\User;
use App\Models\Volunteer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([ProdSeeder::class]);

        User::factory()->create([
            'name' => 'Utilizador User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'locked' => false,
        ])->assignRole(RolesEnum::USER->value);

        User::factory(100)->create()->each(function ($user) {
            $roles = Role::all();

            $userRoles = $roles->filter(fn($role) => !str_starts_with($role->name, 'module_'))->values();
            $moduleAccess = $roles->filter(fn($role) => str_starts_with($role->name, 'module_'))->values();

            $userRole = $userRoles->random()->name;
            $user->assignRole($userRole);

            if (RolesEnum::from($userRole) === RolesEnum::USER) {
                $temp = $moduleAccess->random(random_int(1, 2));
                $user->assignRole($temp->pluck('name')->toArray());
            }

        });

        Entity::factory(30)->create();
        Incident::factory(600)->create();
        Volunteer::factory(100)->create();
        Facility::factory(30)->create();
        IncidentPCO::factory(60)->create();

        for ($i = 0; $i <= 100; $i++) {
            Incident::factory()->minor()->create([
                'incident_id' => Incident::where(['is_major' => true])->inRandomOrder()->first()->id,
            ]);
        }

        IncidentParty::factory(500)->create();

        DonationLog::factory(70)
            ->has(DonationContent::factory()
                ->afterMaking(function (DonationContent $donationContent) {
                    DonationStock::upsert(
                        [
                            'donation_goods_type_id' => $donationContent->donation_goods_types_id,
                            'stock' => $donationContent->quantity,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                        'donation_goods_type_id',
                        [
                            'stock' => DB::raw('"donation_stocks".stock + ' . (int)$donationContent->quantity),
                            'updated_at' => now()
                        ]
                    );
                })
                ->count(2)
            )
            ->create();

        DonationDistribution::factory(20)
            ->has(DistributionContent::factory()
                ->afterMaking(function (DistributionContent $distributionContent) {
                    DonationStock::where(['donation_goods_type_id' => $distributionContent->donation_goods_type_id])
                        ->lockForUpdate()
                        ->decrement('stock', $distributionContent->quantity);
                })
                ->count(2)
            )
            ->create();

        DonationAudit::factory(10)
            ->afterMaking(function (DonationAudit $donationAudit) {
                if($donationAudit->adjustment_type === "add")
                {
                    DonationStock::upsert(
                        [
                            'donation_goods_type_id' => $donationAudit->donation_goods_type_id,
                            'stock' => $donationAudit->quantity,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                        'donation_goods_type_id',
                        [
                            'stock' => DB::raw('"donation_stocks".stock + ' . (int)$donationAudit->quantity),
                            'updated_at' => now()
                        ]
                    );
                }
                else
                {
                    DonationStock::upsert(
                        [
                            'donation_goods_type_id' => $donationAudit->donation_goods_type_id,
                            'stock' => -$donationAudit->quantity,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                        'donation_goods_type_id',
                        [
                            'stock' => DB::raw('"donation_stocks".stock + ' . -(int)$donationAudit->quantity),
                            'updated_at' => now()
                        ]
                    );
                }
            });
    }
}
