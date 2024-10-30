<?php
/**
 * @package MTC_Fortune_Cookies
 * @version 1.0
 */
/*
Plugin Name: MTC Fortune Cookies
Plugin URI: http://www.microtekcorporation.com/wordpress-plugins/mtc-fortune-cookies
Description: Allows creation of fortune cookies that can be added to the sidebar widget
Version: 1.0
Author: Joshua Hyatt - Microtek Corporation
Author URI: http://www.microtekcorporation.com
*/

global $mtc_fortune_cookie_db_version;
$mtc_fortune_cookie_db_version = 1.0;

add_action("admin_menu", "mtc_fortune_cookies_menu");

function mtc_fortune_cookies_print_styles() {
	wp_enqueue_style("thickbox");
}

function mtc_fortune_cookies_print_scripts() {
	wp_enqueue_script("jquery");
	wp_enqueue_script("jquery-form");
	wp_enqueue_script("thickbox");
}

function mtc_fortune_cookies_head() {
?>
<script type="text/javascript">
	var editFortuneCookieId = 0;
	var editFortuneCookie = function(e) {
		editFortuneCookieId = jQuery(this).attr("href").substr(jQuery(this).attr("href").indexOf("#")+1);
		var parentNode = jQuery(this).closest("tr");
		jQuery("#title").val(jQuery(".cookietitle",parentNode).text());
		jQuery("#cookie").val(jQuery(".cookievalue",parentNode).html());
		jQuery("#source").val(jQuery(".cookiesource",parentNode).text());
		jQuery("#url").val(jQuery(".cookieurl",parentNode).text());
		if(jQuery(".cookiedisabled",parentNode).attr("alt") == "Disabled") {
			jQuery("#disabled").attr("checked","checked");
		} else {
			jQuery("#disabled").removeAttr("checked");
		}
		jQuery("#showEditDlg").click();
		e.preventDefault();
	}
	var deleteFortuneCookie = function(e) {
		var cookieid = jQuery(this).attr("href").substr(jQuery(this).attr("href").indexOf("#")+1);
		if(confirm("Are you sure you want to delete " + jQuery(".cookietitle",jQuery(this).closest("tr")).text() + "?")) {
			jQuery("#deletecookieid").val(cookieid);
			jQuery("#deleteForm").submit();
		}
		e.preventDefault();
	}
	jQuery(document).ready(function() {
		jQuery(".editcookie").click(editFortuneCookie);
		jQuery(".deletecookie").click(deleteFortuneCookie);
		jQuery("#formSubmit").click(function(e) {
			jQuery("#cookieid").val(editFortuneCookieId);
			jQuery("#editCookieForm").submit();
		});
	});
</script>
<?php
}

function mtc_fortune_cookies_menu() {
	$page = add_menu_page("Fortune Cookies", "Fortune Cookies", "edit_theme_options", "mtc_fortune_cookies","mtc_fortune_cookies", plugins_url("/mtc-fortune-cookies/book_open.png"));
	add_action("admin_head-$page","mtc_fortune_cookies_head");
	add_action("admin_print_styles-" . $page, "mtc_fortune_cookies_print_styles");
	add_action("admin_print_scripts-" . $page, "mtc_fortune_cookies_print_scripts");
}

