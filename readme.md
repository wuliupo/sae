SAE本地开发环境支持的服务：
appconfig，counter，FetchURL，KVDB ，Mail ，Memcache，MySQL ，Rank ，Storage ，TaskQueue ，TmpFS ，Image，Wrappers，XHProf，验证码 

注意：
1.本地开发环境不需要安装，解压之后就可以直接使用。
2.如果你是在windows7或者windows vista 系统下运行本地开发环境，请使用管理员身份来启动本地开发环境（打开本地开发环境根目录，选择init.cmd，点击鼠标右键，选择以管理员身份运行）。
3.如果你是在xp系统下运行本地开发环境，请确保你登陆的用户是一个计算机管理员（打开"控制面板"，选择"用户账户"，可以看到当前系统的所有用户信息，找到你登陆的用户名，看看这个用户是不是一个计算机管理员。如果不是，请切换到一个计算机管理员用户）。


本地环境配置文件参数说明：
本地开发环境的根目录底下有一个文件sae.conf，用来配置SAE的本地开发环境
DocumentRoot      指定主文档树的根目录，如果不指定这个参数，默认为本地wwwroot目录。如果指定了路径，注意路径中不能含有中文字符。

http_port         指定apache的http端口号
https_port        指定apache的https的端口号
redis_port        指定redis的端口号
domain            用来设置本地开发环境访问是的域名，如果domain设置为saetest,那么访问应用时的域名就为 $appname.saetest.com，默认情况下domain为'sinaapp'。

mysql_user        指定mysql的用户名
mysql_pass        指定mysql用户密码
mysql_host        指定mysql的ip，默认值为127.0.0.1
mysql_port        指定mysql的端口号，默认值为3306
如果需要使用代理服务器，请配置下面四个参数
proxy_host 
proxy_port 80
proxy_username 
proxy_password 

open_xdebug 0     本地开发环境是否使用xdebug，如果使用设置为1，不适用设置为0，默认为0
autoupgrade 1     本地开发环境是否自动升级，如果设置为1将会自动升级本地开发环境，如果设置为0，在有更新时会询问用户是否升级。


本地开发环境命令及命令参数说明：

应用管理命令：
1.capp $appname        创建一个应用，$appname为应用名称
2.dapp $appname        删除一个应用，$appname为应用名称
3.cstorage $domain     创建一个storage，$domain为创建的storage的名称
4.dstorage $domain     删除一个storage，$domain为要删除的storage的名称
5.cversion $version    创建一个应用的版本,$version为要创建的版本号
6.dversion $version    删除一个应用的版本，$version为要删除的版本号，将要删除的版本号不能是默认版本号。
7.use $appname         选择一个应用来进行操作,$appname为应用名称
8.defver $version      设置应用的默认版本,$version为版本号
9.sversions            显示当前应用的所有版本号
10.sversion            显示当前默认版本号
11.sstorages           显示当前应用所有的storage名称
12.sapp                显示当前选择的应用名称
13.sapps               显示当前所有的应用名称
14.upconfig $version   更新$version版本的config.yaml文件，使配置起作用。如果你已经启动了本地开发环境，在修改config.yaml后需要调用这个命令才能使你的修改生效
15.upallconfig         更新当前应用所有版本的config.yaml文件，使这些配置起作用。如果你同时修改了一个应用底下了不同版本的config.yaml文件，那么你可以使用这个命令来方便的更行这个应用的全部修改。

apache控制命令：
1.之前版本中restart命令是用来重启apache的，新版本中将这个命令修改为重启本地开发环境。
2.apachestat          查看当前apache服务器的状态，是启动状态还是关闭状态
3.apachestart         启动apache服务
4.apachestop          停止apache服务
5.apacherestart       重启apache服务

有关host文件的命令
1.openhost            开启host的绑定，使用这个命令将会绑定当前所有应用所有版本的host
2.closehost           关闭host绑定，使用这个命令将会取消绑定当前所有应用所有版本的host
3.addhost[$version]   添加当前应用的host绑定，如果指定version，则是添加当天应用version这个版本的host，如果没有指定version，则是添加当前应用所有版本的host。使用这个命令之前确保你已经调用过use命令
4.delhost[$version]   删除当前应用绑定的host。如果指定version，则会删除当前应用version版本的host，如果没有指定version，则会删除当前应用所有版本的host，包括默认版本的host。使用这个命令之前确保你已经调用过use命令

