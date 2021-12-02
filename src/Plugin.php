<?php

declare(strict_types=1);

namespace Orba\LoadTestPlugin;

use Composer\Autoload\ClassLoader;
use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    private const OBSERVABLE_MODULE = "orba/module-load-test";
    private const PATH_TO_DEPLOY = "dev" . DIRECTORY_SEPARATOR . "tools" . DIRECTORY_SEPARATOR . "locust";
    /**
     * @var Composer
     */
    private $composer;
    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var InventoryModuleDeployment
     */
    private $moduleDeployment;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public static function getSubscribedEvents()
    {
        return [
            'post-package-install' => 'onPackageChange',
            'post-package-update' => 'onPackageChange',
        ];
    }

    public function onPackageChange(PackageEvent $event): void
    {
        $operation = $event->getOperation();
        if ($operation instanceof InstallOperation) {
            $package = $operation->getPackage();
        } elseif ($operation instanceof UpdateOperation) {
            $package = $operation->getTargetPackage();
        } else {
            return;
        }

        if (self::OBSERVABLE_MODULE !== $package->getName()) {
            return;
        }

        $installationManager = $this->composer->getInstallationManager();
        $source = $installationManager->getInstallPath($package) . DIRECTORY_SEPARATOR . self::PATH_TO_DEPLOY;
        $target = getcwd() . DIRECTORY_SEPARATOR . self::PATH_TO_DEPLOY;

        // Direcotry present
        if(!is_dir($target)) {
            mkdir($target, 0777, true);
        }

        $this->copyWhenAbsent($source, $target);
    }

    /**
     * @param string $src
     * @param string $dst
     */
    private function copyWhenAbsent(string $src, string $dst) : void {
        $dir = opendir($src);
        while(( $file = readdir($dir)) ) {
            $sourceFile = $src . DIRECTORY_SEPARATOR . $file;
            $targetFile = $dst . DIRECTORY_SEPARATOR . $file;
            if (( $file != '.' ) && ( $file != '..' )) {
                // Not recursive!
                if (is_file($sourceFile) && !file_exists($targetFile)) {
                    copy($sourceFile, $targetFile);
                }
            }
        }
        closedir($dir);
    }

    /**
     * @inheritdoc
     */
    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    /**
     * @inheritdoc
     */
    public function uninstall(Composer $composer, IOInterface $io)
    {
    }
}