<?php

include ('../../../inc/includes.php');

Html::header(__('Scrumban - Quadro Kanban', 'scrumban'), $_SERVER['PHP_SELF'], "tools", "PluginScrumbanMenu", "quadro");

// Verificar se existe pelo menos um quadro
$boards = PluginScrumbanBoard::getActiveBoards();
if (empty($boards)) {
   echo "<div class='center'>";
   echo "<h3>Nenhum quadro encontrado</h3>";
   echo "<p>Você precisa criar um quadro antes de usar o sistema Scrumban.</p>";
   echo "<a href='" . $CFG_GLPI['root_doc'] . "/plugins/scrumban/front/board.form.php' class='btn btn-primary'>";
   echo "<i class='fas fa-plus me-2'></i>Criar Primeiro Quadro";
   echo "</a>";
   echo "</div>";
   Html::footer();
   exit;
}

// Pegar ID do quadro (padrão: primeiro disponível)
$board_id = isset($_GET['board_id']) ? intval($_GET['board_id']) : $boards[0]['id'];

// Carregar o quadro
$board = new PluginScrumbanBoard();
if (!$board->getFromDB($board_id)) {
   echo "<div class='alert alert-danger'>Quadro não encontrado!</div>";
   Html::footer();
   exit;
}

// Incluir CSS e JS específicos
echo "<link rel='stylesheet' type='text/css' href='" . Plugin::getWebDir('scrumban') . "/css/scrumban.css'>";
echo "<script src='https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js'></script>";
echo "<script src='" . Plugin::getWebDir('scrumban') . "/js/scrumban.js'></script>";

// Renderizar o quadro Kanban
$board->showKanbanBoard();

