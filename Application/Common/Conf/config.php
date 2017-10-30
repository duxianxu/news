<?php
return array(
	 /* 模块相关配置 */
    'DEFAULT_MODULE'     => 'Statistics',
	'MODULE_ALLOW_LIST'    =>    array('Home','Admin','Statistics','Api'),
	'MODULE_DENY_LIST'      =>  array('Common','Runtime'),
	 'URL_MODEL'              => 1,  //启用rewrite

    'SHOW_PAGE_TRACE'        => false,                           // 是否显示调试面板
    'URL_CASE_INSENSITIVE'   => false,                           // url区分大小写
      /* 数据库配置 */
    'DB_TYPE' => 'mysqli', // 数据库类型
    // 'DB_HOST' => '10.1.11.118', // 服务器地址
    // 'DB_NAME' => 'baojia', // 数据库名
    // 'DB_USER' => 'api-baojia',
    // 'DB_PWD' => 'baojia', // 密码
    // 'DB_PORT' => '3306', // 端口
    'DB_PREFIX' => '', // 数据库表前缀

    'DB_HOST' => '10.1.11.2', // 服务器地址
    'DB_NAME' => 'baojia', // 数据库名
     'DB_USER' => 'api-baojia',
    'DB_PWD' => 'CSDV4smCSztRcvVb', // 密码
    'DB_PORT' => '3306', // 端口

    'TMPL_PARSE_STRING' => array(
    '__PUBLIC__' => '/Public', // 更改默认的/Public 替换规
    '__JS__' => '/Public/js', // 增加新的JS类库路径替换规则
    '__CSS__' => '/Public/css', // 增加新的CSS路径替换规则
    //'__IMG__' => '/images', // 增加新的图片径替换规则
	//'__PLG__'=>'/plugins',
    ),
    /*链接参数*/
    'BAOJIA_LINK' =>'mysql://yfread:yfread@10.1.11.14:3306/baojia_box#utf8',
    'BAOJIA_CS_LINK' =>'mysql://bjtest_dev1:yU23VFFFwfZ9y5YG@10.1.11.110:3306/testbaojia#utf8',

);