<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password'),
            'role' => 'superadmin',
        ]);

        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Kasir 1',
            'email' => 'kasir@example.com',
            'password' => bcrypt('password'),
            'role' => 'cashier',
        ]);

        // Create Raw Materials
        $gula = RawMaterial::create([
            'name' => 'Gula',
            'unit' => 'gram',
            'stock' => 50000, // 50kg = 50000gram
            'min_stock' => 5000,
        ]);

        $teh = RawMaterial::create([
            'name' => 'Teh',
            'unit' => 'ml',
            'stock' => 10000,
            'min_stock' => 1000,
        ]);

        $jeruk = RawMaterial::create([
            'name' => 'Jeruk',
            'unit' => 'ml',
            'stock' => 5000,
            'min_stock' => 500,
        ]);

        $air = RawMaterial::create([
            'name' => 'Air',
            'unit' => 'ml',
            'stock' => 100000,
            'min_stock' => 10000,
        ]);

        $es = RawMaterial::create([
            'name' => 'Es Batu',
            'unit' => 'gram',
            'stock' => 20000,
            'min_stock' => 2000,
        ]);

        $kopi = RawMaterial::create([
            'name' => 'Kopi',
            'unit' => 'gram',
            'stock' => 5000,
            'min_stock' => 500,
        ]);

        $susu = RawMaterial::create([
            'name' => 'Susu',
            'unit' => 'ml',
            'stock' => 10000,
            'min_stock' => 1000,
        ]);

        // Create Products
        $esTeh = Product::create([
            'name' => 'Es Teh',
            'description' => 'Teh manis dingin yang menyegarkan',
            'price' => 5000,
            'is_available' => true,
        ]);

        $esJeruk = Product::create([
            'name' => 'Es Jeruk',
            'description' => 'Jeruk peras segar dengan es',
            'price' => 7000,
            'is_available' => true,
        ]);

        $kopiHitam = Product::create([
            'name' => 'Kopi Hitam',
            'description' => 'Kopi hitam original tanpa gula',
            'price' => 8000,
            'is_available' => true,
        ]);

        $kopiSusu = Product::create([
            'name' => 'Kopi Susu',
            'description' => 'Kopi dengan susu creamy',
            'price' => 12000,
            'is_available' => true,
        ]);

        $tehTawar = Product::create([
            'name' => 'Teh Tawar Panas',
            'description' => 'Teh hangat tanpa gula',
            'price' => 3000,
            'is_available' => true,
        ]);

        // Create Recipes for Es Teh
        Recipe::create([
            'product_id' => $esTeh->id,
            'raw_material_id' => $teh->id,
            'quantity' => 200, // 200ml teh
        ]);

        Recipe::create([
            'product_id' => $esTeh->id,
            'raw_material_id' => $gula->id,
            'quantity' => 10, // 10 gram gula
        ]);

        Recipe::create([
            'product_id' => $esTeh->id,
            'raw_material_id' => $air->id,
            'quantity' => 100, // 100ml air
        ]);

        Recipe::create([
            'product_id' => $esTeh->id,
            'raw_material_id' => $es->id,
            'quantity' => 50, // 50 gram es
        ]);

        // Create Recipes for Es Jeruk
        Recipe::create([
            'product_id' => $esJeruk->id,
            'raw_material_id' => $jeruk->id,
            'quantity' => 150, // 150ml jeruk
        ]);

        Recipe::create([
            'product_id' => $esJeruk->id,
            'raw_material_id' => $gula->id,
            'quantity' => 15, // 15 gram gula
        ]);

        Recipe::create([
            'product_id' => $esJeruk->id,
            'raw_material_id' => $air->id,
            'quantity' => 100, // 100ml air
        ]);

        Recipe::create([
            'product_id' => $esJeruk->id,
            'raw_material_id' => $es->id,
            'quantity' => 50, // 50 gram es
        ]);

        // Create Recipes for Kopi Hitam
        Recipe::create([
            'product_id' => $kopiHitam->id,
            'raw_material_id' => $kopi->id,
            'quantity' => 15, // 15 gram kopi
        ]);

        Recipe::create([
            'product_id' => $kopiHitam->id,
            'raw_material_id' => $air->id,
            'quantity' => 200, // 200ml air
        ]);

        // Create Recipes for Kopi Susu
        Recipe::create([
            'product_id' => $kopiSusu->id,
            'raw_material_id' => $kopi->id,
            'quantity' => 15, // 15 gram kopi
        ]);

        Recipe::create([
            'product_id' => $kopiSusu->id,
            'raw_material_id' => $susu->id,
            'quantity' => 150, // 150ml susu
        ]);

        Recipe::create([
            'product_id' => $kopiSusu->id,
            'raw_material_id' => $gula->id,
            'quantity' => 10, // 10 gram gula
        ]);

        Recipe::create([
            'product_id' => $kopiSusu->id,
            'raw_material_id' => $air->id,
            'quantity' => 100, // 100ml air
        ]);

        // Create Recipes for Teh Tawar Panas
        Recipe::create([
            'product_id' => $tehTawar->id,
            'raw_material_id' => $teh->id,
            'quantity' => 200, // 200ml teh
        ]);

        Recipe::create([
            'product_id' => $tehTawar->id,
            'raw_material_id' => $air->id,
            'quantity' => 200, // 200ml air panas
        ]);
    }
}
