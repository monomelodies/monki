<?php

namespace Monki\Endpoint\Browse;

use Disclosure\Injector;
use Disclosure\Container;
use Improse\Html;
use PDO;
use Exception;

class View extends Html
{
    use Injector;

    protected $adapter;
    protected $types = [];
    protected $id;
    protected $template = 'Monki/Endpoint/Browse/template.php';

    public function __construct($type, $id = null)
    {
        $this->inject(function (PDO $adapter) {});
        $this->id = $id;
        switch ($type) {
            case 'image':
                $this->types = ['image/jpeg', 'image/jpg', 'image/png'];
                break;
        }
    }

    public function __invoke(array $__viewdata = [])
    {
        // This is an exception:
        header("Content-type: text/html", true);
        try {
            $where = [];
            if ($this->types) {
                $where['mimetype'] = ['IN' => $this->types];
            }
            $medias = $this->adapter->fetchAll(
                'media',
                '*',
                $where,
                ['order' => 'originalname ASC']
            );
        } catch (SelectException $e) {
            $medias = [];
        }
        $id = $this->id;
        return parent::__invoke($__viewdata + compact('medias', 'id'));
    }
}

