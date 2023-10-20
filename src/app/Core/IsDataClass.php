<?php

namespace App\Core;

trait IsDataClass
{
    public function __construct(array $data = [])
    {
        $array = $data;

        if (method_exists($this, 'transformData')) {
            $array = $this->transformData($array);
        }

        foreach (array_keys($array) as $key) {
            if (array_key_exists($key, $array)) {
                $this->$key = $array[$key];
            }
        }
    }
}