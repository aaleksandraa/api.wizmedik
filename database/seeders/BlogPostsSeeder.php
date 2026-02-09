<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BlogPostsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔧 Seeding blog posts...');

        // First, ensure we have the "Zdravlje djece" category
        $category = DB::table('blog_categories')->where('slug', 'zdravlje-djece')->first();

        if (!$category) {
            $categoryId = DB::table('blog_categories')->insertGetId([
                'naziv' => 'Zdravlje djece',
                'slug' => 'zdravlje-djece',
                'opis' => 'Savjeti i informacije o zdravlju djece',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info('  ✓ Created category: Zdravlje djece');
        } else {
            $categoryId = $category->id;
            $this->command->info('  ✓ Using existing category: Zdravlje djece');
        }

        // Get first doctor for author
        $doctor = DB::table('doktori')->where('aktivan', true)->first();
        $doctorId = $doctor ? $doctor->id : null;

        // Check if posts already exist
        $existingPosts = DB::table('blog_posts')
            ->where('doktor_id', $doctorId)
            ->count();

        if ($existingPosts >= 5) {
            $this->command->info('  ℹ Blog posts already exist, skipping...');
            return;
        }

        $posts = [
            [
                'naslov' => 'Vakcinacija djece: Šta roditelji trebaju znati',
                'slug' => 'vakcinacija-djece-sta-roditelji-trebaju-znati',
                'excerpt' => 'Kompletni vodič kroz vakcinaciju djece, kalendar vakcinacija i najčešća pitanja roditelja.',
                'sadrzaj' => '<h2>Zašto je vakcinacija važna?</h2><p>Vakcinacija je jedan od najvažnijih načina zaštite djece od ozbiljnih bolesti.</p>',
                'thumbnail' => null,
                'doktor_id' => $doctorId,
                'status' => 'published',
                'featured' => false,
                'views' => rand(150, 500),
                'published_at' => now()->subDays(10),
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(10),
            ],
            [
                'naslov' => 'Ishrana beba: Od 0 do 12 mjeseci',
                'slug' => 'ishrana-beba-od-0-do-12-mjeseci',
                'excerpt' => 'Sve što trebate znati o ishrani bebe u prvoj godini života, od dojenja do uvođenja čvrste hrane.',
                'sadrzaj' => '<h2>Ishrana u prvoj godini života</h2><p>Prva godina života je kritična za razvoj djeteta.</p>',
                'thumbnail' => null,
                'doktor_id' => $doctorId,
                'status' => 'published',
                'featured' => false,
                'views' => rand(200, 600),
                'published_at' => now()->subDays(8),
                'created_at' => now()->subDays(8),
                'updated_at' => now()->subDays(8),
            ],
            [
                'naslov' => 'Prehlada kod djece: Simptomi i liječenje',
                'slug' => 'prehlada-kod-djece-simptomi-i-lijecenje',
                'excerpt' => 'Kako prepoznati prehladu kod djece, kada posjetiti ljekara i najbolji načini liječenja kod kuće.',
                'sadrzaj' => '<h2>Prehlada kod djece</h2><p>Prehlada je najčešća bolest kod djece.</p>',
                'thumbnail' => null,
                'doktor_id' => $doctorId,
                'status' => 'published',
                'featured' => false,
                'views' => rand(300, 700),
                'published_at' => now()->subDays(5),
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ],
            [
                'naslov' => 'Razvoj govora kod djece: Milestones i zabrinjavajući znaci',
                'slug' => 'razvoj-govora-kod-djece-milestones',
                'excerpt' => 'Vodič kroz normalan razvoj govora kod djece i kada potražiti pomoć logopeda.',
                'sadrzaj' => '<h2>Razvoj govora kod djece</h2><p>Razvoj govora je važan dio ukupnog razvoja djeteta.</p>',
                'thumbnail' => null,
                'doktor_id' => $doctorId,
                'status' => 'published',
                'featured' => false,
                'views' => rand(250, 550),
                'published_at' => now()->subDays(3),
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ],
            [
                'naslov' => 'San kod djece: Koliko sna je potrebno i kako uspostaviti rutinu',
                'slug' => 'san-kod-djece-koliko-sna-je-potrebno',
                'excerpt' => 'Vodič kroz potrebe za snom u različitim uzrastima i savjeti za uspostavljanje zdrave rutine spavanja.',
                'sadrzaj' => '<h2>Važnost sna za djecu</h2><p>San je kritičan za fizički i mentalni razvoj djeteta.</p>',
                'thumbnail' => null,
                'doktor_id' => $doctorId,
                'status' => 'published',
                'featured' => false,
                'views' => rand(180, 480),
                'published_at' => now()->subDays(1),
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],
        ];

        foreach ($posts as $post) {
            $postId = DB::table('blog_posts')->insertGetId($post);

            // Link post to category
            DB::table('blog_post_category')->insert([
                'blog_post_id' => $postId,
                'blog_category_id' => $categoryId,
            ]);
        }

        $this->command->info('  ✓ Created 5 blog posts in "Zdravlje djece" category');
        $this->command->info('✅ Blog posts seeding completed!');
    }
}
