@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../vendor/composer/satis/bin/satis
php "%BIN_TARGET%" %*
