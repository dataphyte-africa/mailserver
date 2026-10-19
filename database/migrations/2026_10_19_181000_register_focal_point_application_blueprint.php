<?php

use Illuminate\Database\Migrations\Migration;
use Statamic\Facades\Blueprint;
use Symfony\Component\Yaml\Yaml;

return new class extends Migration
{
    public function up(): void
    {
        if (Blueprint::find('globals.focal_point_application')) {
            return;
        }

        $contents = Yaml::parseFile(resource_path('blueprints/globals/focal_point_application.yaml'));
        Blueprint::make('focal_point_application')
            ->setNamespace('globals')
            ->setContents($contents)
            ->save();
    }

    public function down(): void
    {
        Blueprint::find('globals.focal_point_application')?->delete();
    }
};
