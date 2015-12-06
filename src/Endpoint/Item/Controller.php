<?php

namespace Monki\Endpoint\Item;

use PDO;
use PDOException;
use Dabble\Query\Where;

/**
 * Controller to handle CRUD operations on items.
 */
class Controller
{
    /**
     * @var PDO $adapter
     * Database adapter to use.
     */
    protected $adapter;
    /**
     * @var string $table
     * The table to work on.
     */
    protected $table;
    /**
     * @var null|array $item
     * The item to work on, or null if we are creating a new one.
     */
    protected $item;

    /**
     * @param PDO $adapter
     * @param string $table
     * @param null|array $item
     */
    public function __construct(PDO $adapter, $table, $item = null)
    {
        $this->adapter = $adapter;
        $this->table = $table;
        $this->item = $item;
    }

    /**
     * Private helper to normalize a value (format dates, check null etc.).
     *
     * @param mixed $value The value to normalize.
     * @return mixed $value The normalized value.
     */
    private function normalize($value)
    {
        if (is_array($value)) {
            return array_map([$this, 'normalize'], $value);
        }
        if (!is_scalar($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return $value;
        }
        if (is_null($value) || !strlen($value)) {
            return null;
        }
        if (false !== ($time = strtotime($value))) {
            return date('Y-m-d H:i:s', $time);
        }
        return $value;
    }

    /**
     * Create a new entity in this table.
     *
     * @param array $data Hash of column/values to create with.
     * @return int The new item's serial id, or 0 on failure.
     */
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

    /**
     * Replace an entity in this table. Can be used to either insert or update
     * a record if for whatever reason you're unsure which one to use.
     *
     * @param array $data Hash of column/values to replace with.
     * @return int The new item's serial id, or 0 on failure.
     */
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

    /**
     * Update an entity in this table. The item _must_ contain an `"id"` key.
     *
     * @param array $data Hash of column/values to update with.
     * @return int The number of affected rows on success (hopefully "1"...) or
     *  0 on failure.
     */
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

    /**
     * Delete an entity in this table. The item _must_ contain an `"id"` key.
     *
     * @return int The number of affected rows on success (hopefully "1"...) or
     *  0 on failure.
     */
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

