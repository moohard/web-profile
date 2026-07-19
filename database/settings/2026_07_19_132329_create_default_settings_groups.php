<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('site.site_name', config('app.name'));
        $this->migrator->add('site.logo_path', null);
        $this->migrator->add('site.favicon_path', null);

        $this->migrator->add('whatsapp.number', '');
        $this->migrator->add('whatsapp.enabled', false);
        $this->migrator->add('whatsapp.default_message', '');

        $this->migrator->add('seo.default_meta_title', null);
        $this->migrator->add('seo.default_meta_description', null);
        $this->migrator->add('seo.og_default_image_path', null);
        $this->migrator->add('seo.default_og_type', 'website');
    }
};