function mtc_fortune_cookies() {
	global $wpdb;
	$cookiesperpage = (int)get_user_option("edit_page_per_page");
	if(empty($cookiesperpage) || $cookiesperpage < 1) {
		$cookiesperpage = 20;
	}
	if(isset($_REQUEST["cookie"]) && $_REQUEST["cookie"]) {
		$rows = array(
			"title" => "",
			"cookie" => "",
			"source" => "",
			"url" => "",
			"disabled" => 0
		);
		foreach($rows as $key => $value) {
			if(isset($_POST[$key])) {
				$rows[$key] = stripslashes($_POST[$key]);
			}
		}
		if($_REQUEST["cookieid"]) {
			$wpdb->update($wpdb->prefix . "mtc_fortune_cookies",$rows,array("id" => $_REQUEST["cookieid"]));
		} else {
			$wpdb->insert($wpdb->prefix . "mtc_fortune_cookies",$rows);
		}
	}
	if(isset($_REQUEST["deletecookieid"]) && $_REQUEST["deletecookieid"]) {
		$wpdb->query("DELETE FROM " . $wpdb->prefix . "mtc_fortune_cookies WHERE id=" . intval($_REQUEST["deletecookieid"]));
	}
	$paged = isset($_REQUEST["paged"])?$_REQUEST["paged"]:1;
	$cookiescount = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->prefix . "mtc_fortune_cookies");
	$disabledcookiescount = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->prefix . "mtc_fortune_cookies WHERE disabled = 1");
	switch($_REQUEST["cookie_type"]) {
		case "disabled":
			$where = " WHERE disabled = 1";
			$cookie_type = "disabled";
			break;
		case "enabled":
			$where = " WHERE disabled != 1";
			$cookie_type = "enabled";
			break;
		case "all":
		default:
			$where = "";
			$cookie_type = "all";
	}
	if(isset($_REQUEST["s"]) && $_REQUEST["s"]) {
		if($where) {
			$where .= " AND title LIKE '%" . $wpdb->escape($_REQUEST["s"]) . "%'";
		} else {
			$where = " WHERE title LIKE '%" . $wpdb->escape($_REQUEST["s"]) . "%'";
		}
		$s = $_REQUEST["s"];
	}
	$results = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->prefix . "mtc_fortune_cookies$where");
	$maxpaged = ceil($results/$cookiesperpage);
	if($maxpaged < $paged) {
		$paged = $maxpaged;
	}
	$start = ($paged - 1) * $cookiesperpage;
	$orderby = $wpdb->escape(isset($_REQUEST["orderby"])&&$_REQUEST["orderby"]?$_REQUEST["orderby"]:"title");
	$order = $wpdb->escape(isset($_REQUEST["order"])&&$_REQUEST["order"]?$_REQUEST["order"]:"asc");
	$cookies = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "mtc_fortune_cookies$where ORDER BY $orderby $order LIMIT $start, $cookiesperpage");
	$sorturl = "admin.php?page=mtc_fortune_cookies&amp;cookie_type=$cookie_type&amp;paged=$paged" . ($s?"&amp;s=$s":"");
?>
<div class="wrap">
	<h2>Fortune Cookies <a href="/#TB_inline?height=325&amp;width=450&amp;inlineId=editDlg" class="thickbox add-new-h2">Add New</a></h2>
	<ul class="subsubsub">
		<li class="all"><a href="admin.php?page=mtc_fortune_cookies"<?=$cookie_type=="all"?' class="current"':""?>>All <span class="count">(<?=$cookiescount?>)</span></a></li>
<?php if($cookiescount-$disabledcookiescount) { ?>
		<li class="nonassociate">| <a href="admin.php?page=mtc_fortune_cookies&amp;cookie_type=enabled"<?=$cookie_type=="enabled"?' class="current"':""?>>Enabled <span class="count">(<?=$cookiescount-$disabledcookiescount?>)</span></a></li>
<?php } ?>
<?php if($disabledcookiescount) { ?>
		<li class="disabled">| <a href="admin.php?page=mtc_fortune_cookies&amp;cookie_type=disabled"<?=$cookie_type=="disabled"?' class="current"':""?>>Disabled <span class="count">(<?=$disabledcookiescount?>)</span></a></li>
<?php } ?>
	</ul>
	<form action="admin.php" method="get">
		<input type="hidden" name="page" value="mtc_fortune_cookies" />
		<input type="hidden" name="cookie_type" value="<?=$cookie_type?>" />
		<p class="search-box">
			<label class="screen-reader-text" for="member-search-input">Search Fortune Cookies:</label>
			<input type="text" name="s" value="<?=isset($_REQUEST["s"])?$_REQUEST["s"]:""?>" />
			<input type="submit" class="button" value="Search" />
		</p>
	</form>
	<form class="tablenav top" action="admin.php" method="get">
		<input type="hidden" name="page" value="mtc_fortune_cookies" />
		<input type="hidden" name="cookie_type" value="<?=$cookie_type?>" />
<?php if($s) { ?>
		<input type="hidden" name="s" value="<?=$s?>" />
<?php } ?>
		<div class="tablenav-pages">
			<span class="displaying-num"><?=$results?> item<?=$results!=1?"s":""?></span>
