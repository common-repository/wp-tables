jQuery(function($){
    var xhr = [];
    var wp_tables = {
        init: function () {
            $('.wp-tables-wrap').on('click','.wp-tables-save',{view:this},this.SaveWpTables);
            $('.wp-tables-wrap').on('click','.wp-tables-edit-header',{view:this},this.EditWpTablesHead);
            $('.wp-tables-wrap').on('click','.wp-table-head-save',{view:this},this.SaveWpTablesHead);
            $('table.wp-tables-admin-edit-header').dragtable({
                dragHandle:'.icon-1311',
                persistState: function(table) {
                    var cols = {};
                    $('.wp-tables-wrap').find('.wp-tables-notice').remove();
                    table.el.find('th').each(function(i) {
                        if(this.id != '') {
                            //table.sortOrder[this.id]=i;
                            cols[i]={};
                            cols[i][$(this).data('id')] = {};
                            cols[i][$(this).data('id')][this.id] = $(this).find('.wp-table-head-text > .p-head-text').text();
                        }
                    });
                    var id = table.el.data('id');
                    wp_tables.InitAjax(
                        {
                            'wp-tables-data':{
                                'id': id,
                                'cols': cols
                            },
                            'case':'wp-tables-save_order'
                        }
                    );
                }
            });
        },
        SaveWpTables:function(event){
            event.preventDefault();
            $('.wp-tables-wrap').find('div.notice').remove();
            var wp_tables_err = false;
            var title =  $(this).closest(".wp-tables-row").find('#wp-tables-title').val();
            var posttype =  $(this).closest(".wp-tables-row").find('select.wp-tables-field-post-type').val();
            var item_per_page =  $(this).closest(".wp-tables-row").find('select.wp-tables-items-per-page').val();
            var item_orderby =  $(this).closest(".wp-tables-row").find('select.wp-tables-items-orderby').val();
            var item_order =  $(this).closest(".wp-tables-row").find('select.wp-tables-items-order').val();
            var id =  $(this).closest(".wp-tables-row").find('#_wp_tables_page').val();

            var postcolumns = [];
            var metacolumns = [];
            $(this).closest(".wp-tables-row").find("input.wp-tables-field-post-columns:checked").each(function(){
                postcolumns.push($(this).val());
            });
            $(this).closest(".wp-tables-row").find("input.wp-tables-field-meta-columns:checked").each(function(){
                metacolumns.push($(this).val());
            });

            if(title==''){
                var html = '<div class="notice is-dismissible notice-error"><p>'+wp_tables_js.title_err+'</p></div>';
                wp_tables_err = true;
            }
            else if(posttype==0 && !wp_tables_err){
                var html = '<div class="notice is-dismissible notice-error"><p>'+wp_tables_js.postype_err+'</p></div>';
                wp_tables_err = true;
            }
            else if(item_per_page==0 && !wp_tables_err){
                var html = '<div class="notice is-dismissible notice-error"><p>'+wp_tables_js.per_page_err+'</p></div>';
                wp_tables_err = true;
            }
            else if(item_orderby==0 && !wp_tables_err){
                var html = '<div class="notice is-dismissible notice-error"><p>'+wp_tables_js.orderby_err+'</p></div>';
                wp_tables_err = true;
            }
            else if(item_order==0 && !wp_tables_err){
                var html = '<div class="notice is-dismissible notice-error"><p>'+wp_tables_js.order_err+'</p></div>';
                wp_tables_err = true;
            }
            else if(postcolumns.length==0 && !wp_tables_err){
                var html = '<div class="notice is-dismissible notice-error"><p>'+wp_tables_js.post_cols_err+'</p></div>';
                wp_tables_err = true;
            }

            if(wp_tables_err){
                $("h1.wp-tables-heading-inline").after(html);
                $('html,body').animate({scrollTop:'0px'},500);
                return false;
            }
            $('.wp-tables-wrap').find('div.notice').remove();
            wp_tables.InitAjax(
                {
                    'wp-tables-data':{
                        'title': title,
                        'post_type': posttype,
                        'post_cols': postcolumns,
                        'meta_cols': metacolumns,
                        'per_page': item_per_page,
                        'orderby': item_orderby,
                        'order': item_order,
                        'id': id
                    },
                    'case':'save-wp-tables'
                }
            );
        },
        InitAjax:function(wzs_data){

            var index = $('.wp-tables-form').index(this);
            if(xhr[index]){
                xhr[index].abort();
            }
            wp_tables.WpTablesBlock();
            xhr[index] = $.ajax({
                type	: "POST",
                cache	: false,
                url     : wp_tables_js.ajax_url,
                dataType : 'json',
                data: {
                    'action':'wp-tables-admin',
                    'data': wzs_data
                },
                success: function(res){
                    if(res.msg!=''){
                        var html = '<div class="notice is-dismissible wp-tables-notice '+res.class+'"><p>'+res.msg+'</p></div>';
                        $("h1.wp-tables-heading-inline").after(html);
                        $('html,body').animate({scrollTop:'0px'},500);
                    }
                    if(res.reload){
                        $(document.body).find('#wp-tables-title').val('');
                        $(document.body).find('select.wp-tables-field-post-type').prop('selectedIndex',0);
                        $(document.body).find('select.wp-tables-items-per-page').prop('selectedIndex',0);
                        $(document.body).find('select.wp-tables-items-orderby').prop('selectedIndex',0);
                        $(document.body).find('select.wp-tables-items-order').prop('selectedIndex',0);
                        $(document.body).find("input.wp-tables-field-post-columns:checked").each(function(){
                            $(this).prop('checked',false);
                        });
                        $(document.body).find("input.wp-tables-field-meta-columns:checked").each(function(){
                            $(this).prop('checked', false);
                        });
                    }
                    wp_tables.WpTablesUnBlockUi();
                }
            });
        },
        EditWpTablesHead:function(event){
            event.preventDefault();
            var text = $(this).closest('.wp-table-head-text').find('.p-head-text').text().trim();
            $(this).closest('.wp-table-head-text').addClass('edit-mode');
            $(this).closest('.wp-table-head-text').html('<input data-text="'+text+'" type="text" value="'+text+'"><a href="javascript:void(0);" class="wp-table-head-save">'+wp_tables_js.save_text+'</a>');
        },
        SaveWpTablesHead:function(event){
            event.preventDefault();
            $this = $(this);

            var cols = {};
            $('.wp-tables-wrap').find('.wp-tables-notice').remove();
            $('table.wp-tables-admin-edit-header').find('th').each(function(i) {
                if(this.id != '') {
                    cols[i]={};
                    cols[i][$(this).data('id')] = {};
                    if($(this).find('.wp-table-head-text').hasClass('edit-mode')){
                        cols[i][$(this).data('id')][this.id] = $(this).find('.wp-table-head-text > input[type="text"]').val();
                    }
                    else
                    {
                        cols[i][$(this).data('id')][this.id] = $(this).find('.wp-table-head-text > .p-head-text').text();
                    }
                }
            });
            var col_text = $this.closest(".edit-mode").find('input[type="text"]').val();
            var col_html = '<span class="p-head-text">'+col_text+'</span><span class="wp-tables-edit-header">'+wp_tables_js.edit_text+'</span>';
            $this.closest(".wp-table-head-text").removeClass('edit-mode').html(col_html);

            var id = $('table.wp-tables-admin-edit-header').data('id');
            wp_tables.InitAjax(
                {
                    'wp-tables-data':{
                        'id': id,
                        'cols': cols
                    },
                    'case':'wp-tables-save_order'
                }
            );
        },
        WpTablesBlock:function(){
            $('table.wp-tables-admin').block({ message: '',overlayCSS: { backgroundColor: '#ddd'},css: {'border':'none','background':'none'}  });
        },
        WpTablesUnBlockUi:function(){
            $('table.wp-tables-admin').unblock();
        }
    };
    wp_tables.init();
});