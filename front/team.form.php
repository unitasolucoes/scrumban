<?php
include ('../../../inc/includes.php');

Session::checkLoginUser();
Session::haveRight(PluginScrumbanProfile::RIGHT_TEAM, READ);

$team = new PluginScrumbanTeam();

if (isset($_POST['add'])) {
   Session::checkRight(PluginScrumbanProfile::RIGHT_TEAM, CREATE);
   $team->check(-1, CREATE, $_POST);
   $team->add($_POST);
   Html::back();
}

if (isset($_POST['update'])) {
   Session::checkRight(PluginScrumbanProfile::RIGHT_TEAM, UPDATE);
   $team->check($_POST['id'], UPDATE);
   $team->update($_POST);
   Html::back();
}

if (isset($_POST['purge'])) {
   Session::checkRight(PluginScrumbanProfile::RIGHT_TEAM, DELETE);
   $team->check($_POST['id'], DELETE);
   $team->delete($_POST);
   Html::redirect('team.php');
}

Html::header(__('Equipe', 'scrumban'), '', 'tools', 'pluginScrumbanMenu', 'team');

$team->display($_GET);

Html::footer();
