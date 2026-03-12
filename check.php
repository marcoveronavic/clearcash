<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$userId = 1;

$categories = [
    'stipendio'          => 'fa-solid fa-wallet',
    'affitto'            => 'fa-solid fa-house',
    'mutuo'              => 'fa-solid fa-building-columns',
    'bollette'           => 'fa-solid fa-bolt',
    'spesa_alimentare'   => 'fa-solid fa-cart-shopping',
    'ristoranti'         => 'fa-solid fa-utensils',
    'trasporti'          => 'fa-solid fa-bus',
    'carburante'         => 'fa-solid fa-gas-pump',
    'abbigliamento'      => 'fa-solid fa-shirt',
    'salute'             => 'fa-solid fa-heart-pulse',
    'farmacia'           => 'fa-solid fa-prescription-bottle-medical',
    'assicurazioni'      => 'fa-solid fa-shield-halved',
    'telefono_internet'  => 'fa-solid fa-wifi',
    'abbonamenti'        => 'fa-solid fa-rotate',
    'intrattenimento'    => 'fa-solid fa-film',
    'viaggi'             => 'fa-solid fa-plane',
    'istruzione'         => 'fa-solid fa-graduation-cap',
    'cura_personale'     => 'fa-solid fa-spa',
    'casa_manutenzione'  => 'fa-solid fa-screwdriver-wrench',
    'regali'             => 'fa-solid fa-gift',
    'donazioni'          => 'fa-solid fa-hand-holding-heart',
    'tasse'              => 'fa-solid fa-file-invoice-dollar',
    'risparmi'           => 'fa-solid fa-piggy-bank',
    'investimenti'       => 'fa-solid fa-chart-line',
    'animali_domestici'  => 'fa-solid fa-paw',
    'figli'              => 'fa-solid fa-baby',
    'sport_fitness'      => 'fa-solid fa-dumbbell',
    'altro'              => 'fa-solid fa-ellipsis',
    'non_categorizzato'  => 'fa-solid fa-question',
];

$created = 0;
foreach ($categories as $name => $icon) {
    $exists = DB::table('budget_categories')->where('name', $name)->where('user_id', $userId)->exists();
    if (!$exists) {
        DB::table('budget_categories')->insert([
            'name'       => $name,
            'icon'       => $icon,
            'user_id'    => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $created++;
        echo "Created: $name ($icon)\n";
    }
}

echo "\nDone! $created categories created.\n";
echo "Total: " . DB::table('budget_categories')->where('user_id', $userId)->count() . "\n";
