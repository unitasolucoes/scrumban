<?php

include ('../../../inc/includes.php');

Html::header(__('Scrumban - Sprint', 'scrumban'), $_SERVER['PHP_SELF'], "tools", "PluginScrumbanMenu", "sprints");

$sprint = new PluginScrumbanSprint();

if (isset($_POST["add"])) {
   $newID = $sprint->add($_POST);
   if ($newID) {
      Html::redirect(Plugin::getWebDir('scrumban') . "/front/sprint.php");
   }
} else if (isset($_POST["update"])) {
   $sprint->update($_POST);
   Html::back();
} else if (isset($_POST["delete"])) {
   $sprint->delete($_POST);
   Html::redirect(Plugin::getWebDir('scrumban') . "/front/sprint.php");
}

$ID = $_GET["id"] ?? 0;
$sprint->showForm($ID);

Html::footer();
?>