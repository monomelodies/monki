<?php

class SimpleTest extends PHPUnit_Extensions_Database_TestCase
{
    private $pdo;
    private $conn;

    public function getConnection()
    {
        if (!isset($this->pdo)) {
            $this->pdo = new PDO('sqlite:memory:');
            $this->conn = $this->createDefaultDBConnection(
                $this->pdo,
                ':memory:'
            );
            $this->pdo->exec(file_get_contents(dirname(__FILE__).'/_files/schema.sql'));
        }
        return $this->conn;
    }

    public function getDataSet()
    {
        return $this->createXMLDataSet(dirname(__FILE__).'/_files/TwitterDemo.xml');
    }

    public function testUserFixture()
    {
        $this->assertEquals(2, $this->conn->getRowCount('twitter_user'));
    }

    public function testTweetFixture()
    {
        $this->assertEquals(3, $this->conn->getRowCount('twitter_tweet'));
    }

    public function testGenerateUserApi()
    {
        $this->expectOutputString('Testapi\Twitter\User');
        $generator = new Monki\Generator(
            'Testapi\Twitter\User',
            $this->pdo
        );
        echo $generator;
    }
}

