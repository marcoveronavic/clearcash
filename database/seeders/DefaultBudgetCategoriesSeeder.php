<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DefaultBudgetCategories;

class DefaultBudgetCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['slug' => 'rent',          'name' => 'Rent'],
            ['slug' => 'mortgage',      'name' => 'Mortgage'],
            ['slug' => 'car',           'name' => 'Car'],
            ['slug' => 'grocery',       'name' => 'Grocery'],
            ['slug' => 'travel',        'name' => 'Travel'],
            ['slug' => 'holiday',       'name' => 'Holiday'],
            ['slug' => 'family',        'name' => 'Family'],
            ['slug' => 'eating_out',    'name' => 'Eating out'],
            ['slug' => 'drinking_out',  'name' => 'Drinking out'],
            ['slug' => 'shopping',      'name' => 'Shopping'],
            ['slug' => 'utilities',     'name' => 'Utilities'],
            ['slug' => 'subscriptions', 'name' => 'Subscriptions'],
        ];

        foreach ($rows as $r) {
            DefaultBudgetCategories::updateOrCreate(
                ['slug' => $r['slug']],
                ['name' => $r['name']]
            );
        }
    }
}
