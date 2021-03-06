<?php

/*
 * This file is part of Rocketeer
 *
 * (c) Maxime Fabre <ehtnam6@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Rocketeer\Services\Config;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Configuration extends Collection
{
    /**
     * @var array
     */
    protected $rootNodes = [
        'application_name',
        'plugins',
        'logs',
        'default',
        'connections',
        'on',
    ];

    /**
     * {@inheritdoc}
     */
    protected function getArrayableItems($items)
    {
        // Replace environment variables
        $items = parent::getArrayableItems($items);
        array_walk_recursive($items, function (&$value) {
            if (is_string($value) && strpos($value, '%%') === 0) {
                $value = getenv(substr($value, 2, -2));
            }
        });

        return $items;
    }

    /**
     * Get an item from the collection by key.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $key = $this->prefixKey($key);
        if ($value = Arr::get($this->items, $key, $default)) {
            return $value;
        }

        return value($default);
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function set($key, $value)
    {
        $key = $this->prefixKey($key);

        Arr::set($this->items, $key, $value);
    }

    /**
     * Replace the current configuration.
     *
     * @param array $items
     */
    public function replace(array $items)
    {
        $this->items = $this->getArrayableItems($items);
    }

    /**
     * @param string $key
     *
     * @return string
     */
    private function prefixKey($key)
    {
        return Str::startsWith($key, $this->rootNodes) ? 'config.'.$key : $key;
    }
}
