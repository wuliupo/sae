@echo off
title SAE����ģ���ʼ������
set SAE_PATH=%~dp0

"%SAE_PATH%bin\\php-5.3.8\\php.exe" -c "%SAE_PATH%bin\\php-5.3.8\\php.sae" -d extension_dir="%SAE_PATH%bin\\php-5.3.8" -d enable_dl=On -f "%SAE_PATH%emulation\\init.php"

pause