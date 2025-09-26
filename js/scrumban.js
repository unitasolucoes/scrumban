/* global $, bootbox, GLPI */

(function($) {
   'use strict';

   var metaCache = null;

   function updateProgressFromSlider($slider, $bar) {
      var value = parseInt($slider.val(), 10) || 0;
      $bar.css('width', value + '%').text(value + '%');
      return value;
   }

   function renderEmptyState($container) {
      var message = $container.data('empty');
      if (message) {
         $container.html('<div class="text-muted small fst-italic">' + message + '</div>');
      }
   }

   function renderAcceptance(criteria) {
      var $container = $('#scrumban-card-acceptance');
      $container.empty();
      if (!criteria.length) {
         renderEmptyState($container);
         return;
      }
      criteria.forEach(function(item) {
         addCriteriaItem(item.description || '', !!item.completed);
      });
   }

   function renderScenarios(scenarios) {
      var $container = $('#scrumban-card-scenarios');
      $container.empty();
      if (!scenarios.length) {
         renderEmptyState($container);
         return;
      }
      scenarios.forEach(function(item) {
         addScenarioItem(item.title || '', item.notes || '', item.status || 'pending');
      });
   }

   function addCriteriaItem(text, completed) {
      var $container = $('#scrumban-card-acceptance');
      if ($container.find('.scrumban-dynamic-item').length === 0) {
         $container.empty();
      }
      var $item = $('<div class="scrumban-dynamic-item scrumban-criteria-item"></div>');
      $item.append('<input type="text" class="form-control scrumban-criteria-text" value="' + (text || '') + '" placeholder="Descrição do critério">');
      var $toggle = $('<div class="form-check form-switch"><input class="form-check-input scrumban-criteria-done" type="checkbox"><label class="form-check-label">Concluído</label></div>');
      if (completed) {
         $toggle.find('input').prop('checked', true);
      }
      $item.append($toggle);
      var $actions = $('<div class="scrumban-dynamic-actions"></div>');
      var $remove = $('<button type="button" class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i></button>');
      $remove.on('click', function() {
         $item.remove();
         if ($container.find('.scrumban-dynamic-item').length === 0) {
            renderEmptyState($container);
         }
      });
      $actions.append($remove);
      $item.append($actions);
      $container.append($item);
   }

   function addScenarioItem(title, notes, status) {
      var $container = $('#scrumban-card-scenarios');
      if ($container.find('.scrumban-dynamic-item').length === 0) {
         $container.empty();
      }
      var $item = $('<div class="scrumban-dynamic-item scrumban-scenario-item"></div>');
      $item.append('<input type="text" class="form-control scrumban-scenario-title" value="' + (title || '') + '" placeholder="Título do cenário">');
      var $status = $('<select class="form-select scrumban-scenario-status"></select>');
      $.each(metaCache.scenario_statuses, function(value, label) {
         var selected = value === status ? 'selected' : '';
         $status.append('<option value="' + value + '" ' + selected + '>' + label + '</option>');
      });
      $item.append($status);
      $item.append('<textarea class="form-control scrumban-scenario-notes" rows="2" placeholder="Resultado esperado ou observações">' + (notes || '') + '</textarea>');
      var $actions = $('<div class="scrumban-dynamic-actions"></div>');
      var $remove = $('<button type="button" class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i></button>');
      $remove.on('click', function() {
         $item.remove();
         if ($container.find('.scrumban-dynamic-item').length === 0) {
            renderEmptyState($container);
         }
      });
      $actions.append($remove);
      $item.append($actions);
      $container.append($item);
   }

   function gatherAcceptance() {
      var results = [];
      $('#scrumban-card-acceptance .scrumban-dynamic-item').each(function() {
         var $item = $(this);
         results.push({
            description: $item.find('.scrumban-criteria-text').val(),
            completed: $item.find('.scrumban-criteria-done').is(':checked')
         });
      });
      return results;
   }

   function gatherScenarios() {
      var results = [];
      $('#scrumban-card-scenarios .scrumban-dynamic-item').each(function() {
         var $item = $(this);
         results.push({
            title: $item.find('.scrumban-scenario-title').val(),
            status: $item.find('.scrumban-scenario-status').val(),
            notes: $item.find('.scrumban-scenario-notes').val()
         });
      });
      return results;
   }

   function populateSelect($select, options, selectedValue, includeEmpty) {
      $select.empty();
      if (includeEmpty) {
         $select.append('<option value="">--</option>');
      }
      $.each(options, function(value, label) {
         var selected = value == selectedValue ? 'selected' : '';
         $select.append('<option value="' + value + '" ' + selected + '>' + label + '</option>');
      });
   }

   function populateUsers($select, users, selectedValue, includeEmpty) {
      $select.empty();
      if (includeEmpty) {
         $select.append('<option value="">--</option>');
      }
      $.each(users, function(_, user) {
         var selected = user.id == selectedValue ? 'selected' : '';
         $select.append('<option value="' + user.id + '" ' + selected + '>' + user.name + '</option>');
      });
   }

   var teamUsersCatalog = null;

   function ensureTeamUsers() {
      if (teamUsersCatalog) {
         return $.Deferred().resolve(teamUsersCatalog).promise();
      }
      return $.getJSON('ajax/team.php', {
         action: 'meta',
         _glpi_csrf_token: glpi_csrf_token()
      }).done(function(response) {
         teamUsersCatalog = response.users || [];
         populateTeamUserSelect();
      });
   }

   function populateTeamUserSelect() {
      var $select = $('#scrumban-team-member-user');
      if (!$select.length) {
         return;
      }
      $select.empty();
      var placeholder = $select.data('placeholder') || 'Selecionar usuário';
      $select.append('<option value="">' + placeholder + '</option>');
      (teamUsersCatalog || []).forEach(function(user) {
         $select.append('<option value="' + user.id + '">' + user.name + '</option>');
      });
   }

   function renderTeamMembers(members) {
      var $container = $('#scrumban-team-members-list');
      if (!$container.length) {
         return;
      }
      $container.empty();
      if (!members.length) {
         renderEmptyState($container);
         return;
      }
      var roleLabels = {
         member: 'Membro',
         lead: 'Líder',
         admin: 'Administrador'
      };
      members.forEach(function(member) {
         var badge = roleLabels[member.role] || member.role;
         var $item = $('<div class="scrumban-team-members__item"></div>');
         $item.append('<div><strong>' + member.name + '</strong><div class="text-muted small text-uppercase">' + badge + '</div></div>');
         var $btn = $('<button type="button" class="btn btn-sm btn-outline-danger"><i class="ti ti-user-minus"></i></button>');
         $btn.data('member-id', member.id);
         $item.append($btn);
         $container.append($item);
      });
   }

   function loadTeamMembers(teamId) {
      return $.getJSON('ajax/team.php', {
         action: 'members',
         team_id: teamId,
         _glpi_csrf_token: glpi_csrf_token()
      }).done(function(response) {
         if (response.status === 'ok') {
            renderTeamMembers(response.members || []);
            $('.scrumban-team-members').show();
         }
      });
   }

   function resetTeamForm() {
      var $form = $('#scrumban-team-form');
      if (!$form.length) {
         return;
      }
      $form[0].reset();
      $form.find('input[name="id"]').val('');
      renderTeamMembers([]);
      $('.scrumban-team-members').hide();
   }

   function openTeamModal(teamId) {
      ensureTeamUsers().always(function() {
         populateTeamUserSelect();
         var $form = $('#scrumban-team-form');
         resetTeamForm();
         $form.find('input[name="id"]').val(teamId || '');
         if (teamId) {
            $.getJSON('ajax/team.php', {
               action: 'get',
               id: teamId,
               _glpi_csrf_token: glpi_csrf_token()
            }).done(function(response) {
               if (response.status !== 'ok') {
                  return;
               }
               var team = response.team;
               $form.find('input[name="id"]').val(team.id);
               $form.find('input[name="name"]').val(team.name);
               $form.find('textarea[name="description"]').val(team.description);
               $form.find('select[name="manager_id"]').val(team.manager_id).trigger('change');
               $form.find('select[name="is_active"]').val(team.is_active).trigger('change');
               renderTeamMembers(response.members || []);
               $('.scrumban-team-members').show();
               $('#scrumbanTeamModal').modal('show');
            });
         } else {
            resetTeamForm();
            $('#scrumbanTeamModal').modal('show');
         }
      });
   }

   function serializeForm($form) {
      var data = {};
      $.each($form.serializeArray(), function(_, field) {
         data[field.name] = field.value;
      });
      return data;
   }

   function getActiveFilters() {
      return {
         assignee: $('#scrumban-filter-assignee').val() || '',
         type: $('#scrumban-filter-type').val() || '',
         priority: $('#scrumban-filter-priority').val() || ''
      };
   }

   function buildBoardUrl(boardId, teamId) {
      var params = [];
      if (teamId) {
         params.push('team_id=' + encodeURIComponent(teamId));
      }
      if (boardId) {
         params.push('boards_id=' + encodeURIComponent(boardId));
      }
      var filters = getActiveFilters();
      $.each(filters, function(key, value) {
         if (value) {
            params.push(key + '=' + encodeURIComponent(value));
         }
      });
      return 'board.php' + (params.length ? '?' + params.join('&') : '');
   }

   function fetchKanban(boardId) {
      if (!boardId) {
         return;
      }
      var kanban = $('#scrumban-kanban');
      kanban.data('board', boardId);
      var filters = getActiveFilters();
      var requestData = {
         action: 'load',
         board_id: boardId,
         _glpi_csrf_token: glpi_csrf_token()
      };
      if (filters.assignee) {
         requestData.assignee = filters.assignee;
      }
      if (filters.type) {
         requestData.type = filters.type;
      }
      if (filters.priority) {
         requestData.priority = filters.priority;
      }
      $.getJSON('ajax/kanban.php', requestData).done(function(response) {
         if (response.status !== 'ok') {
            return;
         }
         var columns = {};
         $('.scrumban-column__body').each(function() {
            var status = $(this).data('status-list');
            columns[status] = $(this);
            $(this).empty();
         });
         var counters = {};
         $.each(response.cards, function(_, card) {
            var column = columns[card.status];
            if (!column) {
               return;
            }
            counters[card.status] = (counters[card.status] || 0) + 1;
            var labelsHtml = '';
            if (card.labels && card.labels.length) {
               labelsHtml = '<div class="scrumban-card__labels">' + card.labels.map(function(label) {
                  return '<span class="badge bg-secondary">' + label + '</span>';
               }).join(' ') + '</div>';
            }
            var assignee = card.assignee_name || 'Não atribuído';
            var cardHtml = [
               '<div class="scrumban-kanban-card" data-id="' + card.id + '">',
               '<div class="scrumban-kanban-card__header">',
               '<span class="scrumban-kanban-card__title">' + card.name + '</span>',
               '<span class="scrumban-kanban-card__meta">' + card.type.toUpperCase() + '</span>',
               '</div>',
               '<div class="scrumban-kanban-card__body">',
               '<div class="scrumban-kanban-card__assignee">' + assignee + '</div>',
               labelsHtml,
               '</div>',
               '<div class="scrumban-kanban-card__footer">',
               '<span class="badge priority-' + card.priority.toLowerCase() + '">' + card.priority + '</span>',
               '</div>',
               '</div>'
            ].join('');
            column.append(cardHtml);
         });
         $('.scrumban-column').each(function() {
            var status = $(this).data('status');
            $(this).find('[data-count]').text(counters[status] || 0);
         });

         $('.scrumban-column__body').sortable({
            connectWith: '.scrumban-column__body',
            placeholder: 'scrumban-kanban-placeholder',
            forcePlaceholderSize: true,
            receive: function(event, ui) {
               var cardId = ui.item.data('id');
               var newStatus = $(this).data('status-list');
               $.post('ajax/kanban.php', {
                  action: 'move',
                  card_id: cardId,
                  status: newStatus,
                  _glpi_csrf_token: glpi_csrf_token()
               }).fail(function() {
                  fetchKanban($('#scrumban-kanban').data('board'));
               });
            }
         }).disableSelection();
      });
   }

   function loadCard(cardId, boardId) {
      var params = {
         action: 'get',
         id: cardId,
         _glpi_csrf_token: glpi_csrf_token()
      };
      if (boardId) {
         params.board_id = boardId;
      }
      return $.getJSON('ajax/card.php', params);
   }

   function openCardModal(cardId, boardId) {
      loadCard(cardId, boardId).done(function(response) {
         if (response.status !== 'ok') {
            return;
         }
         metaCache = response.meta;
         var card = response.card;
         var modal = $('#scrumbanCardModal');
         modal.data('card-id', card.id);
         modal.data('board-id', card.boards_id);
         $('#scrumban-card-id').text(card.id ? 'ID #' + card.id : 'Novo card');
         $('#scrumban-card-title').val(card.name);
         $('#scrumban-card-description').val(card.description || '');
         $('#scrumban-card-story-points').val(card.story_points || '');
         $('#scrumban-card-date-planned').val(card.date_planned ? card.date_planned.replace(' ', 'T') : '');
         $('#scrumban-card-date-completion').val(card.date_completion ? card.date_completion.replace(' ', 'T') : '');
         $('#scrumban-card-branch').val(card.branch || '');
         $('#scrumban-card-pr').val(card.pull_request || '');
         $('#scrumban-card-commits').val(card.commits || '');
         $('#scrumban-card-labels').val((card.labels || []).join(','));

         populateSelect($('#scrumban-card-status'), metaCache.statuses, card.status, false);
         populateSelect($('#scrumban-card-type'), metaCache.types, card.type, false);
         populateSelect($('#scrumban-card-priority'), metaCache.priorities, card.priority, false);
         populateUsers($('#scrumban-card-assignee'), metaCache.users, card.users_id_assigned, true);
         populateUsers($('#scrumban-card-requester'), metaCache.users, card.users_id_requester, true);
         populateSelect($('#scrumban-card-sprint'), metaCache.sprints.reduce(function(acc, sprint) {
            acc[sprint.id] = sprint.name;
            return acc;
         }, {}), card.sprint_id, true);

         updateProgressFromSlider($('#scrumban-card-dor-input').val(card.dor_percentage), $('#scrumban-card-dor'));
         updateProgressFromSlider($('#scrumban-card-dod-input').val(card.dod_percentage), $('#scrumban-card-dod'));
         $('#scrumban-card-updated').text(card.date_mod || '');

         renderAcceptance(card.acceptance_criteria || []);
         renderScenarios(card.test_scenarios || []);

         var historyHtml = '';
         if (card.history && card.history.length) {
            historyHtml = card.history.map(function(item) {
               return '<div class="scrumban-history__item"><i class="ti ti-point"></i><div><div>' + item.content + '</div><small class="text-muted">' + item.date_mod + '</small></div></div>';
            }).join('');
         }
         $('#scrumban-card-history').html(historyHtml);

         modal.modal('show');
      });
   }

   function saveCard() {
      var modal = $('#scrumbanCardModal');
      var cardId = modal.data('card-id') || 0;
      var boardId = modal.data('board-id') || $('#scrumban-kanban').data('board');
      var payload = {
         action: 'save',
         id: cardId,
         boards_id: boardId,
         name: $('#scrumban-card-title').val(),
         description: $('#scrumban-card-description').val(),
         story_points: $('#scrumban-card-story-points').val(),
         date_planned: $('#scrumban-card-date-planned').val(),
         date_completion: $('#scrumban-card-date-completion').val(),
         labels: $('#scrumban-card-labels').val(),
         branch: $('#scrumban-card-branch').val(),
         pull_request: $('#scrumban-card-pr').val(),
         commits: $('#scrumban-card-commits').val(),
         dor_percentage: updateProgressFromSlider($('#scrumban-card-dor-input'), $('#scrumban-card-dor')),
         dod_percentage: updateProgressFromSlider($('#scrumban-card-dod-input'), $('#scrumban-card-dod')),
         status: $('#scrumban-card-status').val(),
         type: $('#scrumban-card-type').val(),
         priority: $('#scrumban-card-priority').val(),
         users_id_assigned: $('#scrumban-card-assignee').val(),
         users_id_requester: $('#scrumban-card-requester').val(),
         sprint_id: $('#scrumban-card-sprint').val(),
         acceptance_criteria: gatherAcceptance(),
         test_scenarios: gatherScenarios(),
         _glpi_csrf_token: glpi_csrf_token()
      };
      $.ajax({
         url: 'ajax/card.php',
         method: 'POST',
         data: payload,
         success: function(response) {
            if (response.status === 'ok') {
               modal.modal('hide');
               fetchKanban($('#scrumban-kanban').data('board'));
            }
         }
      });
   }

   function deleteCard() {
      var modal = $('#scrumbanCardModal');
      var cardId = modal.data('card-id');
      if (!cardId) {
         return;
      }
      bootbox.confirm({
         message: 'Confirma a exclusão deste card?',
         buttons: {
            confirm: {
               label: 'Excluir',
               className: 'btn-danger'
            },
            cancel: {
               label: 'Cancelar',
               className: 'btn-secondary'
            }
         },
         callback: function(result) {
            if (!result) {
               return;
            }
            $.post('ajax/card.php', {
               action: 'delete',
               id: cardId,
               _glpi_csrf_token: glpi_csrf_token()
            }).done(function(response) {
               if (response.status === 'ok') {
                  modal.modal('hide');
                  fetchKanban($('#scrumban-kanban').data('board'));
               }
            });
         }
      });
   }

   function glpi_csrf_token() {
      return window.GLPI && GLPI.csrf_token ? GLPI.csrf_token : $('input[name="_glpi_csrf_token"]').first().val();
   }

   $(function() {
      var kanban = $('#scrumban-kanban');
      if (kanban.length) {
         fetchKanban(kanban.data('board'));

         kanban.on('click', '.scrumban-kanban-card', function() {
            openCardModal($(this).data('id'));
         });
      }

      $('#scrumban-save-card').on('click', saveCard);
      $('#scrumban-delete-card').on('click', deleteCard);

      $('#scrumban-card-dor-input').on('input change', function() {
         updateProgressFromSlider($(this), $('#scrumban-card-dor'));
      });
      $('#scrumban-card-dod-input').on('input change', function() {
         updateProgressFromSlider($(this), $('#scrumban-card-dod'));
      });

      $('#scrumban-add-criteria').on('click', function(e) {
         e.preventDefault();
         addCriteriaItem('', false);
      });

      $('#scrumban-add-scenario').on('click', function(e) {
         e.preventDefault();
         if (!metaCache) {
            return;
         }
         addScenarioItem('', '', 'pending');
      });

      $('#scrumban-board-select').on('change', function() {
         var boardId = $(this).val();
         var teamId = $('#scrumban-team-select').val();
         window.location = buildBoardUrl(boardId, teamId);
      });

      $('#scrumban-team-select').on('change', function() {
         var teamId = $(this).val();
         window.location = buildBoardUrl('', teamId);
      });

      $('#scrumban-filter-assignee, #scrumban-filter-type, #scrumban-filter-priority').on('change', function() {
         var boardId = $('#scrumban-kanban').data('board');
         if (boardId) {
            var teamId = $('#scrumban-team-select').val();
            var newUrl = buildBoardUrl(boardId, teamId);
            if (window.history && window.history.replaceState) {
               window.history.replaceState({}, '', newUrl);
            }
            fetchKanban(boardId);
         }
      });

      $('#scrumban-add-card').on('click', function() {
         var boardId = $('#scrumban-kanban').data('board');
         openCardModal(0, boardId);
      });

      $('#scrumban-add-team').on('click', function() {
         openTeamModal();
      });

      $(document).on('click', '[data-action="manage-members"]', function() {
         var teamId = $(this).data('team');
         openTeamModal(teamId);
      });

      $('#scrumban-save-team').on('click', function() {
         var data = serializeForm($('#scrumban-team-form'));
         data.action = 'save';
         data._glpi_csrf_token = glpi_csrf_token();
         $.post('ajax/team.php', data).done(function(response) {
            if (response.status === 'ok') {
               window.location.reload();
            }
         });
      });

      $('#scrumban-add-member').on('click', function() {
         var teamId = $('#scrumban-team-form').find('input[name="id"]').val();
         var userId = $('#scrumban-team-member-user').val();
         var role = $('#scrumban-team-member-role').val();
         if (!teamId || !userId) {
            return;
         }
         $.post('ajax/team.php', {
            action: 'addMember',
            teams_id: teamId,
            users_id: userId,
            role: role,
            _glpi_csrf_token: glpi_csrf_token()
         }).done(function(response) {
            if (response.status === 'ok') {
               loadTeamMembers(teamId);
            }
         });
      });

      $('#scrumban-team-members-list').on('click', 'button', function() {
         var memberId = $(this).data('member-id');
         var teamId = $('#scrumban-team-form').find('input[name="id"]').val();
         if (!memberId) {
            return;
         }
         $.post('ajax/team.php', {
            action: 'removeMember',
            id: memberId,
            _glpi_csrf_token: glpi_csrf_token()
         }).done(function(response) {
            if (response.status === 'ok') {
               loadTeamMembers(teamId);
            }
         });
      });
   });
})(jQuery);
