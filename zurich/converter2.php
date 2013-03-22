<?PHP
error_reporting(E_ALL);

include 'functions.inc.php';
include 'klasses.inc.php';
?><!DOCTYPE html>
<html>
    <head>
        <title>zurich converter for the new data format</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    </head>
    <body>
        <pre><?php
$file = "B13_Institution_Konzernkonto.csv";
$has_title_row = true;

$json = array();
$newData = array(
    "meta" => array(
        "name" => "Stadt Zürich Budget 2013",
        "hierarchy" => array("Departement", "Dienstabteilung", "Konto")
    ),
    "nodes" => array()
);

if (($handle = fopen($file, "r")) !== FALSE) {
    $row = 0;
    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
        if ($row == 0 && $has_title_row) {
            $row++;
            continue;
        }
        $row++;

        $r = new zh2013($data);

        // LEVEL 1
        
        if ($r->isRoot()) {
            if (!array_key_exists($r->getId(), $json)) {
                $node = new BudgetNode();
                $node->name = $r->departement;
                $node->number = $r->getId();
                $json[$r->getId()] = $node;
            }
        }

        // LEVEL 2
        
        if (!array_key_exists($r->getLevel2Id(), $json)) {
            $node = new BudgetNode();
            $node->level = 2;
            $node->name = $r->dienstabteilung;
            $node->number = $r->getLevel2Id();
            $node->parent = $r->getId();
            $json[$r->getLevel2Id()] = $node;
        }
        if ($r->isCost()) {
//            echo "got cost for " . $r->getId() . " -> " . $r->getLevel2Id() . PHP_EOL;
//            echo "\t" . $r->budget_2012 . PHP_EOL;
//            echo "\t" . $r->budget_2013 . PHP_EOL;
            addAmount($node, '2012', $r->budget_2012);
            addAmount($node, '2013', $r->budget_2012);
        }
        if ($r->isExpense()) {
            // hack when there is no Aufwand
            @ $node->gross_cost['budgets']['2012'] += 0;
            @ $node->gross_cost['budgets']['2013'] += 0;
            @ $node->revenue['budgets']['2012'] += 0;
            @ $node->revenue['budgets']['2013'] += 0;
        }
        if ($r->isRevenue()) {
//            echo "got revenue for " . $r->getId() . " -> " . $r->getLevel2Id() . PHP_EOL;
//            echo "\t" . $r->budget_2012 . PHP_EOL;
//            echo "\t" . $r->budget_2013 . PHP_EOL;
            addAmount($node, '2012', $r->budget_2012);
            addAmount($node, '2013', $r->budget_2012);
        }
        
        // LEVEL 3

        if ($r->hasKonto()) {
            $node = new BudgetNode();
            $node->level = 3;
            $node->name = $r->bezeichnung;
            $node->number = $r->getLevel3Id();
            $node->parent = $r->getLevel2Id();
            addAmount($node, '2012', $r->budget_2012);
            addAmount($node, '2013', $r->budget_2013);
            $json[$r->getLevel3Id()] = $node;
        }
    }
    fclose($handle);
}


$zhData = array();

foreach ($json as $node) {
    if ($node->level == 1) {
//        echo "1\t" . $node->name . PHP_EOL;
        $zhData[$node->number] = $node;
    }
    if ($node->level == 2) {
        $parent = $zhData[$node->parent];
        $parent->agencies[$node->number] = $node;
    }
    if ($node->level == 3) {
        $level2 = $json[$node->parent];
        $level1 = $json[$level2->parent];
        $l2node = $level1->agencies[$level2->number];
        $l2node->product_groups[$node->number] = $node;
    }
}

// aggregate root nodes

foreach ($zhData as $node) {
    $sum_12 = 0;
    $sum_13 = 0;
    $rev_12 = 0;
    $rev_13 = 0;
    foreach ($node->agencies as $subNode) {
        $sum_12 += $subNode->gross_cost['budgets']['2012'];
        $sum_13 += $subNode->gross_cost['budgets']['2013'];
        $rev_12 += $subNode->revenue['budgets']['2012'];
        $rev_13 += $subNode->revenue['budgets']['2013'];
    }
    $node->gross_cost['budgets']['2012'] = $sum_12;
    $node->gross_cost['budgets']['2013'] = $sum_13;
    $node->revenue['budgets']['2012'] = $rev_12;
    $node->revenue['budgets']['2013'] = $rev_13;
}





// echo indent(json_encode($json));
// echo indent(json_encode($zhData));

// convert to new format


foreach($json as $l1) {
    $newL1 = new NewBudgetNode($l1);
    foreach($l1->agencies as $l2) {
        $newL2 = new NewBudgetNode($l2);
        $newL1->children[] = $newL2;
        foreach($l2->product_groups as $l3) {
            $newL3 = new NewBudgetNode($l3);
            $newL2->children[] = $newL3;
        }
    }
    $newData['nodes'][] = $newL1;
}

echo indent(json_encode($newData));


?></pre>
    </body>
</html>
