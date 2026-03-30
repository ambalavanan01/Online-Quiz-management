<?php
// includes/db.php

class JSONDB {
    private $directory;

    public function __construct() {
        $this->directory = __DIR__ . '/../data/';
        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0777, true);
        }
    }

    private function getFilePath($table) {
        return $this->directory . $table . '.json';
    }

    public function selectAll($table) {
        $file = $this->getFilePath($table);
        if (!file_exists($file)) {
            $this->saveAll($table, []);
            return [];
        }
        $contents = file_get_contents($file);
        return json_decode($contents, true) ?: [];
    }

    public function saveAll($table, $data) {
        $file = $this->getFilePath($table);
        $json = json_encode($data, JSON_PRETTY_PRINT);
        
        // Use file locking to prevent concurrency corruption
        $fp = fopen($file, 'c');
        if (flock($fp, LOCK_EX)) {
            ftruncate($fp, 0); // Clear file
            fwrite($fp, $json);
            fflush($fp);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    }

    public function insert($table, $record) {
        $data = $this->selectAll($table);
        $record['id'] = uniqid();
        $record['created_at'] = date('Y-m-d H:i:s');
        $data[] = $record;
        $this->saveAll($table, $data);
        return $record['id'];
    }

    public function update($table, $id, $newData) {
        $data = $this->selectAll($table);
        foreach ($data as &$record) {
            if ($record['id'] === $id) {
                $record = array_merge($record, $newData);
                $this->saveAll($table, $data);
                return true;
            }
        }
        return false;
    }

    public function delete($table, $id) {
        $data = $this->selectAll($table);
        $data = array_filter($data, function($record) use ($id) {
            return $record['id'] !== $id;
        });
        $this->saveAll($table, array_values($data)); // re-index
    }

    public function findById($table, $id) {
        $data = $this->selectAll($table);
        foreach ($data as $record) {
            if ($record['id'] === $id) return $record;
        }
        return null;
    }

    public function findWhere($table, $key, $value) {
        $data = $this->selectAll($table);
        $results = array_filter($data, function($record) use ($key, $value) {
            return isset($record[$key]) && $record[$key] === $value;
        });
        return array_values($results);
    }
}
$db = new JSONDB();
?>
