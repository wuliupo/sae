@echo off
title SAE本地模拟环境数据清理工具
"%cd%\bin\php-5.3.8\php.exe" -c "%cd%\bin\php-5.3.8\php.sae" -d extension_dir="%cd%\bin\php-5.3.8" -d enable_dl=On -f "%cd%\emulation\cleandata.php"