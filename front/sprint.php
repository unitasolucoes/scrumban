<?php

include ('../../../inc/includes.php');

Html::header(__('Scrumban - Sprints', 'scrumban'), $_SERVER['PHP_SELF'], "tools", "PluginScrumbanMenu", "sprints");

// Verificar quadros disponíveis
$team_id = isset($_GET['team_id']) ? (int)$_GET['team_id'] : null;
$user_teams = PluginScrumbanTeam::getTeamsForUser($_SESSION['glpiID']);

if ($team_id) {
   $allowed_team_ids = array_map('intval', array_column($user_teams, 'id'));
   if (!in_array($team_id, $allowed_team_ids, true)) {
      $team_id = null;
   }
}

$boards = PluginScrumbanTeam::getBoardsForUser($_SESSION['glpiID'], $team_id);
if (empty($boards)) {
   echo "<div class='center'>";
   if ($team_id) {
      echo "<h3>" . __('Nenhum quadro disponível para esta equipe', 'scrumban') . "</h3>";
      echo "<p>" . __('Associe um quadro à equipe selecionada ou escolha outra equipe para continuar.', 'scrumban') . "</p>";
   } else {
      echo "<h3>" . __('Nenhum quadro encontrado', 'scrumban') . "</h3>";
      echo "<p>" . __('Você precisa criar um quadro antes de gerenciar sprints.', 'scrumban') . "</p>";
   }
   echo "<a href='" . $CFG_GLPI['root_doc'] . "/plugins/scrumban/front/board.form.php' class='btn btn-primary'>";
   echo "<i class='fas fa-plus me-2'></i>Criar Quadro";
   echo "</a>";
   echo "</div>";
   Html::footer();
   exit;
}

// Pegar ID do quadro
$board_id = isset($_GET['board_id']) ? (int)$_GET['board_id'] : (int)$boards[0]['id'];
$board_ids = array_map('intval', array_column($boards, 'id'));

if (!in_array($board_id, $board_ids, true)) {
   $board_id = (int)$boards[0]['id'];
}

// Incluir CSS e JS específicos
echo "<link rel='stylesheet' type='text/css' href='" . Plugin::getWebDir('scrumban') . "/css/scrumban.css'>";
echo "<script src='" . Plugin::getWebDir('scrumban') . "/js/scrumban.js'></script>";

// Renderizar header do sistema
$board = new PluginScrumbanBoard();
$board->getFromDB($board_id);

if (!PluginScrumbanTeam::canUserAccessBoard($_SESSION['glpiID'], $board_id)) {
   echo "<div class='container mt-4'>";
   echo "<div class='alert alert-danger'>";
   echo "<h4 class='alert-heading'>" . __('Acesso negado', 'scrumban') . "</h4>";
   echo "<p class='mb-0'>" . __('Você não possui permissão para visualizar este quadro. Escolha outro quadro ou contate o administrador da equipe.', 'scrumban') . "</p>";
   echo "</div>";
   echo "</div>";
   Html::footer();
   exit;
}

echo "<div class='agilepm-container'>";

// Header
echo "<div class='agilepm-header'>";
echo "<div class='container-fluid'>";
echo "<div class='d-flex justify-content-between align-items-center'>";
echo "<div>";
echo "<h1><i class='fas fa-project-diagram me-3'></i>" . $board->fields['name'] . "</h1>";
echo "<p class='mb-0 opacity-75'>" . __('Sistema de Gestão de Projetos Ágeis', 'scrumban') . "</p>";
echo "</div>";
echo "<div class='d-flex gap-2 flex-wrap justify-content-end align-items-center'>";

$board_selector = plugin_scrumban_render_board_selector($board_id, [
   'boards' => $boards,
   'team_id' => $team_id,
   'team_selector_id' => 'scrumban-team-selector',
   'always_show' => true,
   'label' => __('Quadro', 'scrumban'),
   'label_class' => 'form-label text-white-50 mb-1 small',
   'wrapper_class' => 'scrumban-selector text-start d-flex flex-column'
]);

