<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('site.address', null);
        $this->migrator->add('site.phone', '');
        $this->migrator->add('site.email', null);
        $this->migrator->add('site.social_links', []);
        $this->migrator->add('site.maps_embed', null);
        $this->migrator->add('site.contact_notification_email', null);
    }
};
