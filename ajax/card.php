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
        'tickets_id' => intval($_POST['tickets_id'] ?? 0)
    ];
    
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
    
    // Gerar HTML dos detalhes
    $html = generateCardDetailsHtml($card, $labels, $activity);
    
    return [
        'success' => true,
        'html' => $html,
        'card' => $card->exportData()
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

/**
 * Gerar HTML dos detalhes do card
 */
function generateCardDetailsHtml($card, $labels, $activity) {
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

    $acceptance_criteria = $card->getAcceptanceCriteria();
    $criteria_total = count($acceptance_criteria);
    $criteria_completed = 0;
    foreach ($acceptance_criteria as $criterion) {
        if (!empty($criterion['is_completed'])) {
            $criteria_completed++;
        }
    }
    $criteria_percent = $criteria_total > 0 ? round(($criteria_completed / $criteria_total) * 100) : null;

    $test_scenarios = $card->getTestScenarios();
    $scenario_counts = ['pending' => 0, 'passed' => 0, 'failed' => 0];
    foreach ($test_scenarios as $scenario) {
        $status = $scenario['status'] ?? 'pending';
        if (!isset($scenario_counts[$status])) {
            $scenario_counts[$status] = 0;
        }
        $scenario_counts[$status]++;
    }

    $commits = [];
    if (!empty($card->fields['development_commits'])) {
        $commits = array_filter(array_map('trim', preg_split("/(\\r\\n|\\r|\\n)/", $card->fields['development_commits'])));
    }

    $planned_date = $card->fields['planned_delivery_date'] ? Html::convDate($card->fields['planned_delivery_date']) : null;
    $completed_at = $card->fields['completed_at'] ? Html::convDateTime($card->fields['completed_at']) : null;

    $external_reference = trim((string)$card->fields['external_reference']);
    $dor = (int)$card->fields['dor_percent'];
    $dod = (int)$card->fields['dod_percent'];

    $pull_request = trim((string)$card->fields['development_pull_request']);
    $pull_is_url = filter_var($pull_request, FILTER_VALIDATE_URL);

    $branch = trim((string)$card->fields['development_branch']);

    ob_start();
    ?>
    <div class="row">
        <div class="col-md-8">
            <div class="mb-4">
                <h4><?php echo htmlspecialchars($card->fields['title']); ?></h4>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge bg-secondary"><?php echo $type_labels[$card->fields['type']] ?? $card->fields['type']; ?></span>
                    <span class="badge bg-warning text-dark"><?php echo $priority_labels[$card->fields['priority']] ?? $card->fields['priority']; ?></span>
                    <?php if ($card->fields['story_points'] > 0): ?>
                        <span class="badge bg-primary"><?php echo $card->fields['story_points']; ?> pts</span>
                    <?php endif; ?>
                    <?php if ($external_reference !== ''): ?>
                        <span class="badge bg-info text-dark"><?php echo htmlspecialchars($external_reference); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($card->fields['description'])): ?>
            <div class="mb-4">
                <h6 class="text-uppercase text-muted small"><?php echo __('Descrição', 'scrumban'); ?></h6>
                <div class="p-3 bg-light rounded shadow-sm">
                    <?php echo nl2br(htmlspecialchars($card->fields['description'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-muted small mb-0"><?php echo __('Critérios de Aceitação', 'scrumban'); ?></h6>
                    <?php if ($criteria_percent !== null): ?>
                    <span class="badge bg-primary bg-opacity-25 text-primary"><?php echo $criteria_percent; ?>%</span>
                    <?php endif; ?>
                </div>
                <div class="p-3 bg-warning bg-opacity-10 rounded shadow-sm">
                    <?php if ($criteria_total === 0): ?>
                        <p class="mb-0 text-muted"><?php echo __('Nenhum critério cadastrado ainda.', 'scrumban'); ?></p>
                    <?php else: ?>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($acceptance_criteria as $criterion): ?>
                                <li class="d-flex align-items-start mb-2">
                                    <span class="me-2">
                                        <?php if (!empty($criterion['is_completed'])): ?>
                                            <i class="fas fa-check-circle text-success"></i>
                                        <?php else: ?>
                                            <i class="far fa-circle text-muted"></i>
                                        <?php endif; ?>
                                    </span>
                                    <div>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($criterion['title']); ?></div>
                                        <?php if (!empty($criterion['description'])): ?>
                                            <div class="text-muted small"><?php echo nl2br(htmlspecialchars($criterion['description'])); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-muted small mb-0"><?php echo __('Cenários de Teste', 'scrumban'); ?></h6>
                    <?php if (!empty($test_scenarios)): ?>
                        <div class="d-flex gap-2 small text-muted">
                            <span><i class="fas fa-hourglass-half text-warning me-1"></i><?php echo $scenario_counts['pending']; ?></span>
                            <span><i class="fas fa-check text-success me-1"></i><?php echo $scenario_counts['passed']; ?></span>
                            <span><i class="fas fa-times text-danger me-1"></i><?php echo $scenario_counts['failed']; ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="p-3 bg-light rounded shadow-sm">
                    <?php if (empty($test_scenarios)): ?>
                        <p class="mb-0 text-muted"><?php echo __('Nenhum cenário de teste cadastrado.', 'scrumban'); ?></p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($test_scenarios as $scenario): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($scenario['title']); ?></h6>
                                        <?php
                                            $status = $scenario['status'] ?? 'pending';
                                            $status_labels = [
                                                'pending' => ['label' => __('Pendente', 'scrumban'), 'class' => 'bg-warning text-dark'],
                                                'passed' => ['label' => __('Passou', 'scrumban'), 'class' => 'bg-success'],
                                                'failed' => ['label' => __('Falhou', 'scrumban'), 'class' => 'bg-danger']
                                            ];
                                            $badge = $status_labels[$status] ?? $status_labels['pending'];
                                        ?>
                                        <span class="badge <?php echo $badge['class']; ?>"><?php echo $badge['label']; ?></span>
                                    </div>
                                    <?php if (!empty($scenario['description'])): ?>
                                        <p class="mb-1 text-muted small"><?php echo nl2br(htmlspecialchars($scenario['description'])); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($scenario['expected_result'])): ?>
                                        <div class="small"><strong><?php echo __('Resultado esperado:', 'scrumban'); ?></strong> <?php echo htmlspecialchars($scenario['expected_result']); ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($labels)): ?>
            <div class="mb-4">
                <h6 class="text-uppercase text-muted small"><?php echo __('Labels', 'scrumban'); ?></h6>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($labels as $label): ?>
                        <span class="badge" style="background-color: <?php echo htmlspecialchars($label['color']); ?>;">
                            <?php echo htmlspecialchars($label['name']); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="card-title text-uppercase text-muted small"><?php echo __('Informações', 'scrumban'); ?></h6>

                    <div class="mb-3">
                        <strong><?php echo __('Responsável', 'scrumban'); ?>:</strong><br>
                        <span class="text-muted"><?php echo htmlspecialchars($card->fields['assignee'] ?: __('Não atribuído', 'scrumban')); ?></span>
                    </div>

                    <div class="mb-3">
                        <strong><?php echo __('Solicitante', 'scrumban'); ?>:</strong><br>
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

                    <?php if ($card->fields['due_date']): ?>
                    <div class="mb-3">
                        <strong><?php echo __('Data Limite', 'scrumban'); ?>:</strong><br>
                        <span class="text-muted"><?php echo Html::convDate($card->fields['due_date']); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($planned_date): ?>
                    <div class="mb-3">
                        <strong><?php echo __('Data Planejada - Entrega', 'scrumban'); ?>:</strong><br>
                        <span class="text-muted"><?php echo $planned_date; ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <strong><?php echo __('Data de Conclusão', 'scrumban'); ?>:</strong><br>
                        <span class="text-muted"><?php echo $completed_at ?: __('Será preenchida quando finalizado', 'scrumban'); ?></span>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="card-title text-uppercase text-muted small"><?php echo __('Progresso', 'scrumban'); ?></h6>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small">
                            <span><?php echo __('DoR', 'scrumban'); ?></span>
                            <span><?php echo $dor; ?>%</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $dor; ?>%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between small">
                            <span><?php echo __('DoD', 'scrumban'); ?></span>
                            <span><?php echo $dod; ?>%</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $dod; ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="card-title text-uppercase text-muted small"><?php echo __('Desenvolvimento', 'scrumban'); ?></h6>
                    <?php if ($branch !== ''): ?>
                    <div class="mb-3">
                        <strong><?php echo __('Branch', 'scrumban'); ?>:</strong><br>
                        <code><?php echo htmlspecialchars($branch); ?></code>
                    </div>
                    <?php endif; ?>

                    <?php if ($pull_request !== ''): ?>
                    <div class="mb-3">
                        <strong><?php echo __('Pull Request', 'scrumban'); ?>:</strong><br>
                        <?php if ($pull_is_url): ?>
                            <a href="<?php echo htmlspecialchars($pull_request); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($pull_request); ?></a>
                        <?php else: ?>
                            <span class="text-muted"><?php echo htmlspecialchars($pull_request); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($commits)): ?>
                    <div>
                        <strong><?php echo __('Commits', 'scrumban'); ?>:</strong>
                        <ul class="small mt-2 mb-0 ps-3">
                            <?php foreach ($commits as $commit): ?>
                                <li><?php echo htmlspecialchars($commit); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php else: ?>
                    <p class="text-muted mb-0"><?php echo __('Nenhum commit registrado ainda.', 'scrumban'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($activity)): ?>
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-uppercase text-muted small"><?php echo __('Atividade Recente', 'scrumban'); ?></h6>
                    <div class="timeline-sm">
                        <?php foreach ($activity as $item): ?>
                        <div class="timeline-item">
                            <small class="text-muted"><?php echo Html::convDateTime($item['date']); ?></small><br>
                            <strong><?php echo htmlspecialchars($item['user']); ?></strong>
                            <?php if ($item['type'] == 'comment'): ?>
                                <?php echo __('comentou', 'scrumban'); ?>: <?php echo htmlspecialchars(substr($item['content'], 0, 120)); ?><?php echo strlen($item['content']) > 120 ? '…' : ''; ?>
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