<?php

namespace Monki\Source;

use PDOException;

class PDO
{
    private $pdo;
    private $table;

    public function __construct($name, \PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->table = $this->nameToTable($name);
    }

    private function nameToTable($name)
    {
        do {
            $table = str_replace('\\', '_', strtolower($name));
            try {
                $this->pdo->query("SELECT 1 FROM $table");
                break;
            } catch (PDOException $e) {
            }
            $name = substr($name, strpos($name, '\\') + 1);
            if (!strlen($name)) {
                throw new PDO\TableNotFoundException;
            }
        } while (true);
        return $table;
    }

    public function methodGet()
    {
        return <<<EOT
\$stmt = \$this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
\$stmt->execute([\$id]);
if (\$row = \$stmt->fetchObject()) {
    return \$row;
}
return null;
EOT;
    }

    public function methodPut()
    {
        return <<<EOT
\$params = [];
\$fields = [];
\$placeholders = [];
foreach (\$_POST as \$key => \$value) {
    \$fields[] = \$key;
    \$placeholders[] = '?';
    \$params[] = \$value;
}
\$stmt = \$this->pdo->prepare(sprintf(
    "INSERT INTO {$this->table} (%s) VALUES (%s)",
    implode(', ', \$fields),
    implode(', ', \$placeholders)
));
\$stmt->execute(\$params);
EOT;
    }

    public function methodPost()
    {
        return <<<EOT
\$params = [];
\$fields = [];
foreach (\$_POST as \$key => \$value) {
    \$fields[] = "\$key = ?";
    \$params[] = \$value;
}
\$params[] = \$id;
\$stmt = \$this->pdo->prepare(sprintf(
    "UPDATE {$this->table} SET %s WHERE id = ?",
    implode(', ', \$fields)
);
\$stmt->execute(\$params);
EOT;
    }

    public function methodDelete()
    {
        return <<<EOT
\$stmt = \$this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
\$stmt->execute([\$id]);
EOT;
    }
}

