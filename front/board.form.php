<?php

include ('../../../inc/includes.php');

Html::header(__('Scrumban - Quadro', 'scrumban'), $_SERVER['PHP_SELF'], "tools", "PluginScrumbanMenu", "quadro");

$board = new PluginScrumbanBoard();

if (isset($_POST["add"])) {
   $board->check(-1, CREATE);
   $newID = $board->add($_POST);
   Html::redirect(Plugin::getWebDir('scrumban') . "/front/board.php?board_id=$newID");
} else if (isset($_POST["update"])) {
   $board->check($_POST['id'], UPDATE);
   $board->update($_POST);
   Html::back();
} else if (isset($_POST["delete"])) {
   $board->check($_POST['id'], DELETE);
   $board->delete($_POST);
   Html::redirect(Plugin::getWebDir('scrumban') . "/front/board.php");
}

$ID = $_GET["id"] ?? 0;

if ($ID > 0) {
   $board->check($ID, READ);
} else {
   $board->check(-1, CREATE);
}

$board->showForm($ID);

Html::footer();
?>