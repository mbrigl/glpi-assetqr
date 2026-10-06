<?php

/**
 * Creates sample computers incl. manufacturers, types, models, states and locations.
 *
 * Usage: sudo -u www-data php .devcontainer/seed.php [count] [--purge]
 *
 * Idempotent: computers that already exist (same name) are skipped.
 * --purge permanently deletes all computers first.
 */

$args    = array_slice($argv, 1);
$purge   = in_array('--purge', $args, true);
$numeric = array_values(array_filter($args, 'is_numeric'));
$count   = max(1, (int) ($numeric[0] ?? 50));

require_once '/var/www/glpi/vendor/autoload.php';

(new \Glpi\Kernel\Kernel())->boot();

// Act as super-admin "glpi", so history and author are set correctly
$_SESSION['glpiID']            = 2;
$_SESSION['glpiname']          = 'glpi';
$_SESSION['glpiactive_entity'] = 0;

$computer = new Computer();

if ($purge) {
    $purged = 0;
    foreach ($computer->find() as $row) {
        $computer->delete(['id' => $row['id']], true);
        $purged++;
    }
    echo "$purged computers deleted.\n";
}

/** Returns the ID of a dropdown item, creating it if necessary. */
function seed_dropdown(string $itemtype, string $name, array $extra = []): int
{
    $item = new $itemtype();
    if ($item->getFromDBByCrit(['name' => $name])) {
        return (int) $item->getID();
    }
    $input = ['name' => $name] + $extra;
    if ($item->isEntityAssign()) {
        $input += ['entities_id' => 0, 'is_recursive' => 1];
    }
    return (int) $item->add($input);
}

$catalog = [
    'Dell'   => ['Latitude 5440', 'OptiPlex 7010', 'Precision 3660'],
    'HP'     => ['EliteBook 840 G10', 'ProDesk 400 G9', 'ZBook Firefly 14'],
    'Lenovo' => ['ThinkPad T14 Gen 4', 'ThinkCentre M70q', 'ThinkPad X1 Carbon'],
    'Apple'  => ['MacBook Pro 14"', 'Mac mini M2'],
];

$models = [];
foreach ($catalog as $manufacturer => $model_names) {
    $manufacturers_id = seed_dropdown(Manufacturer::class, $manufacturer);
    foreach ($model_names as $model) {
        $models[] = [
            'manufacturers_id'  => $manufacturers_id,
            'computermodels_id' => seed_dropdown(ComputerModel::class, $model),
            'portable'          => (bool) preg_match('/Latitude|Book|ThinkPad/', $model),
        ];
    }
}

$types = [
    'laptop'  => seed_dropdown(ComputerType::class, 'Laptop'),
    'desktop' => seed_dropdown(ComputerType::class, 'Desktop'),
];

$states = array_map(
    fn($name) => seed_dropdown(State::class, $name, ['is_visible_computer' => 1]),
    ['In Betrieb', 'Auf Lager', 'In Reparatur', 'Ausgemustert'],
);

$locations = array_map(
    fn($name) => seed_dropdown(Location::class, $name),
    ['Wien', 'Graz', 'Linz', 'Homeoffice'],
);

// Generate all random values up front: GLPI uses mt_rand() internally (e.g. in add()),
// which would otherwise shift the sequence and change the data between runs.
mt_srand(42);
$rows = [];
for ($i = 1; $i <= $count; $i++) {
    $model  = $models[mt_rand(0, count($models) - 1)];
    $rows[] = [
        'name'              => sprintf('%s-%04d', $model['portable'] ? 'NB' : 'PC', $i),
        'entities_id'       => 0,
        'serial'            => sprintf('SN%08X', mt_rand()),
        'otherserial'       => sprintf('INV-%05d', $i),
        'manufacturers_id'  => $model['manufacturers_id'],
        'computermodels_id' => $model['computermodels_id'],
        'computertypes_id'  => $model['portable'] ? $types['laptop'] : $types['desktop'],
        // Weighted: most devices are in use
        'states_id'         => $states[[0, 0, 0, 0, 0, 1, 1, 2, 3][mt_rand(0, 8)]],
        'locations_id'      => $locations[mt_rand(0, count($locations) - 1)],
    ];
}

$created = 0;
foreach ($rows as $row) {
    if ($computer->getFromDBByCrit(['name' => $row['name'], 'entities_id' => 0])) {
        continue;
    }
    $computer->add($row);
    $created++;
}

echo "Sample data: $created computers created (" . ($count - $created) . " already existed).\n";
