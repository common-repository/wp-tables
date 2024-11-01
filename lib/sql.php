<?php

if (!defined('ABSPATH')) {
    exit;
}

class Wp_Tables_WooExp_DB{


    function __construct(){
    }

    public function create_db()
    {
        include_once ABSPATH . 'wp-admin/includes/upgrade.php';
        global $wpdb;
        $sql1 = "CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."tables_list_wooexp` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `title` varchar(255) NOT NULL,
                `table_data` longtext NOT NULL,
                `post_keys` longtext NOT NULL,
                `meta_keys` longtext NOT NULL,
                `col_order` longtext NOT NULL,
                `status` int(2) NOT NULL DEFAULT '1',
                 PRIMARY KEY (`id`)
                 ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
        dbDelta($sql1);
    }
}
?>