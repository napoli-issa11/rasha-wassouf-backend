<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Project;
use App\Models\HomeSlide;
use App\Models\Setting;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Main Admin User
        $this->call(AdminUserSeeder::class);

        // 2. Seed Initial 14 Projects (All initialized with 0 likes and 0 comments)
        $projects = [
            [
                'folder_name' => 'project 01',
                'title' => 'The Monolith Pavilion',
                'category' => 'Architecture',
                'description' => 'Sculpted concrete and bronze louvers framing panoramic natural surroundings with strict geometric purity and structural integrity.',
                'cover_image' => '/project 01/project-1-1.webp',
                'images' => ['/project 01/project-1-1.webp', '/project 01/project-1-2.webp'],
                'likes' => 0,
            ],
            [
                'folder_name' => 'project 02',
                'title' => 'Cliffside Stone Villa',
                'category' => 'Architecture',
                'description' => 'Dramatic cantilevered terraces embedded in rock foundations, blurring the line between raw topography and modern structural elegance.',
                'cover_image' => '/project 02/project-2-1.webp',
                'images' => ['/project 02/project-2-1.webp', '/project 02/project-2-2.webp'],
                'likes' => 0,
            ],
            [
                'folder_name' => 'project 03',
                'title' => 'Palazzo Di Luce',
                'category' => 'Residential',
                'description' => 'Double-height interior atrium, acoustic slatted timber ceilings, and warm evening illumination crafted for serene family living.',
                'cover_image' => '/project 03/project-3-4.webp',
                'images' => ['/project 03/project-3-1.webp', '/project 03/project-3-2.webp', '/project 03/project-3-3.webp', '/project 03/project-3-4.webp'],
                'likes' => 0,
            ],
            [
                'folder_name' => 'project 04',
                'title' => 'Skyline Glass Residence',
                'category' => 'Residential',
                'description' => 'Expansive floor-to-ceiling glass curtain walls providing 360-degree metropolitan vistas and tailored minimalist interior architecture.',
                'cover_image' => '/project 04/project-4-1.webp',
                'images' => ['/project 04/project-4-1.webp', '/project 04/project-4-2.webp', '/project 04/project-4-3.webp'],
                'likes' => 0,
            ],
            [
                'folder_name' => 'project 05',
                'title' => 'Golden Horizon Penthouse',
                'category' => 'Interior Design',
                'description' => 'Ultra-luxury penthouse featuring custom brass joinery, Italian Calacatta marble, and bespoke furniture crafted for discerning collectors.',
                'cover_image' => '/project 05/project-5-1.webp',
                'images' => ['/project 05/project-5-1.webp', '/project 05/project-5-2.webp', '/project 05/project-5-3.webp', '/project 05/project-5-4.webp', '/project 05/project-5-5.webp'],
                'likes' => 0,
            ],
            [
                'folder_name' => 'project 06',
                'title' => 'Sculpted Minimalist Loft',
                'category' => 'Interior Design',
                'description' => 'Fluted dark oak partitions, cast micro-cement flooring, and recessed architectural luminaires generating serene contemplative rhythm.',
                'cover_image' => '/project 06/project-6-1.webp',
                'images' => ['/project 06/project-6-1.webp', '/project 06/project-6-2.webp', '/project 06/project-6-3.webp', '/project 06/project-6-4.webp'],
                'likes' => 0,
            ],
            [
                'folder_name' => 'project 07',
                'title' => 'Courtyard Sanctuary Residence',
                'category' => 'Residential',
                'description' => 'Enclosed central reflecting pool, sliding Shoji-inspired glass partitions, and travertine stone corridors fostering calm privacy.',
                'cover_image' => '/project 07/project-7-2.webp',
                'images' => ['/project 07/project-7-1.webp', '/project 07/project-7-2.webp'],
                'likes' => 0,
            ],
            [
                'folder_name' => 'project 08',
                'title' => 'Noir Elegance Master Suite',
                'category' => 'Interior Design',
                'description' => 'Sophisticated moody palette featuring charcoal leather paneling, custom brushed gold hardware, and integrated acoustic dampening.',
                'cover_image' => '/project 08/project-8-2.webp',
                'images' => ['/project 08/project-8-1.webp', '/project 08/project-8-2.webp'],
                'likes' => 0,
            ],
            [
                'folder_name' => 'project 09',
                'title' => 'Vortex Corporate Headquarters',
                'category' => 'Commercial',
                'description' => 'Futuristic curved glass atrium and sustainable geothermal ventilation engineered for dynamic international creative teams.',
                'cover_image' => '/project 09/project-9-3.webp',
                'images' => ['/project 09/project-9-1.webp', '/project 09/project-9-2.webp', '/project 09/project-9-3.webp'],
                'likes' => 0,
            ],
            [
                'folder_name' => 'project 10',
                'title' => 'Oasis Dunes Private Estate',
                'category' => 'Architecture',
                'description' => 'Fluid rammed-earth contours integrating into desert dunes with passive shading louvers and subterranean thermal buffering.',
                'cover_image' => '/project 10/project-10-1.webp',
                'images' => ['/project 10/project-10-1.webp', '/project 10/project-10-2.webp'],
                'likes' => 0,
            ],
            [
                'folder_name' => 'project 11',
                'title' => 'Artisan Culinary Atelier',
                'category' => 'Commercial',
                'description' => 'Flagship restaurant interior featuring hand-hewn basalt stone counters, bronze mesh pendants, and dramatic ambient illumination.',
                'cover_image' => '/project 11/project-11-2.webp',
                'images' => ['/project 11/project-11-1.webp', '/project 11/project-11-2.webp', '/project 11/project-11-3.webp'],
                'likes' => 0,
            ],
            [
                'folder_name' => 'project 12',
                'title' => 'Nordic Pine Alpine Chalet',
                'category' => 'Residential',
                'description' => 'A-frame charred timber architecture with triple-glazed glass gables framing snow-covered pines and monumental stone fireplaces.',
                'cover_image' => '/project 12/project-12-2.webp',
                'images' => ['/project 12/project-12-1.webp', '/project 12/project-12-2.webp'],
                'likes' => 0,
            ],
            [
                'folder_name' => 'project 13',
                'title' => 'Azure Shoreline Villa',
                'category' => 'Architecture',
                'description' => 'Cascading infinity terraces hovering above turquoise waters, built with salt-resistant white render and frameless balustrades.',
                'cover_image' => '/project 13/project-13-3.webp',
                'images' => ['/project 13/project-13-1.webp', '/project 13/project-13-2.webp', '/project 13/project-13-3.webp'],
                'likes' => 0,
            ],
            [
                'folder_name' => 'project 14',
                'title' => 'Grand Opera Salon & Gallery',
                'category' => 'Interior Design',
                'description' => 'Curated exhibition salon with acoustically tuned coffered ceilings, Venetian plaster finishes, and motorized gallery track spotlights.',
                'cover_image' => '/project 14/project-14-1.webp',
                'images' => ['/project 14/project-14-1.webp', '/project 14/project-14-2.webp', '/project 14/project-14-3.webp'],
                'likes' => 0,
            ],
        ];

        foreach ($projects as $data) {
            Project::create($data);
        }

        // 3. Seed 11 Home Hero Slides
        $homeSlides = [
            [
                'image' => '/home-imgs/home1.webp',
                'subtitle' => 'MODERN ARCHITECTURE',
                'title' => 'Harmonious Spaces Defined by Bold Geometry',
                'description' => 'Transforming landscapes with monolithic elegance, sustainable structural integrity, and bespoke spatial harmony.',
                'sort_order' => 1,
            ],
            [
                'image' => '/home-imgs/project-14-1.webp',
                'subtitle' => 'BESPOKE INTERIOR DESIGN',
                'title' => 'Artisanal Elegance in Every Crafted Detail',
                'description' => 'Elevating everyday living through tailored palettes, refined acoustics, and sculpted natural materials.',
                'sort_order' => 2,
            ],
            [
                'image' => '/home-imgs/project-5-1.webp',
                'subtitle' => 'VILLA & RESIDENTIAL DESIGN',
                'title' => 'Sanctuaries of Light, Calm & Splendor',
                'description' => 'Seamlessly blurring the threshold between indoor luxury and breathtaking natural surroundings.',
                'sort_order' => 3,
            ],
            [
                'image' => '/home-imgs/project-9-3.webp',
                'subtitle' => 'COMMERCIAL & URBAN CONCEPTS',
                'title' => 'Visionary Architecture for Tomorrow’s Icons',
                'description' => 'Curating dynamic corporate and public environments designed to inspire innovation and human connection.',
                'sort_order' => 4,
            ],
            [
                'image' => '/home-imgs/project-10-1.webp',
                'subtitle' => 'CONTEMPORARY MASTERPLANNING',
                'title' => 'Sculptural Precision & Environmental Fluidity',
                'description' => 'Masterfully integrating organic textures, water features, and expansive living panoramas.',
                'sort_order' => 5,
            ],
            [
                'image' => '/home-imgs/project-8-2.webp',
                'subtitle' => 'HAUTE INTERIORS',
                'title' => 'Understated Opulence & Timeless Textures',
                'description' => 'Bespoke marble millwork and customized illumination designed for discerning lifestyles.',
                'sort_order' => 6,
            ],
            [
                'image' => '/home-imgs/project-3-4.webp',
                'subtitle' => 'PRIVATE ESTATES',
                'title' => 'Iconic Form Rooted in Landscape',
                'description' => 'Exquisite private compounds engineered with refined acoustic intimacy and structural mastery.',
                'sort_order' => 7,
            ],
            [
                'image' => '/home-imgs/project-12-2.webp',
                'subtitle' => 'INNOVATIVE DESIGN STUDIO',
                'title' => 'Tailored Concepts from Vision to Reality',
                'description' => 'Delivering turnkey architectural excellence with uncompromising attention to every facet.',
                'sort_order' => 8,
            ],
            [
                'image' => '/home-imgs/project-7-2.webp',
                'subtitle' => 'SPATIAL ARTISTRY',
                'title' => 'Refined Proportions, Pure Serenity',
                'description' => 'Where minimalist architecture converges with bespoke warmth and material richness.',
                'sort_order' => 9,
            ],
            [
                'image' => '/home-imgs/project-2-1.webp',
                'subtitle' => 'STRUCTURAL GRANDEUR',
                'title' => 'Timeless Monolithic Silhouettes',
                'description' => 'Crafting spaces that honor light, air, and the poetry of enduring geometry.',
                'sort_order' => 10,
            ],
            [
                'image' => '/home-imgs/project-13-3.webp',
                'subtitle' => 'LUXURY LIVING ENVIRONMENTS',
                'title' => 'Harmonious Indoor-Outdoor Balance',
                'description' => 'Framing breathtaking vistas with cantilevered terraces and seamless transitions.',
                'sort_order' => 11,
            ],
        ];

        foreach ($homeSlides as $slide) {
            HomeSlide::create($slide);
        }

        // 4. Seed About Page Statistics (Defaults: 14+, 120+, 18, 100%)
        $this->call(StatisticSeeder::class);

        // 5. Seed Global Social Media Settings
        Setting::firstOrCreate([], [
            'facebook_url' => 'https://facebook.com/rashawassouf.arch',
            'instagram_url' => 'https://instagram.com/rashawassouf',
            'linkedin_url' => 'https://linkedin.com/in/rashawassouf',
            'threads_url' => 'https://threads.net/@rashawassouf',
            'pinterest_url' => 'https://pinterest.com/rashawassouf',
        ]);
    }
}
