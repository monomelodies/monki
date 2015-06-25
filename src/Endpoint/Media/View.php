<?php

namespace Api\Media;

use Disclosure\Injector;
use Disclosure\Container;
use Improse\Json;
use Dabble\Query\SelectException;

class View extends Json
{
    use Injector;

    protected $adapter;
    protected $project;
    protected $position;

    public function __construct($project, $position)
    {
        $this->inject(function ($adapter) {});
        $this->project = $project;
        $this->position = $position;
    }

    public function __invoke(array $__viewdata = [])
    {
        try {
            $__viewdata += $this->adapter->fetch(
                'media m
                 JOIN project_media pm ON m.id = pm.media
                 JOIN project p ON p.id = pm.project',
                ['m.*', 'pm.position', 'p.slug', 'p.title'],
                ['p.slug' => $this->project, 'pm.position' => $this->position]
            );
        } catch (SelectException $e) {
        }
        return parent::__invoke($__viewdata);
    }
}

