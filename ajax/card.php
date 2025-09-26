<?php

include ('../../../inc/includes.php');

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    $action = $_REQUEST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $response = createCard();
            break;
            
        case 'move':
            $response = moveCard();
            break;
            
        case 'get_details':
            $response = getCardDetails();
            break;
            
        case 'update':
            $response = updateCard();
            break;
            
        case 'delete':
            $response = deleteCard();
            break;
            
        default:
            $response['message'] = __('Ação não reconhecida', 'scrumban');
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit;

/**
 * Criar novo card
 */
function createCard() {
    global $DB;
    
    $card = new PluginScrumbanCard();
    
    // Buscar primeira coluna do quadro se não especificada
    $columns_id = $_POST['columns_id'] ?? null;
    if (!$columns_id && isset($_POST['boards_id'])) {
        $result = $DB->request([
            'SELECT' => 'id',
            'FROM' => 'glpi_plugin_scrumban_columns',
            'WHERE' => [
                'boards_id' => $_POST['boards_id'],
                'is_active' => 1
            ],
            'ORDER' => 'position',
            'LIMIT' => 1
        ]);
        
        foreach ($result as $row) {
            $columns_id = $row['id'];
        }
    }
    
    $input = [
        'boards_id' => $_POST['boards_id'] ?? 1,
        'columns_id' => $columns_id,
        'sprints_id' => $_POST['sprints_id'] ?? 0,
        'title' => $_POST['title'] ?? '',
        'description' => $_POST['description'] ?? '',
        'type' => $_POST['type'] ?? 'story',
        'priority' => $_POST['priority'] ?? 'normal',
        'story_points' => intval($_POST['story_points'] ?? 0),
        'assignee' => $_POST['assignee'] ?? '',
        'reporter' => $_POST['reporter'] ?? $_SESSION['glpiname'],
        'due_date' => $_POST['due_date'] ?: null,
        'planned_due_date' => $_POST['planned_due_date'] ?: null,
        'done_date' => $_POST['done_date'] ?: null,
        'tickets_id' => intval($_POST['tickets_id'] ?? 0),
        'branch' => trim($_POST['branch'] ?? ''),
        'pull_request' => trim($_POST['pull_request'] ?? ''),
        'dor_percent' => intval($_POST['dor_percent'] ?? 0),
        'dod_percent' => intval($_POST['dod_percent'] ?? 0)
    ];

    if (($criteria = getCollectionFromRequest($_POST, 'acceptance_criteria')) !== null) {
        $input['acceptance_criteria'] = $criteria;
    }

    if (($scenarios = getCollectionFromRequest($_POST, 'test_scenarios')) !== null) {
        $input['test_scenarios'] = $scenarios;
    }
    
    if ($card->add($input)) {
        $card_id = $card->getID();
        
        // Adicionar labels se fornecidas
        if (!empty($_POST['labels'])) {
            $labels = explode(',', $_POST['labels']);
            foreach ($labels as $label_name) {
                $label_name = trim($label_name);
                if (!empty($label_name)) {
                    $card->addLabel($label_name);
                }
            }
        }
        
        return [
            'success' => true,
            'message' => __('Issue criada com sucesso', 'scrumban'),
            'card_id' => $card_id
        ];
    } else {
        return [
            'success' => false,
            'message' => __('Erro ao criar issue', 'scrumban')
        ];
    }
}

/**
 * Mover card para nova coluna/posição
 */
function moveCard() {
    $card = new PluginScrumbanCard();
    $card_id = intval($_POST['card_id']);
    $column_id = intval($_POST['column_id']);
    $position = intval($_POST['position'] ?? 0);
    
    if ($card->getFromDB($card_id)) {
        if ($card->moveToColumn($column_id, $position)) {
            return [
                'success' => true,
                'message' => __('Issue movida com sucesso', 'scrumban')
            ];
        }
    }
    
    return [
        'success' => false,
        'message' => __('Erro ao mover issue', 'scrumban')
    ];
}

/**
 * Obter detalhes do card
 */
