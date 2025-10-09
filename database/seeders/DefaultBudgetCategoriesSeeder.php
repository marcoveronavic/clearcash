<?php

namespace Database\Seeders;

use App\Models\DefaultBudgetCategories;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DefaultBudgetCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DefaultBudgetCategories::create([
            'name' => 'Mortgage',
        ]);
        DefaultBudgetCategories::create([
            'name' => 'Rent',
        ]);
        DefaultBudgetCategories::create([
            'name' => 'Utilities',
        ]);
        DefaultBudgetCategories::create([
           'name' => 'Groceries',
        ]);
        DefaultBudgetCategories::create([
            'name' => 'Loans'
        ]);
        DefaultBudgetCategories::create([
            'name' => 'Credit Card'
        ]);
        DefaultBudgetCategories::create([
            'name' => 'Transport'
        ]);
        DefaultBudgetCategories::create([
           'name' => 'Insurance'
        ]);
        DefaultBudgetCategories::create([
           'name' => 'Eating Out'
        ]);
        DefaultBudgetCategories::create([
            'name' => 'Entertainment'
        ]);
        DefaultBudgetCategories::create([
           'name' => 'Home & Family'
        ]);
        DefaultBudgetCategories::create([
           'name' => 'Shopping'
        ]);
        DefaultBudgetCategories::create([
           'name' => 'Gifts'
        ]);
        DefaultBudgetCategories::create([
           'name' => 'Education'
        ]);
        DefaultBudgetCategories::create([
            'name' => 'Charity'
        ]);
        DefaultBudgetCategories::create([
            'name' => 'Other'
        ]);
    }
}
