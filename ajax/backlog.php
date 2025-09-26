<?php

include ('../../../inc/includes.php');

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    $action = $_REQUEST['action'] ?? '';
    
    switch ($action) {
        case 'reorder_backlog':
            $response = reorderBacklog();
            break;
            
        case 'export':
            exportBacklogToCsv();
            break;
            
        case 'import':
            $response = importBacklogFromCsv();
            break;
            
        case 'get_suggestions':
            $response = getSprintSuggestions();
            break;
            
        case 'add_to_sprint':
            $response = addCardsToSprint();
            break;
            
        default:
            $response['message'] = __('Ação não reconhecida', 'scrumban');
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

if ($action != 'export') {
    echo json_encode($response);
}
exit;

/**
 * Reordenar itens do backlog
 */
function reorderBacklog() {
    $board_id = intval($_POST['board_id']);
    $orders = json_decode($_POST['orders'], true);
    
    if (PluginScrumbanBacklog::reorderBacklog($board_id, $orders)) {
        return [
            'success' => true,
            'message' => __('Backlog reordenado com sucesso', 'scrumban')
        ];
    }
    
    return [
        'success' => false,
        'message' => __('Erro ao reordenar backlog', 'scrumban')
    ];
}

/**
 * Exportar backlog para CSV
 */
function exportBacklogToCsv() {
    $board_id = intval($_GET['board_id']);
    PluginScrumbanBacklog::exportToCsv($board_id);
}

/**
 * Importar backlog de CSV
 */
function importBacklogFromCsv() {
    $board_id = intval($_POST['board_id']);
    
    if (!isset($_FILES['file'])) {
        return [
            'success' => false,
            'message' => __('Nenhum arquivo enviado', 'scrumban')
        ];
    }
    
    $file = $_FILES['file'];
    $temp_path = $file['tmp_name'];
    
    $imported = PluginScrumbanBacklog::importFromCsv($board_id, $temp_path);
    
    if ($imported > 0) {
        return [
            'success' => true,
            'message' => sprintf(__('%d itens importados com sucesso', 'scrumban'), $imported),
            'imported' => $imported
        ];
    }
    
    return [
        'success' => false,
        'message' => __('Erro ao importar arquivo', 'scrumban')
    ];
}

/**
 * Obter sugestões para planejamento de sprint
 */
function getSprintSuggestions() {
    $board_id = intval($_GET['board_id']);
    $capacity = intval($_GET['capacity'] ?? 0);
    
    $suggestions = PluginScrumbanBacklog::getSuggestions($board_id, $capacity);
    
    return [
        'success' => true,
        'suggestions' => $suggestions
    ];
}

/**
 * Adicionar múltiplos cards à sprint
 */
function addCardsToSprint() {
    $card_ids = $_POST['card_ids'] ?? [];
    $sprint_id = intval($_POST['sprint_id']);
    
    if (is_string($card_ids)) {
        $card_ids = json_decode($card_ids, true);
    }
    
    if (PluginScrumbanBacklog::addCardsToSprint($card_ids, $sprint_id)) {
        return [
            'success' => true,
            'message' => sprintf(__('%d cards adicionados à sprint', 'scrumban'), count($card_ids))
        ];
    }
    
    return [
        'success' => false,
        'message' => __('Erro ao adicionar cards à sprint', 'scrumban')
    ];
}