<?php

include ('../../../inc/includes.php');

Html::header(__('Scrumban - Teams', 'scrumban'), $_SERVER['PHP_SELF'], "tools", "PluginScrumbanMenu", "teams");

// Check if user can manage teams
$can_manage = PluginScrumbanTeam::canUserManageTeams($_SESSION['glpiID']);

// Include CSS and JS
echo "<link rel='stylesheet' type='text/css' href='" . Plugin::getWebDir('scrumban') . "/css/scrumban.css'>";
echo "<link rel='stylesheet' type='text/css' href='" . Plugin::getWebDir('scrumban') . "/css/teams.css'>";
echo "<script src='" . Plugin::getWebDir('scrumban') . "/js/scrumban.js'></script>";
echo "<script src='" . Plugin::getWebDir('scrumban') . "/js/teams.js'></script>";

echo "<div class='agilepm-container'>";

// Header
echo "<div class='agilepm-header'>";
echo "<div class='container-fluid'>";
echo "<div class='d-flex justify-content-between align-items-center'>";
echo "<div>";
echo "<h1><i class='fas fa-users me-3'></i>" . __('Team Management', 'scrumban') . "</h1>";
echo "<p class='mb-0 opacity-75'>" . __('Manage teams and access control', 'scrumban') . "</p>";
echo "</div>";
echo "<div class='d-flex gap-2'>";
echo "<button class='btn btn-light btn-sm'><i class='fas fa-cog'></i> " . __('Settings', 'scrumban') . "</button>";
echo "<button class='btn btn-outline-light btn-sm'><i class='fas fa-user'></i> " . $_SESSION['glpiname'] . "</button>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

// Show team management page
PluginScrumbanTeam::showTeamManagementPage();

echo "</div>";

?>

<!-- Modal for creating new team -->
<div class="modal fade" id="createTeamModal" tabindex="-1">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">
               <i class="fas fa-plus me-2"></i><?php echo __('Create New Team', 'scrumban'); ?>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <form id="createTeamForm">
            <div class="modal-body">
               <div class="row">
                  <div class="col-md-8">
                     <div class="form-group mb-3">
                        <label class="form-label"><?php echo __('Team Name', 'scrumban'); ?> *</label>
                        <input type="text" class="form-control" name="name" required maxlength="255" placeholder="<?php echo __('Enter team name', 'scrumban'); ?>">
                     </div>
                  </div>
                  <div class="col-md-4">
                     <div class="form-group mb-3">
                        <label class="form-label"><?php echo __('Manager', 'scrumban'); ?></label>
                        <?php User::dropdown(['name' => 'manager_id', 'display_emptychoice' => true]); ?>
                     </div>
                  </div>
               </div>

               <div class="form-group mb-3">
                  <label class="form-label"><?php echo __('Description', 'scrumban'); ?></label>
                  <textarea class="form-control" name="description" rows="4" placeholder="<?php echo __('Describe the team purpose and goals...', 'scrumban'); ?>"></textarea>
               </div>

               <div class="alert alert-info">
                  <i class="fas fa-info-circle me-2"></i>
                  <?php echo __('You will be automatically added as administrator of this team.', 'scrumban'); ?>
               </div>
            </div>
            <div class="modal-footer">
               <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php echo __('Cancel', 'scrumban'); ?></button>
               <button type="submit" class="btn btn-primary"><?php echo __('Create Team', 'scrumban'); ?></button>
            </div>
         </form>
      </div>
   </div>
</div>

