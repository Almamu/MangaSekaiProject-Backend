<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperJob
 */
class Job extends Model
{
    protected function casts(): array
    {
        return [
            'payload' => AsArrayObject::class,
        ];
    }

    /**
     * Obtains the name of the class that handles the job
     */
    public function getName(): string
    {
        return $this->payload->displayName ?? 'Unknown';
    }
}
