@echo off
title SAE����ģ�⻷������������
"%cd%\bin\php-5.3.8\php.exe" -c "%cd%\bin\php-5.3.8\php.sae" -d extension_dir="%cd%\bin\php-5.3.8" -d enable_dl=On -f "%cd%\emulation\cleandata.php"