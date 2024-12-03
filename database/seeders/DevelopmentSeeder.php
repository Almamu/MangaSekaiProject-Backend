<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Page;
use App\Models\Serie;
use App\Models\Staff;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DevelopmentSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CommonSeeder::class,
        ]);

        Serie::factory(10)->create();
        Staff::factory(10)->create();

        Serie::all()->each(function (Serie $serie) {
            Chapter::factory(10)->create([
                'serie_id' => $serie->id,
            ]);
        });

        Chapter::all()->each(function (Chapter $chapter) {
            Page::factory($chapter->pages_count)->create([
                'chapter_id' => $chapter->id,
                'mime_type' => 'image/jpeg',
            ]);
        });
    }
}
