<?php

include ('../../../inc/includes.php');

Html::header(__('Scrumban - Coluna', 'scrumban'), $_SERVER['PHP_SELF'], "tools", "PluginScrumbanMenu", "quadro");

$column = new PluginScrumbanColumn();

if (isset($_POST["add"])) {
   $newID = $column->add($_POST);
   if ($newID) {
      Html::redirect(Plugin::getWebDir('scrumban') . "/front/board.php");
   }
} else if (isset($_POST["update"])) {
   $column->update($_POST);
   Html::back();
} else if (isset($_POST["delete"])) {
   $column->delete($_POST);
   Html::redirect(Plugin::getWebDir('scrumban') . "/front/board.php");
}

$ID = $_GET["id"] ?? 0;
$column->showForm($ID);

Html::footer();
?>