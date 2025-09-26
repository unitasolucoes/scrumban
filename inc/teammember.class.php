<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You cannot access directly to this file");
}

class PluginScrumbanTeamMember extends CommonDBTM {
   
   static function getTypeName($nb = 0) {
      return _n('Team Member', 'Team Members', $nb, 'scrumban');
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if ($item->getType() == 'PluginScrumbanTeam') {
         return self::getTypeName(Session::getPluralNumber());
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() == 'PluginScrumbanTeam') {
         self::showForTeam($item);
      }
      return true;
   }

   function prepareInputForAdd($input) {
      $input['date_creation'] = $_SESSION['glpi_currenttime'];
      
      // Validate role
      if (!in_array($input['role'], ['member', 'lead', 'admin'])) {
         $input['role'] = 'member';
      }
      
      return $input;
   }

   function prepareInputForUpdate($input) {
      // Validate role
      if (isset($input['role']) && !in_array($input['role'], ['member', 'lead', 'admin'])) {
         $input['role'] = 'member';
      }
      
      return $input;
   }

   /**
    * Show members tab for team
    */
   static function showForTeam(PluginScrumbanTeam $team) {
      global $DB;

      $team_id = $team->getID();
      $can_manage = self::canManageTeamMembers($_SESSION['glpiID'], $team_id);

      echo "<div class='center'>";

      if ($can_manage) {
         echo "<div class='team-member-actions mb-3'>";
         echo "<button class='btn btn-primary' onclick='openAddMemberModal($team_id)'>";
         echo "<i class='fas fa-user-plus me-2'></i>" . __('Add Member', 'scrumban');
         echo "</button>";
         echo "</div>";
      }

      // Get team members
      $members = $team->getMembers();

      if (empty($members)) {
         echo "<div class='alert alert-info'>";
         echo __('No members in this team yet.', 'scrumban');
         echo "</div>";
         echo "</div>";
         return;
      }

      echo "<div class='table-responsive'>";
      echo "<table class='table table-hover'>";
      echo "<thead>";
      echo "<tr>";
      echo "<th>" . __('User', 'scrumban') . "</th>";
      echo "<th>" . __('Role', 'scrumban') . "</th>";
      echo "<th>" . __('Since', 'scrumban') . "</th>";
      if ($can_manage) {
         echo "<th>" . __('Actions', 'scrumban') . "</th>";
      }
      echo "</tr>";
      echo "</thead>";
      echo "<tbody>";

      foreach ($members as $member) {
         echo "<tr>";
         
         // User info
         echo "<td>";
         echo "<div class='d-flex align-items-center'>";
         $initials = self::getInitials($member['firstname'] . ' ' . $member['realname']);
         $role_class = 'role-' . $member['role'];
         echo "<div class='member-avatar-sm $role_class me-3'>$initials</div>";
         echo "<div>";
         echo "<div class='fw-bold'>" . htmlspecialchars($member['firstname'] . ' ' . $member['realname']) . "</div>";
         echo "<div class='text-muted small'>" . htmlspecialchars($member['username']) . "</div>";
         echo "</div>";
         echo "</div>";
         echo "</td>";
         
         // Role
         echo "<td>";
         $role_labels = [
            'member' => __('Member', 'scrumban'),
            'lead' => __('Lead', 'scrumban'),
            'admin' => __('Administrator', 'scrumban')
         ];
         
         if ($can_manage && $member['users_id'] != $_SESSION['glpiID']) {
            echo "<select class='form-select form-select-sm' onchange='updateMemberRole({$member['users_id']}, $team_id, this.value)'>";
            foreach ($role_labels as $role => $label) {
               $selected = ($member['role'] == $role) ? 'selected' : '';
               echo "<option value='$role' $selected>$label</option>";
            }
            echo "</select>";
         } else {
            $badge_class = [
               'member' => 'bg-secondary',
               'lead' => 'bg-warning text-dark',
               'admin' => 'bg-success'
            ];
            echo "<span class='badge {$badge_class[$member['role']]}'>";
            echo $role_labels[$member['role']];
            echo "</span>";
         }
         echo "</td>";
         
         // Date
         echo "<td>";
         echo Html::convDateTime($member['date_creation']);
         echo "</td>";
         
         // Actions
         if ($can_manage) {
            echo "<td>";
            if ($member['users_id'] != $_SESSION['glpiID'] && !self::isLastAdmin($team_id, $member['users_id'])) {
               echo "<button class='btn btn-outline-danger btn-sm' onclick='removeMember({$member['users_id']}, $team_id)'>";
               echo "<i class='fas fa-trash'></i>";
               echo "</button>";
            }
            echo "</td>";
         }
         
         echo "</tr>";
      }

      echo "</tbody>";
      echo "</table>";
      echo "</div>";
      echo "</div>";
   }

