<?php

namespace App\Core;

trait IsDataClass
{
    public function __construct(array $data = [])
    {
        foreach (array_keys((array) $this) as $key) {
            if (array_key_exists($key, $data)) {
                $this->$key = $data[$key];
            }
        }
    }
}