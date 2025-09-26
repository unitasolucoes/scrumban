/**
 * Scrumban JavaScript Functions
 * Sistema de Gestão Ágil para GLPI
 */

let currentBoardId = 1;

document.addEventListener('DOMContentLoaded', () => {
    initializeBoardSelectors();
});

function initializeBoardSelectors() {
    const boardSelectors = document.querySelectorAll('[data-scrumban-board-selector]');
    boardSelectors.forEach((selector) => {
        selector.addEventListener('change', handleBoardSelectorChange);
    });

    const teamSelectors = document.querySelectorAll('[data-scrumban-team-selector]');
    teamSelectors.forEach((selector) => {
        selector.addEventListener('change', () => handleTeamSelectorChange(selector));
    });
}

function handleBoardSelectorChange(event) {
    const selector = event.target;
    const boardId = selector.value;

    if (!boardId) {
        return;
    }

    const url = new URL(window.location.href);
    url.searchParams.set('board_id', boardId);

    const teamField = selector.dataset.teamField;
    if (teamField) {
        const teamSelector = document.getElementById(teamField);
        if (teamSelector && teamSelector.value) {
            url.searchParams.set('team_id', teamSelector.value);
        } else {
            url.searchParams.delete('team_id');
        }
    }

    window.location.href = url.toString();
}

function handleTeamSelectorChange(selector) {
    const url = new URL(window.location.href);

    if (selector.value) {
        url.searchParams.set('team_id', selector.value);
    } else {
        url.searchParams.delete('team_id');
    }

    const boardField = selector.dataset.boardField;
    if (boardField) {
        const boardSelector = document.getElementById(boardField);
        if (boardSelector && boardSelector.value) {
            url.searchParams.set('board_id', boardSelector.value);
        }
    }

    window.location.href = url.toString();
}

/**
 * Inicializar funcionalidades do Scrumban
 */
function initializeScrumban(boardId) {
    currentBoardId = boardId;
    
    // Inicializar drag and drop para Kanban
    initializeKanbanDragDrop();
    
    // Inicializar drag and drop para Backlog
    initializeBacklogDragDrop();
    
    // Inicializar eventos
    initializeEvents();
    
    // Atualizar contadores das colunas
    updateColumnCounts();
    
    // Inicializar tooltips
    initializeTooltips();
    
    console.log('Scrumban inicializado para quadro:', boardId);
}

/**
 * Inicializar drag and drop do Kanban
 */
function initializeKanbanDragDrop() {
    const columns = document.querySelectorAll('.cards-container');
    
    columns.forEach(column => {
        new Sortable(column, {
            group: 'kanban',
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            onEnd: function(evt) {
                const cardId = evt.item.dataset.cardId;
                const newColumnId = evt.to.id.replace('column-', '');
                const newIndex = evt.newIndex;
                
                console.log(`Card ${cardId} movido para coluna ${newColumnId} na posição ${newIndex}`);
                
                // Enviar atualização via AJAX
                moveCard(cardId, newColumnId, newIndex);
                
                // Atualizar contadores
                updateColumnCounts();
                
                // Mostrar notificação
                showNotification('Issue movida com sucesso!', 'success');
            }
        });
    });
}

/**
 * Inicializar drag and drop do Backlog
 */
function initializeBacklogDragDrop() {
    const backlogContainer = document.querySelector('.backlog-items');
    if (backlogContainer) {
        new Sortable(backlogContainer, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function(evt) {
                const cardId = evt.item.dataset.cardId;
                console.log(`Item do backlog ${cardId} movido para posição ${evt.newIndex}`);
                
                // Enviar nova ordem via AJAX
                updateBacklogOrder();
                
                showNotification('Posição no backlog atualizada!', 'success');
            }
        });
    }
}

/**
 * Inicializar eventos
 */
function initializeEvents() {
    // Clique em cards para abrir detalhes
    const cards = document.querySelectorAll('.kanban-card');
    cards.forEach(card => {
        card.addEventListener('click', function() {
            const cardId = this.dataset.cardId;
            openCardDetails(cardId);
        });
    });
    
    // Formulários via AJAX
    const createForm = document.getElementById('createIssueForm');
    if (createForm) {
        createForm.addEventListener('submit', handleCreateIssue);
    }
    
    // Busca no backlog
    const searchInput = document.getElementById('backlog-search');
    if (searchInput) {
        searchInput.addEventListener('input', handleBacklogSearch);
    }
    
    // Filtros do backlog
    const priorityFilter = document.getElementById('priority-filter');
    const typeFilter = document.getElementById('type-filter');
    
    if (priorityFilter) {
        priorityFilter.addEventListener('change', handleBacklogFilter);
    }
    
    if (typeFilter) {
        typeFilter.addEventListener('change', handleBacklogFilter);
    }
    
    // Atalhos de teclado
    document.addEventListener('keydown', handleKeyboardShortcuts);
}