上传下载代码的svn命令：
如果没有特殊说明，下面提到的版本库都是本地开发环境的版本库。
1.chickoutall/coa                                      下载账户中所有应用的代码
2.chickout/co $appname [-v=$version]                   如果没有指定-v选项，则是下载$appname的所有版本代码，如果指定了-v选项，则下载$appname应用的$version版本的代码
3.add [-v=$version] $filename [$filename2 ...]         添加一个或者多个文件到svn的代码库中，如果指定-v选项，就是添加$version版本的文件，如果没有指定-v选项，就是添加默认版本的文件
4.addall [-v=$version]                                 将所有没在代码库中的文件添加到代码库中，如果指定-v选项，就是添加$version版本的文件，如果没有指定-v选项，就是添加默认版本的文件
5.saddall [-v=$version]                                显示addall [-v=$version]要添加的文件
6.delete/del [-v=$version] $filename [$filename2 ...]  删除一个或者多个文件，如果指定-v选项，就是删除$version版本的文件，如果没有指定-v选项，就是删除默认版本的文件
7.delall  [-v=$version]                                将所有已经删除的文件从代码库中删除，，如果指定-v选项，就是删除$version版本的文件，如果没有指定-v选项，就是删除默认版本的文件
8.sdelall [-v=$version]                                显示delall  [-v=$version]要删除的文件
9.update/up [-v=$version]                              更新代码，如果指定-v选项，则更新$version版本的文件，如果没有指定-v选项，则更新默认版本的文件
11.commit/ci                                           [-v=$version]  将代码库中所做的修改上传到线上环境的代码库中，如果指定了-v选项，就是将$version版本所做的修改上传到线上环境的代码库中，如果没有指定-v选项，就是讲默认版本的修改上传到代码库中
10.stat [-v=$version]                                  显示svn文件的状态，如果指定-v选项，则显示$version版本的文件状态，如果没有指定-v选项，则显示默认版本的文件状态。文件状态描述：
	add       表示这个添加文件已经添加到版本库中，不过还没有上传到线上环境的代码库中
	del       表示删除的这个文件已经添加到版本库中，不过还没有上传到线上环境的代码库中
	change    表示这个文件做了修改，不过还没有上传到线上环境的代码库中
	nadd      表示这个文件是新添加的，不过没有添加到代码库中
	ndel      表示这个文件已经删除，不过还没有从代码库中删除
12.第3-11条命令使用之前都应调用'use'命令来选择一个应用进行操作

其他命令：
1.changeuser           用来修改本地开发环境中使用的SAE的安全邮箱和密码，这个命令不带参数，使用该命令会提示你输入用户名和密码
2.exit/quit            退出本地开发环境
3.restart              重启本地开发环境
4.saever               显示本地开发环境的当前版本
5.uninstall            卸载本地开发环境，这个命令是清理本地开发环境中的临时数据，并卸载apache服务。想要完全的卸载本地开发环境，你需要先调用这个命令，再将本地开发环境删除即可。
6.help [-host/-svn/-app/other/-apache/-all]                帮助信息，如果没有指定参数，则显示提示信息，选择一个参数查询，指定参数只会显示指定部分命令的信息
7.clear                用来清屏的


部分命令的具体的使用方法：
1.启动本地开发环境：
	运行sae目录底下的init.cmd即可启动SAE本地开发环境（windows7或者windows vista 请点击鼠标右键，选择以管理员身份运行）。如果windows防火墙阻止了Apache HTTP Server和redis-server程序，请使这两个程序允许访问。
2.创建应用
	capp $appname   capp是创建一个应用的命令，$appname是要创建的应用名称，本地环境最多只能创建10个应用，创建应用后并没有给应用创建版本，所以需要通过'cversion'命令来给应用创建新版本。
3.选择一个应用进行操作
	use $appname    $appname是要选择的应用名称，$appname必须是应经存在的，你可以通过sapps命令来查看当前的应用名称。如果新创建了一个应用，需要对新创建的应用操作，可以省略use命令，因为capp命令在创建完成之后会调用use命令，将当前要操作的应用名设置为新建的应用名称。
