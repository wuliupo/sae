@echo off
title SAE本地模拟初始化工具
set SAE_PATH=%~dp0
IF EXIST "%SAE_PATH%init.cmd.now" del /S /Q "%SAE_PATH%init.cmd.now"
IF EXIST "%SAE_PATH%init.cmd.new" copy "%SAE_PATH%init.cmd.new" "%SAE_PATH%init.cmd"
IF EXIST "%SAE_PATH%init.cmd.new" move "%SAE_PATH%init.cmd.new" "%SAE_PATH%init.cmd.now"
IF EXIST "%SAE_PATH%init.cmd.now" echo "init.cmd脚本文件修改完成，请重新启动init.cmd"
IF EXIST "%SAE_PATH%init.cmd.now" timeout /t 10
IF EXIST "%SAE_PATH%init.cmd.now" exit

IF EXIST "%SAE_PATH%tmp\\download" xcopy /E /Y "%SAE_PATH%tmp\\download" "%SAE_PATH%"
IF EXIST "%SAE_PATH%tmp\\download" rd /S /Q "%SAE_PATH%tmp\\download"

IF EXIST "%SAE_PATH%init.cmd.old" del /S /Q "%SAE_PATH%init.cmd.old"

IF EXIST "%SAE_PATH%bin\\php-5.3.8\\ext" xcopy /E /Y "%SAE_PATH%bin\\php-5.3.8\\ext" "%SAE_PATH%bin\\php-5.3.8"
IF EXIST "%SAE_PATH%bin\\php-5.3.8\\ext" rd /S /Q "%SAE_PATH%bin\\php-5.3.8\\ext"

IF NOT EXIST "%SAE_PATH%bin\\other\\Graphviz\\bin\\Microsoft.VC90.CRT.manifest" copy "%SAE_PATH%bin\\php-5.3.8\\Microsoft.VC90.CRT.manifest" "%SAE_PATH%bin\\other\\Graphviz\\bin\\Microsoft.VC90.CRT.manifest"
IF NOT EXIST "%SAE_PATH%bin\\other\\Graphviz\\bin\\msvcr90.dll" copy "%SAE_PATH%bin\\php-5.3.8\\msvcr90.dll" "%SAE_PATH%bin\\other\\Graphviz\\bin\\msvcr90.dll"

IF NOT EXIST "%SAE_PATH%bin\\Apache2.2\\bin\\php5ts.dll" copy "%SAE_PATH%bin\\php-5.3.8\\php5ts.dll" "%SAE_PATH%bin\\Apache2.2\\bin\\php5ts.dll"
IF NOT EXIST "%SAE_PATH%bin\\Apache2.2\\bin\\libyaml2appconfig.dll" copy "%SAE_PATH%bin\\php-5.3.8\\libyaml2appconfig.dll" "%SAE_PATH%bin\\Apache2.2\\bin\\libyaml2appconfig.dll"
IF NOT EXIST "%SAE_PATH%bin\\Apache2.2\\bin\\libeay32.dll" copy "%SAE_PATH%bin\\php-5.3.8\\libeay32.dll" "%SAE_PATH%bin\\Apache2.2\\bin\\libeay32.dll"
IF NOT EXIST "%SAE_PATH%bin\\Apache2.2\\bin\\ssleay32.dll" copy "%SAE_PATH%bin\\php-5.3.8\\ssleay32.dll" "%SAE_PATH%bin\\Apache2.2\\bin\\ssleay32.dll"

IF NOT EXIST "%SAE_PATH%bin\\Apache2.2\\modules\\libeay32.dll" copy "%SAE_PATH%bin\\php-5.3.8\\libeay32.dll" "%SAE_PATH%bin\\Apache2.2\\modules\\libeay32.dll"
IF NOT EXIST "%SAE_PATH%bin\\Apache2.2\\modules\\Microsoft.VC90.CRT.manifest" copy "%SAE_PATH%bin\\php-5.3.8\\Microsoft.VC90.CRT.manifest" "%SAE_PATH%bin\\Apache2.2\\modules\\Microsoft.VC90.CRT.manifest"
IF NOT EXIST "%SAE_PATH%bin\\Apache2.2\\modules\\msvcr90.dll" copy "%SAE_PATH%bin\\php-5.3.8\\msvcr90.dll" "%SAE_PATH%bin\\Apache2.2\\modules\\msvcr90.dll"
IF NOT EXIST "%SAE_PATH%bin\\Apache2.2\\modules\\ssleay32.dll" copy "%SAE_PATH%bin\\php-5.3.8\\ssleay32.dll" "%SAE_PATH%bin\\Apache2.2\\modules\\ssleay32.dll"

