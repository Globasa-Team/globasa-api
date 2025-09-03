<?php

namespace WorldlangDict\API;

class Dictionary_log
{

    private $db;
    private $db_prefix;

    public function __construct($c)
    {
        $this->db = new \PDO($c['db_dsn'], $c['db_user'], $c['db_pass']);
        $this->db_prefix = $c['db_prefix'];
    }

    public function add($changes)
    {

        if ($changes == null) return;
        $q = $this->db->prepare("INSERT INTO `{$this->db_prefix}term_log` (`type`, `term`, `field`, `message`, `timestamp`) values (:change_type, :term, :field, :msg, :change_date);");
        $midnight = (new \DateTime('midnight -1 µs'))->format("Y-m-d H:i:s");

        foreach ($changes as $change) {
            $q->bindParam(':change_type', $change['type'], \PDO::PARAM_STR);
            $q->bindParam(':term', $change['term'], \PDO::PARAM_STR);
            $q->bindParam(':field', $change['field'], \PDO::PARAM_STR);
            $q->bindParam(':msg', $change['message'], \PDO::PARAM_STR);
            $q->bindParam(':change_date', $midnight, \PDO::PARAM_STR_CHAR);
            $result = $q->execute(); // this takes an array!

            if (!$result) {
                echo "\nError\n";
                var_dump($q->errorCode());
                var_dump($q->errorInfo());
            }
        }
    }
}