3.创建应用版本
	cversion $version   cversion是创建一个版本的命令，$version是要创建的版本号。在创建好一个应用需创建一个应用版本才能使用。每一个应用最多可以创建10个版本。如果当前应用只有一个版本，那么这个版本将被设置为默认版本。
4.设置应用的默认版本
	defver $version     $version为要设置为默认版本的版本号。这个版本号一定要是你已经创建好的，可以通过sversion命令来查看当前应用的版本号
5.创建storage
	cstorage $domain    $domain为要创建的storage名称.每个应用最多只能有5个storage，全部应用的storage数量最多为10个
6.关闭本地开发环境：exit/quit
	在推出本地开发环境时一定要使用exit/quit推出，本地开发环境推出时回关闭apache，redis，并取消host的绑定。

	
本地开发环境中svn命令的使用方法：
1.如果用户需要下载自己账户的全部应用的代码，直接使用chickoutall或者coa命令就可以了，如果在启动本地开发环境之后没有输入SAE的用户名跟密码，会提示输入用户名跟密码。
2.如果本地开发环境中不存在要下载的应用，将会帮用户新建这个应用。
3.用户可以使用'changeuser'命令来设置/修改SAE的安全邮箱和密码，输入'changeuser'后会提示输入用户名跟密码，输入密码时密码不会显示任何字符。
4.用户可以使用chickoutall/coa命令来下载账户中的全部代码。
5.用户可以使用chickout/co命令来下载单个应用或者单个版本的代码，例如要下载test应用的代码：chickout test 。如果要下载test应用中版本2的代码：chickout test -v=2
6.在使用chickoutall/coa或者chickout/co命令下载文件之前请确认要下载的应用版本是不存在的，如果存在将下载失败。例如用户想要下载test应用的版本2的代码，但是本地开发环境中test这个应用中已经有了版本2，这是会提示错误信息，并不会下载代码到本地。如果你依然想下载这个版本的代码，可以向将这个版本删除，然后在下载。
7.add命令是用来添加一个或者多个文件到版本库中，命令格式：add [-v=$version] $filename [,$filename2,...],-v是可选的，默认是当前应用的默认版本。例如你已经将test应用的版本2的代码下载到本地开发环境中，test应用当前默认版本为1，首先使用选择test应用为当前操作的应用：use test ,然后你在 wwwtoor/test/2/(如果你没有设置过sae.conf中的DocumentRoot参数，那么就是这个路径了)路径下添加了一个文件'ttt.php',你需要将这个文件添加到版本库中，就可以使用命令 'add -v=2 ttt.php'来完成。当然，你可以一次添加多个文件到版本库中。
8.addall 命令是将新添加的文件全部添加到版本库中，-v选项用来指定是操作哪个版本的文件
9.saddall就是现实哪些文件时新添加了，并且没有添加到版本库中。-v选项用来指定是操作哪个版本的文件。如果你在使用addall之前想知道使用这个命令都会将哪些文件添加到版本库中，那么就可以使用saddall这个命令了。
10.del/delall/sdelall命令与上面的add/addall/saddall命令用法差不多，详细信息可以看上面的命令介绍
11.update/up是用来更新文件用的，命令格式： up [-v=$version]。-v是可选的，默认是当前应用的默认版本。比如你test应用这个应用有多个人在共同开发，其他人对test应用做了修改，并且将代码上传到了线上环境的代码库中，那么现在你本地开发环境中的代码肯定与线上环境的代码不同，这是你就需要使用update命令来将线上环境的代码同步到本地。
12.commit/ci 上传代码到线上代码服务器。如果你使用了add/addall/del/deladd或者是你修给了某一个文件，需要将这个文件同步到线上代码服务器中，你需要调用这个命令。
13.stat [-v=$version]显示当前应用的$version版本的文件信息，详细请看svn命令说明。



代码管理：
通过SAE本地开发环境的配置文件已经配置好了代码所在的根目录，即$DocumentRoot，默认的$DocumentRoot是本地开发环境的wwwroot目录底下。在$DocumentRoot目录中找到已经创建的应用名，在这个应用名底下找到创建的版本号文件夹，现在你就可以在这个文件夹下开始书写你的代码了。

