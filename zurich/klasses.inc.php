<?php

/**
 * a csv row 
 */
class zh2013 {

//                var $department;
//                var $code;
//                var $dienstabteil;
//                var $konto;
//                var $bezeichnung;
//                var $rechnung_2013;
//                var $budget_2012;
//                var $budget_2013;
//                var $abweichung;
//                var $bezeichnung2;

    function __construct($row) {

        $fields = array("departement",
            "code",
            "dienstabteilung",
            "konto",
            "bezeichnung",
            "rechnung_2011",
            "budget_2012",
            "budget_2013",
            "abweichung",
            "bezeichnung2");

        foreach ($fields as $i => $var) {
            $this->$var = $row[$i];
        }

        if ($this->dienstabteilung == "Kultur") {
            $this->code = "1501";
        }

        if ($this->dienstabteilung == "Museum Rietberg") {
            $this->code = "1520";
        }
    }

    // ROOT
    function getId() {
        $id = intval($this->code);
        $id = intval($id / 100);
        return $id;
    }

    // LEVEL 1

    function getLevel2Id() {
        return $this->getId() . "_" . $this->code;
    }

    function isSum() {
        return $this->konto == "";
    }

    function isCost() {
        return $this->isSum() && $this->bezeichnung == "Aufwand";
    }

    function isExpense() {
        return $this->isSum() && $this->bezeichnung == "Ausgaben";
    }

    function isRevenue() {
        return $this->isSum() && trim($this->bezeichnung) == "Ertrag";
    }

    function isBalance() {
        return $this->isSum() && $this->bezeichnung == "Saldo (+ Aufwandüberschuss/- Ertragsüberschuss)";
    }

    function isRoot() {
        return $this->isCost() || $this->isRevenue() || $this->isBalance();
    }

    // LEVEL 2

    function hasKonto() {
        return $this->konto != "";
    }

    function getLevel3Id() {
        return $this->getLevel2Id() . "_" . $this->konto;
    }

}

class BudgetNode {

    var $level = 1;
    var $number;
    var $name;
//    var $net_cost = array("budgets" => array(), "accounts" => array());
//    var $subNodes = array();
    var $parent;
    var $gross_cost = array("budgets" => array());
    var $revenue = array("budgets" => array());
    var $agencies = array();
    var $product_groups = array();
    var $products = array();

}

class NewBudgetNode {

    var $id;
    var $name;
    var $gross_cost = array(
        "budgets" => array()
    );
    var $revenue = array(
        "budgets" => array()
    );
    var $children = array();

    function __construct($oldNode = null) {
        if ($oldNode != null) {
            $this->id = $oldNode->number;
            $this->name = $oldNode->name;
            $this->gross_cost = $oldNode->gross_cost;
            $this->revenue = $oldNode->revenue;
        }
    }

}

?>