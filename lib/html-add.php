<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="wp-table-add-inner">
    <table class="form-table widefat">
        <tr valign="top">
            <td>
                <label><?php echo __('Title','wp-tables'); ?>:</label>
                <input type="text" name="wp-tables-title" value="<?php echo isset($wp_table_data['title']) ? $wp_table_data['title'] : ''; ?>" id="wp-tables-title">
            </td>
        </tr>
        <tr valign="top">
            <td>
                <label><?php echo __('Select post type','wp-tables'); ?>:</label>
                <select name="wp-tables-post-types" class="wp-tables-field-post-type">
                    <option value="0"><?php echo __('Select post type','wp-tables'); ?></option>
                    <?php
                    $post_types = get_post_types(array('public'=>true));
                    foreach($post_types as $type){
                        $typeobj = get_post_type_object($type);
                        echo '<option value="'.$typeobj->name.'" '.selected($wp_table_data['table_data']['post_type'],$typeobj->name).'>'.$typeobj->label.'</option>';
                    }
                    ?>
                </select>
            </td>
        </tr>
        <tr valign="top">
            <td>
                <label><?php echo __('Select items per page','wp-tables'); ?>:</label>
                <select name="wp-tables-items-per-page" class="wp-tables-items-per-page">
                    <option value="0"><?php echo __('Select items per page','wp-tables'); ?></option>
                    <?php
                    for($item=1;$item<=60;$item++){
                        echo '<option value="'.$item.'" '.selected($wp_table_data['table_data']['posts_per_page'],$item).'>'.$item.'</option>';
                    }
                    ?>
                </select>
            </td>
        </tr>
        <tr valign="top">
            <td>
                <label><?php echo __('Order By','wp-tables'); ?>:</label>
                <select name="wp-tables-items-orderby" class="wp-tables-items-orderby">
                    <option value="0"><?php echo __('Order By','wp-tables'); ?></option>
                    <?php
                        echo '<option value="none" '.selected($wp_table_data['table_data']['orderby'],'none').'>'.__('None','wp-tables').'</option>';
                        echo '<option value="ID" '.selected($wp_table_data['table_data']['orderby'],'ID').'>'.__('ID','wp-tables').'</option>';
                        echo '<option value="post_date" '.selected($wp_table_data['table_data']['orderby'],'post_date').'>'.__('Post Date','wp-tables').'</option>';
                        echo '<option value="post_title" '.selected($wp_table_data['table_data']['orderby'],'post_title').'>'.__('Post Title','wp-tables').'</option>';
                        echo '<option value="rand" '.selected($wp_table_data['table_data']['orderby'],'rand').'>'.__('Random','wp-tables').'</option>';
                    ?>
                </select>
            </td>
        </tr>
        <tr valign="top">
            <td>
                <label><?php echo __('Select Order','wp-tables'); ?>:</label>
                <select name="wp-tables-items-order" class="wp-tables-items-order">
                    <option value="0"><?php echo __('Select Order','wp-tables'); ?></option>
                    <?php
                    echo '<option value="ASC" '.selected($wp_table_data['table_data']['order'],'ASC').'>'.__('ASC','wp-tables').'</option>';
                    echo '<option value="DESC" '.selected($wp_table_data['table_data']['order'],'DESC').'>'.__('DESC','wp-tables').'</option>';
                    ?>
                </select>
            </td>
        </tr>
        <tr valign="top">
            <td>
                <label><?php echo __('Select post columns','wp-tables'); ?>:</label>
                <?php
                $i = 0;
                $post_columns = $wp_tables->get_post_columns();
                if(is_array($post_columns) && count($post_columns)>0){
                    echo '<div class="wp-tables-meta"><ul>';
                    foreach($post_columns as $col){
                        if($i>0 && $i % 10 == 0){
                            echo "</ul><ul>";
                        }
                        $checked = isset($wp_table_data['post_keys']) && is_array($wp_table_data['post_keys']) && array_key_exists($col->COLUMN_NAME,$wp_table_data['post_keys']) ? ' checked="checked" ' : '';
                        echo '<li><input name="post_keys" type="checkbox" value="'.$col->COLUMN_NAME.'" '.$checked.' class="wp-tables-field-post-columns"><span>'.$col->COLUMN_NAME.'</span></li>';
                        $i++;
                    }
                    echo '</ul></div>';
                }
                ?>
            </td>
        </tr>
        <tr valign="top">
            <td>
                <label><?php echo __('Select meta columns','wp-tables'); ?>:</label>
                <?php
                $i = 0;
                $meta_keys = $wp_tables->get_meta_columns();
                if(is_array($post_columns) && count($post_columns)>0){
                    echo '<div class="wp-tables-meta"><ul>';
                    foreach($meta_keys as $mcol){
                        if($i>0 && $i % 10 == 0){
                            echo "</ul><ul>";
                        }
                        $checked = isset($wp_table_data['meta_keys']) && is_array($wp_table_data['meta_keys']) && array_key_exists($mcol,$wp_table_data['meta_keys']) ? ' checked="checked" ' : '';
                        echo '<li><input name="meta_keys" type="checkbox" value="'.$mcol.'" '.$checked.' class="wp-tables-field-meta-columns"><span>'.$mcol.'</span></li>';
                        $i++;
                    }
                    echo '</ul></div>';
                }
                //echo '<pre>'; print_r($post_columns); echo '</pre>';
                ?>
            </td>
        </tr>
        <tr valign="top">
            <td>
                <input type="hidden" name="_wp_tables_page" id="_wp_tables_page" value="<?php echo $wp_tables->get_wp_tables_id(); ?>">
                <a href="javascript:void(0);" class="button-primary wp-tables-save"><?php echo __('Save','wp-tables'); ?></a>
            </td>
        </tr>
    </table>
</div>