function getCardDetails() {
    $card = new PluginScrumbanCard();
    $card_id = intval($_GET['card_id']);

    if (!$card->getFromDB($card_id)) {
        return [
            'success' => false,
            'message' => __('Issue não encontrada', 'scrumban')
        ];
    }
    
    // Buscar informações adicionais
    $labels = $card->getLabels();
    $activity = $card->getActivityHistory();
    $acceptance = PluginScrumbanCard::getAcceptanceCriteriaForCard($card_id);
    $test_scenarios = PluginScrumbanCard::getTestScenariosForCard($card_id);

    // Gerar HTML dos detalhes
    $html = generateCardDetailsHtml($card, $labels, $activity, $acceptance, $test_scenarios);

    return [
        'success' => true,
        'html' => $html,
        'card' => $card->exportData(),
        'acceptance_criteria' => $acceptance,
        'test_scenarios' => $test_scenarios
    ];
}

/**
 * Atualizar card
 */
function updateCard() {
    $card = new PluginScrumbanCard();
    $card_id = intval($_POST['id']);
    
    if (!$card->getFromDB($card_id)) {
        return [
            'success' => false,
            'message' => __('Issue não encontrada', 'scrumban')
        ];
    }
    
    $input = $_POST;
    if (($criteria = getCollectionFromRequest($_POST, 'acceptance_criteria')) !== null) {
        $input['acceptance_criteria'] = $criteria;
    }
    if (($scenarios = getCollectionFromRequest($_POST, 'test_scenarios')) !== null) {
        $input['test_scenarios'] = $scenarios;
    }
    $input['id'] = $card_id;
    
    if ($card->update($input)) {
        return [
            'success' => true,
            'message' => __('Issue atualizada com sucesso', 'scrumban')
        ];
    }
    
    return [
        'success' => false,
        'message' => __('Erro ao atualizar issue', 'scrumban')
    ];
}

/**
 * Deletar card
 */
function deleteCard() {
    $card = new PluginScrumbanCard();
    $card_id = intval($_POST['card_id']);

    if ($card->getFromDB($card_id)) {
        if ($card->delete(['id' => $card_id])) {
            return [
                'success' => true,
                'message' => __('Issue excluída com sucesso', 'scrumban')
            ];
        }
    }
    
    return [
        'success' => false,
        'message' => __('Erro ao excluir issue', 'scrumban')
    ];
}

