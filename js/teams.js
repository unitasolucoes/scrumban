/**
 * Teams Management JavaScript
 * Sistema de Equipes para Scrumban Plugin
 */

let currentTeamId = null;

/**
 * Initialize teams functionality
 */
function initializeTeams() {
    console.log('Initializing teams functionality...');
    
    // Initialize form handlers
    initializeTeamForms();
    
    // Initialize member management
    initializeMemberManagement();
    
    // Initialize board management
    initializeBoardManagement();
    
    // Initialize tooltips
    initializeTooltips();
    
    console.log('Teams functionality initialized');
}

/**
 * Initialize team forms
 */
function initializeTeamForms() {
    // Create team form
    const createForm = document.getElementById('createTeamForm');
    if (createForm) {
        createForm.addEventListener('submit', handleCreateTeam);
    }
    
    // Edit team form
    const editForm = document.getElementById('editTeamForm');
    if (editForm) {
        editForm.addEventListener('submit', handleEditTeam);
    }
    
    // Delete team button
    const deleteBtn = document.getElementById('deleteTeamBtn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', handleDeleteTeam);
    }
}

/**
 * Initialize member management
 */
function initializeMemberManagement() {
    // Add member form
    const addMemberForm = document.getElementById('addMemberForm');
    if (addMemberForm) {
        addMemberForm.addEventListener('submit', handleAddMember);
    }
    
    // Add member button
    const addMemberBtn = document.getElementById('addMemberBtn');
    if (addMemberBtn) {
        addMemberBtn.addEventListener('click', openAddMemberModal);
    }
}

/**
 * Initialize board management
 */
function initializeBoardManagement() {
    // Add board form
    const addBoardForm = document.getElementById('addBoardForm');
    if (addBoardForm) {
        addBoardForm.addEventListener('submit', handleAddBoard);
    }
    
    // Add board button
    const addBoardBtn = document.getElementById('addBoardBtn');
    if (addBoardBtn) {
        addBoardBtn.addEventListener('click', openAddBoardModal);
    }
}

/**
 * Handle create team form submission
 */
function handleCreateTeam(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    formData.append('action', 'create_team');
    
    showLoading('Creating team...');
    
    fetch(getPluginUrl() + '/ajax/team.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            showNotification('Team created successfully!', 'success');
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('createTeamModal'));
            modal.hide();
            
            // Reset form
            e.target.reset();
            
            // Reload page
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('Error creating team: ' + data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showNotification('Network error occurred', 'error');
    });
}

/**
 * Handle edit team form submission
 */
function handleEditTeam(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    formData.append('action', 'update_team');
    
    showLoading('Updating team...');
    
    fetch(getPluginUrl() + '/ajax/team.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            showNotification('Team updated successfully!', 'success');
        } else {
            showNotification('Error updating team: ' + data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showNotification('Network error occurred', 'error');
    });
}

/**
 * Handle delete team
 */
function handleDeleteTeam() {
    if (!currentTeamId) {
        showNotification('No team selected', 'error');
        return;
    }
    
    if (!confirm('Are you sure you want to delete this team? This action cannot be undone.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_team');
    formData.append('team_id', currentTeamId);
    
    showLoading('Deleting team...');
    
    fetch(getPluginUrl() + '/ajax/team.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            showNotification('Team deleted successfully!', 'success');
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('manageTeamModal'));
            modal.hide();
            
            // Reload page
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('Error deleting team: ' + data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showNotification('Network error occurred', 'error');
    });
}

/**
 * Load team management modal
 */
function loadTeamManagement(teamId) {
    currentTeamId = teamId;
    
    showLoading('Loading team details...');
    
    fetch(getPluginUrl() + '/ajax/team.php?action=get_team_details&team_id=' + teamId)
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            populateTeamManagement(data);
            
            const modal = new bootstrap.Modal(document.getElementById('manageTeamModal'));
            modal.show();
        } else {
            showNotification('Error loading team: ' + data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showNotification('Network error occurred', 'error');
    });
}

/**
 * Populate team management modal with data
 */
function populateTeamManagement(data) {
    const team = data.team;
    const members = data.members;
    const boards = data.boards;
    
    // Populate team info
    document.getElementById('edit_team_id').value = team.id;
    document.getElementById('edit_team_name').value = team.name || '';
    document.getElementById('edit_team_description').value = team.description || '';
    
    // Set manager dropdown
    const managerSelect = document.querySelector('#editTeamForm select[name="manager_id"]');
    if (managerSelect && team.manager_id) {
        managerSelect.value = team.manager_id;
    }
    
    // Populate members list
    populateMembersList(members);
    
    // Populate boards list
    populateBoardsList(boards);
}

/**
 * Populate members list
 */
