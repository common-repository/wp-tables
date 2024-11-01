<?php
if (!defined('ABSPATH')) {
    exit;
}
if((isset($_GET['wp-table']) && isset($_GET['action']) && $_GET['action']=='edit') || isset($_GET['wp-table']) && isset($_GET['action']) && $_GET['action']=='edit-header'){
    if(!$wp_tables->is_valid_wp_tables($_GET['wp-table'])){
        wp_redirect(admin_url('admin.php?page=wp-tables'));
        exit;
    }
}
if((isset($_GET['page']) && $_GET['page']=='wp-table-add') ||
    (isset($_GET['page']) && $_GET['page']=='wp-tables' && isset($_GET['action']) && $_GET['action']=='edit')
)
{
    echo '<form method="post" class="wp-tables-form">';
    echo '<table class="form-table widefat fixed wp-tables-admin">';
    echo '<thead>';
    echo '<tr><th></th></tr>';
    echo '</thead>';
    echo '<tfoot>';
    echo '<tr><th></th></tr>';
    echo '</tfoot>';
    echo '<tbody>';
    echo '<tr class="wp-tables-row" valign="top">';
    echo '<td>';
    $wp_table_data = $wp_tables->get_table_data();
    include(plugin_dir_path(__FILE__).'/html-add.php');
    echo '</td>';
    echo '</tr>';
    echo '</tbody>';
    echo '</table>';
    echo '</form>';
}
elseif(isset($_GET['page']) && $_GET['page']=='wp-tables' && isset($_GET['action']) && $_GET['action']=='edit-header'){
    echo '<form method="post" class="wp-tables-form">';
    $id = isset($_GET['wp-table']) ? $_GET['wp-table'] : 0;
    $cols = $wp_tables->get_wp_tables_col_order($id);
    echo '<p>'.__('You can re-order the table columns by drag and drop table header using move icon.','wp-tables').'</p>';
    echo '<table data-id="'.$id.'" class="form-table widefat fixed wp-tables-admin wp-tables-admin-edit-header">';
    echo '<thead>';
    echo '<tr>';
    $col_count = 0;
    $col_html = '';
    if(is_array($cols) && count($cols)>0){
        foreach($cols as $col_type=>$col_key){
            if(is_array($col_key) && count($col_key)>0) {
                foreach ($col_key as $column_key => $column) {
                    $col_count++;
                    $col_html.='<th data-id="' . $column_key . '" id="' . key($column) . '"><span class="icon-1311"></span><div class="wp-table-head-text"><span class="p-head-text"> ' .$column[key($column)].'</span><span class="wp-tables-edit-header">'.__('Edit','wp-tables').'</span></div></th>';
                }
            }
        }
    }
    echo $col_html;
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    for($row=0;$row<=7;$row++){
        echo '<tr>';
        for($c=1;$c<=$col_count;$c++){
            echo '<td></td>';
        }
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</form>';
}
elseif(isset($_GET['page']) && $_GET['page']=='wp-tables'){
    echo '<form method="post" action="'.admin_url('admin.php?page=wp-tables').'">';
    include(plugin_dir_path(__FILE__).'/prepare.php');
    $WpListTable = new Wp_Lists_Data_Table_WooExp();
    $WpListTable->prepare_items();
    $WpListTable->display();
    echo '</form>';
}
?>

