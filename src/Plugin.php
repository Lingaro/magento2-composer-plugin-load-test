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
    private $ioInterface;

    public function activate(Composer $composer, IOInterface $ioInterface)
    {
        $this->composer = $composer;
        $this->ioInterface = $ioInterface;
    }

    // @codingStandardsIgnoreStart
    public static function getSubscribedEvents()
    {
        return [
            'post-package-install' => 'onPackageChange',
            'post-package-update' => 'onPackageChange',
        ];
    }
    // @codingStandardsIgnoreEnd

    /**
     * @param PackageEvent $event
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function onPackageChange(PackageEvent $event): void
    {
        $operation = $event->getOperation();
        $package = null;
        if ($operation instanceof InstallOperation) {
            $package = $operation->getPackage();
        } elseif ($operation instanceof UpdateOperation) {
            $package = $operation->getTargetPackage();
        }

        if (!$package) {
            return;
        }

        if (self::OBSERVABLE_MODULE !== $package->getName()) {
            return;
        }

        $installationManager = $this->composer->getInstallationManager();
        $source = $installationManager->getInstallPath($package) . DIRECTORY_SEPARATOR . self::PATH_TO_DEPLOY;
        $target = getcwd() . DIRECTORY_SEPARATOR . self::PATH_TO_DEPLOY;

        // @codingStandardsIgnoreStart
        if (!is_dir($target)) {
            mkdir($target, 0777, true);
        }
        // @codingStandardsIgnoreEnd

        $this->copyWhenAbsent($source, $target);
    }

    /**
     * @param string $src
     * @param string $dst
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function copyWhenAbsent(string $src, string $dst): void
    {
        // @codingStandardsIgnoreStart
        $dir = opendir($src);
        // @codingStandardsIgnoreEnd
        while (($file = readdir($dir))) {
            $sourceFile = $src . DIRECTORY_SEPARATOR . $file;
            $targetFile = $dst . DIRECTORY_SEPARATOR . $file;
            if (( $file != '.' ) && ( $file != '..' )) {
                // @codingStandardsIgnoreStart
                if (file_exists($sourceFile) && is_file($sourceFile) && !file_exists($targetFile)) {
                    copy($sourceFile, $targetFile);
                }
                // @codingStandardsIgnoreEnd
            }
        }
        closedir($dir);
    }

    /**
     * @inheritdoc
     */
    // @codingStandardsIgnoreStart
    public function deactivate(Composer $composer, IOInterface $ioInterface)
    {
    }
    // @codingStandardsIgnoreEnd

    /**
     * @inheritdoc
     */
    // @codingStandardsIgnoreStart
    public function uninstall(Composer $composer, IOInterface $ioInterface)
    {
    }
    // @codingStandardsIgnoreEnd
}
