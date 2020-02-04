<?php
/**
 * Created by PhpStorm.
 * User: Bojidar
 * Date: 2/4/2020
 * Time: 2:51 PM
 */

namespace Playbloom\Satisfy\Builder;

use Composer\Package\Dumper\ArrayDumper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class PreparePackages
{
    protected $outputDir = false;

    public function __construct($outputDir)
    {
        $this->outputDir = $outputDir;
    }

    public function dump(array $packages)
    {
        $filesystem = new Filesystem();

        foreach ($packages as &$package) {

            if ($package->isDev()) {
                continue;
            }

            $distUrl = $package->getDistUrl();
            $distUrlParsed = parse_url($distUrl);
            $packageMainUrl = $distUrlParsed['scheme'] . '://'. $distUrlParsed['host'] . '/';

            if ($distUrlParsed['path']) {

                $distZip = $this->outputDir . $distUrlParsed['path'];
                if (!$filesystem->exists($distZip)) {
                    continue;
                }

                // Create Meta Folder
                $metaFolder = dirname($this->outputDir) . '/git_meta/' . $package->getDistSha1Checksum() . '/';
                $metaFolderPublicUrl = $packageMainUrl . 'git_meta/' . $package->getDistSha1Checksum() . '/';
                if (!$filesystem->exists($metaFolder)) {
                    $filesystem->mkdir($metaFolder);
                }

                $zip = new \ZipArchive();
                $zip->open($distZip);
                $zip->extractTo($metaFolder);
                $zip->close();

                // Set extra
                $extra = $package->getExtra();
                $extraMeta = array();
                $finder = new Finder();
                $finder->files()->in($metaFolder)->name(['readme.md','README.md','screenshot.png','screenshot.jpg','screenshot.jpeg','screenshot.gif']);
                if ($finder->hasResults()) {
                    foreach ($finder as $file) {
                        $extraMeta[$file->getFilenameWithoutExtension()] = $metaFolderPublicUrl . $file->getFilename();
                    }
                }
                if (!empty($extraMeta)) {
                    $extra['_meta'] = $extraMeta;
                }
                $package->setExtra($extra);

                // Remove all files without media files
                $finder = new Finder();
                $finder->files()->in($metaFolder)->notName(['*.md', '*.jpg', '*.gif', '*.jpeg', '*.bmp', '*.png', '*.svg', '*.mp4', '*.mov', '*.avi']);
                if ($finder->hasResults()) {
                    foreach ($finder as $file) {
                        $filesystem->remove($file->getRealPath());
                        continue;
                    }
                }

                // Remove all empty folders
                $filesForDelete = [];
                $finder = new Finder();
                $finder->files()->in($metaFolder)->directories();
                if ($finder->hasResults()) {
                    foreach ($finder as $folder) {
                        // Check folder files
                        if (!$filesystem->exists($folder->getRealPath())) {
                            continue;
                        }

                        $checkFolder = new Finder();
                        $checkFolder->files()->in($folder->getRealPath());
                        if (!$checkFolder->hasResults()) {
                          // Delete empty folder
                            $filesForDelete[] = $folder->getRealPath();
                            continue;
                        }
                    }
                }

                if (!empty($filesForDelete)) {
                    foreach ($filesForDelete as $fileForDelete) {
                        $filesystem->remove($fileForDelete);
                    }
                }

            }
        }

        return $packages;
    }

}