function populateMembersList(members) {
    const container = document.getElementById('team-members-list');
    
    if (!members || members.length === 0) {
        container.innerHTML = '<div class="alert alert-info">No members in this team yet.</div>';
        return;
    }
    
    let html = '<div class="table-responsive">';
    html += '<table class="table table-hover">';
    html += '<thead><tr>';
    html += '<th>User</th>';
    html += '<th>Role</th>';
    html += '<th>Since</th>';
    html += '<th>Actions</th>';
    html += '</tr></thead><tbody>';
    
    members.forEach(member => {
        const initials = getInitials(member.firstname + ' ' + member.realname);
        const roleClass = 'role-' + member.role;
        
        html += '<tr>';
        html += '<td>';
        html += '<div class="d-flex align-items-center">';
        html += `<div class="member-avatar-sm ${roleClass} me-3">${initials}</div>`;
        html += '<div>';
        html += `<div class="fw-bold">${escapeHtml(member.firstname + ' ' + member.realname)}</div>`;
        html += `<div class="text-muted small">${escapeHtml(member.username)}</div>`;
        html += '</div></div></td>';
        
        html += '<td>';
        html += `<select class="form-select form-select-sm" onchange="updateMemberRole(${member.users_id}, ${currentTeamId}, this.value)">`;
        html += `<option value="member" ${member.role === 'member' ? 'selected' : ''}>Member</option>`;
        html += `<option value="lead" ${member.role === 'lead' ? 'selected' : ''}>Lead</option>`;
        html += `<option value="admin" ${member.role === 'admin' ? 'selected' : ''}>Administrator</option>`;
        html += '</select>';
        html += '</td>';
        
        html += `<td>${formatDate(member.date_creation)}</td>`;
        
        html += '<td>';
        html += `<button class="btn btn-outline-danger btn-sm" onclick="removeMember(${member.users_id}, ${currentTeamId})">`;
        html += '<i class="fas fa-trash"></i></button>';
        html += '</td>';
        
        html += '</tr>';
    });
    
    html += '</tbody></table></div>';
    container.innerHTML = html;
}

/**
 * Populate boards list
 */
function populateBoardsList(boards) {
    const container = document.getElementById('team-boards-list');
    
    if (!boards || boards.length === 0) {
        container.innerHTML = '<div class="alert alert-info">No boards associated with this team yet.</div>';
        return;
    }
    
    let html = '<div class="row">';
    
    boards.forEach(board => {
        html += '<div class="col-md-6 col-lg-4 mb-3">';
        html += '<div class="board-card">';
        html += '<div class="board-card-header">';
        html += `<h5>${escapeHtml(board.name)}</h5>`;
        html += '<div class="board-actions">';
        html += `<button class="btn btn-outline-secondary btn-sm" onclick="editBoardPermissions(${currentTeamId}, ${board.id})">`;
        html += '<i class="fas fa-cog"></i></button>';
        html += `<button class="btn btn-outline-danger btn-sm" onclick="removeBoardFromTeam(${currentTeamId}, ${board.id})">`;
        html += '<i class="fas fa-unlink"></i></button>';
        html += '</div></div>';
        
        if (board.description) {
            html += `<p class="board-description text-muted">${escapeHtml(board.description.substring(0, 100))}${board.description.length > 100 ? '...' : ''}</p>`;
        }
        
        // Permissions
        html += '<div class="board-permissions">';
        html += '<div class="permissions-grid">';
        html += '<div class="permission-item">';
        html += '<i class="fas fa-eye text-primary"></i><span>View</span>';
        html += '<i class="fas fa-check text-success"></i>';
        html += '</div>';
        html += '<div class="permission-item">';
        html += '<i class="fas fa-edit text-warning"></i><span>Edit</span>';
        html += board.can_edit ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>';
        html += '</div>';
        html += '<div class="permission-item">';
        html += '<i class="fas fa-cogs text-info"></i><span>Manage</span>';
        html += board.can_manage ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>';
        html += '</div>';
        html += '</div></div>';
        
        html += '<div class="board-card-footer">';
        html += `<a href="${getPluginUrl()}/front/board.php?board_id=${board.id}" class="btn btn-outline-primary btn-sm">`;
        html += '<i class="fas fa-external-link-alt me-1"></i>Open Board</a>';
        html += '</div>';
        
        html += '</div></div>';
    });
    
    html += '</div>';
    container.innerHTML = html;
}

/**
 * Open add member modal
 */