<!-- Modal for team management -->
<div class="modal fade" id="manageTeamModal" tabindex="-1">
   <div class="modal-dialog modal-xl">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">
               <i class="fas fa-cogs me-2"></i><?php echo __('Manage Team', 'scrumban'); ?>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <ul class="nav nav-tabs" id="teamManagementTabs" role="tablist">
               <li class="nav-item" role="presentation">
                  <button class="nav-link active" id="team-info-tab" data-bs-toggle="tab" data-bs-target="#team-info" type="button" role="tab">
                     <i class="fas fa-info-circle me-2"></i><?php echo __('Team Info', 'scrumban'); ?>
                  </button>
               </li>
               <li class="nav-item" role="presentation">
                  <button class="nav-link" id="team-members-tab" data-bs-toggle="tab" data-bs-target="#team-members" type="button" role="tab">
                     <i class="fas fa-users me-2"></i><?php echo __('Members', 'scrumban'); ?>
                  </button>
               </li>
               <li class="nav-item" role="presentation">
                  <button class="nav-link" id="team-boards-tab" data-bs-toggle="tab" data-bs-target="#team-boards" type="button" role="tab">
                     <i class="fas fa-columns me-2"></i><?php echo __('Boards', 'scrumban'); ?>
                  </button>
               </li>
            </ul>
            
            <div class="tab-content mt-3" id="teamManagementTabContent">
               <!-- Team Info Tab -->
               <div class="tab-pane fade show active" id="team-info" role="tabpanel">
                  <form id="editTeamForm">
                     <input type="hidden" name="id" id="edit_team_id">
                     <div class="row">
                        <div class="col-md-8">
                           <div class="form-group mb-3">
                              <label class="form-label"><?php echo __('Team Name', 'scrumban'); ?> *</label>
                              <input type="text" class="form-control" name="name" id="edit_team_name" required maxlength="255">
                           </div>
                        </div>
                        <div class="col-md-4">
                           <div class="form-group mb-3">
                              <label class="form-label"><?php echo __('Manager', 'scrumban'); ?></label>
                              <?php User::dropdown(['name' => 'manager_id', 'display_emptychoice' => true]); ?>
                           </div>
                        </div>
                     </div>

                     <div class="form-group mb-3">
                        <label class="form-label"><?php echo __('Description', 'scrumban'); ?></label>
                        <textarea class="form-control" name="description" id="edit_team_description" rows="4"></textarea>
                     </div>

                     <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-danger" id="deleteTeamBtn"><?php echo __('Delete Team', 'scrumban'); ?></button>
                        <button type="submit" class="btn btn-primary"><?php echo __('Save Changes', 'scrumban'); ?></button>
                     </div>
                  </form>
               </div>

               <!-- Team Members Tab -->
               <div class="tab-pane fade" id="team-members" role="tabpanel">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                     <h6><?php echo __('Team Members', 'scrumban'); ?></h6>
                     <button class="btn btn-primary btn-sm" id="addMemberBtn">
                        <i class="fas fa-user-plus me-2"></i><?php echo __('Add Member', 'scrumban'); ?>
                     </button>
                  </div>
                  <div id="team-members-list">
                     <!-- Members will be loaded here -->
                  </div>
               </div>

               <!-- Team Boards Tab -->
               <div class="tab-pane fade" id="team-boards" role="tabpanel">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                     <h6><?php echo __('Associated Boards', 'scrumban'); ?></h6>
                     <button class="btn btn-primary btn-sm" id="addBoardBtn">
                        <i class="fas fa-plus me-2"></i><?php echo __('Add Board', 'scrumban'); ?>
                     </button>
                  </div>
                  <div id="team-boards-list">
                     <!-- Boards will be loaded here -->
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>

<!-- Modal for editing board permissions -->
<div class="modal fade" id="editBoardPermissionsModal" tabindex="-1">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">
               <i class="fas fa-shield-alt me-2"></i><?php echo __('Board Permissions', 'scrumban'); ?>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <form id="editBoardPermissionsForm">
            <div class="modal-body">
               <input type="hidden" name="team_id" value="">
               <input type="hidden" name="board_id" value="">

               <div class="mb-3">
                  <h6 class="mb-0" id="edit_board_name"></h6>
                  <p class="text-muted small mb-0"><?php echo __('Defina os níveis de acesso para os membros desta equipe neste quadro.', 'scrumban'); ?></p>
               </div>

               <div class="form-check form-switch mb-3">
                  <input class="form-check-input" type="checkbox" role="switch" id="edit_board_can_edit" name="can_edit">
                  <label class="form-check-label" for="edit_board_can_edit">
                     <strong><?php echo __('Pode editar cards e sprints', 'scrumban'); ?></strong><br>
                     <span class="text-muted small"><?php echo __('Permite criar, mover e atualizar cards e sprints do quadro.', 'scrumban'); ?></span>
                  </label>
               </div>

               <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" role="switch" id="edit_board_can_manage" name="can_manage">
                  <label class="form-check-label" for="edit_board_can_manage">
                     <strong><?php echo __('Pode gerenciar configurações do quadro', 'scrumban'); ?></strong><br>
                     <span class="text-muted small"><?php echo __('Inclui acesso para modificar colunas, membros e integrações do quadro.', 'scrumban'); ?></span>
                  </label>
               </div>
            </div>
            <div class="modal-footer">
               <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php echo __('Cancelar', 'scrumban'); ?></button>
               <button type="submit" class="btn btn-primary"><?php echo __('Salvar permissões', 'scrumban'); ?></button>
            </div>
         </form>
      </div>
   </div>
</div>