/**
 * Mover card para nova coluna
 */
function moveCard(cardId, columnId, position) {
    const formData = new FormData();
    formData.append('action', 'move');
    formData.append('card_id', cardId);
    formData.append('column_id', columnId);
    formData.append('position', position);
    
    fetch(getPluginUrl() + '/ajax/card.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Erro ao mover card:', data.message);
            showNotification('Erro ao mover issue: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erro na requisição:', error);
        showNotification('Erro de conexão', 'error');
    });
}

/**
 * Atualizar ordem do backlog
 */
function updateBacklogOrder() {
    const items = document.querySelectorAll('.backlog-item');
    const orders = [];
    
    items.forEach((item, index) => {
        orders.push({
            card_id: item.dataset.cardId,
            position: index
        });
    });
    
    const formData = new FormData();
    formData.append('action', 'reorder_backlog');
    formData.append('board_id', currentBoardId);
    formData.append('orders', JSON.stringify(orders));
    
    fetch(getPluginUrl() + '/ajax/backlog.php', {
        method: 'POST',
        body: formData
    })
    .catch(error => {
        console.error('Erro ao reordenar backlog:', error);
    });
}

/**
 * Criar nova issue
 */
function handleCreateIssue(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    fetch(getPluginUrl() + '/ajax/card.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Fechar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('createIssueModal'));
            modal.hide();
            
            // Resetar formulário
            e.target.reset();
            
            // Mostrar sucesso
            showNotification('Issue criada com sucesso!', 'success');
            
            // Recarregar página ou adicionar card dinamicamente
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showNotification('Erro ao criar issue: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showNotification('Erro ao criar issue', 'error');
    });
}

/**
 * Abrir modal de criação de issue
 */
function openCreateIssueModal() {
    const modal = new bootstrap.Modal(document.getElementById('createIssueModal'));
    modal.show();
}

/**
 * Abrir detalhes do card
 */
function openCardDetails(cardId) {
    const modal = new bootstrap.Modal(document.getElementById('cardDetailsModal'));
    const content = document.getElementById('cardDetailsContent');
    
    // Mostrar loading
    content.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    
    // Carregar detalhes via AJAX
    fetch(getPluginUrl() + '/ajax/card.php?action=get_details&card_id=' + cardId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                content.innerHTML = data.html;
            } else {
                content.innerHTML = '<div class="alert alert-danger">Erro ao carregar detalhes</div>';
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            content.innerHTML = '<div class="alert alert-danger">Erro de conexão</div>';
        });
    
    modal.show();
}

/**
 * Busca no backlog
 */