function getCollectionFromRequest($source, $key) {
    if (!isset($source[$key])) {
        return null;
    }

    $value = $source[$key];
    if (is_string($value)) {
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    if (is_array($value)) {
        return $value;
    }

    return [];
}

/**
 * Gerar HTML dos detalhes do card
 */
function generateCardDetailsHtml($card, $labels, $activity, $acceptance = [], $test_scenarios = []) {
    $priority_labels = [
        'trivial' => __('Trivial', 'scrumban'),
        'menor' => __('Menor', 'scrumban'),
        'normal' => __('Normal', 'scrumban'),
        'maior' => __('Maior', 'scrumban'),
        'critico' => __('Crítico', 'scrumban')
    ];

    $type_labels = [
        'story' => __('História', 'scrumban'),
        'task' => __('Tarefa', 'scrumban'),
        'bug' => __('Bug', 'scrumban'),
        'epic' => __('Épico', 'scrumban')
    ];

    $acceptance_labels = PluginScrumbanCard::getAcceptanceStatusOptions();
    $test_labels = PluginScrumbanCard::getTestScenarioStatusOptions();

    $acceptance_status_classes = [
        'pending' => 'secondary',
        'in_progress' => 'info',
        'done' => 'success'
    ];

    $test_status_classes = [
        'not_run' => 'secondary',
        'in_progress' => 'info',
        'passed' => 'success',
        'failed' => 'danger',
        'blocked' => 'warning'
    ];

    $acceptance_total = count($acceptance);
    $acceptance_done = 0;
    foreach ($acceptance as $criterion) {
        if ($criterion['status'] === 'done') {
            $acceptance_done++;
        }
    }
    $acceptance_progress = $acceptance_total > 0 ? (int)round(($acceptance_done / $acceptance_total) * 100) : 0;

    $test_counts = [];
    foreach ($test_labels as $key => $label) {
        $test_counts[$key] = 0;
    }
    foreach ($test_scenarios as $scenario) {
        $status = $scenario['status'];
        if (!isset($test_counts[$status])) {
            $test_counts[$status] = 0;
        }
        $test_counts[$status]++;
    }
    $test_total = count($test_scenarios);

    $branch_display = !empty($card->fields['branch']) ? htmlspecialchars($card->fields['branch'], ENT_QUOTES, 'UTF-8') : __('Não informado', 'scrumban');
    $pull_request_display = __('Não informado', 'scrumban');
    if (!empty($card->fields['pull_request'])) {
        $pull = $card->fields['pull_request'];
        if (filter_var($pull, FILTER_VALIDATE_URL)) {
            $safe = htmlspecialchars($pull, ENT_QUOTES, 'UTF-8');
            $pull_request_display = "<a href='" . $safe . "' target='_blank' rel='noopener'>" . $safe . "</a>";
        } else {
            $pull_request_display = htmlspecialchars($pull, ENT_QUOTES, 'UTF-8');
        }
    }

    $dor_percent = intval($card->fields['dor_percent']);
    $dod_percent = intval($card->fields['dod_percent']);

    ob_start();
    ?>
    <div class="row">
        <div class="col-md-8">
            <div class="mb-4">
                <h4><?php echo htmlspecialchars($card->fields['title']); ?></h4>
                <div class="d-flex gap-2 mb-3 flex-wrap">
                    <span class="badge bg-secondary"><?php echo $type_labels[$card->fields['type']] ?? $card->fields['type']; ?></span>
                    <span class="badge bg-warning text-dark"><?php echo $priority_labels[$card->fields['priority']] ?? $card->fields['priority']; ?></span>
                    <?php if ($card->fields['story_points'] > 0): ?>
                        <span class="badge bg-primary"><?php echo $card->fields['story_points']; ?> pts</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($card->fields['description'])): ?>
            <div class="mb-4">
                <h6><?php echo __('Descrição', 'scrumban'); ?></h6>
                <div class="p-3 bg-light rounded">
                    <?php echo nl2br(htmlspecialchars($card->fields['description'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-body">
                    <h6 class="card-title"><?php echo __('Desenvolvimento', 'scrumban'); ?></h6>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <strong><?php echo __('Branch', 'scrumban'); ?>:</strong><br>
                            <span class="text-muted"><?php echo $branch_display; ?></span>
                        </div>
                        <div class="col-sm-6">
                            <strong><?php echo __('Pull Request', 'scrumban'); ?>:</strong><br>
                            <span class="text-muted"><?php echo $pull_request_display; ?></span>
                        </div>
                        <div class="col-sm-6">
                            <strong><?php echo __('Planned Due Date', 'scrumban'); ?>:</strong><br>
                            <span class="text-muted"><?php echo $card->fields['planned_due_date'] ? Html::convDate($card->fields['planned_due_date']) : __('Não definido', 'scrumban'); ?></span>
                        </div>
                        <div class="col-sm-6">
                            <strong><?php echo __('Due Date', 'scrumban'); ?>:</strong><br>
                            <span class="text-muted"><?php echo $card->fields['due_date'] ? Html::convDate($card->fields['due_date']) : __('Não definido', 'scrumban'); ?></span>
                        </div>
                        <div class="col-sm-6">
                            <strong><?php echo __('Done Date', 'scrumban'); ?>:</strong><br>
                            <span class="text-muted"><?php echo $card->fields['done_date'] ? Html::convDate($card->fields['done_date']) : __('Não definido', 'scrumban'); ?></span>
                        </div>
                    </div>
                    <div class="row g-3 mt-2">
                        <div class="col-sm-6">
                            <strong><?php echo __('Definition of Ready', 'scrumban'); ?></strong>
                            <div class="progress" role="progressbar" aria-valuenow="<?php echo $dor_percent; ?>" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar bg-info" style="width: <?php echo $dor_percent; ?>%;"><?php echo $dor_percent; ?>%</div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <strong><?php echo __('Definition of Done', 'scrumban'); ?></strong>
                            <div class="progress" role="progressbar" aria-valuenow="<?php echo $dod_percent; ?>" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar bg-success" style="width: <?php echo $dod_percent; ?>%;"><?php echo $dod_percent; ?>%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="card-title mb-0"><?php echo __('Critérios de Aceitação', 'scrumban'); ?></h6>
                        <span class="badge bg-primary"><?php echo sprintf(__('%1$d de %2$d concluídos', 'scrumban'), $acceptance_done, $acceptance_total); ?></span>
                    </div>
                    <div class="progress mb-3" role="progressbar" aria-valuenow="<?php echo $acceptance_progress; ?>" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar bg-success" style="width: <?php echo $acceptance_progress; ?>%;"><?php echo $acceptance_progress; ?>%</div>
                    </div>
                    <?php if (!empty($acceptance)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($acceptance as $criterion): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div class="me-3">
                                <?php echo nl2br(htmlspecialchars($criterion['description'])); ?>
                            </div>
                            <span class="badge bg-<?php echo $acceptance_status_classes[$criterion['status']] ?? 'secondary'; ?>"><?php echo $acceptance_labels[$criterion['status']] ?? $criterion['status']; ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <p class="text-muted mb-0"><?php echo __('Nenhum critério de aceitação cadastrado.', 'scrumban'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="card-title mb-0"><?php echo __('Cenários de Teste', 'scrumban'); ?></h6>
                        <?php if ($test_total > 0): ?>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php foreach ($test_labels as $key => $label): ?>
                                <?php $count = $test_counts[$key] ?? 0; ?>
                                <?php if ($count > 0): ?>
                                    <span class="badge bg-<?php echo $test_status_classes[$key] ?? 'secondary'; ?>"><?php echo $label; ?>: <?php echo $count; ?></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($test_scenarios)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th><?php echo __('Cenário', 'scrumban'); ?></th>
                                    <th style="width: 160px;"><?php echo __('Status', 'scrumban'); ?></th>
                                    <th><?php echo __('Notas', 'scrumban'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($test_scenarios as $scenario): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($scenario['name']); ?></td>
                                    <td><span class="badge bg-<?php echo $test_status_classes[$scenario['status']] ?? 'secondary'; ?>"><?php echo $test_labels[$scenario['status']] ?? $scenario['status']; ?></span></td>
                                    <td><?php echo nl2br(htmlspecialchars($scenario['notes'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted mb-0"><?php echo __('Nenhum cenário de teste registrado.', 'scrumban'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($labels)): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h6 class="card-title"><?php echo __('Labels', 'scrumban'); ?></h6>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php foreach ($labels as $label): ?>
                            <span class="badge" style="background-color: <?php echo htmlspecialchars($label['color']); ?>; color: #fff;">
                                <?php echo htmlspecialchars($label['name']); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="card-title"><?php echo __('Informações', 'scrumban'); ?></h6>
                    <div class="mb-3">
                        <strong><?php echo __('Responsável', 'scrumban'); ?>:</strong><br>
                        <span class="text-muted"><?php echo htmlspecialchars($card->fields['assignee'] ?: __('Não atribuído', 'scrumban')); ?></span>
                    </div>
                    <div class="mb-3">
                        <strong><?php echo __('Reporter', 'scrumban'); ?>:</strong><br>
                        <span class="text-muted"><?php echo htmlspecialchars($card->fields['reporter']); ?></span>
                    </div>
                    <div class="mb-3">
                        <strong><?php echo __('Story Points', 'scrumban'); ?>:</strong><br>
                        <span class="text-muted"><?php echo $card->fields['story_points'] ?: __('Não estimado', 'scrumban'); ?></span>
                    </div>
                    <div class="mb-3">
                        <strong><?php echo __('Criado em', 'scrumban'); ?>:</strong><br>
                        <span class="text-muted"><?php echo Html::convDateTime($card->fields['date_creation']); ?></span>
                    </div>
                    <?php if ($card->fields['done_date']): ?>
                    <div class="mb-3">
                        <strong><?php echo __('Concluído em', 'scrumban'); ?>:</strong><br>
                        <span class="text-muted"><?php echo Html::convDate($card->fields['done_date']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($activity)): ?>
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title"><?php echo __('Atividade Recente', 'scrumban'); ?></h6>
                    <div class="timeline-sm">
                        <?php foreach ($activity as $item): ?>
                        <div class="timeline-item">
                            <small class="text-muted"><?php echo Html::convDateTime($item['date']); ?></small><br>
                            <strong><?php echo htmlspecialchars($item['user']); ?></strong>
                            <?php if ($item['type'] == 'comment'): ?>
                                <?php echo __('comentou', 'scrumban'); ?>: <?php echo htmlspecialchars(substr($item['content'], 0, 100)); ?>...
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

?>