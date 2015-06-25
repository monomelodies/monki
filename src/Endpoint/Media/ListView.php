<?php

namespace Api\Media;

use Disclosure\Injector;
use Disclosure\Container;
use Improse\Json;
use Dabble\Query\SelectException;

class ListView extends Json
{
    use Injector;

    protected $adapter;
    protected $project;

    public function __construct($project)
    {
        $this->inject(function ($adapter) {});
        $this->project = $project;
    }

    public function __invoke(array $__viewdata = [])
    {
        try {
            $__viewdata += $this->adapter->fetchAll(
                'media m
                 JOIN project_media pm ON m.id = pm.media
                 JOIN project p ON p.id = pm.project',
                ['m.*', 'pm.position'],
                ['p.slug' => $this->project],
                ['order' => 'position ASC']
            );
        } catch (SelectException $e) {
        }
        return parent::__invoke($__viewdata);
    }
}

