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
    
    ob_start();
    ?>
    <div class="row">
        <div class="col-md-8">
            <div class="mb-4">
                <h4><?php echo htmlspecialchars($card->fields['title']); ?></h4>
                <div class="d-flex gap-2 mb-3">
                    <span class="badge bg-secondary"><?php echo $type_labels[$card->fields['type']] ?? $card->fields['type']; ?></span>
                    <span class="badge bg-info"><?php echo __('Backlog', 'scrumban'); ?></span>
                    <span class="badge bg-warning"><?php echo $priority_labels[$card->fields['priority']] ?? $card->fields['priority']; ?></span>
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
            
            <div class="mb-4">
                <h6><?php echo __('Critérios de Aceitação', 'scrumban'); ?></h6>
                <div class="p-3 bg-warning bg-opacity-10 rounded">
                    <ul class="mb-0">
                        <li><?php echo __('Funcionalidade deve estar implementada', 'scrumban'); ?></li>
                        <li><?php echo __('Testes unitários aprovados', 'scrumban'); ?></li>
                        <li><?php echo __('Documentação atualizada', 'scrumban'); ?></li>
                        <li><?php echo __('Code review realizado', 'scrumban'); ?></li>
                    </ul>
                </div>
            </div>
            
            <?php if (!empty($labels)): ?>
            <div>
                <h6><?php echo __('Labels', 'scrumban'); ?></h6>
                <div class="d-flex gap-2">
                    <?php foreach ($labels as $label): ?>
                        <span class="badge bg-primary" style="background-color: <?php echo $label['color']; ?> !important;">
                            <?php echo htmlspecialchars($label['name']); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-4">
            <div class="card">
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
                        <strong><?php echo __('Criado em', 'scrumban'); ?>:</strong><br>
                        <span class="text-muted"><?php echo Html::convDateTime($card->fields['date_creation']); ?></span>
                    </div>
                    
                    <div class="mb-3">
                        <strong><?php echo __('Story Points', 'scrumban'); ?>:</strong><br>
                        <span class="text-muted"><?php echo $card->fields['story_points'] ?: __('Não estimado', 'scrumban'); ?></span>
                    </div>
                    
                    <?php if ($card->fields['due_date']): ?>
                    <div class="mb-3">
                        <strong><?php echo __('Data Limite', 'scrumban'); ?>:</strong><br>
                        <span class="text-muted"><?php echo Html::convDate($card->fields['due_date']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($activity)): ?>
            <div class="card mt-3">
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