function handleBacklogSearch(e) {
    const searchTerm = e.target.value.toLowerCase();
    const items = document.querySelectorAll('.backlog-item');
    
    items.forEach(item => {
        const title = item.querySelector('.item-title').textContent.toLowerCase();
        const description = item.querySelector('.item-description')?.textContent.toLowerCase() || '';
        
        if (title.includes(searchTerm) || description.includes(searchTerm)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

/**
 * Filtros do backlog
 */
function handleBacklogFilter() {
    const priorityFilter = document.getElementById('priority-filter')?.value || '';
    const typeFilter = document.getElementById('type-filter')?.value || '';
    const items = document.querySelectorAll('.backlog-item');
    
    items.forEach(item => {
        const priority = item.dataset.priority || '';
        const type = item.dataset.type || '';
        
        const priorityMatch = !priorityFilter || priority === priorityFilter;
        const typeMatch = !typeFilter || type === typeFilter;
        
        if (priorityMatch && typeMatch) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

/**
 * Atalhos de teclado
 */
function handleKeyboardShortcuts(e) {
    // Ctrl + N: Nova issue
    if (e.ctrlKey && e.key === 'n') {
        e.preventDefault();
        openCreateIssueModal();
    }
    
    // Escape: Fechar modais
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.modal.show');
        modals.forEach(modalEl => {
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
        });
    }
}

/**
 * Atualizar contadores das colunas
 */
function updateColumnCounts() {
    const columns = document.querySelectorAll('.kanban-column');
    columns.forEach(column => {
        const cards = column.querySelectorAll('.kanban-card').length;
        const countElement = column.querySelector('.column-count');
        if (countElement) {
            countElement.textContent = cards;
        }
    });
}

/**
 * Mostrar notificação toast
 */
function showNotification(message, type = 'info') {
    const toastHtml = `
        <div class="toast align-items-center text-white bg-${getBootstrapClass(type)} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${getIconClass(type)} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    // Criar container se não existir
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    // Adicionar toast
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    // Mostrar toast
    const toastElement = toastContainer.lastElementChild;
    const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
    toast.show();
    
    // Remover após ocultar
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

/**
 * Inicializar tooltips
 */
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Utilitários
 */
function getPluginUrl() {
    return location.protocol + '//' + location.host + '/plugins/scrumban';
}

function getBootstrapClass(type) {
    const classes = {
        'success': 'success',
        'error': 'danger',
        'warning': 'warning',
        'info': 'info'
    };
    return classes[type] || 'primary';
}

function getIconClass(type) {
    const icons = {
        'success': 'check-circle',
        'error': 'exclamation-circle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

/**
 * Editar card atual
 */
function editCurrentCard() {
    showNotification('Funcionalidade de edição em desenvolvimento', 'info');
}

/**
 * Editar card específico
 */
function editCard(cardId) {
    showNotification('Funcionalidade de edição em desenvolvimento', 'info');
}

/**
 * Auto-save para formulários
 */
function initializeAutoSave() {
    const formInputs = document.querySelectorAll('input, textarea, select');
    formInputs.forEach(input => {
        let timeout;
        input.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                console.log('Auto-saving...', this.name, this.value);
                // Implementar salvamento automático
            }, 1000);
        });
    });
}

/**
 * Monitoramento de performance
 */
function initializePerformanceMonitoring() {
    if ('performance' in window) {
        window.addEventListener('load', function() {
            setTimeout(() => {
                const perfData = performance.getEntriesByType('navigation')[0];
                console.log('Tempo de carregamento da página:', 
                    perfData.loadEventEnd - perfData.loadEventStart, 'ms');
            }, 0);
        });
    }
}

/**
 * Simulação de atualizações em tempo real
 */
function initializeRealTimeUpdates() {
    setInterval(() => {
        // Simular recebimento de atualizações em tempo real
        if (Math.random() > 0.97) { // 3% de chance a cada 5 segundos
            showNotification('Nova atividade no projeto!', 'info');
        }
    }, 5000);
}

/**
 * Exportar dados do backlog
 */
function exportBacklog() {
    window.location.href = getPluginUrl() + '/ajax/backlog.php?action=export&board_id=' + currentBoardId;
}

/**
 * Importar dados para o backlog
 */
function importBacklog() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.csv';
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (file) {
            const formData = new FormData();
            formData.append('action', 'import');
            formData.append('board_id', currentBoardId);
            formData.append('file', file);
            
            fetch(getPluginUrl() + '/ajax/backlog.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(`${data.imported} itens importados com sucesso!`, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification('Erro ao importar: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erro na importação:', error);
                showNotification('Erro na importação', 'error');
            });
        }
    };
    input.click();
}

/**
 * Inicializar quando documento estiver pronto
 */
document.addEventListener('DOMContentLoaded', function() {
    // Adicionar estilos CSS dinâmicos
    const style = document.createElement('style');
    style.textContent = `
        .sortable-ghost {
            opacity: 0.5;
            background: #e3f2fd;
            transform: rotate(5deg);
        }
        
        .sortable-chosen {
            cursor: grabbing;
        }
        
        .sortable-drag {
            background: white;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .timeline-sm .timeline-item {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .timeline-sm .timeline-item:last-child {
            border-bottom: none;
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .slide-up {
            animation: slideUp 0.3s ease-out;
        }
        
        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
    `;
    document.head.appendChild(style);
    
    // Inicializar auto-save
    initializeAutoSave();
    
    // Inicializar monitoramento de performance
    initializePerformanceMonitoring();
    
    // Inicializar atualizações em tempo real
    initializeRealTimeUpdates();
    
    console.log('Scrumban JavaScript carregado com sucesso');
});

// Expor funções globalmente para uso em templates PHP
window.ScrumbanJS = {
    initializeScrumban,
    openCreateIssueModal,
    openCardDetails,
    showNotification,
    moveCard,
    exportBacklog,
    importBacklog,
    editCard,
    editCurrentCard
};