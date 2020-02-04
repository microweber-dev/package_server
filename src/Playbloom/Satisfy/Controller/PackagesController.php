<?php
/**
 * Created by PhpStorm.
 * User: Bojidar
 * Date: 2/4/2020
 * Time: 2:09 PM
 */

namespace Playbloom\Satisfy\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

class PackagesController
{

    protected $compiledPackageFolder = 'compiled_packages/'; // dont forget /

    public function indexAction() {

        $packages = $this->_getCompiledPackageJson();

        return new JsonResponse([
            'packages'=>$packages
        ]);
    }

    private function _getCompiledPackageJson()
    {
        $packages = [];
        $compiledPackages = $this->_jsonDecodeFile($this->compiledPackageFolder  . 'packages.json');
        if ($compiledPackages) {
            foreach ($compiledPackages as $compiledPackage) {
                if (is_array($compiledPackage)) {
                   foreach ($compiledPackage as $package=>$packageSha) {
                       $getPackages = $this->_jsonDecodeFile($this->compiledPackageFolder . $package);
                        if ($getPackages['packages']) {
                            $packages = array_merge($packages, $getPackages['packages']);
                        }
                   }
                }
            }
        }

        return $packages;
    }

    private function _jsonDecodeFile($file) {
        $json = file_get_contents($file);
        $json = json_decode($json, TRUE);
        return $json;
    }
}