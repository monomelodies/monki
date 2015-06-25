<?php

namespace Monki\Endpoint\Item;

use PDO;
use PDOException;

class Controller
{
    protected $adapter;

    public function __construct(PDO $adapter)
    {
        $this->adapter = $adapter;
    }

    private function normalize($value)
    {
        if (is_array($value)) {
            return array_map([$this, 'normalize'], $value);
        }
        if (!is_scalar($value)) {
            return $value;
        }
        if (false !== ($time = strtotime($value))) {
            return date('Y-m-d H:i:s', $time);
        }
        return $value;
    }

    public function create($table)
    {
        $data = array_map([$this, 'normalize'], $_POST['data']);
        unset($data['id']); // never when creating...
        foreach ($data as $key => &$item) {
            if (is_array($item)) {
                if (isset($item['id'])) {
                    $item = $item['id'];
                } else {
                    unset($data[$key]);
                }
            }
        }
        try {
            $stmt = $this->adapter->prepare(sprintf(
                "INSERT INTO %s (%s) VALUES (%s)",
                $table,
                implode(', ', array_keys($data)),
                implode(', ', array_fill(0, count($data), '?'))
            ));
            $stmt->execute(array_values($data));
            return $stmt->rowCount();
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function update($table, $id)
    {
        $data = array_map([$this, 'normalize'], $_POST['data']);
        $fields = [];
        foreach ($data as $name => $value) {
            $fields[] = sprintf('%s = ?', $name);
        }
        try {
            $stmt = $this->adapter->prepare(sprintf(
                "UPDATE %s SET %s WHERE id = ?",
                $table,
                implode(', ', $fields)
            ));
            $stmt->execute(array_merge(array_values($data), [$id]));
            return $stmt->rowCount();
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function delete($table, $id)
    {
        try {
            $stmt = $this->adapter->prepare(sprintf(
                "SELECT 1 FROM %s WHERE id = ?",
                $table
            ));
            $stmt->execute([$id]);
            if (false !== $stmt->fetchColumn()) {
                $stmt = $this->adapter->prepare(sprintf(
                    "DELETE FROM %s WHERE id = ?",
                    $table
                ));
                $stmt->execute([$id]);
                return $stmt->rowCount();
            }
        } catch (PDOException $e) {
            return 0;
        }
    }
}

