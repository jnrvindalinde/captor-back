<?php

namespace App\Cms;

/**
 * Single source of truth for CMS section types. Each entry describes:
 *  - label   : human-readable name shown in the admin
 *  - fields  : field list used both for admin form rendering and for backend
 *              validation (rules are generated below from the field list).
 *
 * Field shape:
 *   key       string  required
 *   type      string  string | text | richtext | url | image | bool | number | collection_ref
 *   label     string  optional
 *   required  bool    optional
 *   help      string  optional
 *   default   mixed   optional
 */
class SectionRegistry
{
    /**
     * @return array<string, array{label: string, fields: array<int, array<string, mixed>>}>
     */
    public static function all(): array
    {
        return [
            'hero.split' => [
                'label'  => 'Hero — split',
                'fields' => [
                    ['key' => 'eyebrow_en',   'type' => 'string', 'label' => 'Eyebrow (EN)'],
                    ['key' => 'eyebrow_fr',   'type' => 'string', 'label' => 'Eyebrow (FR)'],
                    ['key' => 'title_en',     'type' => 'string', 'label' => 'Title (EN)', 'required' => true],
                    ['key' => 'title_fr',     'type' => 'string', 'label' => 'Title (FR)'],
                    ['key' => 'subtitle_en',  'type' => 'text',   'label' => 'Subtitle (EN)'],
                    ['key' => 'subtitle_fr',  'type' => 'text',   'label' => 'Subtitle (FR)'],
                    ['key' => 'image_url',    'type' => 'url',    'label' => 'Image URL'],
                    ['key' => 'cta_label_en', 'type' => 'string', 'label' => 'CTA label (EN)'],
                    ['key' => 'cta_label_fr', 'type' => 'string', 'label' => 'CTA label (FR)'],
                    ['key' => 'cta_href',     'type' => 'string', 'label' => 'CTA link'],
                ],
            ],
            'hero.centered' => [
                'label'  => 'Hero — centered',
                'fields' => [
                    ['key' => 'eyebrow_en',  'type' => 'string', 'label' => 'Eyebrow (EN)'],
                    ['key' => 'eyebrow_fr',  'type' => 'string', 'label' => 'Eyebrow (FR)'],
                    ['key' => 'title_en',    'type' => 'string', 'label' => 'Title (EN)', 'required' => true],
                    ['key' => 'title_fr',    'type' => 'string', 'label' => 'Title (FR)'],
                    ['key' => 'subtitle_en', 'type' => 'text',   'label' => 'Subtitle (EN)'],
                    ['key' => 'subtitle_fr', 'type' => 'text',   'label' => 'Subtitle (FR)'],
                ],
            ],
            'richtext' => [
                'label'  => 'Rich text',
                'fields' => [
                    ['key' => 'body_en', 'type' => 'richtext', 'label' => 'Body (EN)', 'required' => true],
                    ['key' => 'body_fr', 'type' => 'richtext', 'label' => 'Body (FR)'],
                ],
            ],
            'cta.banner' => [
                'label'  => 'CTA banner',
                'fields' => [
                    ['key' => 'title_en',     'type' => 'string', 'label' => 'Title (EN)', 'required' => true],
                    ['key' => 'title_fr',     'type' => 'string', 'label' => 'Title (FR)'],
                    ['key' => 'subtitle_en',  'type' => 'text',   'label' => 'Subtitle (EN)'],
                    ['key' => 'subtitle_fr',  'type' => 'text',   'label' => 'Subtitle (FR)'],
                    ['key' => 'cta_label_en', 'type' => 'string', 'label' => 'CTA label (EN)', 'required' => true],
                    ['key' => 'cta_label_fr', 'type' => 'string', 'label' => 'CTA label (FR)'],
                    ['key' => 'cta_href',     'type' => 'string', 'label' => 'CTA link', 'required' => true],
                ],
            ],
            'marquee.logos' => [
                'label'  => 'Logo marquee',
                'fields' => [
                    ['key' => 'collection_slug', 'type' => 'string', 'label' => 'Collection slug', 'required' => true, 'default' => 'partners'],
                ],
            ],
            'steps.numbered' => [
                'label'  => 'Numbered steps',
                'fields' => [
                    ['key' => 'header_en', 'type' => 'string', 'label' => 'Section header (EN)'],
                    ['key' => 'header_fr', 'type' => 'string', 'label' => 'Section header (FR)'],
                    ['key' => 'items',     'type' => 'json',   'label' => 'Items (JSON array of {n, title_en, title_fr, copy_en, copy_fr})', 'required' => true],
                ],
            ],
            'reasons.grid' => [
                'label'  => 'Reasons grid',
                'fields' => [
                    ['key' => 'header_en', 'type' => 'string', 'label' => 'Section header (EN)'],
                    ['key' => 'header_fr', 'type' => 'string', 'label' => 'Section header (FR)'],
                    ['key' => 'items',     'type' => 'json',   'label' => 'Items (JSON array of {icon, title_en, title_fr, copy_en, copy_fr})', 'required' => true],
                ],
            ],
            'services.cards' => [
                'label'  => 'Services cards',
                'fields' => [
                    ['key' => 'header_en', 'type' => 'string', 'label' => 'Section header (EN)'],
                    ['key' => 'header_fr', 'type' => 'string', 'label' => 'Section header (FR)'],
                    ['key' => 'lede_en',   'type' => 'text',   'label' => 'Section lede (EN)'],
                    ['key' => 'lede_fr',   'type' => 'text',   'label' => 'Section lede (FR)'],
                    ['key' => 'items',     'type' => 'json',   'label' => 'Items (JSON array of {image_url, title_en, title_fr, blurb_en, blurb_fr, cta_label_en, cta_label_fr, cta_href})', 'required' => true],
                ],
            ],
            'stories.carousel' => [
                'label'  => 'Stories carousel',
                'fields' => [
                    ['key' => 'header_en', 'type' => 'string', 'label' => 'Section header (EN)'],
                    ['key' => 'header_fr', 'type' => 'string', 'label' => 'Section header (FR)'],
                    ['key' => 'items',     'type' => 'json',   'label' => 'Items (JSON array of {image_url, name, country, program, quote_en, quote_fr})', 'required' => true],
                ],
            ],
            'countries.tabs' => [
                'label'  => 'Countries tabs',
                'fields' => [
                    ['key' => 'header_en', 'type' => 'string', 'label' => 'Section header (EN)'],
                    ['key' => 'header_fr', 'type' => 'string', 'label' => 'Section header (FR)'],
                    ['key' => 'items',     'type' => 'json',   'label' => 'Items (JSON array of {flag, country_en, country_fr, blurb_en, blurb_fr, programs:[{name, level}]})', 'required' => true],
                ],
            ],
            'faq.list' => [
                'label'  => 'FAQ list',
                'fields' => [
                    ['key' => 'header_en', 'type' => 'string', 'label' => 'Section header (EN)'],
                    ['key' => 'header_fr', 'type' => 'string', 'label' => 'Section header (FR)'],
                    ['key' => 'items',     'type' => 'json',   'label' => 'Items (JSON array of {question_en, question_fr, answer_en, answer_fr})', 'required' => true],
                ],
            ],
            'gallery.grid' => [
                'label'  => 'Image gallery',
                'fields' => [
                    ['key' => 'header_en', 'type' => 'string', 'label' => 'Section header (EN)'],
                    ['key' => 'header_fr', 'type' => 'string', 'label' => 'Section header (FR)'],
                    ['key' => 'items',     'type' => 'json',   'label' => 'Items (JSON array of {image_url, caption_en, caption_fr})', 'required' => true],
                ],
            ],
        ];
    }

    public static function types(): array
    {
        return array_keys(self::all());
    }

    public static function find(string $type): ?array
    {
        return self::all()[$type] ?? null;
    }

    /**
     * Convert a registry field-list into Laravel validation rules under `data.*`.
     *
     * @return array<string, array<int, string>>
     */
    public static function rulesFor(string $type): array
    {
        $section = self::find($type);
        if (! $section) return [];

        $rules = [];
        foreach ($section['fields'] as $f) {
            $key = "data.{$f['key']}";
            $r = [];
            $r[] = $f['required'] ?? false ? 'required' : 'nullable';

            switch ($f['type']) {
                case 'bool':    $r[] = 'boolean';   break;
                case 'number':  $r[] = 'numeric';   break;
                case 'json':    $r[] = 'array';     break;
                case 'url':     $r[] = 'string'; $r[] = 'max:2048'; break;
                case 'image':   $r[] = 'string'; $r[] = 'max:2048'; break;
                case 'text':    $r[] = 'string'; $r[] = 'max:5000'; break;
                case 'richtext':$r[] = 'string'; $r[] = 'max:50000'; break;
                default:        $r[] = 'string'; $r[] = 'max:1000';
            }

            $rules[$key] = $r;
        }

        return $rules;
    }
}