   /**
    * Check if user can manage team members
    */
   static function canManageTeamMembers($user_id, $team_id) {
      $role = PluginScrumbanTeam::getUserRoleInTeam($user_id, $team_id);
      return in_array($role, ['admin', 'lead']);
   }

   /**
    * Check if user is the last administrator
    */
   static function isLastAdmin($team_id, $user_id) {
      global $DB;

      // Count total admins
      $iterator = $DB->request([
         'SELECT' => 'COUNT(*) as count',
         'FROM' => 'glpi_plugin_scrumban_team_members',
         'WHERE' => [
            'teams_id' => $team_id,
            'role' => 'admin'
         ]
      ]);

      $total_admins = 0;
      foreach ($iterator as $row) {
         $total_admins = $row['count'];
      }

      // If only one admin and it's this user, they are the last
      if ($total_admins == 1) {
         $iterator = $DB->request([
            'FROM' => 'glpi_plugin_scrumban_team_members',
            'WHERE' => [
               'teams_id' => $team_id,
               'users_id' => $user_id,
               'role' => 'admin'
            ],
            'LIMIT' => 1
         ]);

         return count($iterator) > 0;
      }

      return false;
   }

   /**
    * Add member to team
    */
   static function addMemberToTeam($team_id, $user_id, $role = 'member') {
      global $DB;

      // Check if already member
      $existing = $DB->request([
         'FROM' => 'glpi_plugin_scrumban_team_members',
         'WHERE' => [
            'teams_id' => $team_id,
            'users_id' => $user_id
         ],
         'LIMIT' => 1
      ]);

      if (count($existing) > 0) {
         return false; // Already a member
      }

      $member = new self();
      return $member->add([
         'teams_id' => $team_id,
         'users_id' => $user_id,
         'role' => $role
      ]);
   }

   /**
    * Update member role
    */
   static function updateMemberRole($team_id, $user_id, $new_role) {
      global $DB;

      // Validate role
      if (!in_array($new_role, ['member', 'lead', 'admin'])) {
         return false;
      }

      // Check if changing from admin and if they are the last admin
      $current_role = PluginScrumbanTeam::getUserRoleInTeam($user_id, $team_id);
      
      if ($current_role == 'admin' && $new_role != 'admin') {
         if (self::isLastAdmin($team_id, $user_id)) {
            return false; // Cannot remove last admin
         }
      }

      return $DB->update('glpi_plugin_scrumban_team_members', [
         'role' => $new_role
      ], [
         'teams_id' => $team_id,
         'users_id' => $user_id
      ]);
   }

   /**
    * Remove member from team
    */
   static function removeMemberFromTeam($team_id, $user_id) {
      global $DB;

      // Check if user is last admin
      if (self::isLastAdmin($team_id, $user_id)) {
         return false; // Cannot remove last admin
      }

      return $DB->delete('glpi_plugin_scrumban_team_members', [
         'teams_id' => $team_id,
         'users_id' => $user_id
      ]);
   }

   /**
    * Get available users to add to team
    */
   static function getAvailableUsers($team_id) {
      global $DB;

      // Get current team members
      $current_members = $DB->request([
         'SELECT' => 'users_id',
         'FROM' => 'glpi_plugin_scrumban_team_members',
         'WHERE' => ['teams_id' => $team_id]
      ]);

      $excluded_users = [];
      foreach ($current_members as $member) {
         $excluded_users[] = $member['users_id'];
      }

      $where = ['is_active' => 1];
      if (!empty($excluded_users)) {
         $where['id'] = ['NOT' => $excluded_users];
      }

      // Get available users
      $iterator = $DB->request([
         'SELECT' => ['id', 'firstname', 'realname', 'name'],
         'FROM' => 'glpi_users',
         'WHERE' => $where,
         'ORDER' => ['firstname', 'realname']
      ]);

      $users = [];
      foreach ($iterator as $data) {
         $users[] = $data;
      }

      return $users;
   }

   /**
    * Get initials from name
    */
   static function getInitials($name) {
      $words = explode(' ', trim($name));
      $initials = '';
      foreach ($words as $word) {
         if (!empty($word)) {
            $initials .= strtoupper(substr($word, 0, 1));
            if (strlen($initials) >= 2) break;
         }
      }
      return $initials ?: '--';
   }

