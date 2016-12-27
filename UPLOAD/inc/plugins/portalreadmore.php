<?php

/*
PortalReadMore Button Plugin v 1.3 for MyBB
Copyright (C) 2015 SvePu

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

function portalreadmore_info()
{
	global $plugins_cache, $mybb, $db;
	
	$info = array
	(
		"name"			=>	"Portal ReadMore Button",
		"description"	=>	"Adds a button to portal announcements to collapse long posts.<br />This plugin uses the readmore.js script by <a href=\"https://github.com/jedfoster/Readmore.js\" target=\"_blank\">Jed Foster</a>.",
		"website"		=>	"https://github.com/SvePu/PortalReadMore",
		"author"		=>	"SvePu",
		"authorsite"	=> 	"https://github.com/SvePu",
		"codename"		=>	"portalreadmore",
		"version"		=>	"1.3",
		"compatibility"	=>	"18*"
	);
	
	$info_desc = '';
	$result = $db->simple_select('settinggroups', 'gid, title', "name = 'portal'", array('limit' => 1));
	$settings_group = $db->fetch_array($result);
	if(!empty($settings_group['gid']) && is_array($plugins_cache) && is_array($plugins_cache['active']) && $plugins_cache['active']['portalreadmore'])
	{
		$info_desc .= "<span style=\"font-size: 0.9em;\">(--<a href=\"index.php?module=config-settings&action=change&gid=".$settings_group['gid']."\"> ".$settings_group['title']." </a>--)</span>";
		$info_desc .= '<form action="https://www.paypal.com/cgi-bin/webscr" method="post" style="float: right;" target="_blank" />
<input type="hidden" name="cmd" value="_s-xclick" />
<input type="hidden" name="hosted_button_id" value="VGQ4ZDT8M7WS2" />
<input type="image" src="https://www.paypalobjects.com/webstatic/en_US/btn/btn_donate_pp_142x27.png" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!" />
<img alt="" border="0" src="https://www.paypalobjects.com/de_DE/i/scr/pixel.gif" width="1" height="1" />
</form>';
	}
	
	if($info_desc != '')
	{
		$info['description'] = $info_desc.'<br />'.$info['description'];
	}
    
    return $info;
}

function portalreadmore_activate()
{
	global $db;
	
	$query = $db->simple_select("settinggroups", "gid", "name='portal'");
	$gid = $db->fetch_field($query, "gid");
	$query_add = $db->simple_select("settings", "COUNT(*) as rows", "gid='{$gid}'");
	$rows = $db->fetch_field($query_add, "rows");
	
	$setting = array(
		'name'		=> 'portalreadmore_enable',
		'title'		=> '"Read More" Button in Portal Announcements',
		'description'=> 'Enable "Read More" Button in Portal Announcements?',
		'optionscode'=> 'yesno',
		'value'		=> '1',
		'disporder'	=> $rows+1,
		'gid'		=> intval($gid)
	);
	$db->insert_query('settings',$setting);
	
	$setting = array(
		'name'		=> 'portalreadmore_height',
		'title'		=> '"Read More" Minimum Height',
		'description'=> 'Set min height for closed view (default: 150)',
		'optionscode'=> 'numeric',
		'value'		=> '150',
		'disporder'	=> $rows+2,
		'gid'		=> intval($gid)
	);
	$db->insert_query('settings',$setting);	
	
	$setting = array(
		'name'		=> 'portalreadmore_speed',
		'title'		=> '"Read More" Opening Speed',
		'description'=> 'Set the opening and closing speed in ms (1s = 1000ms - default: 100)',
		'optionscode'=> 'numeric',
		'value'		=> '100',
		'disporder'	=> $rows+3,
		'gid'		=> intval($gid)
	);
	$db->insert_query('settings',$setting);

	rebuild_settings();
	
	require_once MYBB_ROOT."inc/adminfunctions_templates.php";
	find_replace_templatesets("headerinclude", '#{\$stylesheets}(\r?)\n#', "{\$stylesheets}\n{\$portalreadmore_css}\n");
    find_replace_templatesets("portal", '#{\$footer}(\r?)\n#', "{\$footer}\n{\$portalreadmore}\n");
	find_replace_templatesets("portal_announcement", "#".preg_quote('<td class="trow1 scaleimages">')."#i", "<td class=\"trow1 scaleimages\">\n\t\t{\$prm_pa_start}");
	find_replace_templatesets("portal_announcement", "#".preg_quote('{$post[\'attachments\']}')."#i", "{\$post['attachments']}\n\t\t{\$prm_pa_end}");
	
}

function portalreadmore_deactivate()
{
	global $db;
	
	$settingsarray = array(
        "portalreadmore_enable",
		"portalreadmore_height",
		"portalreadmore_speed"
    );
	$delsettings = implode("','", $settingsarray);
	$db->delete_query("settings", "name in ('{$delsettings}')");
    
	rebuild_settings();
	
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("headerinclude", '#{\$portalreadmore_css}(\r?)\n#', "", 0);
    find_replace_templatesets("portal", '#{\$portalreadmore}(\r?)\n#', "", 0);
	find_replace_templatesets("portal_announcement", '#\n\t\t{\$prm_pa_start}(\r?)#', "", 0);
	find_replace_templatesets("portal_announcement", '#\n\t\t{\$prm_pa_end}(\r?)#', "", 0);
}

function portalreadmore_css()
{
	global $mybb, $portalreadmore_css;	
	
	$portalreadmore_css = "";
	if(my_strpos($_SERVER['PHP_SELF'], 'portal.php'))
	{
		$mybb->settings['portalreadmore_height'] = $mybb->settings['portalreadmore_height'] > 0 ? $mybb->settings['portalreadmore_height'] : 150;
		$portalreadmore_css = "<style type=\"text/css\">
	div.readmore{max-height:".$mybb->settings['portalreadmore_height']."px;overflow:hidden;}
	div[data-readmore]{display: block;width: 100%;}
	div[data-readmore-toggle]{margin: 5px 0-2.5em 0;border-top: 1px solid #ccc;}
	div[data-readmore-toggle] a.prm_open,div[data-readmore-toggle] a.prm_close{margin-top: 10px;}
	div[data-readmore-toggle] a.prm_open span{background-position: 0 -200px;}
	div[data-readmore-toggle] a.prm_close span{background-position: 0 -180px;}
</style>";
	}
}
$plugins->add_hook("global_start", "portalreadmore_css");

function portalreadmore_announcement()
{
	global $mybb, $announcement, $prm_pa_start, $prm_pa_end;	
	$prm_pa_start = '';
	$prm_pa_end = '';
	if ($mybb->settings['portalreadmore_enable'] != 0)
	{
		$prm_pa_start = '<div class="readmore" id="content_tid'.$announcement['tid'].'">';
		$prm_pa_end = '</div>';
	}
}
$plugins->add_hook("portal_announcement", "portalreadmore_announcement");

function portalreadmore_run()
{
	global $mybb, $lang, $portalreadmore;
	$lang->load('portalreadmore');
	$portalreadmore = '';
	$mybb->settings['portalreadmore_height'] = $mybb->settings['portalreadmore_height'] > 0 ? $mybb->settings['portalreadmore_height'] : 150;
	$mybb->settings['portalreadmore_speed'] = $mybb->settings['portalreadmore_speed'] > 0 ? $mybb->settings['portalreadmore_speed'] : 100;
	if ($mybb->settings['portalreadmore_enable'] != 0)
	{
		$portalreadmore = '<script type="text/javascript" src="'.$mybb->asset_url.'/jscripts/readmore.min.js"></script>
<script type="text/javascript">
//<!--
$(\'div.readmore\').readmore({
	moreLink: \'<div class="postbit_buttons"><a href="#" class="prm_open"><span>'.$lang->portalreadmore_readmore.'</span></a></div>\',
	lessLink: \'<div class="postbit_buttons"><a href="#" class="prm_close"><span>'.$lang->portalreadmore_close.'</span></a></div>\',
	collapsedHeight: '.$mybb->settings['portalreadmore_height'].',
	speed: '.$mybb->settings['portalreadmore_speed'].',
	afterToggle: function(trigger, element, expanded) {
		if(! expanded) {
		  $(\'html, body\').animate( { scrollTop: element.offset().top -80}, {duration: 100 } );
		}
	}
});
// -->
</script>';
	}
}
$plugins->add_hook("portal_end", "portalreadmore_run");
