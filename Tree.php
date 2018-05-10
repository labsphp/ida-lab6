<?php

/**
 * Created by PhpStorm.
 * User: Serhii
 * Date: 09.05.2018
 * Time: 23:29
 */
class Tree
{
    private $root = null;

    /**
     * @param mixed $root
     */
    public function setRoot($root)
    {
        $this->root = $root;
    }

    /**
     * @return Node
     */
    public function getRoot(): ?Node
    {
        return $this->root;
    }
}