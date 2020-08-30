<?php

use Vo\BaseComponent;

class HomePage extends BaseComponent
{
    public string $title = 'Wellcome to my awesome application\'s';
    public int $count = 0;
    public $messages = [];
    protected $any = 'Any\\\' var\\';
    private string $priv = 'Secret';
    public $json = ['Name' => 'My App'];
    function __construct()
    {
    }

    function Increment()
    {
        $this->count++;
        $this->json['Name'] = 'New name';
        $this->count++;
        $this->priv .= "Code";
        echo $this->count;
    }

    public function Test($argument): string
    {
        return 'Test ' . $argument;
    }

    public function GetCount(): int
    {
        return $this->count;
    }
}

$test = 'Test';
