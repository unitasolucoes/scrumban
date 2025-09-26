<?php

include ('../../../inc/includes.php');

Session::checkLoginUser();
Session::haveRight(PluginScrumbanProfile::RIGHT_TEAM, READ);

Html::header(__('Equipes Scrumban', 'scrumban'), '', 'tools', 'pluginScrumbanMenu', 'team');

$team = new PluginScrumbanTeam();
$teams = $team->find([], ['name']);

echo "<div class='scrumban-page'>";

echo "<div class='scrumban-page__header'>";
echo "<div>";
echo "<h2><i class='ti ti-users me-2'></i>" . __('Equipes', 'scrumban') . "</h2>";
echo "<p class='text-muted'>" . __('Gerencie equipes e permissões de acesso aos quadros Kanban.', 'scrumban') . "</p>";
echo "</div>";

if (Session::haveRight(PluginScrumbanProfile::RIGHT_TEAM, CREATE)) {
   echo "<button class='btn btn-primary' id='scrumban-add-team'>";
   echo "<i class='ti ti-plus'></i> " . __('Nova equipe', 'scrumban');
   echo "</button>";
}

echo "</div>";

if (empty($teams)) {
   echo "<div class='alert alert-info'>" . __('Nenhuma equipe cadastrada ainda. Crie a primeira equipe para iniciar o controle de acesso.', 'scrumban') . "</div>";
} else {
   echo "<div class='row g-4'>";
   foreach ($teams as $data) {
      $role = PluginScrumbanTeam::getUserRole(Session::getLoginUserID(false), $data['id']);
      echo "<div class='col-md-4'>";
      echo "<div class='scrumban-card card h-100'>";
      echo "<div class='card-body d-flex flex-column gap-3'>";
      echo "<div class='d-flex justify-content-between align-items-start'>";
      echo "<div>";
      echo "<h5 class='card-title mb-1'>" . htmlspecialchars($data['name']) . "</h5>";
      echo "<span class='badge bg-secondary'>" . __('Função: ', 'scrumban') . strtoupper($role ?: '-') . "</span>";
      echo "</div>";
      echo "<div class='text-end'>";
      echo $data['is_active'] ? "<span class='badge bg-success'>" . __('Ativa', 'scrumban') . "</span>" : "<span class='badge bg-danger'>" . __('Inativa', 'scrumban') . "</span>";
      echo "</div>";
      echo "</div>";

      if (!empty($data['description'])) {
         echo "<p class='text-muted mb-0'>" . nl2br(Toolbox::substr($data['description'], 0, 160)) . "</p>";
      }

      echo "<div class='mt-auto d-flex gap-2'>";
      echo "<a class='btn btn-outline-primary btn-sm' href='" . $team->getFormURL() . "?id=" . $data['id'] . "'>" . __('Detalhes', 'scrumban') . "</a>";
      echo "<button class='btn btn-outline-secondary btn-sm' data-action='manage-members' data-team='" . $data['id'] . "'>" . __('Membros', 'scrumban') . "</button>";
      echo "</div>";
      echo "</div>";
      echo "</div>";
      echo "</div>";
   }
   echo "</div>";
}

echo "</div>";

include __DIR__ . '/templates/team_modal.php';

Html::footer();
