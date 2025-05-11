<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperSettings
 */
class Settings extends Model
{
    public $timestamps = false;

    protected $fillable = ['key', 'value'];

    protected $primaryKey = 'key';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // TODO: TYPECAST THIS TO INCLUDE UUID
            'value' => 'array',
        ];
    }

    /**
     * @return self Setting with all the scanner dirs available
     */
    public static function getScannerDirs(): self
    {
        return static::query()->firstOrCreate(['key' => 'scanner_dirs'], ['value' => []]);
    }

    /**
     * @param array<string, string> $config
     * @throws \Throwable
     */
    public static function addScannerDir(array $config): string
    {
        $uuid = uuid_create();

        throw_unless(is_string($uuid), new \Exception('Failed to generate UUID'));

        $setting = static::getScannerDirs();
        $setting->value = array_merge($setting->value, [['uuid' => $uuid, 'config' => $config]]);
        $setting->save();

        return $uuid;
    }
}
