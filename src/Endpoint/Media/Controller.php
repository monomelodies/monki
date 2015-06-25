<?php

namespace Api\Media;

use Api\Table\Item;
use Disclosure\Injector;
use Dabble\Query\SelectException;
use Dabble\Query\InsertException;
use Dabble\Query\UpdateException;
use Dabble\Query\DeleteException;

class Controller extends Item\Controller
{
    public function create($table)
    {
        $file = $_FILES['file'];
        $data = [
            'mimetype' => $file['type'],
            'originalname' => $file['name'],
            'filename' => $file['tmp_name'],
            'md5' => md5(file_get_contents($file['tmp_name'])),
        ];
        try {
            $id = $this->adapter->column(
                'media',
                'id',
                ['originalname' => $data['originalname']]
            );
            try {
                $this->adapter->update($table, $data, compact('id'));
            } catch (UpdateException $e) {
                return $id;
            }
        } catch (SelectException $e) {
            try {
                $this->adapter->insert($table, $data);
                $id = $this->adapter->lastInsertId();
            } catch (InsertException $e) {
                return;
            }
        }
        $parts = str_split($id, 3);
        $name = array_pop($parts);
        $target = dirname(__DIR__)."/../../assets";
        if ($parts) {
            $dir = implode('/', $parts);
            @mkdir("$target/$dir", 0755, true);
            $dir .= '/';
        } else {
            $dir = '';
        }
        $ext = substr($file['type'], strrpos($file['type'], '/') + 1);
        move_uploaded_file($file['tmp_name'], "$target/$dir$name.$ext");
        $this->adapter->update(
            $table,
            ['filename' => "$target/$dir$name.$ext"],
            compact('id')
        );
        return $id;
    }

    public function delete($table, $id)
    {
        $curr = $this->adapter->fetch($table, '*', compact('id'));
        @unlink(dirname(__DIR__)."/../../assets{$curr['actualname']}");
        parent::delete($table, $id);
    }
}