// Modal para criar nova issue
?>
<div class="modal fade" id="createIssueModal" tabindex="-1">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">
               <i class="fas fa-plus me-2"></i><?php echo __('Criar Nova Issue', 'scrumban'); ?>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <form id="createIssueForm" action="<?php echo Plugin::getWebDir('scrumban'); ?>/ajax/card.php" method="post">
            <div class="modal-body">
               <input type="hidden" name="boards_id" value="<?php echo $board_id; ?>">
               <input type="hidden" name="action" value="create">
               
               <div class="row">
                  <div class="col-md-8">
                     <div class="form-group">
                        <label class="form-label"><?php echo __('Título', 'scrumban'); ?> *</label>
                        <input type="text" class="form-control" name="title" required>
                     </div>
                  </div>
                  <div class="col-md-4">
                     <div class="form-group">
                        <label class="form-label"><?php echo __('Tipo', 'scrumban'); ?> *</label>
                        <select class="form-select" name="type" required>
                           <option value="story"><?php echo __('História', 'scrumban'); ?></option>
                           <option value="task"><?php echo __('Tarefa', 'scrumban'); ?></option>
                           <option value="bug"><?php echo __('Bug', 'scrumban'); ?></option>
                           <option value="epic"><?php echo __('Épico', 'scrumban'); ?></option>
                        </select>
                     </div>
                  </div>
               </div>

               <div class="row">
                  <div class="col-md-6">
                     <div class="form-group">
                        <label class="form-label"><?php echo __('Prioridade', 'scrumban'); ?></label>
                        <select class="form-select" name="priority">
                           <option value="normal" selected><?php echo __('Normal', 'scrumban'); ?></option>
                           <option value="critico"><?php echo __('Crítico', 'scrumban'); ?></option>
                           <option value="maior"><?php echo __('Maior', 'scrumban'); ?></option>
                           <option value="menor"><?php echo __('Menor', 'scrumban'); ?></option>
                           <option value="trivial"><?php echo __('Trivial', 'scrumban'); ?></option>
                        </select>
                     </div>
                  </div>
                  <div class="col-md-6">
                     <div class="form-group">
                        <label class="form-label"><?php echo __('Story Points', 'scrumban'); ?></label>
                        <select class="form-select" name="story_points">
                           <option value="0"><?php echo __('Não estimado', 'scrumban'); ?></option>
                           <option value="1">1</option>
                           <option value="2">2</option>
                           <option value="3">3</option>
                           <option value="5">5</option>
                           <option value="8">8</option>
                           <option value="13">13</option>
                           <option value="21">21</option>
                        </select>
                     </div>
                  </div>
               </div>

               <div class="form-group">
                  <label class="form-label"><?php echo __('Descrição', 'scrumban'); ?></label>
                  <textarea class="form-control" name="description" rows="4" placeholder="<?php echo __('Descreva a issue...', 'scrumban'); ?>"></textarea>
               </div>

               <div class="row">
                  <div class="col-md-6">
                     <div class="form-group">
                        <label class="form-label"><?php echo __('Responsável', 'scrumban'); ?></label>
                        <input type="text" class="form-control" name="assignee" placeholder="Nome do responsável">
                     </div>
                  </div>
                  <div class="col-md-6">
                     <div class="form-group">
                        <label class="form-label"><?php echo __('Reporter', 'scrumban'); ?></label>
                        <input type="text" class="form-control" name="reporter" value="<?php echo $_SESSION['glpiname']; ?>">
                     </div>
                  </div>
               </div>

               <?php
               // Buscar sprints disponíveis
               $sprints = PluginScrumbanSprint::getActiveSprints($board_id);
               if (!empty($sprints)) {
                  echo "<div class='row'>";
                  echo "<div class='col-md-6'>";
                  echo "<div class='form-group'>";
                  echo "<label class='form-label'>" . __('Sprint', 'scrumban') . "</label>";
                  echo "<select class='form-select' name='sprints_id'>";
                  echo "<option value='0'>" . __('Backlog', 'scrumban') . "</option>";
                  foreach ($sprints as $sprint) {
                     echo "<option value='" . $sprint['id'] . "'>" . htmlspecialchars($sprint['name']) . "</option>";
                  }
                  echo "</select>";
                  echo "</div>";
                  echo "</div>";
                  echo "<div class='col-md-6'>";
                  echo "<div class='form-group'>";
                  echo "<label class='form-label'>" . __('Data Limite', 'scrumban') . "</label>";
                  echo "<input type='date' class='form-control' name='due_date'>";
                  echo "</div>";
                  echo "</div>";
                  echo "</div>";
               }
               ?>

               <div class="form-group">
                  <label class="form-label"><?php echo __('Labels', 'scrumban'); ?></label>
                  <input type="text" class="form-control" name="labels" placeholder="<?php echo __('Separar por vírgula (ex: ui, api, database)', 'scrumban'); ?>">
               </div>
            </div>
            <div class="modal-footer">
               <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php echo __('Cancelar', 'scrumban'); ?></button>
               <button type="submit" class="btn btn-primary"><?php echo __('Criar Issue', 'scrumban'); ?></button>
            </div>
         </form>
      </div>
   </div>
</div>

<!-- Modal para detalhes do card -->
<div class="modal fade" id="cardDetailsModal" tabindex="-1">
   <div class="modal-dialog modal-xl">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">
               <i class="fas fa-eye me-2"></i><?php echo __('Detalhes da Issue', 'scrumban'); ?>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body" id="cardDetailsContent">
            <!-- Conteúdo carregado dinamicamente -->
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php echo __('Fechar', 'scrumban'); ?></button>
            <button type="button" class="btn btn-primary" onclick="editCurrentCard()"><?php echo __('Editar Issue', 'scrumban'); ?></button>
         </div>
      </div>
   </div>
</div>

<script>
// Inicializar funcionalidades JavaScript
document.addEventListener('DOMContentLoaded', function() {
   initializeScrumban(<?php echo $board_id; ?>);
});
</script>

<?php
Html::footer();
?>