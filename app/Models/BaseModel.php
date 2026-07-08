<?php

namespace App\Models;

use Maravel\Models\ModelBase;

abstract class BaseModel extends ModelBase
{
    public function getAttribute($key)
    {
        foreach ($this->enumCasts ?? [] as $info) {
            if (($info['additional_column_name'] ?? null) === $key) {
                $column = $info['colum_name'];
                $raw = parent::getAttribute($column);

                if ($raw !== null && $raw !== '') {
                    return $info['choices'][$raw] ?? $raw;
                }

                return null;
            }
        }

        return parent::getAttribute($key);
    }
}
