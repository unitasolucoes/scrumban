<div class="modal fade" id="scrumbanCardModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div class="d-flex flex-column w-100">
          <span class="text-muted small" id="scrumban-card-id"></span>
          <input type="text" class="form-control form-control-lg" id="scrumban-card-title" placeholder="<?= __('Nome do card', 'scrumban'); ?>">
        </div>
        <div class="d-flex gap-2 align-items-center">
          <select class="form-select form-select-sm w-auto" id="scrumban-card-type"></select>
          <select class="form-select form-select-sm w-auto" id="scrumban-card-priority"></select>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
      </div>
      <div class="modal-body">
        <div class="row g-4">
          <div class="col-lg-8">
            <section class="scrumban-section">
              <h5 class="scrumban-section__title"><i class="ti ti-id"></i> <?= __('Informações principais', 'scrumban'); ?></h5>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label"><?= __('Responsável', 'scrumban'); ?></label>
                  <select id="scrumban-card-assignee" class="form-select"></select>
                </div>
                <div class="col-md-6">
                  <label class="form-label"><?= __('Solicitante', 'scrumban'); ?></label>
                  <select id="scrumban-card-requester" class="form-select"></select>
                </div>
                <div class="col-md-4">
                  <label class="form-label"><?= __('Story Points', 'scrumban'); ?></label>
                  <input type="number" min="0" class="form-control" id="scrumban-card-story-points">
                </div>
                <div class="col-md-4">
                  <label class="form-label"><?= __('Planejado', 'scrumban'); ?></label>
                  <input type="datetime-local" class="form-control" id="scrumban-card-date-planned">
                </div>
                <div class="col-md-4">
                  <label class="form-label"><?= __('Conclusão', 'scrumban'); ?></label>
                  <input type="datetime-local" class="form-control" id="scrumban-card-date-completion">
                </div>
              </div>
              <div class="row g-3 mt-1">
                <div class="col-md-6">
                  <label class="form-label"><?= __('Sprint', 'scrumban'); ?></label>
                  <select id="scrumban-card-sprint" class="form-select"></select>
                </div>
                <div class="col-md-6">
                  <label class="form-label"><?= __('Labels', 'scrumban'); ?></label>
                  <input type="text" class="form-control" id="scrumban-card-labels" placeholder="bug,backend,prioridade">
                </div>
              </div>
              <div class="mt-3">
                <label class="form-label"><?= __('Descrição detalhada', 'scrumban'); ?></label>
                <textarea class="form-control" id="scrumban-card-description" rows="6"></textarea>
              </div>
              <button class="btn btn-outline-primary mt-3" id="scrumban-card-attachments"><i class="ti ti-paperclip"></i> <?= __('Anexos', 'scrumban'); ?></button>
            </section>
            <section class="scrumban-section">
              <h5 class="scrumban-section__title"><i class="ti ti-code"></i> <?= __('Informações de desenvolvimento', 'scrumban'); ?></h5>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label"><?= __('Branch', 'scrumban'); ?></label>
                  <input type="text" class="form-control" id="scrumban-card-branch">
                </div>
                <div class="col-md-6">
                  <label class="form-label"><?= __('Pull Request', 'scrumban'); ?></label>
                  <input type="text" class="form-control" id="scrumban-card-pr">
                </div>
                <div class="col-12">
                  <label class="form-label"><?= __('Commits', 'scrumban'); ?></label>
                  <textarea class="form-control" id="scrumban-card-commits" rows="3"></textarea>
                </div>
              </div>
            </section>
            <section class="scrumban-section">
              <h5 class="scrumban-section__title"><i class="ti ti-clipboard-list"></i> <?= __('Critérios', 'scrumban'); ?></h5>
              <div class="row g-3 align-items-center mb-3">
                <div class="col-md-6">
                  <label class="form-label"><?= __('Definition of Ready', 'scrumban'); ?></label>
                  <div class="progress" role="progressbar" aria-label="DoR">
                    <div class="progress-bar bg-info" id="scrumban-card-dor" style="width:0%">0%</div>
                  </div>
                  <input type="range" class="form-range mt-2" id="scrumban-card-dor-input" min="0" max="100">
                </div>
                <div class="col-md-6">
                  <label class="form-label"><?= __('Definition of Done', 'scrumban'); ?></label>
                  <div class="progress" role="progressbar" aria-label="DoD">
                    <div class="progress-bar bg-success" id="scrumban-card-dod" style="width:0%">0%</div>
                  </div>
                  <input type="range" class="form-range mt-2" id="scrumban-card-dod-input" min="0" max="100">
                </div>
              </div>
              <div>
                <label class="form-label"><?= __('Critérios de aceitação', 'scrumban'); ?></label>
                <div id="scrumban-card-acceptance" class="scrumban-dynamic-list" data-empty="<?= __('Nenhum critério cadastrado', 'scrumban'); ?>"></div>
                <button class="btn btn-sm btn-outline-primary mt-2" id="scrumban-add-criteria"><i class="ti ti-circle-plus"></i> <?= __('Adicionar critério', 'scrumban'); ?></button>
              </div>
            </section>
            <section class="scrumban-section">
              <h5 class="scrumban-section__title"><i class="ti ti-flask"></i> <?= __('Cenários de teste', 'scrumban'); ?></h5>
              <div id="scrumban-card-scenarios" class="scrumban-dynamic-list" data-empty="<?= __('Nenhum cenário cadastrado', 'scrumban'); ?>"></div>
              <button class="btn btn-sm btn-outline-primary mt-2" id="scrumban-add-scenario"><i class="ti ti-circle-plus"></i> <?= __('Adicionar cenário', 'scrumban'); ?></button>
            </section>
            <section class="scrumban-section">
              <h5 class="scrumban-section__title"><i class="ti ti-messages"></i> <?= __('Comentários', 'scrumban'); ?></h5>
              <div id="scrumban-card-comments" class="scrumban-comments"></div>
              <div class="input-group mt-2">
                <input type="text" class="form-control" id="scrumban-card-new-comment" placeholder="<?= __('Adicionar comentário...', 'scrumban'); ?>">
                <button class="btn btn-primary" id="scrumban-submit-comment"><i class="ti ti-send"></i></button>
              </div>
            </section>
          </div>
          <div class="col-lg-4">
            <section class="scrumban-section">
              <h5 class="scrumban-section__title"><i class="ti ti-history"></i> <?= __('Histórico', 'scrumban'); ?></h5>
              <div id="scrumban-card-history" class="scrumban-history"></div>
            </section>
          </div>
        </div>
      </div>
      <div class="modal-footer d-flex justify-content-between">
        <div class="d-flex gap-2 align-items-center">
          <select class="form-select form-select-sm w-auto" id="scrumban-card-status"></select>
          <span class="badge bg-light text-dark" id="scrumban-card-updated"></span>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-danger" id="scrumban-delete-card"><i class="ti ti-trash"></i> <?= __('Excluir', 'scrumban'); ?></button>
          <button class="btn btn-primary" id="scrumban-save-card"><i class="ti ti-device-floppy"></i> <?= __('Salvar', 'scrumban'); ?></button>
        </div>
      </div>
    </div>
  </div>
</div>
