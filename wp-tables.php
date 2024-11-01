<?php
/*
Plugin Name: WP Tables
Plugin URI: https://wordpress.org/plugin/wp-tables
Description: Display your wordpress posts, pages, custom post types and custom fields in table with pagination, filtering, sorting and searching.
Author: Vikram Singh
Version: 1.1
Author URI: http://wooexperts.com
Text Domain: wp-tables
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if(!class_exists('Wp_Tables_WooExp')){
    class Wp_Tables_WooExp{

        private $wp_tables = array();
        private $wp_tables_draw = array();
        private $wp_table_js = 0;
        private $wp_table_per_page = 10;

        function __construct(){

            if(!defined('WP_TABLES_VERSION')){
                define('WP_TABLES_VERSION',1.0);
            }

            add_action('admin_menu',array($this,'wp_tables_menu_items'));
            add_action('admin_enqueue_scripts', array($this, 'wp_tables_admin_scripts'),999);
            add_action('wp_enqueue_scripts',array($this,'wp_tables_scripts'));
            add_shortcode('wp_tables',array($this,'wp_tables_init'),100,1);
            add_action('wp_ajax_wp_tables',array($this,'wp_tables_callback'));
            add_action('wp_ajax_nopriv_wp_tables',array($this,'wp_tables_callback'));
            add_action('wp_ajax_wp-tables-admin',array($this,'wp_tables_admin'));
            add_action('admin_notices',array($this,'wp_tables_admin_notice'));
            add_filter('posts_where',array($this,'wp_tables_posts_where'),10,2);
            register_activation_hook(__FILE__,array($this,'create_sql'));
        }

        function wp_tables_menu_items(){
            add_menu_page(__('WP Tables','wp-tables'),__('WP Tables','wp-tables'),'manage_options','wp-tables',array($this,'wp_tables_settings'),'dashicons-editor-table');
            add_submenu_page('wp-tables',__('Add WP Table','wp-tables'),__('Add WP Table','wp-tables'),'manage_options','wp-table-add',array($this,'wp_tables_settings'));
        }

        function plugin_url(){
            return untrailingslashit(plugins_url('/', __FILE__));
        }

        function wp_tables_admin_scripts(){
            $screen = get_current_screen();
            if(isset($screen->id) && !in_array($screen->id,array('toplevel_page_wp-tables','wp-tables_page_wp-table-add'))){
                return;
            }
            wp_enqueue_style('wp_tables-admin',$this->plugin_url(). '/assets/css/wp-tables-admin.css');
            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-widget');
            wp_enqueue_script('jquery-ui-mouse');
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('jquery-blockui',$this->plugin_url().'/assets/js/jquery.blockUI.js',array('jquery'),WP_TABLES_VERSION,false);
            wp_enqueue_script('jquery-dragtable',$this->plugin_url().'/assets/js/jquery.dragtable.js',array('jquery','jquery-ui-core','jquery-ui-widget','jquery-ui-mouse','jquery-ui-sortable'),WP_TABLES_VERSION,false);
            wp_register_script('wp_tables-admin',$this->plugin_url().'/assets/js/wp-tables-admin.js',array('jquery'),WP_TABLES_VERSION,false);
            $translation_array = array(
                'title_err' => __('Enter Table title.','wp-tables'),
                'postype_err' => __('Select post type field.','wp-tables'),
                'per_page_err' => __('Select item per page field.','wp-tables'),
                'orderby_err' => __('Select Orderby field.','wp-tables'),
                'order_err' => __('Select Order field.','wp-tables'),
                'post_cols_err' => __('Select post columns.','wp-tables'),
                'ajax_url' => admin_url('admin-ajax.php'),
                'save_text' => __('Save','wp-tables'),
                'edit_text' => __('Edit','wp-tables'),
            );
            wp_localize_script('wp_tables-admin','wp_tables_js',$translation_array);
            wp_enqueue_script('wp_tables-admin');
        }

        function wp_tables_scripts(){
            wp_register_style('dataTables',$this->plugin_url().'/assets/css/jquery.dataTables.css');
            wp_register_style('wp_tables',$this->plugin_url().'/assets/css/wp-tables.css');
            wp_register_script('dataTables',$this->plugin_url().'/assets/js/jquery.dataTables.js',array('jquery'),WP_TABLES_VERSION,true);
            wp_register_script('wp_tables',$this->plugin_url().'/assets/js/wp-tables.js',array('dataTables'),WP_TABLES_VERSION,true);
        }

        function init_scripts(){
            wp_enqueue_style('dataTables');
            wp_enqueue_style('wp_tables');

            wp_enqueue_script('dataTables');
            wp_enqueue_script('wp_tables');
        }

        function render_header($wpt_id){
            $html='';
            ob_start();
            $columns = $this->get_wp_table_columns($wpt_id);
            if($this->is_valid_array($columns)){
                foreach($columns as $col_key=>$col_val){
                    if($this->is_valid_array($col_val)){
                        foreach($col_val as $key=>$val){
                            if($key=='post_columns'){
                                $head_key = key($val);
                                echo '<th>'.$val[$head_key].'</th>';
                            }
                            if($key=='meta_columns'){
                                $meta_key = key($val);
                                echo '<th>'.$val[$meta_key].'</th>';
                            }
                        }
                    }
                }
            }
            $html = ob_get_contents();
            ob_end_clean();
            return $html;
        }

        function render_body($columns){
            global $post;
            $html = '';
            ob_start();
            if ($this->is_valid_array($columns)) {
                foreach ($columns as $col_key => $col_val) {
                    if ($this->is_valid_array($col_val)) {
                        foreach ($col_val as $key => $val) {
                            if ($key == 'post_columns') {
                                $head_key = key($val);
                                echo isset($post->{"$head_key"}) ? '<td>' . $post->{"$head_key"} . '</td>' : '<td></td>';
                            }
                            if ($key == 'meta_columns') {
                                $meta_key = key($val);
                                echo '<td>' . get_post_meta($post->ID, $meta_key, true) . '</td>';
                            }
                        }
                    }
                }
            }
            $html = ob_get_contents();
            ob_end_clean();
            return $html;
        }

        function render_footer($wpt_id){
            return $this->render_header($wpt_id);
        }

        function render_values($columns){
            global $post;
            $row_data = array();
            if($this->is_valid_array($columns)){
                foreach($columns as $col_key=>$col_val){
                    if($this->is_valid_array($col_val)){
                        foreach($col_val as $key=>$val){
                            if($key=='post_columns'){
                                $head_key = key($val);
                                $row_data[] = isset($post->{"$head_key"}) ? $post->{"$head_key"} : '';
                            }
                            if($key=='meta_columns'){
                                $meta_key = key($val);
                                $row_data[] = get_post_meta($post->ID,$meta_key,true);
                            }
                        }
                    }
                }
            }
            return $row_data;
        }

        function wp_tables_init($atts){

            if((!isset($atts['id'])) || (isset($atts['id']) && !$atts['id'])){
                return '<p>'.__('Invalid wp table id found.','wp-tables').'</p>';
            }
            $wpt_id = $atts['id'];
            $this->wp_tables[$wpt_id] = $wpt_id;
            $this->init_scripts();
            $atts = $this->get_wp_tables_atts($wpt_id);
            $args = shortcode_atts(array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => $this->wp_table_per_page,
                'orderby' => 'date',
                'order' => 'desc'
            ),$atts,'wp_tables');
            $table_query = new WP_Query($args);
            $html = '';
            ob_start();
            if($table_query->have_posts()){
                $columns = $this->get_wp_table_columns($wpt_id);
                echo '<table id="wp-tables-'.$wpt_id.'" class="display wp-tables" cellspacing="0" width="100%">';
                echo '<thead>';
                echo '<tr>';
                echo $this->render_header($wpt_id);
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                while($table_query->have_posts()){
                    $table_query->the_post();
                    echo '<tr>';
                    echo $this->render_body($columns);
                    echo '</tr>';
                }
                echo '</tbody>';
                echo '<tfoot>';
                echo '<tr>';
                echo $this->render_footer($wpt_id);
                echo '</tr>';
                echo '</tfoot>';
                echo '</table>';
            }
            else
            {
                echo '<p>'.__('Sorry, no record found in wp table.','wp-tables').'</p>';
            }
            $html = ob_get_contents();
            ob_end_clean();
            wp_reset_query();

            add_action('wp_print_footer_scripts',function(){
                if($this->wp_table_js){
                    return;
                }
                if(is_array($this->wp_tables) && count($this->wp_tables)>0){
                    $wp_table_script = "<script type='text/javascript'>\n";
                    $wp_table_script .= " jQuery(function($){ \n";
                    foreach($this->wp_tables as $tab_id){
                        if(in_array($tab_id,$this->wp_tables_draw)){
                            continue;
                        }
                        $tab_atts = $this->get_wp_tables_atts($tab_id);
                        $per_page = isset($tab_atts['posts_per_page']) ? $tab_atts['posts_per_page'] : $this->wp_table_per_page;
                        $wp_table_script .= " $('#wp-tables-".$tab_id."').DataTable({ \n";
                        //$wp_table_script .= " 'searching':false, \n";
                        $wp_table_script .= " 'pageLength':".$per_page.", \n";
                        $wp_table_script .= " 'processing':true, \n";
                        $wp_table_script .= " 'serverSide':true, \n";
                        $wp_table_script .= " 'ajax': '".add_query_arg(array('action'=>'wp_tables','id'=>$tab_id),admin_url('admin-ajax.php'))."', \n";
                        $wp_table_script .= " 'bInfo':false, \n";
                        $wp_table_script .= " 'bLengthChange':false, \n";
                        $wp_table_script .= " }); \n";
                        $this->wp_tables_draw[$tab_id]=$tab_id;
                    }
                    $wp_table_script .= " }); \n";
                    $wp_table_script .= "</script>";
                    echo $wp_table_script;
                    $this->wp_table_js=1;
                }
            });

            return $html;
        }

        function get_order($id,$order){
	        $order_by = array('orderby'=>'date','order'=>'desc');
	        $order_cols = array('column'=>'','dir'=>'');
	        if(is_array($order) && count($order)>0){
	        	foreach($order as $order_field){
			        $order_cols['column'] = isset($order_field['column']) ? $order_field['column'] : '';
			        $order_cols['dir'] = isset($order_field['dir']) ? $order_field['dir'] : '';
		        }
		        $data = $this->get_table_data($id);
		        if(isset($data['col_order'][$order_cols['column']]) && count($data['col_order'][$order_cols['column']])>0){
		        	$col_data = $data['col_order'][$order_cols['column']];
		        	if(isset($col_data['post_columns'])){
		        		if(key($col_data['post_columns']) && key($col_data['post_columns'])!=''){
					        $order_by['orderby'] = key($col_data['post_columns']);
					        $order_by['order'] = isset($order_cols['dir']) ? $order_cols['dir'] : 'asc';
				        }
			        }
			        elseif(isset($col_data['meta_columns'])){
				        if(key($col_data['meta_columns']) && key($col_data['meta_columns'])!=''){
					        $order_by['meta_key'] = key($col_data['meta_columns']);
					        $order_by['order'] = isset($order_cols['dir']) ? $order_cols['dir'] : 'asc';
					        $order_by['orderby'] = 'meta_value_num meta_value';
				        }
			        }
		        }

	        }
        	return $order_by;
        }

        function wp_tables_callback(){

            $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
            if(!$id){
                return;
            }

            $atts = $this->get_wp_tables_atts($id);
            $per_page = isset($atts['posts_per_page']) ? $atts['posts_per_page'] : $this->wp_table_per_page;
            $page = isset($_REQUEST['start']) && $_REQUEST['start']>1 ? ($_REQUEST['start']/$per_page)+1 : 1;
            $order = isset($_REQUEST['order']) && is_array($_REQUEST['order']) && count($_REQUEST['order'])>0 ?  $_REQUEST['order'] : 'default';
	        $order_args = $this->get_order($id,$order);
	        $atts = wp_parse_args($order_args,$atts);
            $data = array();
            $defaults = array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => $per_page,
                'paged' => $page,
            );
            $args = wp_parse_args($atts,$defaults);
            $table_query = new WP_Query($args);
            if($table_query->have_posts()){
                $columns = $this->get_wp_table_columns($id);
                while($table_query->have_posts()){
                    $table_query->the_post();
                    $data[]=$this->render_values($columns);
                }
            }
            echo json_encode(array("recordsTotal"=>$table_query->found_posts,"recordsFiltered"=>$table_query->found_posts,"data"=>$data,'page'=>$page));
            exit();
        }

        function wp_tables_posts_where($where,&$wp_query){
            if(isset($_REQUEST['search']['value']) && $_REQUEST['search']['value']!=''){
                global $wpdb;
                $post_title = $_REQUEST['search']['value'];
                $where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . esc_sql( $wpdb->esc_like($post_title)) . '%\'';
            }
            return $where;
        }

        function is_search($args){
            if(isset($_REQUEST['search']['value']) && $_REQUEST['search']['value']!=''){
                $meta_query['relation']='OR';
                $meta_query[] = array(
                    'key' => '_price',
                    'value' 	=> $_REQUEST['search']['value'],
                    'compare' => '='
                );
                $args['meta_query'] = $meta_query;
            }
            return $args;
        }

        function wp_tables_settings(){
            $title = $this->get_wp_tables_title();
            echo '<div class="wrap wp-tables-wrap">';
            echo '<h1 class="wp-tables-heading-inline">'.$title.'</h1>';
            $wp_tables = new self();
            $file = plugin_dir_path(__FILE__)."lib/view.php";
            if(file_exists($file)){
                require $file;
            }
            echo '</div>';
        }

        function get_post_columns_qry(){
            global $wpdb;
            $columns = $wpdb->get_results("SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA`='".$wpdb->dbname."'  AND `TABLE_NAME`='".$wpdb->posts."'");
            # create 1 Day Expiration
            set_transient('_wp_tables_post_cloumns', $columns, 60*60*24);
            return $columns;
        }

        function get_post_columns(){
            $post_cloumns_cache = get_transient('_wp_tables_post_cloumns');
            $post_cloumns = $post_cloumns_cache ? $post_cloumns_cache : $this->get_post_columns_qry();
            return $post_cloumns;
        }

        function get_meta_columns_qry(){
            global $wpdb;
            $query = "SELECT DISTINCT($wpdb->postmeta.meta_key) 
            FROM $wpdb->posts 
            LEFT JOIN $wpdb->postmeta 
            ON $wpdb->posts.ID = $wpdb->postmeta.post_id 
            WHERE $wpdb->postmeta.meta_key != ''";
            $meta_keys = $wpdb->get_col($query);
            # create 1 Day Expiration
            set_transient('_wp_tables_meta_keys', $meta_keys, 60*60*24);
            return $meta_keys;
        }

        function get_meta_columns(){
            $meta_keys_cache = get_transient('_wp_tables_meta_keys');
            $meta_keys = $meta_keys_cache ? $meta_keys_cache : $this->get_meta_columns_qry();
            return $meta_keys;
        }

        function clean($string){
            $string = str_replace(array('_','-'),' ',$string);
            $string = preg_replace("/ {2,}/"," ",$string);
            $string = preg_replace('/[^A-Za-z0-9\s]/','',$string);
            return $string;
        }

        function parse_columns($columns){
            $wp_tables_columns = array();
            if(is_array($columns) && count($columns)>0){
                foreach($columns as $key=>$val){
                    $wp_tables_columns[$val]= ucwords($this->clean($val));
                }
            }
            return $wp_tables_columns;
        }

        function wp_tables_admin(){

            global $wpdb;
            $res = array('msg'=>'','class'=>'','reload'=>false);
            if(isset($_POST['data']['case'])){
                $wp_db_table = $wpdb->prefix."tables_list_wooexp";
                switch($_POST['data']['case']){

                    case 'save-wp-tables':
                        $wp_tables_data = array();
                        $wp_tables_qry_data = array();
                        if(isset($_POST['data']['wp-tables-data'])){

                            $wp_table_title = isset($_POST['data']['wp-tables-data']['title']) && trim($_POST['data']['wp-tables-data']['title'])!='' ? trim($_POST['data']['wp-tables-data']['title']) : '';
                            if($wp_table_title==''){
                                $last_id = $wpdb->get_var("SELECT MAX(id) FROM ".$wp_db_table);
                                $wp_table_title = 'Table #'.$last_id+1;
                            }
                            $wp_table_id = isset($_POST['data']['wp-tables-data']['id']) ? $_POST['data']['wp-tables-data']['id'] : 0;
                            $post_columns = isset($_POST['data']['wp-tables-data']['post_cols']) ? $_POST['data']['wp-tables-data']['post_cols'] : array();
                            $post_columns = $this->parse_columns($post_columns);
                            $meta_columns = isset($_POST['data']['wp-tables-data']['meta_cols']) ? $_POST['data']['wp-tables-data']['meta_cols'] : array();
                            $meta_columns = $this->parse_columns($meta_columns);

                            $wp_tables_qry_data['post_type']= isset($_POST['data']['wp-tables-data']['post_type']) ? $_POST['data']['wp-tables-data']['post_type'] : 'post';
                            $wp_tables_qry_data['posts_per_page']= isset($_POST['data']['wp-tables-data']['per_page']) ? $_POST['data']['wp-tables-data']['per_page'] : 10;
                            $wp_tables_qry_data['orderby']= isset($_POST['data']['wp-tables-data']['orderby']) ? $_POST['data']['wp-tables-data']['orderby'] : 'date';
                            $wp_tables_qry_data['order']= isset($_POST['data']['wp-tables-data']['order']) ? $_POST['data']['wp-tables-data']['order'] : 'desc';
                            $wp_tables_data['post_columns']= is_array($post_columns) && !empty($post_columns) ? $post_columns : array('post_title'=>'Title','post_date'=>'Date');
                            $wp_tables_data['meta_columns']= is_array($meta_columns) && !empty($meta_columns) ? $meta_columns : array();
                            if($wp_table_id){
                                $wp_tables_data['col_order']= $this->order_columns($wp_tables_data,$wp_table_id);
                                $wpdb->update($wp_db_table,array('title'=>$wp_table_title,'table_data'=>maybe_serialize($wp_tables_qry_data),'post_keys'=>maybe_serialize($wp_tables_data['post_columns']),'meta_keys'=>maybe_serialize($wp_tables_data['meta_columns']),'col_order'=>maybe_serialize($wp_tables_data['col_order'])),array('id'=>$wp_table_id),array('%s','%s','%s','%s','%s'),array('%d'));
                                $res['msg']= __('Table updated successfully.','wp-tables');
                                $res['reload'] = false;
                                $res['class'] = 'notice-success';
                            }
                            else
                            {
                                $wp_tables_data['col_order']= $this->order_columns($wp_tables_data);
                                $wpdb->insert($wp_db_table,array('title'=>$wp_table_title,'table_data'=>maybe_serialize($wp_tables_qry_data),'post_keys'=>maybe_serialize($wp_tables_data['post_columns']),'meta_keys'=>maybe_serialize($wp_tables_data['meta_columns']),'col_order'=>maybe_serialize($wp_tables_data['col_order'])),array('%s','%s','%s','%s','%s'));
                                $res['msg']= __('New table added successfully.','wp-tables');
                                $res['reload'] = true;
                                $res['class'] = 'notice-success';
                            }

                        }
                        break;
                    case 'wp-tables-save_order':
                        if(isset($_POST['data']['wp-tables-data']['id'])){
                            $id = isset($_POST['data']['wp-tables-data']['id']) ? $_POST['data']['wp-tables-data']['id'] : 0;
                            $col_order = isset($_POST['data']['wp-tables-data']['cols']) ? $_POST['data']['wp-tables-data']['cols'] : '';
                            $wpdb->update($wp_db_table,array('col_order'=>maybe_serialize($col_order)),array('id'=>$id),array('%s'),array('%d'));
                            $res['msg']= __('Table columns order successfully.','wp-tables');
                            $res['reload'] = false;
                            $res['class'] = 'notice-success';
                        }
                        break;
                }
                echo json_encode($res);
                die;
            }
        }

        function wp_tables_admin_notice(){
            global $wpdb;
            $notice = false;
            if(isset($_GET['ids'])){
                $ids = explode(',',$_GET['ids']);
                if(is_array($ids) && count($ids)>0){
                    if($wpdb->query("DELETE FROM ".$wpdb->prefix."tables_list_wooexp WHERE id IN (".join(',',$ids).") AND status=0")){
                        $notice = true;
                    }
                }
                if($notice){
                    $class = 'notice notice-success is-dismissible';
                    $message = __('wp table deleted successfully.','wp-tables');
                    printf('<div class="%1$s"><p>%2$s</p></div>',esc_attr($class),esc_html($message));
                }
            }
        }

        function is_valid_wp_tables($id){
            global $wpdb;
            $count = $wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->prefix."tables_list_wooexp WHERE id=".$id." AND status=1");
            return $count;
        }

        function create_sql(){
            include(dirname(__FILE__).'/lib/sql.php');
            $wp_tables_db = new Wp_Tables_WooExp_DB;
            $wp_tables_db->create_db();
        }

        function get_wp_tables_title(){
            global $title;
            if(isset($_GET['page']) && $_GET['page']=='wp-table-add'){
                $title = __('Add Table','wp-tables');
            }
            elseif(isset($_GET['page']) && $_GET['page']=='wp-tables' && isset($_GET['action']) && $_GET['action']=='edit'){
                $title = __('Edit Table','wp-tables');
            }
            elseif(isset($_GET['page']) && $_GET['page']=='wp-tables' && isset($_GET['action']) && $_GET['action']=='edit-header'){
                $title = __('Edit Table Header','wp-tables');
            }
            return $title;
        }

        function get_table_data($table_id=0){
            $defaults = array(
                'post_type'=>'',
                'posts_per_page'=>'',
                'orderby'=>'',
                'order'=>''
            );
            if(!$table_id){
                $table_id = isset($_GET['wp-table']) && isset($_GET['action']) && $_GET['action']=='edit' ? $_GET['wp-table'] : 0;
            }
            $data = $this->get_wp_table($table_id);
            $data['table_data'] = isset($data['table_data']) ? maybe_unserialize($data['table_data']) : array();
            $data['table_data'] = isset($data['table_data']) ? wp_parse_args($data['table_data'],$defaults) : $defaults;
            $data['post_keys'] = isset($data['post_keys']) ? maybe_unserialize($data['post_keys']) : array();
            $data['meta_keys'] = isset($data['meta_keys']) ? maybe_unserialize($data['meta_keys']) : array();
            $data['col_order'] = isset($data['col_order']) ? maybe_unserialize($data['col_order']) : array();
            return $data;
        }

        function get_wp_table_columns($id){
            $data = $this->get_table_data($id);
            return isset($data['col_order']) ? $data['col_order'] : array();
        }

        function get_columns($id){
            $cols = array();
            $data = $this->get_table_data($id);
            $cols['post_keys'] = isset($data['post_keys']) ? $data['post_keys'] : array();
            $cols['meta_keys'] = isset($data['meta_keys']) ? $data['meta_keys'] : array();
            return $cols;
        }

        function get_wp_tables_atts($id){
            $cols = array();
            $data = $this->get_table_data($id);
            $cols['table_data'] = isset($data['table_data']) ? $data['table_data'] : array();
            return $cols['table_data'];
        }

        function get_wp_tables_col_order($id){
            $cols = array();
            $data = $this->get_table_data($id);
            $cols['col_order'] = isset($data['col_order']) ? $data['col_order'] : array();
            return $cols['col_order'];
        }


        function get_wp_table($id){
            global $wpdb;
            $results = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."tables_list_wooexp WHERE id=".$id,ARRAY_A);
            return $results;
        }

        function get_wp_tables_id(){
            $table_id = isset($_GET['wp-table']) && isset($_GET['action']) && $_GET['action']=='edit' ? $_GET['wp-table'] : 0;
            return $table_id;
        }

        function get_lowest_key($array){
            $last_key='';
            if(is_array($array) && count($array)>0){
                if(is_array(end($array))){
                    foreach(end($array) as $key=>$val){
                        $last_key = $key;
                    }
                }
            }
            return $last_key;
        }

        function check_if_element_exists($col,$cols_array){
            $exists = false;
            if (is_array($cols_array) && count($cols_array) > 0) {
                foreach ($cols_array as $col_key => $col_val) {
                    if($this->get_lowest_key($col_val)!='' && $this->get_lowest_key($col_val)==$this->get_lowest_key($col)) {
                        $exists = true;
                        break;
                    }
                }
            }
            return $exists;
        }

        function add_new_columns($prev_data,$new_data){
            if(is_array($new_data) && count($new_data)>0){
                foreach($new_data as $new_ord=>$new_col){
                    if($this->check_if_element_exists($new_col,$prev_data)){
                        unset($new_data[$new_ord]);
                        continue;
                    }
                }
            }
            if(is_array($prev_data) && count($prev_data)>0 && is_array($new_data) && count($new_data)>0){
                foreach($new_data as $nk=>$nv){
                    $prev_data[]=$nv;
                }
            }
            elseif(is_array($prev_data) && count($prev_data)==0 && is_array($new_data) && count($new_data)>0){
                $prev_data = $new_data;
            }
            return $prev_data;
        }

        function order_columns($data,$id=0){
            $order_data = array();
            $order=0;
            if($id){
                $cdata = $this->get_wp_table($id);
                if(is_array($data['post_columns']) && count($data['post_columns'])>0){
                    foreach($data['post_columns'] as $pk=>$pv){
                        $order_data[$order]['post_columns'][$pk]=$pv;
                        $order++;
                    }
                }
                if(is_array($data['meta_columns']) && count($data['meta_columns'])>0){
                    foreach($data['meta_columns'] as $mk=>$mv){
                        $order_data[$order]['meta_columns'][$mk]=$mv;
                        $order++;
                    }
                }

                if(is_array($cdata['col_order']) && count($cdata['col_order'])>0){
                    foreach($cdata['col_order'] as $prev_order=>$prev_cols){
                        if(!$this->check_if_element_exists($prev_cols,$order_data)){
                            unset($cdata['col_order'][$prev_order]);
                        }
                    }
                }
                $cdata['col_order'] = array_values($this->add_new_columns($cdata['col_order'],$order_data));
            }
            else
            {
                if(is_array($data['post_columns']) && count($data['post_columns'])>0){
                    foreach($data['post_columns'] as $pk=>$pv){
                        $order_data[$order]['post_columns'][$pk]=$pv;
                        $order++;
                    }
                }
                if(is_array($data['meta_columns']) && count($data['meta_columns'])>0){
                    foreach($data['meta_columns'] as $mk=>$mv){
                        $order_data[$order]['meta_columns'][$mk]=$mv;
                        $order++;
                    }
                }
            }
            return $order_data;
        }

        function is_valid_array($array){
            $valid = false;
            if(is_array($array) && count($array)>0){
                $valid = true;
            }
            return $valid;
        }

    }
}

new Wp_Tables_WooExp();
?>