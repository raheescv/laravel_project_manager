<?php

namespace Database\Seeders;

use App\Models\FamilyMember;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FamilyMemberSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('family_members')->truncate();

        $gf = FamilyMember::create(['name' => 'Mp Muhammed', 'gender' => 'male', 'date_of_birth' => '1952-08-22']);
        $gm = FamilyMember::create(['name' => 'Fathima', 'gender' => 'female', 'date_of_birth' => '1950-05-15']);
        $gf->addSpouse($gm);

        $thajunnisa = FamilyMember::create(['name' => 'Thajunnisa', 'gender' => 'female', 'date_of_birth' => '1975-03-10']);
        $gf->addChild($thajunnisa);
        $gm->addChild($thajunnisa);
        $shabeeba = FamilyMember::create(['name' => 'Shabeeba', 'gender' => 'female', 'date_of_birth' => '1975-03-10']);
        $gf->addChild($shabeeba);
        $gm->addChild($shabeeba);
        $mushthak = FamilyMember::create(['name' => 'Mushthak', 'gender' => 'male', 'date_of_birth' => '1975-03-10']);
        $gf->addChild($mushthak);
        $gm->addChild($mushthak);
        $shakeer = FamilyMember::create(['name' => 'Shakeer', 'gender' => 'male', 'date_of_birth' => '1975-03-10']);
        $gf->addChild($shakeer);
        $gm->addChild($shakeer);

        // Create spouses for Michael and Sarah
        $razak = FamilyMember::create(['name' => 'Razak', 'gender' => 'male', 'date_of_birth' => '1977-06-15']);
        $thajunnisa->addSpouse($razak);

        $rajina = FamilyMember::create(['name' => 'Rajina', 'gender' => 'female', 'date_of_birth' => '1977-06-15']);
        $razak->addChild($rajina);
        $thajunnisa->addChild($rajina);

        $rahees = FamilyMember::create(['name' => 'Rahees', 'gender' => 'male', 'date_of_birth' => '1977-06-15']);
        $razak->addChild($rahees);
        $thajunnisa->addChild($rahees);
    }
}
