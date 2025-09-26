<?php

include ('../../../inc/includes.php');

Html::header(__('Scrumban - Card', 'scrumban'), $_SERVER['PHP_SELF'], "tools", "PluginScrumbanMenu", "quadro");

$card = new PluginScrumbanCard();

if (isset($_POST["add"])) {
   $newID = $card->add($_POST);
   if ($newID) {
      Html::redirect(Plugin::getWebDir('scrumban') . "/front/board.php");
   }
} else if (isset($_POST["update"])) {
   $card->update($_POST);
   Html::back();
} else if (isset($_POST["delete"])) {
   $card->delete($_POST);
   Html::redirect(Plugin::getWebDir('scrumban') . "/front/board.php");
}

$ID = $_GET["id"] ?? 0;
$card->showForm($ID);

Html::footer();
?>