if (!empty($board_selector)) {
   echo $board_selector;
}

$team_selector = plugin_scrumban_render_team_selector($team_id, [
   'teams' => $user_teams,
   'board_selector_id' => 'scrumban-board-selector',
   'label' => __('Equipe', 'scrumban'),
   'placeholder' => __('Todas as equipes', 'scrumban'),
   'label_class' => 'form-label text-white-50 mb-1 small',
   'wrapper_class' => 'scrumban-selector text-start d-flex flex-column'
]);

if (!empty($team_selector)) {
   echo $team_selector;
}

echo "<button class='btn btn-light btn-sm'><i class='fas fa-cog'></i> " . __('Configurações', 'scrumban') . "</button>";
echo "<button class='btn btn-outline-light btn-sm'><i class='fas fa-user'></i> " . $_SESSION['glpiname'] . "</button>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

// Navegação
$board->showNavigationTabs('sprints', ['team_id' => $team_id, 'board_id' => $board_id]);

// Mostrar página de sprints
PluginScrumbanSprint::showSprintsPage($board_id);

echo "</div>";

// Modal para criar nova sprint
?>
<div class="modal fade" id="createSprintModal" tabindex="-1">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">
               <i class="fas fa-plus me-2"></i><?php echo __('Criar Nova Sprint', 'scrumban'); ?>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <form id="createSprintForm" action="<?php echo Plugin::getWebDir('scrumban'); ?>/ajax/sprint.php" method="post">
            <div class="modal-body">
               <input type="hidden" name="boards_id" value="<?php echo $board_id; ?>">
               <input type="hidden" name="action" value="create">
               
               <div class="row">
                  <div class="col-md-8">
                     <div class="form-group">
                        <label class="form-label"><?php echo __('Nome', 'scrumban'); ?> *</label>
                        <input type="text" class="form-control" name="name" required placeholder="Ex: Sprint 1 - Funcionalidades Básicas">
                     </div>
                  </div>
                  <div class="col-md-4">
                     <div class="form-group">
                        <label class="form-label"><?php echo __('Status', 'scrumban'); ?> *</label>
                        <select class="form-select" name="status" required>
                           <option value="planejado"><?php echo __('Planejado', 'scrumban'); ?></option>
                           <option value="ativo"><?php echo __('Ativo', 'scrumban'); ?></option>
                           <option value="concluido"><?php echo __('Concluído', 'scrumban'); ?></option>
                        </select>
                     </div>
                  </div>
               </div>

               <div class="row">
                  <div class="col-md-4">
                     <div class="form-group">
                        <label class="form-label"><?php echo __('Data de Início', 'scrumban'); ?></label>
                        <input type="date" class="form-control" name="start_date" value="<?php echo date('Y-m-d'); ?>">
                     </div>
                  </div>
                  <div class="col-md-4">
                     <div class="form-group">
                        <label class="form-label"><?php echo __('Data de Fim', 'scrumban'); ?></label>
                        <input type="date" class="form-control" name="end_date" value="<?php echo date('Y-m-d', strtotime('+14 days')); ?>">
                     </div>
                  </div>
                  <div class="col-md-4">
                     <div class="form-group">
                        <label class="form-label"><?php echo __('Capacidade (Story Points)', 'scrumban'); ?></label>
                        <input type="number" class="form-control" name="capacity" min="0" max="999" placeholder="Ex: 40">
                     </div>
                  </div>
               </div>

               <div class="form-group">
                  <label class="form-label"><?php echo __('Objetivo da Sprint', 'scrumban'); ?></label>
                  <textarea class="form-control" name="objective" rows="4" placeholder="<?php echo __('Descreva o objetivo principal desta sprint...', 'scrumban'); ?>"></textarea>
               </div>
            </div>
            <div class="modal-footer">
               <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php echo __('Cancelar', 'scrumban'); ?></button>
               <button type="submit" class="btn btn-primary"><?php echo __('Criar Sprint', 'scrumban'); ?></button>
            </div>
         </form>
      </div>
   </div>
