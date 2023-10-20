<?php

namespace App\Services;

use App\Core\Config;
use App\Core\Service;
use App\Core\UsesApplication;
use App\Log\Log;
use ErrorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ValidatorBuilder;

class ConfigManager extends Service
{
    public const DEFAULT_CONFIG_FILE_PATH = __DIR__ . '/../../../config/config.json';
    public Config $config;
    private ValidatorInterface $validator;

    public function load(): void
    {
        $file = $this->application->configFilePath ?? self::DEFAULT_CONFIG_FILE_PATH;
        $contents = file_get_contents($file);
        $json = json_decode($contents, true);
        $this->config = new Config($json);
        $this->validate();
        Log::info("Successfully loaded the configuration file");
    }

    /**
     * @throws ErrorException
     */
    protected function validate(): void
    {
        $errors = $this->validator->validate($this->config);

        if ($errors->count() > 0) {
            throw new ErrorException("Invalid JSON configuration");
        }
    }

    public function save(): void
    {
        $file = $this->application->configFilePath ?? self::DEFAULT_CONFIG_FILE_PATH;
        $json = json_encode($this->config, JSON_PRETTY_PRINT | JSON_OBJECT_AS_ARRAY);

        if (!$json) {
            throw new \Error("Failed to encode JSON!");
        }

        file_put_contents($file, $json);
        Log::info("Successfully saved the configuration file");
    }

    public function boot(): void
    {
        $validatorBuilder = new ValidatorBuilder();
        $this->validator = $validatorBuilder->getValidator();

        $this->load();
    }
}