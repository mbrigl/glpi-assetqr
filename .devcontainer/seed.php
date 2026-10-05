<?php

/**
 * Legt Beispieldaten (Computer inkl. Hersteller, Typen, Modelle, Status, Standorte) an.
 *
 * Aufruf: sudo -u www-data php /var/www/glpi/plugins/assetqr/.devcontainer/seed.php [anzahl]
 *
 * Idempotent: bereits vorhandene Computer (gleicher Name) werden übersprungen.
 * Mit --purge werden vorher alle Computer endgültig gelöscht.
 */

$glpi_dir = '/var/www/glpi';
$args     = array_slice($argv, 1);
$purge    = in_array('--purge', $args, true);
$numeric  = array_values(array_filter($args, 'is_numeric'));
$count    = max(1, (int) ($numeric[0] ?? 50));

require_once $glpi_dir . '/vendor/autoload.php';

$kernel = new \Glpi\Kernel\Kernel();
$kernel->boot();

// Als Super-Admin "glpi" arbeiten, damit Historie/Autor korrekt gesetzt werden
$_SESSION['glpiID']   = 2;
$_SESSION['glpiname'] = 'glpi';
$_SESSION['glpiactive_entity'] = 0;

$entities_id = 0;

if ($purge) {
    $computer = new Computer();
    $purged   = 0;
    foreach ($computer->find() as $row) {
        $computer->delete(['id' => $row['id']], true);
        $purged++;
    }
    echo "$purged Computer gelöscht.\n";
}

/** Dropdown-Eintrag holen oder anlegen und ID zurückgeben. */
function seed_dropdown(string $itemtype, string $name, array $extra = []): int
{
    $item  = new $itemtype();
    $input = ['name' => $name] + $extra;
    if ($item->getFromDBByCrit(['name' => $name])) {
        return (int) $item->getID();
    }
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
            'manufacturers_id'   => $manufacturers_id,
            'computermodels_id'  => seed_dropdown(ComputerModel::class, $model),
            'portable'           => (bool) preg_match('/Latitude|Book|ThinkPad/', $model),
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

// Alle Zufallswerte vorab erzeugen: GLPI nutzt mt_rand() intern (z. B. in add()),
// was sonst die Sequenz verschiebt und die Daten zwischen Läufen ändert.
mt_srand(42);
$rows = [];
for ($i = 1; $i <= $count; $i++) {
    $model  = $models[mt_rand(0, count($models) - 1)];
    $rows[] = [
        'name'              => sprintf('%s-%04d', $model['portable'] ? 'NB' : 'PC', $i),
        'entities_id'       => $entities_id,
        'serial'            => sprintf('SN%08X', mt_rand()),
        'otherserial'       => sprintf('INV-%05d', $i),
        'manufacturers_id'  => $model['manufacturers_id'],
        'computermodels_id' => $model['computermodels_id'],
        'computertypes_id'  => $model['portable'] ? $types['laptop'] : $types['desktop'],
        // Gewichtet: die meisten Geräte sind in Betrieb
        'states_id'         => $states[[0, 0, 0, 0, 0, 1, 1, 2, 3][mt_rand(0, 8)]],
        'locations_id'      => $locations[mt_rand(0, count($locations) - 1)],
    ];
}

$computer = new Computer();
$created  = 0;
foreach ($rows as $row) {
    if ($computer->getFromDBByCrit(['name' => $row['name'], 'entities_id' => $entities_id])) {
        continue;
    }
    $computer->add($row);
    $created++;
}

echo "Beispieldaten: $created Computer angelegt (" . ($count - $created) . " bereits vorhanden).\n";
