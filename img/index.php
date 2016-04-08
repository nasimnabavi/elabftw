<?php

class Foo
{
    public function bar() {
        echo 'baz';
    }
}
/**
 * This file is here to mask the directory.
 */
class Bar
{
    /**
     * This file is here to mask the directory.
     */
    public function foo() {
        echo 'baz';
    }
}