function openAddMemberModal() {
    if (!currentTeamId) {
        showNotification('No team selected', 'error');
        return;
    }
    
    document.getElementById('add_member_team_id').value = currentTeamId;
    
    // Load available users
    fetch(getPluginUrl() + '/ajax/team.php?action=get_available_users&team_id=' + currentTeamId)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const select = document.getElementById('available_users');
            select.innerHTML = '<option value="">Select user...</option>';
            
            data.users.forEach(user => {
                const option = document.createElement('option');
                option.value = user.id;
                option.textContent = `${user.firstname} ${user.realname} (${user.name})`;
                select.appendChild(option);
            });
            
            const modal = new bootstrap.Modal(document.getElementById('addMemberModal'));
            modal.show();
        } else {
            showNotification('Error loading users: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Network error occurred', 'error');
    });
}

/**
 * Handle add member form submission
 */
function handleAddMember(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    formData.append('action', 'add_member');
    
    showLoading('Adding member...');
    
    fetch(getPluginUrl() + '/ajax/team.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            showNotification('Member added successfully!', 'success');
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('addMemberModal'));
            modal.hide();
            
            // Reset form
            e.target.reset();
            
            // Reload team management
            loadTeamManagement(currentTeamId);
        } else {
            showNotification('Error adding member: ' + data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showNotification('Network error occurred', 'error');
    });
}

/**
 * Update member role
 */
function updateMemberRole(userId, teamId, newRole) {
    const formData = new FormData();
    formData.append('action', 'update_member_role');
    formData.append('user_id', userId);
    formData.append('team_id', teamId);
    formData.append('role', newRole);
    
    fetch(getPluginUrl() + '/ajax/team.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Member role updated!', 'success');
        } else {
            showNotification('Error updating role: ' + data.message, 'error');
            // Reload to reset the dropdown
            loadTeamManagement(currentTeamId);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Network error occurred', 'error');
    });
}

/**
 * Remove member from team
 */
function removeMember(userId, teamId) {
    if (!confirm('Are you sure you want to remove this member from the team?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'remove_member');
    formData.append('user_id', userId);
    formData.append('team_id', teamId);
    
    fetch(getPluginUrl() + '/ajax/team.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Member removed successfully!', 'success');
            loadTeamManagement(currentTeamId);
        } else {
            showNotification('Error removing member: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Network error occurred', 'error');
    });
}

/**
 * Open add board modal
 */
function openAddBoardModal() {
    if (!currentTeamId) {
        showNotification('No team selected', 'error');
        return;
    }
    
    document.getElementById('add_board_team_id').value = currentTeamId;
    
    // Load available boards
    fetch(getPluginUrl() + '/ajax/team.php?action=get_available_boards&team_id=' + currentTeamId)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const select = document.getElementById('available_boards');
            select.innerHTML = '<option value="">Select board...</option>';
            
            data.boards.forEach(board => {
                const option = document.createElement('option');
                option.value = board.id;
                option.textContent = board.name;
                select.appendChild(option);
            });
            
            const modal = new bootstrap.Modal(document.getElementById('addBoardModal'));
            modal.show();
        } else {
            showNotification('Error loading boards: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Network error occurred', 'error');
    });
}

/**
 * Handle add board form submission
 */
function handleAddBoard(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    formData.append('action', 'add_board');
    
    showLoading('Adding board...');
    
    fetch(getPluginUrl() + '/ajax/team.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            showNotification('Board added successfully!', 'success');
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('addBoardModal'));
            modal.hide();
            
            // Reset form
            e.target.reset();
            
            // Reload team management
            loadTeamManagement(currentTeamId);
        } else {
            showNotification('Error adding board: ' + data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showNotification('Network error occurred', 'error');
    });
}

/**
 * Remove board from team
 */
function removeBoardFromTeam(teamId, boardId) {
    if (!confirm('Are you sure you want to remove this board from the team?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'remove_board');
    formData.append('team_id', teamId);
    formData.append('board_id', boardId);
    
    fetch(getPluginUrl() + '/ajax/team.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Board removed successfully!', 'success');
            loadTeamManagement(currentTeamId);
        } else {
            showNotification('Error removing board: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Network error occurred', 'error');
    });
}

/**
 * Edit board permissions (placeholder)
 */
function editBoardPermissions(teamId, boardId) {
    showNotification('Board permissions editor - coming soon!', 'info');
}

/**
 * Utility functions
 */
function getInitials(name) {
    const words = name.trim().split(' ');
    let initials = '';
    for (const word of words) {
        if (word.length > 0) {
            initials += word.charAt(0).toUpperCase();
            if (initials.length >= 2) break;
        }
    }
    return initials || '--';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
}

function showLoading(message) {
    // Use existing notification system or create loading indicator
    console.log('Loading:', message);
}

function hideLoading() {
    console.log('Loading finished');
}

function getPluginUrl() {
    return '/plugins/scrumban';
}

// Make functions globally available
window.TeamManagement = {
    initializeTeams,
    loadTeamManagement,
    updateMemberRole,
    removeMember,
    removeBoardFromTeam,
    editBoardPermissions
};