应用访问：
这里假定用户没有修改sae.conf中的domain配置参数，使用默认值'sinaapp'
现在假定你应经创建了一个应用'test',并为这个应用创建了一个版本'1'，里面已经下好了一个php文件'test.php'。你要通过浏览器访问这个应用，需要做下面的工作：
1.之前的版本是需要用户自己来绑定host，最新版本中，本地开发环境将会自动绑定host，如果在自动绑定host时，遇到杀毒软件拦截，请允许本地开发环境对hosts文件的修改
2.你可以直接在浏览器中输入"1.test.sinaapp.com/test.php"来访问test应用中的test.php脚本了,如果版本1为默认版本号，可以使用"test.sinaapp.com/test.php"来访问
3.如果你有一个线上的应用，名称也叫‘test’，现在你想要访问线上应用，应该如何来做呢？你可以先使用'use test'来选择'test'应用进行操作，然后使用'delhost'命令来取消这个应用对应域名的host绑定，这样就可以让问线上应用页面了。当然，如果你关闭本地开发环境同样也会取消服务域名的host绑定。

本地开发环境中xhprof的使用：
1.本地开发环境与线上环境的xhprof在使用上没什么区别，都是在代码开始时使用sae_xhprof_start()函数，在结束时使用sae_xhprof_end()函数。不过在查看xhprof生成内容时与线上环境不同。
2.如果你使用了本地开发环境的xhprof，通过浏览器访问，sae_xhprof_start()函数会打印出你需要访问的连接地址，这是你只需要点击这个连接就可以了。
3.本地开发环境中xhprof生成的所有文件都存放在"本地开发环境根目录/storage/storage/SAE_xhprof"中，生成文件是以应用名称作为后缀名的，文件名的前面部分是xhprof生成的一个字符串。如果你的"test"应用使用了xhprof文件，那么在保存这个文件时，xhprof为它生成了名字的前半部分(假如是"4f6996b2b1fcd"这样一个字符串)，应用名称为它的后半部分，那么这个生成的文件就是：4f6996b2b1fcd.test。当然，这个文件时保存在"本地开发环境根目录/storage/storage/SAE_xhprof"中的。
4.如果你想手动的输入你个连接来访问相应的文件内容的话，你就需要将连接拼出来。这个连接就像这样："http://127.0.0.1/sae/xhprof/xhprof_html/index.php?run=4f6996b2b1fcd&source=test",其中"http://127.0.0.1/sae/xhprof/xhprof_html/index.php"这部分你可以通过打印本地开发环境中的常量 'XHPROF_HOST'；来得到，一般它就是这样了，除非你修改了端口号。"run=4f6996b2b1fcd"，这个字符串就是xhprof生成的。 后面的"source=test"就是你的应用名称了。
5.sae_xhprof_start()和sae_xhprof_end()你可以在 emulation/loadsae.php中找到，如果你不想在sae_xhprof_end()函数中输出任何信息，你就可以将这个函数的输出信息注视掉。在emulation/xhprof中有更多关于xhprof的帮助信息，你可以查看readme来了解更多关于xhprof的内容。

本地开发环境中xdebug的使用
1.要使用xdebug进行调试，必须先将sae.conf中的配置参数open_xdebug设置为1，然后在启动本地开发环境。
2.xdebug生成的文件存放在 "本地开发环境根目录/storage/storage/SAE_xdebug"中。
3.你可以通过"WinCacheGrind.exe"来查看生成文件内容，"WinCacheGrind.exe"文件在"本地开发环境根目录/bin/other/WinCacheGrind"中。
4.本地开发环境不会自动删除这些生成文件，需要用户自己将不需要的生成文件删除掉。

本地开发环境中mysql的使用：
如果用户需要使用mysql，那么需要用户自己安装配置mysql，下面是具体安装配置步骤：
1.下载mysql的windows版本，下载地址：http://dev.mysql.com/downloads/cluster/
2.安装mysql
3.安装完mysql你需要设置密码，当然你也可以创建一个用户给本地开发环境。
4.配置sae.conf文件，你需要配置下面4个参数：
	mysql_user        指定mysql的用户名
	mysql_pass        指定mysql用户密码
	mysql_host        指定mysql的ip，默认值为127.0.0.1
	mysql_port        指定mysql的端口号，默认值为3306
