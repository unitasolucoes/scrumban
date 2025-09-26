<?php

include ('../../../inc/includes.php');

Html::header(__('Scrumban - Team', 'scrumban'), $_SERVER['PHP_SELF'], "tools", "PluginScrumbanMenu", "teams");

$team = new PluginScrumbanTeam();

if (isset($_POST["add"])) {
   $team->check(-1, CREATE);
   $newID = $team->add($_POST);
   Html::redirect(Plugin::getWebDir('scrumban') . "/front/team.php?id=$newID");
} else if (isset($_POST["update"])) {
   $team->check($_POST['id'], UPDATE);
   $team->update($_POST);
   Html::back();
} else if (isset($_POST["delete"])) {
   $team->check($_POST['id'], DELETE);
   $team->delete($_POST);
   Html::redirect(Plugin::getWebDir('scrumban') . "/front/team.php");
}

$ID = $_GET["id"] ?? 0;

if ($ID > 0) {
   $team->check($ID, READ);
} else {
   $team->check(-1, CREATE);
}

$team->showForm($ID);

Html::footer();
?>