</div>

<!-- Modal para editar sprint -->
<div class="modal fade" id="editSprintModal" tabindex="-1">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">
               <i class="fas fa-edit me-2"></i><?php echo __('Editar Sprint', 'scrumban'); ?>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <form id="editSprintForm" action="<?php echo Plugin::getWebDir('scrumban'); ?>/ajax/sprint.php" method="post">
            <div class="modal-body">
               <input type="hidden" name="id" id="edit_sprint_id">
               <input type="hidden" name="boards_id" value="<?php echo $board_id; ?>">
               <input type="hidden" name="action" value="update">
               
               <div class="row">
                  <div class="col-md-8">
                     <div class="form-group">
                        <label class="form-label"><?php echo __('Nome', 'scrumban'); ?> *</label>
                        <input type="text" class="form-control" name="name" id="edit_name" required>
                     </div>
                  </div>
                  <div class="col-md-4">
                     <div class="form-group">
                        <label class="form-label"><?php echo __('Status', 'scrumban'); ?> *</label>
                        <select class="form-select" name="status" id="edit_status" required>
                           <option value="planejado"><?php echo __('Planejado', 'scrumban'); ?></option>
                           <option value="ativo"><?php echo __('Ativo', 'scrumban'); ?></option>
                           <option value="concluido"><?php echo __('Concluído', 'scrumban'); ?></option>
                        </select>
                     </div>
                  </div>
               </div>

               <div class="row">
                  <div class="col-md-4">
                     <div class="form-group">
                        <label class="form-label"><?php echo __('Data de Início', 'scrumban'); ?></label>
                        <input type="date" class="form-control" name="start_date" id="edit_start_date">
                     </div>
                  </div>
                  <div class="col-md-4">
                     <div class="form-group">
                        <label class="form-label"><?php echo __('Data de Fim', 'scrumban'); ?></label>
                        <input type="date" class="form-control" name="end_date" id="edit_end_date">
                     </div>
                  </div>
                  <div class="col-md-4">
                     <div class="form-group">
                        <label class="form-label"><?php echo __('Capacidade (Story Points)', 'scrumban'); ?></label>
                        <input type="number" class="form-control" name="capacity" id="edit_capacity" min="0" max="999">
                     </div>
                  </div>
               </div>

               <div class="form-group">
                  <label class="form-label"><?php echo __('Objetivo da Sprint', 'scrumban'); ?></label>
                  <textarea class="form-control" name="objective" id="edit_objective" rows="4"></textarea>
               </div>
            </div>
            <div class="modal-footer">
               <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php echo __('Cancelar', 'scrumban'); ?></button>
               <button type="submit" class="btn btn-primary"><?php echo __('Salvar Alterações', 'scrumban'); ?></button>
            </div>
         </form>
      </div>
   </div>
</div>

<script>
// Abrir modal de criação
function openCreateSprintModal() {
   const modal = new bootstrap.Modal(document.getElementById('createSprintModal'));
   modal.show();
}

