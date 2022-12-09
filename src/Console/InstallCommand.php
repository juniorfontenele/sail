<?php

namespace JuniorFontenele\Sail\Console;

use Laravel\Sail\Console\InstallCommand as SailInstallCommand;

class InstallCommand extends SailInstallCommand
{
    protected $signature = 'app:install
        {--with= : The services that should be included in the installation}
        {--devcontainer : Create a .devcontainer configuration directory}
        {--dev : Use laravel\'s default dev webserver}';

    /**
     * Build the Docker Compose file.
     *
     * @param  array  $services
     * @return void
     */
    protected function buildDockerCompose(array $services)
    {
        $depends = collect($services)
            ->map(function ($service) {
                return "            - {$service}";
            })->whenNotEmpty(function ($collection) {
                return $collection->prepend('depends_on:');
            })->implode("\n");
        $stubs = rtrim(collect($services)->map(function ($service) {
            return file_get_contents($this->laravel->basePath('vendor/') . "/laravel/sail/stubs/{$service}.stub");
        })->implode(''));

        $volumes = collect($services)
            ->filter(function ($service) {
                return in_array($service, ['mysql', 'pgsql', 'mariadb', 'redis', 'meilisearch', 'minio']);
            })->map(function ($service) {
                return "    sail-{$service}:\n        driver: local";
            })->whenNotEmpty(function ($collection) {
                return $collection->prepend('volumes:');
            })->implode("\n");

        $dockerCompose = $this->option('dev') ? file_get_contents(__DIR__ . '/../../stubs/docker-compose-dev.stub') : file_get_contents(__DIR__ . '/../../stubs/docker-compose.stub');

        $dockerCompose = str_replace('{{depends}}', empty($depends) ? '' : '        '.$depends, $dockerCompose);
        $dockerCompose = str_replace('{{services}}', $stubs, $dockerCompose);
        $dockerCompose = str_replace('{{volumes}}', $volumes, $dockerCompose);

        // Replace Selenium with ARM base container on Apple Silicon...
        if (in_array('selenium', $services) && php_uname('m') === 'arm64') {
            $dockerCompose = str_replace('selenium/standalone-chrome', 'seleniarm/standalone-chromium', $dockerCompose);
        }

        // Remove empty lines...
        $dockerCompose = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $dockerCompose);

        file_put_contents($this->laravel->basePath('docker-compose.yml'), $dockerCompose);
    }
}
