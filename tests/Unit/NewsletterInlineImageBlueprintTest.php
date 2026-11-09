<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

class NewsletterInlineImageBlueprintTest extends TestCase
{
    public function test_newsletter_bard_image_buttons_have_an_asset_container(): void
    {
        $checked = 0;

        foreach (glob(__DIR__.'/../../resources/blueprints/collections/*/*.yaml') as $path) {
            $blueprint = Yaml::parseFile($path);

            foreach ($blueprint['tabs'] ?? [] as $tab) {
                foreach ($tab['sections'] ?? [] as $section) {
                    foreach ($section['fields'] ?? [] as $item) {
                        $field = $item['field'] ?? [];
                        if (($field['type'] ?? null) !== 'bard' || ! in_array('image', $field['buttons'] ?? [], true)) {
                            continue;
                        }

                        $checked++;
                        $this->assertSame('assets', $field['container'] ?? null, $path);
                    }
                }
            }
        }

        $this->assertGreaterThan(0, $checked);
    }
}