<?php if($maxpaged > 1) { ?>
			<a href="admin.php?page=mtc_fortune_cookies&amp;cookie_type=<?=$cookie_type?><?=$s?"&amp;s=$s":""?>" class="first-page<?=$paged==1?" disabled":""?>" title="Go to the first page">&lt;&lt;</a>
			<a href="admin.php?page=mtc_fortune_cookies&amp;cookie_type=<?=$cookie_type?>&amp;paged=<?=$paged==1?1:$paged-1?><?=$s?"&amp;s=$s":""?>" class="prev-page<?=$paged==1?" disabled":""?>" title="Go to the previous page">&lt;</a>
			<span class="pagin-input">
				<input class="current-page" size="1" name="paged" value="<?=$paged?>" /> of <span class="total-pages"><?=$maxpaged?></span>
			</span>
			<a href="admin.php?page=mtc_fortune_cookies&amp;cookie_type=<?=$cookie_type?>&amp;paged=<?=$paged==$maxpaged?$maxpaged:$paged+1?><?=$s?"&amp;s=$s":""?>" class="next-page<?=$paged==$maxpaged?" disabled":""?>" title="Go to the next page">&gt;</a>
			<a href="admin.php?page=mtc_fortune_cookies&amp;cookie_type=<?=$cookie_type?>&amp;paged=<?=$maxpaged?><?=$s?"&amp;s=$s":""?>" class="last-page<?=$paged==$maxpaged?" disabled":""?>" title="Go to the last page">&gt;&gt;</a>
<?php } ?>
		</div>
	</form>
	<table class="widefat">
		<thead>
			<tr>
				<th style="width: 45%" class="<?=$orderby=="title"?"sorted " . ($order=="asc"?"asc":"desc"):"sortable desc"?>"><a href="<?=$sorturl?>&amp;orderby=title&amp;order=<?=$orderby=="title"?($order=="asc"?"desc":"asc"):"asc"?>"><span>Title</span><span class="sorting-indicator"></span></a></th>
				<th style="width: 30%" class="<?=$orderby=="source"?"sorted " . ($order=="asc"?"asc":"desc"):"sortable desc"?>"><a href="<?=$sorturl?>&amp;orderby=source&amp;order=<?=$orderby=="source"?($order=="asc"?"desc":"asc"):"asc"?>"><span>Byline</span><span class="sorting-indicator"></span></a></th>
				<th style="width: 10%" class="<?=$orderby=="disabled"?"sorted " . ($order=="asc"?"asc":"desc"):"sortable desc"?>"><a href="<?=$sorturl?>&amp;orderby=disabled&amp;order=<?=$orderby=="disabled"?($order=="asc"?"desc":"asc"):"asc"?>"><span>Disabled</span><span class="sorting-indicator"></span></a></th>
				<th>Action</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th class="<?=$orderby=="title"?"sorted " . ($order=="asc"?"asc":"desc"):"sortable desc"?>"><a href="<?=$sorturl?>&amp;orderby=title&amp;order=<?=$orderby=="title"?($order=="asc"?"desc":"asc"):"asc"?>"><span>Title</span><span class="sorting-indicator"></span></a></th>
				<th class="<?=$orderby=="source"?"sorted " . ($order=="asc"?"asc":"desc"):"sortable desc"?>"><a href="<?=$sorturl?>&amp;orderby=source&amp;order=<?=$orderby=="source"?($order=="asc"?"desc":"asc"):"asc"?>"><span>Byline</span><span class="sorting-indicator"></span></a></th>
				<th class="<?=$orderby=="disabled"?"sorted " . ($order=="asc"?"asc":"desc"):"sortable desc"?>"><a href="<?=$sorturl?>&amp;orderby=disabled&amp;order=<?=$orderby=="disabled"?($order=="asc"?"desc":"asc"):"asc"?>"><span>Disabled</span><span class="sorting-indicator"></span></a></th>
				<th>Action</th>
			</tr>
		</tfoot>
		<tbody>
<?php foreach($cookies as $cookie) { ?>
			<tr>
				<td class="cookietitle"><?=$cookie->title?></td>
				<td class="cookiesource"><?=$cookie->source?></td>
				<td><img class="cookiedisabled" src="<?=plugins_url("/mtc-fortune-cookies/" . ($cookie->disabled?"accept":"delete") . ".png")?>" alt="<?=$cookie->disabled?"Disabled":"Enabled"?>" title="<?=$cookie->disabled?"Disabled":"Enabled"?>" /></td>
				<td>
					<div style="display: none">
						<div class="cookievalue"><?=$cookie->cookie?></div>
						<div class="cookieurl"><?=$cookie->url?></div>
					</div>
					<a href="#<?=$cookie->id?>" class="button editcookie">Edit</a>
					<a href="#<?=$cookie->id?>" class="button deletecookie">Delete</a>
				</td>
			</tr>
<?php } ?>
		</tbody>
	</table>
	<div class="tablenav bottom">
		<div class="tablenav-pages">
			<span class="displaying-num"><?=$results?> item<?=$results!=1?"s":""?></span>
