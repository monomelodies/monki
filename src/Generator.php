<?php

namespace Monki;

use PDO;

class Generator
{
    private $name;
    private $source;
    private $methods = [];

    public function __construct($name, $source)
    {
        $this->name = $name;
        $this->methods = [
            'get' => new Generator\Method\Pub('get', ['$id']),
            'put' => new Generator\Method\Pub('put'),
            'post' => new Generator\Method\Pub('post', ['$id']),
            'delete' => new Generator\Method\Pub('delete', ['$id']),
        ];
        if ($source instanceof PDO) {
            $this->source = new Source\PDO($name, $source);
            foreach ($this->methods as $type => $method) {
                $fn = 'method'.ucfirst($type);
                $method->setBody($this->source->$fn());
            }
        }
    }

    public function __toString()
    {
        $out = <<<EOT
<?php

use Monki\Endpoint;

class {$this->name} extends Endpoint
{

EOT;
        $i = 0;
        foreach ($this->methods as $method) {
            $out .= ($i++ ? "\n" : '');
            $out .= $method->__toString();
        }
        $out .= "}\n";
        return $out;
    }
}

