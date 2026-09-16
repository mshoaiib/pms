<?php

namespace App\Models\Concerns;

use Illuminate\Support\Arr;

/**
 * MySQL stores a JSON column in its own normalised form, with object keys in a
 * different order than they were written in. Eloquent compares `array` casts
 * with `===`, which is order sensitive, so a re-saved JSON column looks dirty
 * on every write and the row is updated although nothing changed. Comparing
 * the decoded values with `==` ignores key order.
 */
trait ComparesJsonAttributes
{
    /**
     * @param  string  $key
     */
    public function originalIsEquivalent($key): bool
    {
        if ($this->hasCast($key, ['array', 'json']) && array_key_exists($key, $this->original)) {
            return $this->fromJson(Arr::get($this->attributes, $key))
                == $this->fromJson(Arr::get($this->original, $key));
        }

        return parent::originalIsEquivalent($key);
    }
}
