<?php
// The source code packaged with this file is Free Software, Copyright (C) 2005 by
// Ricardo Galli <gallir at uib dot es>.
// It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
// You can get copies of the licenses here:
//				http://www.affero.org/oagpl.html
// AFFERO GENERAL PUBLIC LICENSE is also included in the file called "COPYING".

include('config.php');
include(mnminclude.'html1.php');

if (empty($routes)) die; // Don't allow to be called bypassing dispatcher

if (isset($_GET['all']) || array_key_exists('q', (array)$_GET)) {
	$option = 2; // Show all subs
} elseif (! $current_user->user_id || isset($_GET['active']))  {
	$option = 1; // Show active
} elseif (isset($_GET['subscribed'])) {
	$option = 0; // Show suscribed
} else {
	$option = count(SitesMgr::get_subscriptions($current_user->user_id)) > 0 ? 0 : 1;
}

if (!empty($_GET['q'])) {
	$q = trim(preg_replace('/[^a-zA-Zá-úÁ-ÚñÑ0-9\s]/', '', strip_tags($_GET['q'])));
} else {
	$q = null;
}

if ($q) {
	$option = 2;
}

$char_selected = $chars = false; // User for index by first letter

do_header(_("subs menéame"), 'm/');

echo '<div id="sidebar" class="sidebar-with-section">';
	do_banner_right();
	do_banner_promotions();
echo '</div>';

switch ($option) {
	case 0:
		$subs = SitesMgr::get_subscriptions($current_user->user_id);
		break;
	case 1:
		$sql = "select subs.*, user_id, user_login, user_avatar, count(*) as c from subs LEFT JOIN users ON (user_id = owner), sub_statuses where date > date_sub(now(), interval 5 day) and subs.id = sub_statuses.id and sub_statuses.id = sub_statuses.origen and sub_statuses.status = 'published' and subs.sub = 1 group by subs.id order by c desc limit 50";
		$subs = $db->get_results($sql);
		break;
	default:
		$chars = $db->get_col('SELECT DISTINCT(LEFT(UCASE(name), 1)) FROM subs');

		if (!$q && !empty($_GET['c'])) {
			$char_selected = preg_replace('/[^A-Z]/', '', substr($_GET['c'], 0, 1));
		}

		if ($q) {
			$q_sql = '%'.str_replace(' ', '%', $q).'%';
			$extra = '(subs.name LIKE "'.$q_sql.'" OR subs.name_long LIKE "'.$q_sql.'") AND ';
		} elseif ($char_selected) {
			$extra = 'subs.name LIKE "'.$char_selected.'%" AND ';
		}

		$rows = $db->get_var('SELECT COUNT(*) FROM subs WHERE '.$extra.' subs.sub = 1 AND created_from = '.SitesMgr::my_id());

		$page_size = 20;
		$page = get_current_page();
		$offset=($page-1)*$page_size;

		$sql = "select subs.*, user_id, user_login, user_avatar from subs, users where $extra subs.sub = 1 and created_from = ".SitesMgr::my_id()." and user_id = owner order by name asc limit $offset, $page_size";
		$subs = $db->get_results($sql);
}

$all_subs = $db->get_results($sql);
$subs_followers_counter = $db->get_results("select subs.id, count(*) as c from subs, prefs where pref_key = 'sub_follow' and subs.id = pref_value group by subs.id order by c desc;");
$subs = array();

foreach ($all_subs as $s) {
	foreach ($subs_followers_counter as $sub_counter) {
		if ($s->id == $sub_counter->id) {
			$s->followers = $sub_counter->c;
		}
	}
	if (!isset($s->followers)) $s->followers=0;
	if ($s->enabled) {
		$subs[] = $s;
	}
}

Haanga::Load('subs.html', compact(
	'title', 'subs', 'chars', 'char_selected', 'option', 'rows', 'page_size', 'q'
));

do_footer();