   /**
    * Get member statistics
    */
   static function getMemberStats($user_id) {
      global $DB;

      $stats = [
         'teams_count' => 0,
         'admin_teams' => 0,
         'lead_teams' => 0,
         'boards_access' => 0,
         'cards_assigned' => 0
      ];

      // Count team memberships
      $teams_result = $DB->request([
         'SELECT' => ['COUNT(*) as total', 'role'],
         'FROM' => 'glpi_plugin_scrumban_team_members',
         'WHERE' => ['users_id' => $user_id],
         'GROUP' => 'role'
      ]);

      foreach ($teams_result as $row) {
         $stats['teams_count'] += $row['total'];
         if ($row['role'] == 'admin') {
            $stats['admin_teams'] = $row['total'];
         } elseif ($row['role'] == 'lead') {
            $stats['lead_teams'] = $row['total'];
         }
      }

      // Count accessible boards
      $user_teams = PluginScrumbanTeam::getTeamsForUser($user_id);
      if (!empty($user_teams)) {
         $team_ids = array_column($user_teams, 'id');
         
         $boards_result = $DB->request([
            'SELECT' => 'COUNT(DISTINCT boards_id) as total',
            'FROM' => 'glpi_plugin_scrumban_team_boards',
            'WHERE' => ['teams_id' => $team_ids]
         ]);

         foreach ($boards_result as $row) {
            $stats['boards_access'] = $row['total'];
         }
      }

      // Count assigned cards
      $cards_result = $DB->request([
         'SELECT' => 'COUNT(*) as total',
         'FROM' => 'glpi_plugin_scrumban_cards',
         'WHERE' => [
            'assignee' => User::getCompleteNameById($user_id),
            'is_active' => 1
         ]
      ]);

      foreach ($cards_result as $row) {
         $stats['cards_assigned'] = $row['total'];
      }

      return $stats;
   }

   /**
    * Show user's team dashboard
    */
   static function showUserDashboard($user_id) {
      $teams = PluginScrumbanTeam::getTeamsForUser($user_id);
      $stats = self::getMemberStats($user_id);

      echo "<div class='user-dashboard fade-in'>";
      
      // Stats cards
      echo "<div class='dashboard-stats mb-4'>";
      echo "<div class='row'>";
      
      echo "<div class='col-md-3'>";
      echo "<div class='stat-card'>";
      echo "<div class='stat-value'>" . $stats['teams_count'] . "</div>";
      echo "<div class='stat-label'>" . __('Teams', 'scrumban') . "</div>";
      echo "</div>";
      echo "</div>";
      
      echo "<div class='col-md-3'>";
      echo "<div class='stat-card'>";
      echo "<div class='stat-value'>" . $stats['boards_access'] . "</div>";
      echo "<div class='stat-label'>" . __('Boards Access', 'scrumban') . "</div>";
      echo "</div>";
      echo "</div>";
      
      echo "<div class='col-md-3'>";
      echo "<div class='stat-card'>";
      echo "<div class='stat-value'>" . $stats['cards_assigned'] . "</div>";
      echo "<div class='stat-label'>" . __('Assigned Cards', 'scrumban') . "</div>";
      echo "</div>";
      echo "</div>";
      
      echo "<div class='col-md-3'>";
      echo "<div class='stat-card'>";
      echo "<div class='stat-value'>" . ($stats['admin_teams'] + $stats['lead_teams']) . "</div>";
      echo "<div class='stat-label'>" . __('Leadership Roles', 'scrumban') . "</div>";
      echo "</div>";
      echo "</div>";
      
      echo "</div>";
      echo "</div>";

      // User teams
      if (!empty($teams)) {
         echo "<div class='user-teams'>";
         echo "<h4>" . __('My Teams', 'scrumban') . "</h4>";
         echo "<div class='teams-list'>";
         
         foreach ($teams as $team_data) {
            echo "<div class='team-item'>";
            echo "<div class='team-info'>";
            echo "<h5>" . htmlspecialchars($team_data['name']) . "</h5>";
            
            $role_labels = [
               'member' => __('Member', 'scrumban'),
               'lead' => __('Lead', 'scrumban'),
               'admin' => __('Administrator', 'scrumban')
            ];
            
            $badge_class = [
               'member' => 'bg-secondary',
               'lead' => 'bg-warning text-dark',
               'admin' => 'bg-success'
            ];
            
            echo "<span class='badge {$badge_class[$team_data['role']]}'>";
            echo $role_labels[$team_data['role']];
            echo "</span>";
            echo "</div>";
            
            echo "<div class='team-actions'>";
            echo "<a href='" . Plugin::getWebDir('scrumban') . "/front/team.php?id=" . $team_data['id'] . "' class='btn btn-outline-primary btn-sm'>";
            echo __('View Team', 'scrumban');
            echo "</a>";
            echo "</div>";
            echo "</div>";
         }
         
         echo "</div>";
         echo "</div>";
      } else {
         echo "<div class='alert alert-info'>";
         echo __('You are not a member of any team yet.', 'scrumban');
         echo "</div>";
      }
      
      echo "</div>";
   }

   /**
    * Validate before deleting member
    */
   function pre_deleteItem() {
      // Check if trying to remove last admin
      if ($this->fields['role'] == 'admin') {
         if (self::isLastAdmin($this->fields['teams_id'], $this->fields['users_id'])) {
            Session::addMessageAfterRedirect(__('Cannot remove the last administrator from team', 'scrumban'), false, ERROR);
            return false;
         }
      }

      return true;
   }
}