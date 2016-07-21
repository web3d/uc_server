#uc_server

本项目原本是创建 Discuz uc_server 组件镜像,目前已同步到 v1.6.0 20141101 版本.

后续,产生了基于PHP命名空间改写的一个想法,所以做了当前的尝试.

项目目录结构为:

<pre>
index.php
admin.php
vendor/
|--uc/
app/
|--control/
|--model/
|--view/
plugin/
|--plugin_name1/
|--.....
static/
</pre>