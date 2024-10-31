<?php
/*
Plugin Name: Recent Posts on Editor
Plugin URI: http://takien.com/
Description: Displaying recent contents (post or page) on WordPress editor.
Author: Takien
Version: 0.1
Author URI: http://takien.com/
*/
defined('ABSPATH') or die();

add_action( 'admin_footer','rpoe_footer' );
add_action( 'admin_head', 'rpoe_head' );
add_action( 'wp_ajax_rpoe_ajax', 'rpoe_ajax_callback' );
add_action( 'add_meta_boxes', 'rpoe_add_metabox' );
function rpoe_head(){ 
?>
	<style type="text/css">
	.rpoe-meta-wrap div.tabs-panel{
		min-height: 42px;
		max-height: 200px;
		overflow: auto;
		padding: 0.9em;
		border-style: solid;
		border-width: 1px;
	}
	.rpoe-meta-list li {
		border-bottom:1px solid #e6e6e6;
		margin:0;
		padding:3px;
	}
	.rpoe-meta-list li:hover {
		background:#f0f0f0
	}
	.rpoe-meta-list li a{
		color:rgb(51, 51, 51);
		text-decoration:none;
	}
	.rpoe-meta-list .list-action {
		display:none;
		font-size:11px;
	}
	.rpoe-meta-list .list-action a {
		color: rgb(33, 117, 155);
		padding:0 10px 0 0;
		display:inline-block;
	}




	</style>
		<?php	
	}
function rpoe_footer() {
	global $post_type;
	if( ('post' == $post_type ) OR ('page' == $post_type ) ) {
	?>
	<script type="text/javascript">
		jQuery(document).ready(function($){
			
			$('.rpoe-meta-post-tab a').click(function(e){
				var target_tab = $(this).attr('href');
				var target_content = $(target_tab).find('.recent-post-result');
				
				$(this).parent().siblings().removeClass('tabs');
				$(this).parent().addClass('tabs');
				 var data = {
					args : { 
						post_type : $(this).data().post_type
					}
				}
				
				get_recent_post(data,target_content,true);
				
				$(target_tab).siblings('.tabs-panel').hide();
				$(target_tab).show();
				
				e.preventDefault();
			});
			
			function get_recent_post(data,target,if_empty) {
				data['action'] = 'rpoe_ajax';
				$.get(ajaxurl,data,function(response){
					if(if_empty) {
						if(target.html() == '') {
							target.html(response);
						}
					}
					else {
						target.html(response);
					}
				});
			}
			
			$('#tabs-recent li.tabs a').trigger('click');
			
			
			$('.rpoe-meta-list .list-action').hide();
			$('body').on('mouseenter','.rpoe-meta-list li',function() {
				$(this).find('.list-action').show();
			});
			$('body').on('mouseleave','.rpoe-meta-list li',function() {
				$(this).find('.list-action').hide();
			});
			$('body').on('click','.insert_this_link',function(e) {
				var content = $(this).parent().siblings('.link-to-insert').html();
				tinyMCE.activeEditor.execCommand('mceInsertContent', false, content);
				e.preventDefault();
			});
			
			$('.rpoe-meta-post-search').keyup(function() {
				
				var post_type = $(this).data().post_type;
				var thisbox = $('#tabs-recent-'+post_type);
				var s = $(this).val();
				var target_content = thisbox.find('.recent-post-result');
				var post_status    = thisbox.find('.rpoe-meta-post-status').val();
				var data = {
					args : { 
						post_type  : post_type,
						s          : s,
						post_status: post_status
					}
				}
				if(s.length > 2) {
				   get_recent_post(data,target_content,false);
				}
			});
			
			$('.rpoe-meta-post-status').change(function() {
				var post_type = $('.rpoe-meta-post-search').data().post_type;
				var thisbox = $('#tabs-recent-'+post_type);
				var s = $('.rpoe-meta-post-search').val();
				var target_content = thisbox.find('.recent-post-result');
				var post_status    = thisbox.find('.rpoe-meta-post-status').val();
				//post_status
				//alert(target_content.html());
				var data = {
					args : { 
						post_type  : post_type,
						s          : s,
						post_status: post_status
					}
				}
				get_recent_post(data,target_content,false);
				
			});
		
	});
	</script><?php
}
}


function rpoe_add_metabox() {
    $screens = array( 'post','page' );
    foreach ($screens as $screen) {
        add_meta_box(
            'recent-posts-on-editor',
            __( 'Recent Posts on Editor', 'recent-posts-on-editor' ),
            'rpoe_html',
            $screen,
			'side', //position
			'high' //core/high/low/default priority
        );
    }
}


function rpoe_html( $post ) {
	?>
	<div class="rpoe-meta-wrap">
		<ul id="tabs-recent" class="rpoe-meta-post-tab category-tabs">
			<li class="tabs"><a href="#tabs-recent-post" data-post_type="post">Post</a></li>
			<li class=""><a href="#tabs-recent-page" data-post_type="page">Page</a></li>
		</ul>
		<div id="tabs-recent-post" class="tabs-panel">
			<div class="recent-post-filter">
				<input style="width:100px" type="text" data-post_type="post" value="" name="rpoe-meta-post-search-query" placeholder="search..." class="rpoe-meta-post-search"/>
				<select class="rpoe-meta-post-status" name="post_status">
					<option>publish</option> 
					<option>pending</option> 
					<option>draft  </option> 
					<option>auto  </option>  
					<option>future </option> 
					<option>private</option> 
					<option>inherit</option> 
					<option>trash</option> 
					<option value="any">all</option>   
				</select>
			</div>
			<div class="recent-post-result"></div>
		</div><!--tabs panel-->
		<div id="tabs-recent-page" class="tabs-panel" style="display:none">
			<div class="recent-post-filter"></div>
			<div class="recent-post-result"></div>
		</div><!--tabs panel-->
		
	</div><!--categorydiv-->
	<?php
}


function rpoe_ajax_callback() {
	$args = $_REQUEST['args'];
	$defaults = array(
		'post_type'      => 'post',
		'posts_per_page' => 10,
		'post_status'    => 'publish',
		'orderby'        => 'ID',
		's'              => ''
	);
	$args = wp_parse_args( $args, $defaults );
	$query = new WP_Query($args);
	if($query->have_posts()) :
	?>
	<ul class="rpoe-meta-list">
	<?php
	while($query->have_posts()) : $query->the_post();
		echo '<li><div class="link-to-insert">
			<a href="'.get_permalink().'" title="'.the_title_attribute('echo=0').'">'.get_the_title().'</a></div>
		<div class="list-action"><a class="insert_this_link" href="#">Insert</a> <a href="'.get_edit_post_link(get_the_ID()).'">Edit</a> <a target="_blank" href="'.get_permalink().'">View</a></div>
		</li>';
	endwhile;
	?>
	
	</ul>
	<?php
	else :
		
	endif;
	exit;
}