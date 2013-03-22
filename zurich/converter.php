<?PHP
error_reporting(E_ALL);

include 'functions.inc.php';
include 'klasses.inc.php';
?><!DOCTYPE html>
<html>
    <head>
        <title>zurich converter</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    </head>
    <body>
        <pre><?php

function cleanAmount($in) {
    $out = str_replace("’", "", $in);
    if (!is_numeric($out)) {
        return 0;
    }
    return floatval($out);
}

function add(& $obj, $id, $key, $value) {
    if (!array_key_exists($id, $obj)) {
        $obj[$id] = array();
    }
    $obj[$id][$key] = $value;
}

$file = "B13_Institution_Konzernkonto.csv";
$has_title_row = true;

$nodes = array();
$json = array();

if (($handle = fopen($file, "r")) !== FALSE) {
    $row = 0;
    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
        if ($row == 0 && $has_title_row) {
            $row++;
            continue;
        }
        $num = count($data);
        // echo "<p> $num fields in line $row: <br /></p>\n";
        $row++;

        $r = new zh2013($data);

        if ($r->isRoot()) {
            if (!array_key_exists($r->getId(), $json)) {
                $node = new BudgetNode();
                $node->name = $r->departement;
                $node->number = $r->getId();
                $json[$r->getId()] = $node;
            }
        }

        if (!array_key_exists($r->getLevel2Id(), $json)) {
            $node = new BudgetNode();
            $node->level = 2;
            $node->name = $r->dienstabteilung;
            $node->number = $r->getLevel2Id();
            $node->parent = $r->getId();
            if ($r->isCost()) {
//                echo "got cost for " . $r->getId() . " -> " . $r->getLevel2Id() . PHP_EOL;
                $node->gross_cost['budgets']['2012'] = cleanAmount($r->budget_2012);
                $node->gross_cost['budgets']['2013'] = cleanAmount($r->budget_2013);
            }
            if ($r->isExpense()) {
                // hack when there is no Aufwand
                @ $node->gross_cost['budgets']['2012'] += 0;
                @ $node->gross_cost['budgets']['2013'] += 0;
            }
            $json[$r->getLevel2Id()] = $node;
        }

        if ($r->hasKonto()) {
            $node = new BudgetNode();
            $node->level = 3;
            $node->name = $r->bezeichnung;
            $node->number = $r->getLevel3Id();
            $node->parent = $r->getLevel2Id();
            $node->gross_cost['budgets']['2012'] = cleanAmount($r->budget_2012);
            $node->gross_cost['budgets']['2013'] = cleanAmount($r->budget_2013);
            $json[$r->getLevel3Id()] = $node;
        }


//                    // var_dump($zh2013);
//                    // echo implode("\t", array($r->departement, $r->getId(), "\n"));
//                    $node_name = $r->departement;
//                    $nodes[$node_name] = true;
//
//                    add($json, $r->getId(), "number", $r->getId());
//                    add($json, $r->getId(), "name", $r->departement);
//                    if ($r->isCost()) {
//                        echo $r->departement . "\t" . $r->dienstabteilung . "\tBudget 2012: " . $r->budget_2012 . PHP_EOL;
//                        add($json[$r->getId()], "gross_costs", "budgets", array());
//                        $json[$r->getId()]['gross_costs']['budgets'][2012] = 0;
//                        
//                        add($json[$r->getId()], "gross_costs", "budgets", array());
//
//                    }
    }
    fclose($handle);
}


$zhData = array();

foreach ($json as $node) {
    if ($node->level == 1) {
        echo "1\t" . $node->name . PHP_EOL;
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
//                    $parent->product_groups[$node->number] = $node;
    }
}

// aggregate root nodes

foreach ($zhData as $node) {
    $sum_12 = 0;
    $sum_13 = 0;
    foreach ($node->agencies as $subNode) {
//        var_dump($subNode);
        $sum_12 += $subNode->gross_cost['budgets']['2012'];
        $sum_13 += $subNode->gross_cost['budgets']['2013'];
//        echo $subNode->number . "\t";
//        echo $subNode->gross_cost['budgets']['2012'] . " -> ";
//        echo $sum_12 . PHP_EOL;
//        $sum_13 += $subNode->gross_cost['budgets']['2013'];
    }
    $node->gross_cost['budgets']['2012'] = $sum_12;
    $node->gross_cost['budgets']['2013'] = $sum_13;
}



//            var_dump($nodes);
//
//            echo indent(json_encode($json));
            echo indent(json_encode($zhData));
?></pre>
    </body>
</html>