<?php if($maxpaged > 1) { ?>
			<a href="admin.php?page=mtc_fortune_cookies&amp;cookie_type=<?=$cookie_type?><?=$s?"&amp;s=$s":""?>" class="first-page<?=$paged==1?" disabled":""?>" title="Go to the first page">&lt;&lt;</a>
			<a href="admin.php?page=mtc_fortune_cookies&amp;cookie_type=<?=$cookie_type?>&amp;paged=<?=$paged==1?1:$paged-1?><?=$s?"&amp;s=$s":""?>" class="prev-page<?=$paged==1?" disabled":""?>" title="Go to the previous page">&lt;</a>
			<span class="pagin-input">
				<?=$paged?> of <span class="total-pages"><?=$maxpaged?></span>
			</span>
			<a href="admin.php?page=mtc_fortune_cookies&amp;cookie_type=<?=$cookie_type?>&amp;paged=<?=$paged==$maxpaged?$maxpaged:$paged+1?><?=$s?"&amp;s=$s":""?>" class="next-page<?=$paged==$maxpaged?" disabled":""?>" title="Go to the next page">&gt;</a>
			<a href="admin.php?page=mtc_fortune_cookies&amp;cookie_type=<?=$cookie_type?>&amp;paged=<?=$maxpaged?><?=$s?"&amp;s=$s":""?>" class="last-page<?=$paged==$maxpaged?" disabled":""?>" title="Go to the last page">&gt;&gt;</a>
<?php } ?>
		</div>
	</div>
</div>
<a href="/#TB_inline?height=325&amp;width=450&amp;inlineId=editDlg" class="thickbox" id="showEditDlg" style="display: none">Edit Image</a>
<div id="editDlg" style="display: none;">
	<form id="editCookieForm" action="admin.php?page=mtc_fortune_cookies" method="post">
		<input type="hidden" id="cookieid" name="cookieid" value="0" />
		<input type="hidden" name="paged" value="<?=$paged?>" />
		<input type="hidden" name="cookie_type" value="<?=$cookie_type?>" />
		<table class="describe">
			<tr>
				<td class="label"><label for="title">Title:</label></td>
				<td class="field"><input type="text" id="title" name="title" /></td>
			</tr>
			<tr>
				<td class="label"><label for="cookie">Cookie:</label></td>
				<td class="field"><textarea id="cookie" name="cookie" rows="10" cols="50"></textarea></td>
			</tr>
			<tr>
				<td class="label"><label for="source">Author:</label></td>
				<td class="field"><input type="text" id="source" name="source" /></td>
			</tr>
			<tr>
				<td class="label"><label for="url">Url:</label></td>
				<td class="field"><input type="url" id="url" name="url" /></td>
			</tr>
			<tr>
				<td class="label"><label for="disabled">Disabled:</label></td>
				<td class="field"><input type="checkbox" id="disabled" name="disabled" value="1" /></td>
			</tr>
		</table>
		<input type="button" id="formSubmit" class="button" value="Save" />
		<input type="reset" class="button" value="Close" onclick="tb_remove();editid = 0;" />
	</form>
</div>
<form id="deleteForm" action="admin.php?page=mtc_fortune_cookies" method="post" style="display: none">
	<input type="hidden" name="deletecookieid" id="deletecookieid" value="0" />
	<input type="hidden" name="paged" value="<?=$paged?>" />
	<input type="hidden" name="cookie_type" value="<?=$cookie_type?>" />
</form>
<?php
}


class mtc_fortune_cookie_widget extends WP_Widget {
	function mtc_fortune_cookie_widget() {
		parent::WP_Widget("mtc_fortune_cookie_widget", "Fortune Cookie", array("description" => "Displays a random fortune cookie"));
	}
	
	function widget($args, $instance) {
		global $wpdb;
		$cookie = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "mtc_fortune_cookies WHERE disabled != 1 ORDER BY RAND() LIMIT 1");
?>
<?=$before_widget?>
	<?=$before_title?><?=$cookie->title?><?=$after->title?>
	<div class="cookie"><?=$cookie->cookie?></div>
<?php if($cookie->source) { ?>
	<div class="source"><?=$cookie->source?></div>
<?php } ?>
<?php if($cookie->url) { ?>
	<div class="url"><a href="<?=$cookie->url?>" target="_blank">More Information...</a></div>
<?php } ?>
<?=$after_widget?>
<?php
	}
}

add_action("widgets_init", "mtc_fortune_cookie_register_widgets");

function mtc_fortune_cookie_register_widgets() {
	register_widget("mtc_fortune_cookie_widget");
}

function mtc_fortune_cookie_install() {
	global $wpdb, $mtc_fortune_cookie_db_version;
	
	$sql = "CREATE TABLE " . $wpdb->prefix . "mtc_fortune_cookies (
		id int unsigned NOT NULL AUTO_INCREMENT,
		title varchar(255),
		cookie mediumtext,
		source varchar(255),
		url varchar(255),
		disabled int(1) DEFAULT 0,
		UNIQUE KEY id (id)
	);";
	
	require_once(ABSPATH . "wp-admin/includes/upgrade.php");
	dbDelta($sql);
	
	add_option("mtc_fortune_cookie_db_version", $mtc_fortune_cookie_db_version);
}

register_activation_hook(__FILE__,"mtc_fortune_cookie_install");