<!-- Modal for adding member -->
<div class="modal fade" id="addMemberModal" tabindex="-1">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">
               <i class="fas fa-user-plus me-2"></i><?php echo __('Add Team Member', 'scrumban'); ?>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <form id="addMemberForm">
            <div class="modal-body">
               <input type="hidden" name="team_id" id="add_member_team_id">
               
               <div class="form-group mb-3">
                  <label class="form-label"><?php echo __('User', 'scrumban'); ?> *</label>
                  <select class="form-select" name="user_id" id="available_users" required>
                     <option value=""><?php echo __('Select user...', 'scrumban'); ?></option>
                  </select>
               </div>

               <div class="form-group mb-3">
                  <label class="form-label"><?php echo __('Role', 'scrumban'); ?> *</label>
                  <select class="form-select" name="role" required>
                     <option value="member"><?php echo __('Member', 'scrumban'); ?></option>
                     <option value="lead"><?php echo __('Lead', 'scrumban'); ?></option>
                     <option value="admin"><?php echo __('Administrator', 'scrumban'); ?></option>
                  </select>
               </div>

               <div class="alert alert-info">
                  <h6><?php echo __('Role Permissions:', 'scrumban'); ?></h6>
                  <ul class="mb-0">
                     <li><strong><?php echo __('Member', 'scrumban'); ?>:</strong> <?php echo __('Can view team boards and participate in sprints', 'scrumban'); ?></li>
                     <li><strong><?php echo __('Lead', 'scrumban'); ?>:</strong> <?php echo __('Can manage team members and board associations', 'scrumban'); ?></li>
                     <li><strong><?php echo __('Administrator', 'scrumban'); ?>:</strong> <?php echo __('Full team management and can delete team', 'scrumban'); ?></li>
                  </ul>
               </div>
            </div>
            <div class="modal-footer">
               <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php echo __('Cancel', 'scrumban'); ?></button>
               <button type="submit" class="btn btn-primary"><?php echo __('Add Member', 'scrumban'); ?></button>
            </div>
         </form>
      </div>
   </div>
</div>

<!-- Modal for adding board -->
<div class="modal fade" id="addBoardModal" tabindex="-1">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">
               <i class="fas fa-plus me-2"></i><?php echo __('Add Board to Team', 'scrumban'); ?>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <form id="addBoardForm">
            <div class="modal-body">
               <input type="hidden" name="team_id" id="add_board_team_id">
               
               <div class="form-group mb-3">
                  <label class="form-label"><?php echo __('Board', 'scrumban'); ?> *</label>
                  <select class="form-select" name="board_id" id="available_boards" required>
                     <option value=""><?php echo __('Select board...', 'scrumban'); ?></option>
                  </select>
               </div>

               <div class="form-group mb-3">
                  <label class="form-label"><?php echo __('Permissions', 'scrumban'); ?></label>
                  <div class="form-check">
                     <input class="form-check-input" type="checkbox" name="can_edit" id="can_edit" value="1" checked>
                     <label class="form-check-label" for="can_edit">
                        <?php echo __('Can edit cards and sprints', 'scrumban'); ?>
                     </label>
                  </div>
                  <div class="form-check">
                     <input class="form-check-input" type="checkbox" name="can_manage" id="can_manage" value="1">
                     <label class="form-check-label" for="can_manage">
                        <?php echo __('Can manage board settings', 'scrumban'); ?>
                     </label>
                  </div>
               </div>

               <div class="alert alert-info">
                  <h6><?php echo __('Permission Levels:', 'scrumban'); ?></h6>
                  <ul class="mb-0">
                     <li><strong><?php echo __('View', 'scrumban'); ?>:</strong> <?php echo __('See board and cards (always granted)', 'scrumban'); ?></li>
                     <li><strong><?php echo __('Edit', 'scrumban'); ?>:</strong> <?php echo __('Create/move cards, manage sprints', 'scrumban'); ?></li>
                     <li><strong><?php echo __('Manage', 'scrumban'); ?>:</strong> <?php echo __('Configure board settings and columns', 'scrumban'); ?></li>
                  </ul>
               </div>
            </div>
            <div class="modal-footer">
               <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php echo __('Cancel', 'scrumban'); ?></button>
               <button type="submit" class="btn btn-primary"><?php echo __('Add Board', 'scrumban'); ?></button>
            </div>
         </form>
      </div>
   </div>
</div>

<script>
// Initialize teams functionality
document.addEventListener('DOMContentLoaded', function() {
    initializeTeams();
});

// Global functions for onclick handlers
function openCreateTeamModal() {
    const modal = new bootstrap.Modal(document.getElementById('createTeamModal'));
    modal.show();
}

function manageTeam(teamId) {
    loadTeamManagement(teamId);
}
</script>

<?php
Html::footer();
?>