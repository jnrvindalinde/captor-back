<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\CollectionItem;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\SiteGlobal;
use Illuminate\Database\Seeder;

class CmsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPartners();
        $this->seedDemoPage();
        $this->seedMenus();
        $this->seedGlobals();
        $this->seedHomeInjectionPages();
    }

    private function seedHomeInjectionPages(): void
    {
        // Two empty draft pages that the home page renders into. Admins can
        // publish them and add sections (announcements, promos, CTAs) without
        // touching code.
        foreach ([
            'home-after-hero'         => 'Home — after hero',
            'home-before-footer'      => 'Home — before footer',
            'services-before-footer'  => 'Services — before footer',
            'placements-before-footer'=> 'Placements — before footer',
            'stories-before-footer'   => 'Stories — before footer',
            'resources-before-footer' => 'Resources — before footer',
            'blog-before-footer'      => 'Blog — before footer',
            'contact-before-footer'   => 'Contact — before footer',
            'apply-before-footer'     => 'Apply — before footer',
        ] as $slug => $title) {
            Page::firstOrCreate(
                ['slug' => $slug],
                [
                    'kind'     => 'landing',
                    'status'   => Page::STATUS_DRAFT,
                    'title_en' => $title,
                    'title_fr' => $title,
                ],
            );
        }
    }

    private function seedGlobals(): void
    {
        $g = SiteGlobal::current();
        // Only seed defaults if the row was just created (empty contact_email is a good proxy).
        if ($g->contact_email) return;

        $g->fill([
            'company_name'        => 'Career360 Consult',
            'tagline_en'          => 'Helping students reach the right university — and stay there.',
            'tagline_fr'          => 'Aider les étudiants à intégrer la bonne université — et y rester.',
            'contact_email'       => 'hello@career360consult.com',
            'contact_phone'       => null,
            'socials'             => [
                'linkedin'  => null,
                'instagram' => null,
                'facebook'  => null,
                'twitter'   => null,
                'youtube'   => null,
            ],
            'footer_copyright_en' => '© Career360 Consult. All rights reserved.',
            'footer_copyright_fr' => '© Career360 Consult. Tous droits réservés.',
        ])->save();
    }

    private function seedMenus(): void
    {
        $menus = [
            ['slug' => 'primary',          'name' => 'Primary navigation',  'items' => [
                ['label_en' => 'Home',       'label_fr' => 'Accueil',     'href' => '/'],
                ['label_en' => 'Services',   'label_fr' => 'Services',    'href' => '/services'],
                ['label_en' => 'Stories',    'label_fr' => 'Témoignages', 'href' => '/stories'],
                ['label_en' => 'Placements', 'label_fr' => 'Placements',  'href' => '/placements'],
                ['label_en' => 'Resources',  'label_fr' => 'Ressources',  'href' => '/resources'],
                ['label_en' => 'Blog',       'label_fr' => 'Blog',        'href' => '/blog'],
                ['label_en' => 'Contact',    'label_fr' => 'Contact',     'href' => '/contact'],
            ]],
            ['slug' => 'footer-explore',   'name' => 'Footer — Explore', 'items' => [
                ['label_en' => 'Home',       'label_fr' => 'Accueil',     'href' => '/'],
                ['label_en' => 'Services',   'label_fr' => 'Services',    'href' => '/services'],
                ['label_en' => 'Placements', 'label_fr' => 'Placements',  'href' => '/placements'],
                ['label_en' => 'Stories',    'label_fr' => 'Témoignages', 'href' => '/stories'],
            ]],
            ['slug' => 'footer-resources', 'name' => 'Footer — Resources', 'items' => [
                ['label_en' => 'Guides',         'label_fr' => 'Guides',          'href' => '/resources'],
                ['label_en' => 'Blog',           'label_fr' => 'Blog',            'href' => '/blog'],
                ['label_en' => 'How it works',   'label_fr' => 'Comment ça marche','href' => '/#howitworks'],
            ]],
            ['slug' => 'footer-reach',     'name' => 'Footer — Reach us', 'items' => [
                ['label_en' => 'Start a conversation', 'label_fr' => 'Démarrer une discussion', 'href' => '/contact'],
                ['label_en' => 'hello@career360consult.com', 'label_fr' => 'hello@career360consult.com', 'href' => 'mailto:hello@career360consult.com'],
            ]],
        ];

        foreach ($menus as $m) {
            $menu = NavigationMenu::firstOrCreate(
                ['slug' => $m['slug']],
                ['name' => $m['name']],
            );

            if ($menu->items()->count() > 0) continue;

            foreach ($m['items'] as $i => $it) {
                NavigationItem::create([
                    'menu_id'    => $menu->id,
                    'label_en'   => $it['label_en'],
                    'label_fr'   => $it['label_fr'] ?? null,
                    'href'       => $it['href'],
                    'sort_order' => $i,
                    'visible'    => true,
                    'target'     => '_self',
                ]);
            }
        }
    }

    private function seedDemoPage(): void
    {
        $page = Page::firstOrCreate(
            ['slug' => 'cms-demo'],
            [
                'kind'         => 'landing',
                'status'       => Page::STATUS_PUBLISHED,
                'title_en'     => 'CMS demo',
                'title_fr'     => 'Demo CMS',
                'published_at' => now(),
            ],
        );

        if ($page->sections()->count() > 0) return;

        $sections = [
            ['type' => 'hero.centered', 'data' => [
                'eyebrow_en' => 'CMS pilot',
                'eyebrow_fr' => 'Pilote CMS',
                'title_en'   => 'This page was built with the CMS',
                'title_fr'   => 'Cette page a été créée avec le CMS',
                'subtitle_en'=> 'Edit any field in /admin/cms/pages and the live page updates within a revalidate cycle.',
                'subtitle_fr'=> 'Modifiez un champ dans /admin/cms/pages et la page se met à jour.',
            ]],
            ['type' => 'richtext', 'data' => [
                'body_en' => "We render typed sections in order. Add a hero, then rich text, then a CTA banner, then a marquee that reads from the partners collection. All editable, no code.",
                'body_fr' => "Nous rendons des sections typées en ordre. Ajoutez un hero, du texte riche, une bannière CTA, puis un marquee qui lit la collection partenaires.",
            ]],
            ['type' => 'marquee.logos', 'data' => [
                'collection_slug' => 'partners',
            ]],
            ['type' => 'cta.banner', 'data' => [
                'title_en'     => 'Ready to apply?',
                'title_fr'     => 'Prêt à postuler ?',
                'subtitle_en'  => 'Start your application in under five minutes.',
                'subtitle_fr'  => 'Commencez votre dossier en moins de cinq minutes.',
                'cta_label_en' => 'Apply now',
                'cta_label_fr' => 'Postuler',
                'cta_href'     => '/apply',
            ]],
        ];

        foreach ($sections as $i => $s) {
            PageSection::create([
                'page_id'  => $page->id,
                'type'     => $s['type'],
                'position' => $i,
                'status'   => 'published',
                'data'     => $s['data'],
            ]);
        }
    }

    private function seedPartners(): void
    {
        $collection = Collection::updateOrCreate(
            ['slug' => 'partners'],
            [
                'name'        => 'Partners',
                'description' => 'Logos / names shown in the home page partner marquee.',
                'schema'      => [
                    ['key' => 'name',     'type' => 'string', 'label' => 'Name',     'required' => true],
                    ['key' => 'logo_url', 'type' => 'string', 'label' => 'Logo URL', 'required' => false],
                    ['key' => 'href',     'type' => 'string', 'label' => 'Link URL', 'required' => false],
                ],
            ],
        );

        $names = [
            'Chevening',
            'Mastercard Foundation',
            'Commonwealth',
            'Fulbright',
            'DAAD',
            'British Council',
            'Erasmus+',
        ];

        // Idempotent: only seed if the collection is empty.
        if ($collection->items()->count() > 0) {
            return;
        }

        foreach ($names as $position => $name) {
            CollectionItem::create([
                'collection_id' => $collection->id,
                'position'      => $position,
                'status'        => CollectionItem::STATUS_PUBLISHED,
                'data'          => ['name' => $name, 'logo_url' => null, 'href' => null],
            ]);
        }
    }
}
