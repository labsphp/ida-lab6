<?php

/**
 * Created by PhpStorm.
 * User: Serhii
 * Date: 09.05.2018
 * Time: 23:28
 */
class Node
{
    private $value;
    private $children = [];

    function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @param array $children
     */
    public function setChildren(array $children)
    {
        $this->children = $children;
    }

    /**
     * Добавление значения
     * @param string $type
     * @param string $child
     */
    public function addChild(string $type, string $child)
    {
        $this->children[$type] = $child;
    }

    /**
     * Добавление нового узла
     * @param string $type
     * @param Node $child
     */
    public function addNode(string $type, Node $child)
    {
        $this->children[$type] = $child;
    }


    /**
     * @return array
     */
    public function getChildren(): array
    {
        return $this->children;
    }
}