// Abrir modal de edição
function openEditSprintModal(sprintId) {
   console.log('🔍 Iniciando carregamento da sprint ID:', sprintId);
   
   // Buscar dados da sprint via AJAX
   fetch('<?php echo Plugin::getWebDir('scrumban'); ?>/ajax/sprint.php?action=get_details&sprint_id=' + sprintId)
   .then(response => {
      console.log('📡 Response recebida:', response.status, response.statusText);
      return response.json();
   })
   .then(data => {
      console.log('📊 Dados recebidos:', data);
      
      if (data.success) {
         console.log('✅ Preenchendo formulário com:', data.sprint);
         
         // Preencher formulário
         document.getElementById('edit_sprint_id').value = data.sprint.id || '';
         document.getElementById('edit_name').value = data.sprint.name || '';
         document.getElementById('edit_status').value = data.sprint.status || 'planejado';
         document.getElementById('edit_start_date').value = data.sprint.start_date || '';
         document.getElementById('edit_end_date').value = data.sprint.end_date || '';
         document.getElementById('edit_capacity').value = data.sprint.capacity || '';
         document.getElementById('edit_objective').value = data.sprint.objective || '';
         
         // Abrir modal
         const modal = new bootstrap.Modal(document.getElementById('editSprintModal'));
         modal.show();
         console.log('🎯 Modal de edição aberto');
      } else {
         console.error('❌ Erro nos dados:', data.message);
         alert('Erro ao carregar dados da sprint: ' + data.message);
      }
   })
   .catch(error => {
      console.error('💥 Erro na requisição:', error);
      alert('Erro ao carregar dados da sprint');
   });
}

// Enviar formulário de criação via AJAX
document.getElementById('createSprintForm').addEventListener('submit', function(e) {
   e.preventDefault();
   console.log('🚀 Enviando formulário de criação');
   
   const formData = new FormData(this);
   
   // Log dos dados do formulário
   for (let [key, value] of formData.entries()) {
      console.log('📝 FormData:', key, '=', value);
   }
   
   fetch('<?php echo Plugin::getWebDir('scrumban'); ?>/ajax/sprint.php', {
      method: 'POST',
      body: formData
   })
   .then(response => {
      console.log('📡 Response criação:', response.status, response.statusText);
      return response.json();
   })
   .then(data => {
      console.log('📊 Resultado criação:', data);
      
      if (data.success) {
         console.log('✅ Sprint criada com sucesso');
         
         // Fechar modal
         const modal = bootstrap.Modal.getInstance(document.getElementById('createSprintModal'));
         modal.hide();
         
         // Recarregar página
         location.reload();
      } else {
         console.error('❌ Erro ao criar:', data.message);
         alert('Erro ao criar sprint: ' + data.message);
      }
   })
   .catch(error => {
      console.error('💥 Erro na criação:', error);
      alert('Erro ao criar sprint');
   });
});

// Enviar formulário de edição via AJAX
document.getElementById('editSprintForm').addEventListener('submit', function(e) {
   e.preventDefault();
   console.log('🔄 Enviando formulário de edição');
   
   const formData = new FormData(this);
   
   // Log detalhado dos dados do formulário
   console.log('📋 Dados do formulário de edição:');
   for (let [key, value] of formData.entries()) {
      console.log('  📝', key, '=', value);
   }
   
   fetch('<?php echo Plugin::getWebDir('scrumban'); ?>/ajax/sprint.php', {
      method: 'POST',
      body: formData
   })
   .then(response => {
      console.log('📡 Response edição:', response.status, response.statusText);
      
      // Verificar se response é OK
      if (!response.ok) {
         throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      
      return response.text(); // Primeiro como texto para ver o que está vindo
   })
   .then(text => {
      console.log('📄 Response como texto:', text);
      
      try {
         const data = JSON.parse(text);
         console.log('📊 Dados parseados:', data);
         
         if (data.success) {
            console.log('✅ Sprint atualizada com sucesso');
            
            // Fechar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('editSprintModal'));
            modal.hide();
            
            // Recarregar página
            location.reload();
         } else {
            console.error('❌ Erro ao atualizar:', data.message);
            alert('Erro ao atualizar sprint: ' + data.message);
         }
      } catch (parseError) {
         console.error('💥 Erro ao parsear JSON:', parseError);
         console.error('📄 Texto recebido:', text);
         alert('Erro: Resposta inválida do servidor');
      }
   })
   .catch(error => {
      console.error('💥 Erro na requisição de edição:', error);
      alert('Erro ao atualizar sprint: ' + error.message);
   });
});
</script>

<?php
Html::footer();
?>