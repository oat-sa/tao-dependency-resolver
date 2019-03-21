<?php

namespace OAT\DependencyResolver\Repository\Entity;

use PHPUnit\Framework\TestCase;

class RepositoryFileTest extends TestCase
{
    /** @var RepositoryFile */
    protected $subject;

    public function testConstructorWithDefaultValues()
    {
        $this->subject = new RepositoryFile();

        $this->assertEquals('', $this->subject->getName());
        $this->assertEquals('', $this->subject->getComposerName());
        $this->assertEquals('', $this->subject->getExtensionName());
        $this->assertEquals([], $this->subject->getRequires());
    }

    public function testConstructorAndAccessors()
    {
        $name = 'file name';
        $composerName = 'composer.json';
        $extensionName = 'nameOfTheExtension';
        $requires = ['extension2'];

        $this->subject = new RepositoryFile($name, $composerName, $extensionName, $requires);

        $this->assertEquals($name, $this->subject->getName());
        $this->assertEquals($composerName, $this->subject->getComposerName());
        $this->assertEquals($extensionName, $this->subject->getExtensionName());
        $this->assertEquals($requires, $this->subject->getRequires());
    }

    public function testConstructFromArray()
    {
        $name = 'file name';
        $composerName = 'composer.json';
        $extensionName = 'nameOfTheExtension';
        $requires = ['extension2'];
        $properties = [
            'name' => $name,
            'composerName' => $composerName,
            'extensionName' => $extensionName,
            'requires' => $requires,
        ];

        $this->subject = new RepositoryFile();
        $this->subject->constructFromArray($properties);
        $this->assertEquals($name, $this->subject->getName());
        $this->assertEquals($composerName, $this->subject->getComposerName());
        $this->assertEquals($extensionName, $this->subject->getExtensionName());
        $this->assertEquals($requires, $this->subject->getRequires());
    }

    public function testJsonSerialize()
    {
        $name = 'file name';
        $composerName = 'composer.json';
        $extensionName = 'nameOfTheExtension';
        $required = 'extension2';
        $requires = [$required];
        $expected = '{
    "name": "' . $name . '",
    "composerName": "' . $composerName . '",
    "extensionName": "' . $extensionName . '",
    "requires": [
        "' . $required . '"
    ]
}';

        $this->subject = new RepositoryFile($name, $composerName, $extensionName, $requires);
        $this->assertEquals($expected, json_encode($this->subject, JSON_PRETTY_PRINT));
    }

    public function testToCsv()
    {
        $name = 'file name';
        $composerName = 'composer.json';
        $extensionName = 'nameOfTheExtension';
        $required1 = 'extension1';
        $required2 = 'extension2';
        $requires = [$required1, $required2];

        $this->subject = new RepositoryFile($name, $composerName, $extensionName, $requires);

        $this->assertEquals(
            [$name, $composerName, $extensionName, $required1 . '|' . $required2],
            $this->subject->toCsv()
        );
    }
}