5.当你想要在本地开发环境中使用MySQL时请确保你的MySQL服务是启动状态的。
6.如果你已经创建好了一个应用，这里假定你的应用名称为'test'，'test'应用中需要使用mysql，这时你必须手动的创建一个mysql数据库，并且数据库名称应该为"app_".$appname，也就是说'test'应用创建数据库时数据库名称应该是'app_test'，然后就可以使用mysql了，使用方法跟线上环境相同(连接数据库时使用SAE定义的常量)。

服务的注意事项：
1.Memcache，counter服务将不用初始化，直接使用即可。
2.如果你更改了config.yaml文件，那么你应该使用upconfig或者upallconfig命令来使你做的修改生效。


其他注意事项：
1.关闭本地模拟环境后将会清理所有应用的所有数据（storage服务数据除外）。
2.本地模拟环境存放的路径中不能含有中文。
3.指定Apache根目录路径中不能含有中文。
4.建立的应用名称不能是sae或storage，这两个名字被本地环境使用。
5.启动本地开发环境是将会绑定现有应用的host，在关闭本地开发环境时将会取消这些绑定的host。
6.退出本地开发环境请使用quit或exit命令，不应该直接关闭命令行窗口，如果直接关闭命令行窗口，apache服务将不会被停止，并且不能不能取消绑定的host
7.在使用storage里面的文件时，需要通过storage的接口返回文件的url。不要直接写出要访问文件的url（也就是说不要像这样来访问storage："$appname-$domain.stor.sinaapp.com/$filename"，也不能使用绝对路径来访问）。本地开发环境不支持这种方式来访问storage。
8.本地环境中使用了SAE线上环境的禁用函数stream_socket_client，proc_open,proc_close，但是请用户不要使用这个函数，否则代码上传到服务器上运行会出错。
9.xdebug所生成的信息存放在"本地开发环境根目录/storage/storage/SAE_xdebug"中。。
10.启动本地开发环境后第一次下载代码或者上传代码或者更新代码，需要输入SAE的安全邮箱和安全密码。
11.本地开发环境不会保存用户的用户名和密码，所以关闭本地开发环境后重新启动本地开发环境，需要从新输入用户名和密码。
12.可以使用changeuser命令来手动修改安全邮箱和安全密码。
13.启动本地开发环境之后，如果你通过本地开发环境的svn命令操作过某个应用，那么在关闭本地开发环境之前，不能删除这个应用。如果你通过本地开发环境的svn命令操作过某个应用的某一个版本，那么在关闭本地开发环境之前将不能删除这个应用的这个版本



本地开发环境数据清理工具：
这个工具只要是用来清理counter，KVDB ，Memcache，Rank中的数据的。比如你想清除'test'应用的memcache数据，你需要先使用use命令：use test ;在清除memcache中的数据：clean memcache。
运行sae目录底下的tool.cmd即可启动SAE本地开发环境的数据清理工具。通过输入命令来实现数据的清理，下面是命令及命令参数的说明.
clean alldata          清理所有内存数据，包括KVDB，memcache，Counter，Rank中的数据
clean all memcache     清楚所有应用中的memcache的数据
clean all kvdb         清楚所有应用中的KVDB的数据
clean all counter      清楚所有应用中的Counter的数据
clean all rank         清楚所有应用中的Rank的数据

use appname            选择要处理那个appname的数据
clean all              清除一个应用中所有在内存中的数据，包括KVDB，memcache，Counter，Rank中的数据，使用前应先使用use 命令来选择一个应用
clean memcache         清楚一个应用中的memcache的数据,使用前应先使用use 命令来选择一个应用
clean kvdb             清楚一个应用中的KVDB的数据，使用前应先使用use 命令来选择一个应用
clean counter          清楚一个应用中的Counter的数据，使用前应先使用use 命令来选择一个应用
clean rank             清楚一个应用中的Rank的数据，使用前应先使用use 命令来选择一个应用
showapp                显示当前选中的appname，使用前应先使用use 命令来选择一个应用
showapps               显示当前所有的应用名称
quit/exit              退出数据清理环境
help                   显示帮助信息

SAE本地开发环境需要您的支持才能做的更好，如果您有意见，建议或者你发现了SAE本地开发环境中的bug，请写到SAE的官方论坛中。非常感谢您的支持。