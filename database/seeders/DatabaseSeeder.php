<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Page;
use App\Models\Serie;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'username' => 'admin',
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
            ]);
        });
    }
}
