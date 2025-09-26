<?php

// Evitar qualquer output antes do JSON
ob_start();

include ('../../../inc/includes.php');

// Limpar qualquer output buffer anterior
ob_clean();

header('Content-Type: application/json');

// Função para log de debug
function debugLog($message, $data = null) {
    error_log("[SCRUMBAN DEBUG] $message" . ($data ? " - Data: " . print_r($data, true) : ""));
}

$response = ['success' => false, 'message' => ''];

try {
    $action = $_REQUEST['action'] ?? '';
    debugLog("Ação recebida", $action);
    
    switch ($action) {
        case 'create':
            $response = createSprint();
            break;
            
        case 'update':
            $response = updateSprint();
            break;
            
        case 'start':
            $response = startSprint();
            break;
            
        case 'finish':
            $response = finishSprint();
            break;
            
        case 'get_metrics':
            $response = getSprintMetrics();
            break;
            
        case 'add_card':
            $response = addCardToSprint();
            break;
            
        case 'remove_card':
            $response = removeCardFromSprint();
            break;
            
        case 'get_details':
            $response = getSprintDetails();
            break;
            
        default:
            $response['message'] = "Ação não reconhecida: $action";
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    debugLog("Exception", $e->getMessage());
} catch (Error $e) {
    $response['message'] = "Erro PHP: " . $e->getMessage();
    debugLog("Error", $e->getMessage());
}

// Garantir que só enviamos JSON
ob_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;

/**
 * Criar nova sprint
 */
function createSprint() {
    $sprint = new PluginScrumbanSprint();
    
    $input = [
        'boards_id' => $_POST['boards_id'] ?? 1,
        'name' => $_POST['name'] ?? '',
        'objective' => $_POST['objective'] ?? '',
        'start_date' => $_POST['start_date'] ?: null,
        'end_date' => $_POST['end_date'] ?: null,
        'status' => $_POST['status'] ?? 'planejado',
        'capacity' => intval($_POST['capacity'] ?? 0)
    ];
    
    if ($sprint->add($input)) {
        return [
            'success' => true,
            'message' => __('Sprint criada com sucesso', 'scrumban'),
            'sprint_id' => $sprint->getID()
        ];
    } else {
        return [
            'success' => false,
            'message' => __('Erro ao criar sprint', 'scrumban')
        ];
    }
}

/**
 * Atualizar sprint
 */
function updateSprint() {
    $sprint = new PluginScrumbanSprint();
    $sprint_id = intval($_POST['id']);
    
    if (!$sprint->getFromDB($sprint_id)) {
        return [
            'success' => false,
            'message' => 'Sprint não encontrada'
        ];
    }
    
    $input = $_POST;
    $input['id'] = $sprint_id;
    
    // Remover campos que podem causar problema
    unset($input['action']);
    
    $result = $sprint->update($input);
    
    if ($result) {
        return [
            'success' => true,
            'message' => 'Sprint atualizada com sucesso'
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Erro ao atualizar sprint'
    ];
}

/**
 * Iniciar sprint
 */
function startSprint() {
    $sprint = new PluginScrumbanSprint();
    $sprint_id = intval($_POST['sprint_id']);
    
    if ($sprint->getFromDB($sprint_id)) {
        if ($sprint->startSprint()) {
            return [
                'success' => true,
                'message' => __('Sprint iniciada com sucesso', 'scrumban')
            ];
        }
    }
    
    return [
        'success' => false,
        'message' => __('Erro ao iniciar sprint', 'scrumban')
    ];
}

/**
 * Finalizar sprint
 */
function finishSprint() {
    $sprint = new PluginScrumbanSprint();
    $sprint_id = intval($_POST['sprint_id']);
    
    if ($sprint->getFromDB($sprint_id)) {
        if ($sprint->finishSprint()) {
            return [
                'success' => true,
                'message' => __('Sprint finalizada com sucesso', 'scrumban')
            ];
        }
    }
    
    return [
        'success' => false,
        'message' => __('Erro ao finalizar sprint', 'scrumban')
    ];
}

/**
 * Obter métricas da sprint
 */
function getSprintMetrics() {
    $sprint = new PluginScrumbanSprint();
    $sprint_id = intval($_GET['sprint_id']);
    
    if (!$sprint->getFromDB($sprint_id)) {
        return [
            'success' => false,
            'message' => __('Sprint não encontrada', 'scrumban')
        ];
    }
    
    $metrics = $sprint->calculateMetrics();
    $burndown = $sprint->getBurndownData();
    
    return [
        'success' => true,
        'metrics' => $metrics,
        'burndown' => $burndown
    ];
}

/**
 * Adicionar card à sprint
 */
function addCardToSprint() {
    $sprint = new PluginScrumbanSprint();
    $sprint_id = intval($_POST['sprint_id']);
    $card_id = intval($_POST['card_id']);
    
    if ($sprint->getFromDB($sprint_id)) {
        if ($sprint->addCard($card_id)) {
            return [
                'success' => true,
                'message' => __('Card adicionado à sprint com sucesso', 'scrumban')
            ];
        }
    }
    
    return [
        'success' => false,
        'message' => __('Erro ao adicionar card à sprint', 'scrumban')
    ];
}

/**
 * Remover card da sprint
 */
function removeCardFromSprint() {
    $sprint = new PluginScrumbanSprint();
    $sprint_id = intval($_POST['sprint_id']);
    $card_id = intval($_POST['card_id']);
    
    if ($sprint->getFromDB($sprint_id)) {
        if ($sprint->removeCard($card_id)) {
            return [
                'success' => true,
                'message' => __('Card removido da sprint com sucesso', 'scrumban')
            ];
        }
    }
    
    return [
        'success' => false,
        'message' => __('Erro ao remover card da sprint', 'scrumban')
    ];
}

/**
 * Obter detalhes da sprint
 */
function getSprintDetails() {
    $sprint = new PluginScrumbanSprint();
    $sprint_id = intval($_GET['sprint_id']);
    
    if (!$sprint->getFromDB($sprint_id)) {
        return [
            'success' => false,
            'message' => __('Sprint não encontrada', 'scrumban')
        ];
    }
    
    return [
        'success' => true,
        'sprint' => $sprint->fields
    ];
}