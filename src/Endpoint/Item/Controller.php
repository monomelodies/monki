<?php

namespace Monki\Endpoint\Item;

use PDO;
use PDOException;
use Dabble\Query\Where;

class Controller
{
    protected $adapter;
    protected $table;
    protected $item;

    public function __construct(PDO $adapter, $table, $item = null)
    {
        $this->adapter = $adapter;
        $this->table = $table;
        $this->item = $item;
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

    public function create(array $data)
    {
        $data = array_map([$this, 'normalize'], $data);
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
                $this->table,
                implode(', ', array_keys($data)),
                implode(', ', array_fill(0, count($data), '?'))
            ));
            $stmt->execute(array_values($data));
            return $this->adapter->lastInsertId();
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function replace(array $data)
    {
        $data = array_map([$this, 'normalize'], $data);
        foreach ($data as $key => &$item) {
            if (is_array($item)) {
                if (isset($item['id'])) {
                    $item = $item['id'];
                } else {
                    unset($data[$key]);
                }
            }
        }
        $where = new Where($data);
        try {
            $stmt = $this->adapter->prepare(sprintf(
                "DELETE FROM %s WHERE %s",
                $this->table,
                $where
            ));
            $stmt->execute($where->getBindings());
        } catch (PDOException $e) {
        }
        return $this->create($data);
    }

    public function update(array $data)
    {
        $data = array_map([$this, 'normalize'], $data);
        $fields = [];
        foreach ($data as $name => $value) {
            $fields[] = sprintf('%s = ?', $name);
        }
        try {
            $stmt = $this->adapter->prepare(sprintf(
                "UPDATE %s SET %s WHERE id = ?",
                $this->table,
                implode(', ', $fields)
            ));
            $stmt->execute(array_merge(
                array_values($data),
                [$this->item['id']]
            ));
            return $stmt->rowCount();
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function delete()
    {
        if (!isset($this->item)) {
            return 0;
        }
        try {
            $stmt = $this->adapter->prepare(sprintf(
                "DELETE FROM %s WHERE id = ?",
                $this->table
            ));
            $stmt->execute([$this->item['id']]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            return 0;
        }
    }
}