IF NOT EXIST "%SAE_PATH%bin\\Apache2.2\\bin\\libapr-1.dll" copy "%SAE_PATH%bin\\php-5.3.8\\libapr-1.dll" "%SAE_PATH%bin\\Apache2.2\\bin\\libapr-1.dll"
IF NOT EXIST "%SAE_PATH%bin\\Apache2.2\\bin\\libsvn_client-1.dll" copy "%SAE_PATH%bin\\php-5.3.8\\libsvn_client-1.dll" "%SAE_PATH%bin\\Apache2.2\\bin\\libsvn_client-1.dll"
IF NOT EXIST "%SAE_PATH%bin\\Apache2.2\\bin\\libsvn_delta-1.dll" copy "%SAE_PATH%bin\\php-5.3.8\\libsvn_delta-1.dll" "%SAE_PATH%bin\\Apache2.2\\bin\\libsvn_delta-1.dll"
IF NOT EXIST "%SAE_PATH%bin\\Apache2.2\\bin\\libsvn_diff-1.dll" copy "%SAE_PATH%bin\\php-5.3.8\\libsvn_diff-1.dll" "%SAE_PATH%bin\\Apache2.2\\bin\\libsvn_diff-1.dll"
IF NOT EXIST "%SAE_PATH%bin\\Apache2.2\\bin\\libsvn_fs-1.dll" copy "%SAE_PATH%bin\\php-5.3.8\\libsvn_fs-1.dll" "%SAE_PATH%bin\\Apache2.2\\bin\\libsvn_fs-1.dll"
IF NOT EXIST "%SAE_PATH%bin\\Apache2.2\\bin\\libsvn_ra-1.dll" copy "%SAE_PATH%bin\\php-5.3.8\\libsvn_ra-1.dll" "%SAE_PATH%bin\\Apache2.2\\bin\\libsvn_ra-1.dll"
IF NOT EXIST "%SAE_PATH%bin\\Apache2.2\\bin\\libsvn_repos-1.dll" copy "%SAE_PATH%bin\\php-5.3.8\\libsvn_repos-1.dll" "%SAE_PATH%bin\\Apache2.2\\bin\\libsvn_repos-1.dll"
IF NOT EXIST "%SAE_PATH%bin\\Apache2.2\\bin\\libsvn_subr-1.dll" copy "%SAE_PATH%bin\\php-5.3.8\\libsvn_subr-1.dll" "%SAE_PATH%bin\\Apache2.2\\bin\\libsvn_subr-1.dll"
IF NOT EXIST "%SAE_PATH%bin\\Apache2.2\\bin\\libsvn_wc-1.dll" copy "%SAE_PATH%bin\\php-5.3.8\\libsvn_wc-1.dll" "%SAE_PATH%bin\\Apache2.2\\bin\\libsvn_wc-1.dll"
IF NOT EXIST "%SAE_PATH%bin\\Apache2.2\\bin\\MSVCP100.dll" copy "%SAE_PATH%bin\\php-5.3.8\\MSVCP100.dll" "%SAE_PATH%bin\\Apache2.2\\bin\\MSVCP100.dll"
IF NOT EXIST "%SAE_PATH%bin\\Apache2.2\\bin\\MSVCR100.dll" copy "%SAE_PATH%bin\\php-5.3.8\\MSVCR100.dll" "%SAE_PATH%bin\\Apache2.2\\bin\\MSVCR100.dll"
IF NOT EXIST "%SAE_PATH%bin\\Apache2.2\\bin\\libaprutil-1.dll" copy "%SAE_PATH%bin\\php-5.3.8\\libaprutil-1.dll" "%SAE_PATH%bin\\Apache2.2\\bin\\libaprutil-1.dll"
IF NOT EXIST "%SAE_PATH%bin\\Apache2.2\\bin\\libapriconv-1.dll" copy "%SAE_PATH%bin\\php-5.3.8\\libapriconv-1.dll" "%SAE_PATH%bin\\Apache2.2\\bin\\libapriconv-1.dll"

"%SAE_PATH%bin\\php-5.3.8\\php.exe" -c "%SAE_PATH%bin\\php-5.3.8\\php.sae" -d extension_dir="%SAE_PATH%bin\\php-5.3.8" -d enable_dl=On -f "%SAE_PATH%emulation\\init.php"

pause