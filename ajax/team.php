<?php

// Start output buffering to prevent any accidental output
ob_start();

include('../../../inc/includes.php');

// Clear any previous output
ob_clean();

// Set proper headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Initialize response structure
$response = [
    'success' => false,
    'message' => '',
    'data' => null,
    'errors' => []
];

// Error handling function
function sendError($message, $code = 400) {
    global $response;
    http_response_code($code);
    $response['success'] = false;
    $response['message'] = $message;
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Success function
function sendSuccess($message = '', $data = null) {
    global $response;
    $response['success'] = true;
    $response['message'] = $message;
    $response['data'] = $data;
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Security check
if (!isset($_SESSION['glpiID']) || $_SESSION['glpiID'] <= 0) {
    sendError(__('Authentication required', 'scrumban'), 401);
}

// Validate action parameter
$action = $_REQUEST['action'] ?? '';
if (empty($action)) {
    sendError(__('Action parameter is required', 'scrumban'));
}

// Sanitize input function
function sanitizeInput($input, $type = 'string') {
    if ($input === null) return null;
    
    switch ($type) {
        case 'int':
            return filter_var($input, FILTER_VALIDATE_INT);
        case 'email':
            return filter_var($input, FILTER_VALIDATE_EMAIL);
        case 'string':
        default:
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

try {
    switch ($action) {
        case 'create_team':
            handleCreateTeam();
            break;
            
        case 'update_team':
            handleUpdateTeam();
            break;
            
        case 'delete_team':
            handleDeleteTeam();
            break;
            
        case 'add_member':
            handleAddMember();
            break;
            
        case 'update_member_role':
            handleUpdateMemberRole();
            break;
            
        case 'remove_member':
            handleRemoveMember();
            break;
            
        case 'add_board':
            handleAddBoard();
            break;
            
        case 'remove_board':
            handleRemoveBoard();
            break;
            
        case 'update_board_permissions':
            handleUpdateBoardPermissions();
            break;
            
        case 'get_available_users':
            handleGetAvailableUsers();
            break;
            
        case 'get_available_boards':
            handleGetAvailableBoards();
            break;
            
        case 'get_team_details':
            handleGetTeamDetails();
            break;
            
        case 'get_user_teams':
            handleGetUserTeams();
            break;
            
        default:
            sendError(__('Unknown action', 'scrumban') . ": $action");
    }
    
} catch (Exception $e) {
    error_log("Scrumban Team AJAX Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    sendError(__('Internal server error', 'scrumban'), 500);
} catch (Throwable $e) {
    error_log("Scrumban Team AJAX Fatal Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    sendError(__('Fatal error occurred', 'scrumban'), 500);
}

/**
 * Handle team creation
 */
function handleCreateTeam() {
    // Validate required fields
    $name = sanitizeInput($_POST['name'] ?? '', 'string');
    if (empty($name)) {
        sendError(__('Team name is required', 'scrumban'));
    }
    
    if (strlen($name) > 255) {
        sendError(__('Team name is too long (maximum 255 characters)', 'scrumban'));
    }

    $description = sanitizeInput($_POST['description'] ?? '', 'string');
    $manager_id = sanitizeInput($_POST['manager_id'] ?? 0, 'int');

    // Create team
    $team = new PluginScrumbanTeam();
    $input = [
        'name' => $name,
        'description' => $description,
        'manager_id' => $manager_id ?: 0,
        'is_active' => 1,
        'entities_id' => $_SESSION['glpiactive_entity'] ?? 0
    ];
    
    $team_id = $team->add($input);
    
    if ($team_id) {
        sendSuccess(__('Team created successfully', 'scrumban'), ['team_id' => $team_id]);
    } else {
        sendError(__('Failed to create team', 'scrumban'));
    }
}

/**
 * Handle team update
 */
function handleUpdateTeam() {
    $team_id = sanitizeInput($_POST['id'] ?? 0, 'int');
    if (!$team_id) {
        sendError(__('Team ID is required', 'scrumban'));
    }

    // Load team
    $team = new PluginScrumbanTeam();
    if (!$team->getFromDB($team_id)) {
        sendError(__('Team not found', 'scrumban'), 404);
    }

    // Check permissions
    $user_role = PluginScrumbanTeam::getUserRoleInTeam($_SESSION['glpiID'], $team_id);
    if (!in_array($user_role, ['admin', 'lead'])) {
        sendError(__('You do not have permission to update this team', 'scrumban'), 403);
    }

    // Validate input
    $name = sanitizeInput($_POST['name'] ?? '', 'string');
    if (empty($name)) {
        sendError(__('Team name is required', 'scrumban'));
    }
    
    if (strlen($name) > 255) {
        sendError(__('Team name is too long', 'scrumban'));
    }

    $description = sanitizeInput($_POST['description'] ?? '', 'string');
    $manager_id = sanitizeInput($_POST['manager_id'] ?? 0, 'int');

    $input = [
        'id' => $team_id,
        'name' => $name,
        'description' => $description,
        'manager_id' => $manager_id ?: 0
    ];
    
    if ($team->update($input)) {
        sendSuccess(__('Team updated successfully', 'scrumban'));
    } else {
        sendError(__('Failed to update team', 'scrumban'));
    }
}

/**
 * Handle team deletion
 */
function handleDeleteTeam() {
    $team_id = sanitizeInput($_POST['team_id'] ?? 0, 'int');
    if (!$team_id) {
        sendError(__('Team ID is required', 'scrumban'));
    }

    // Load team
    $team = new PluginScrumbanTeam();
    if (!$team->getFromDB($team_id)) {
        sendError(__('Team not found', 'scrumban'), 404);
    }

    // Check permissions - only admins can delete
    $user_role = PluginScrumbanTeam::getUserRoleInTeam($_SESSION['glpiID'], $team_id);
    if ($user_role !== 'admin') {
        sendError(__('You need administrator role to delete this team', 'scrumban'), 403);
    }

    if ($team->delete(['id' => $team_id])) {
        sendSuccess(__('Team deleted successfully', 'scrumban'));
    } else {
        sendError(__('Failed to delete team', 'scrumban'));
    }
}

/**
 * Handle adding member to team
 */
function handleAddMember() {
    $team_id = sanitizeInput($_POST['team_id'] ?? 0, 'int');
    $user_id = sanitizeInput($_POST['user_id'] ?? 0, 'int');
    $role = sanitizeInput($_POST['role'] ?? 'member', 'string');

    // Validate inputs
    if (!$team_id || !$user_id) {
        sendError(__('Team ID and User ID are required', 'scrumban'));
    }

    if (!in_array($role, ['member', 'lead', 'admin'])) {
        sendError(__('Invalid role specified', 'scrumban'));
    }

    // Check permissions
    if (!PluginScrumbanTeamMember::canManageTeamMembers($_SESSION['glpiID'], $team_id)) {
        sendError(__('You do not have permission to add members', 'scrumban'), 403);
    }

    // Check if user exists
    $user = new User();
    if (!$user->getFromDB($user_id)) {
        sendError(__('User not found', 'scrumban'), 404);
    }

    $result = PluginScrumbanTeamMember::addMemberToTeam($team_id, $user_id, $role);
    
    if ($result) {
        sendSuccess(__('Member added successfully', 'scrumban'));
    } else {
        sendError(__('User is already a member of this team or another error occurred', 'scrumban'));
    }
}

/**
 * Handle member role update
 */
function handleUpdateMemberRole() {
    $team_id = sanitizeInput($_POST['team_id'] ?? 0, 'int');
    $user_id = sanitizeInput($_POST['user_id'] ?? 0, 'int');
    $new_role = sanitizeInput($_POST['role'] ?? '', 'string');

    // Validate inputs
    if (!$team_id || !$user_id) {
        sendError(__('Team ID and User ID are required', 'scrumban'));
    }

    if (!in_array($new_role, ['member', 'lead', 'admin'])) {
        sendError(__('Invalid role specified', 'scrumban'));
    }

    // Check permissions
    if (!PluginScrumbanTeamMember::canManageTeamMembers($_SESSION['glpiID'], $team_id)) {
        sendError(__('You do not have permission to change roles', 'scrumban'), 403);
    }

    $result = PluginScrumbanTeamMember::updateMemberRole($team_id, $user_id, $new_role);
    
    if ($result) {
        sendSuccess(__('Member role updated successfully', 'scrumban'));
    } else {
        sendError(__('Cannot change role - user might be the last administrator', 'scrumban'));
    }
}

/**
 * Handle member removal
 */
function handleRemoveMember() {
    $team_id = sanitizeInput($_POST['team_id'] ?? 0, 'int');
    $user_id = sanitizeInput($_POST['user_id'] ?? 0, 'int');

    // Validate inputs
    if (!$team_id || !$user_id) {
        sendError(__('Team ID and User ID are required', 'scrumban'));
    }

    // Check permissions
    if (!PluginScrumbanTeamMember::canManageTeamMembers($_SESSION['glpiID'], $team_id)) {
        sendError(__('You do not have permission to remove members', 'scrumban'), 403);
    }

    $result = PluginScrumbanTeamMember::removeMemberFromTeam($team_id, $user_id);
    
    if ($result) {
        sendSuccess(__('Member removed successfully', 'scrumban'));
    } else {
        sendError(__('Cannot remove member - might be the last administrator', 'scrumban'));
    }
}

/**
 * Handle adding board to team
 */
function handleAddBoard() {
    $team_id = sanitizeInput($_POST['team_id'] ?? 0, 'int');
    $board_id = sanitizeInput($_POST['board_id'] ?? 0, 'int');
    $can_edit = isset($_POST['can_edit']) ? (bool)$_POST['can_edit'] : true;
    $can_manage = isset($_POST['can_manage']) ? (bool)$_POST['can_manage'] : false;

    // Validate inputs
    if (!$team_id || !$board_id) {
        sendError(__('Team ID and Board ID are required', 'scrumban'));
    }

    // Check permissions
    if (!PluginScrumbanTeamMember::canManageTeamMembers($_SESSION['glpiID'], $team_id)) {
        sendError(__('You do not have permission to add boards', 'scrumban'), 403);
    }

    // Check if board exists
    $board = new PluginScrumbanBoard();
    if (!$board->getFromDB($board_id)) {
        sendError(__('Board not found', 'scrumban'), 404);
    }

    $result = PluginScrumbanTeamBoard::addBoardToTeam($team_id, $board_id, $can_edit, $can_manage);
    
    if ($result) {
        sendSuccess(__('Board added to team successfully', 'scrumban'));
    } else {
        sendError(__('Board is already associated with this team', 'scrumban'));
    }
}

/**
 * Handle removing board from team
 */
function handleRemoveBoard() {
    $team_id = sanitizeInput($_POST['team_id'] ?? 0, 'int');
    $board_id = sanitizeInput($_POST['board_id'] ?? 0, 'int');

    // Validate inputs
    if (!$team_id || !$board_id) {
        sendError(__('Team ID and Board ID are required', 'scrumban'));
    }

    // Check permissions
    if (!PluginScrumbanTeamMember::canManageTeamMembers($_SESSION['glpiID'], $team_id)) {
        sendError(__('You do not have permission to remove boards', 'scrumban'), 403);
    }

    $result = PluginScrumbanTeamBoard::removeBoardFromTeam($team_id, $board_id);
    
    if ($result) {
        sendSuccess(__('Board removed from team successfully', 'scrumban'));
    } else {
        sendError(__('Failed to remove board from team', 'scrumban'));
    }
}

/**
 * Handle board permissions update
 */
function handleUpdateBoardPermissions() {
    $team_id = sanitizeInput($_POST['team_id'] ?? 0, 'int');
    $board_id = sanitizeInput($_POST['board_id'] ?? 0, 'int');
    $can_edit = isset($_POST['can_edit']) ? (bool)$_POST['can_edit'] : false;
    $can_manage = isset($_POST['can_manage']) ? (bool)$_POST['can_manage'] : false;

    // Validate inputs
    if (!$team_id || !$board_id) {
        sendError(__('Team ID and Board ID are required', 'scrumban'));
    }

    // Check permissions
    if (!PluginScrumbanTeamMember::canManageTeamMembers($_SESSION['glpiID'], $team_id)) {
        sendError(__('You do not have permission to update permissions', 'scrumban'), 403);
    }

    $result = PluginScrumbanTeamBoard::updateBoardPermissions($team_id, $board_id, $can_edit, $can_manage);
    
    if ($result) {
        sendSuccess(__('Board permissions updated successfully', 'scrumban'));
    } else {
        sendError(__('Failed to update board permissions', 'scrumban'));
    }
}

/**
 * Handle getting available users
 */
function handleGetAvailableUsers() {
    $team_id = sanitizeInput($_GET['team_id'] ?? 0, 'int');
    
    if (!$team_id) {
        sendError(__('Team ID is required', 'scrumban'));
    }

    // Check if user has access to this team
    $user_role = PluginScrumbanTeam::getUserRoleInTeam($_SESSION['glpiID'], $team_id);
    if (!$user_role) {
        sendError(__('You do not have access to this team', 'scrumban'), 403);
    }

    try {
        $users = PluginScrumbanTeamMember::getAvailableUsers($team_id);
        sendSuccess(__('Available users retrieved successfully', 'scrumban'), $users);
    } catch (Exception $e) {
        sendError(__('Failed to retrieve available users', 'scrumban'));
    }
}

/**
 * Handle getting available boards
 */
function handleGetAvailableBoards() {
    $team_id = sanitizeInput($_GET['team_id'] ?? 0, 'int');
    
    if (!$team_id) {
        sendError(__('Team ID is required', 'scrumban'));
    }

    // Check permissions
    if (!PluginScrumbanTeamMember::canManageTeamMembers($_SESSION['glpiID'], $team_id)) {
        sendError(__('You do not have permission to view available boards', 'scrumban'), 403);
    }

    try {
        $boards = PluginScrumbanTeamBoard::getAvailableBoards($team_id);
        sendSuccess(__('Available boards retrieved successfully', 'scrumban'), $boards);
    } catch (Exception $e) {
        sendError(__('Failed to retrieve available boards', 'scrumban'));
    }
}

/**
 * Handle getting team details
 */
function handleGetTeamDetails() {
    $team_id = sanitizeInput($_GET['team_id'] ?? 0, 'int');
    
    if (!$team_id) {
        sendError(__('Team ID is required', 'scrumban'));
    }

    // Load team
    $team = new PluginScrumbanTeam();
    if (!$team->getFromDB($team_id)) {
        sendError(__('Team not found', 'scrumban'), 404);
    }

    // Check if user has access to this team
    $user_role = PluginScrumbanTeam::getUserRoleInTeam($_SESSION['glpiID'], $team_id);
    if (!$user_role) {
        sendError(__('You do not have access to this team', 'scrumban'), 403);
    }

    try {
        $data = [
            'team' => $team->fields,
            'members' => $team->getMembers(),
            'boards' => $team->getBoards(),
            'stats' => $team->getTeamStats(),
            'user_role' => $user_role
        ];
        
        sendSuccess(__('Team details retrieved successfully', 'scrumban'), $data);
    } catch (Exception $e) {
        sendError(__('Failed to retrieve team details', 'scrumban'));
    }
}

/**
 * Handle getting user teams
 */
function handleGetUserTeams() {
    $user_id = sanitizeInput($_GET['user_id'] ?? $_SESSION['glpiID'], 'int');
    
    // Users can only get their own teams unless they're admin
    if ($user_id != $_SESSION['glpiID']) {
        // Check if current user is admin of any team
        if (!PluginScrumbanTeam::canUserManageTeams($_SESSION['glpiID'])) {
            sendError(__('You can only view your own teams', 'scrumban'), 403);
        }
    }

    try {
        $teams = PluginScrumbanTeam::getTeamsForUser($user_id);
        sendSuccess(__('User teams retrieved successfully', 'scrumban'), $teams);
    } catch (Exception $e) {
        sendError(__('Failed to retrieve user teams', 'scrumban'));
    }
}

// This should never be reached due to switch statement and error handling
sendError(__('Unexpected end of script', 'scrumban'), 500);