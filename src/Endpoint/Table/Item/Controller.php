<?php

namespace Api\Table\Item;

use Disclosure\Injector;
use Dabble\Query\SelectException;
use Dabble\Query\InsertException;
use Dabble\Query\UpdateException;
use Dabble\Query\DeleteException;

class Controller
{
    use Injector;

    public function __construct()
    {
        $this->inject(function ($adapter) {});
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
            $this->adapter->insert($table, $data);
        } catch (InsertException $e) {
        }
    }

    public function update($table, $id)
    {
        $data = array_map([$this, 'normalize'], $_POST['data']);
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
            $this->adapter->update($table, $data, compact('id'));
        } catch (UpdateException $e) {
        }
    }

    public function delete($table, $id)
    {
        try {
            $item = $this->adapter->fetch($table, '*', compact('id'));
            $this->adapter->delete($table, compact('id'));
            if ($table == 'media') {
                @unlink(sprintf(
                    '%s/../../../assets/%s',
                    realpath(__DIR__),
                    $item['actualname']
                ));
            }
        } catch (DeleteException $e) {
        } catch (SelectException $e) {
        }
    }
}

