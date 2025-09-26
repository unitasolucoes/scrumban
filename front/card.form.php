<?php
include_once ('../../../inc/includes.php');

Session::checkLoginUser();
Session::haveRight(PluginScrumbanProfile::RIGHT_EDIT, READ);

$card = new PluginScrumbanCard();

if (isset($_POST['add'])) {
   $card->check(-1, CREATE, $_POST);
   $card->add($_POST);
   Html::back();
}

if (isset($_POST['update'])) {
   $card->check($_POST['id'], UPDATE);
   $card->update($_POST);
   Html::back();
}

if (isset($_POST['purge'])) {
   $card->check($_POST['id'], DELETE);
   $card->delete($_POST);
   Html::back();
}
