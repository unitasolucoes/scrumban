<div class="modal fade" id="scrumbanTeamModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ti ti-users me-2"></i><?= __('Nova equipe', 'scrumban'); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="scrumban-team-form">
          <?php echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]); ?>
          <input type="hidden" name="id" value="">
          <div class="mb-3">
            <label class="form-label"><?= __('Nome', 'scrumban'); ?> *</label>
            <input type="text" class="form-control" name="name" required>
          </div>
          <div class="mb-3">
            <label class="form-label"><?= __('Descrição', 'scrumban'); ?></label>
            <textarea class="form-control" name="description" rows="3"></textarea>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label"><?= __('Gerente', 'scrumban'); ?></label>
              <?php User::dropdown(['name' => 'manager_id', 'value' => 0, 'right' => 'all']); ?>
            </div>
            <div class="col-md-6">
              <label class="form-label"><?= __('Ativa', 'scrumban'); ?></label>
              <?php Dropdown::showYesNo('is_active', 1); ?>
            </div>
          </div>
        </form>
        <div class="scrumban-team-members mt-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0"><i class="ti ti-users-group me-1"></i> <?= __('Membros da equipe', 'scrumban'); ?></h6>
            <div class="d-flex gap-2">
              <select class="form-select form-select-sm" id="scrumban-team-member-user" data-placeholder="<?= __('Selecionar usuário', 'scrumban'); ?>"></select>
              <select class="form-select form-select-sm" id="scrumban-team-member-role">
                <option value="member"><?= __('Membro', 'scrumban'); ?></option>
                <option value="lead"><?= __('Líder', 'scrumban'); ?></option>
                <option value="admin"><?= __('Administrador', 'scrumban'); ?></option>
              </select>
              <button type="button" class="btn btn-sm btn-outline-primary" id="scrumban-add-member"><i class="ti ti-user-plus"></i> <?= __('Adicionar membro', 'scrumban'); ?></button>
            </div>
          </div>
          <div id="scrumban-team-members-list" class="scrumban-team-members__list" data-empty="<?= __('Nenhum membro cadastrado', 'scrumban'); ?>"></div>
        </div>
      </div>
      <div class="modal-footer d-flex justify-content-between">
        <div class="text-muted small"><i class="ti ti-lock"></i> <?= __('Apenas administradores podem editar esta equipe.', 'scrumban'); ?></div>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= __('Cancelar', 'scrumban'); ?></button>
          <button type="button" class="btn btn-primary" id="scrumban-save-team"><?= __('Salvar equipe', 'scrumban'); ?></button>
        </div>
      </div>
    </div>
  </div>
</div>
