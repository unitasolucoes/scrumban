<?php

if (!defined('PLUGIN_SCRUMBAN_VERSION')) {
   require_once __DIR__ . '/setup.php';
}

/**
 * Install plugin schema and bootstrap data.
 */
function plugin_scrumban_install() {
   global $DB;

   include_once GLPI_ROOT . '/inc/migration.class.php';

   $hadBoardsTable = $DB->tableExists('glpi_plugin_scrumban_boards');
   $hadTeamsTable  = $DB->tableExists('glpi_plugin_scrumban_teams');

   $migration = new Migration(PLUGIN_SCRUMBAN_VERSION);
   plugin_scrumban_define_schema($migration, $DB);
   $migration->executeMigration();

   plugin_scrumban_ensure_indexes($DB);

   $isFreshInstall      = !$hadBoardsTable;
   $migratingFromLegacy = $hadBoardsTable && !$hadTeamsTable;
   plugin_scrumban_reconcile_data($DB, $isFreshInstall, $migratingFromLegacy);

   return true;
}

/**
 * Upgrade handler executed by GLPI when updating the plugin version.
 */
function plugin_scrumban_update($current_version) {
   global $DB;

   include_once GLPI_ROOT . '/inc/migration.class.php';

   $migration = new Migration(PLUGIN_SCRUMBAN_VERSION);
   plugin_scrumban_define_schema($migration, $DB);
   $migration->executeMigration();

   plugin_scrumban_ensure_indexes($DB);

   $migratingFromLegacy = version_compare($current_version, '2.0.0', '<');
   plugin_scrumban_reconcile_data($DB, false, $migratingFromLegacy);

   return true;
}

/**
 * Remove plugin tables on uninstall.
 */
function plugin_scrumban_uninstall() {
   global $DB;

   $tables = [
      'glpi_plugin_scrumban_team_boards',
      'glpi_plugin_scrumban_team_members',
      'glpi_plugin_scrumban_teams',
      'glpi_plugin_scrumban_attachments',
      'glpi_plugin_scrumban_comments',
      'glpi_plugin_scrumban_labels',
      'glpi_plugin_scrumban_card_test_scenarios',
      'glpi_plugin_scrumban_card_criteria',
      'glpi_plugin_scrumban_cards',
      'glpi_plugin_scrumban_sprints',
      'glpi_plugin_scrumban_columns',
      'glpi_plugin_scrumban_boards'
   ];

   foreach ($tables as $table) {
      if ($DB->tableExists($table)) {
         $DB->queryOrDie("DROP TABLE `$table`", $DB->error());
      }
   }

   return true;
}

/**
 * Define or update every table and column used by the plugin.
 */
function plugin_scrumban_define_schema($migration, $DB) {
   plugin_scrumban_create_table_if_missing(
      $DB,
      'glpi_plugin_scrumban_teams',
      "CREATE TABLE `glpi_plugin_scrumban_teams` (
         `id` int unsigned NOT NULL auto_increment,
         `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL default '',
         `description` text COLLATE utf8mb4_unicode_ci,
         `manager_id` int unsigned NOT NULL default '0',
         `is_active` tinyint NOT NULL default '1',
         `entities_id` int unsigned NOT NULL default '0',
         `date_creation` timestamp NULL default NULL,
         `date_mod` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         KEY `entities_id` (`entities_id`),
         KEY `manager_id` (`manager_id`),
         KEY `is_active` (`is_active`),
         KEY `name` (`name`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;"
   );

   plugin_scrumban_create_table_if_missing(
      $DB,
      'glpi_plugin_scrumban_team_members',
      "CREATE TABLE `glpi_plugin_scrumban_team_members` (
         `id` int unsigned NOT NULL auto_increment,
         `teams_id` int unsigned NOT NULL,
         `users_id` int unsigned NOT NULL,
         `role` enum('member','lead','admin') NOT NULL default 'member',
         `date_creation` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         UNIQUE KEY `teams_users` (`teams_id`, `users_id`),
         KEY `teams_id` (`teams_id`),
         KEY `users_id` (`users_id`),
         KEY `role` (`role`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;"
   );

   plugin_scrumban_create_table_if_missing(
      $DB,
      'glpi_plugin_scrumban_team_boards',
      "CREATE TABLE `glpi_plugin_scrumban_team_boards` (
         `id` int unsigned NOT NULL auto_increment,
         `teams_id` int unsigned NOT NULL,
         `boards_id` int unsigned NOT NULL,
         `can_edit` tinyint NOT NULL default '1',
         `can_manage` tinyint NOT NULL default '0',
         `date_creation` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         UNIQUE KEY `teams_boards` (`teams_id`, `boards_id`),
         KEY `teams_id` (`teams_id`),
         KEY `boards_id` (`boards_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;"
   );

   plugin_scrumban_create_table_if_missing(
      $DB,
      'glpi_plugin_scrumban_boards',
      "CREATE TABLE `glpi_plugin_scrumban_boards` (
         `id` int unsigned NOT NULL auto_increment,
         `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL default '',
         `description` text COLLATE utf8mb4_unicode_ci,
         `entities_id` int unsigned NOT NULL default '0',
         `teams_id` int unsigned NOT NULL default '0',
         `visibility` enum('public','team','private') NOT NULL default 'public',
         `users_id_created` int unsigned NOT NULL default '0',
         `is_active` tinyint NOT NULL default '1',
         `date_creation` timestamp NULL default NULL,
         `date_mod` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         KEY `entities_id` (`entities_id`),
         KEY `teams_id` (`teams_id`),
         KEY `visibility` (`visibility`),
         KEY `users_id_created` (`users_id_created`),
         KEY `is_active` (`is_active`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;"
   );

   plugin_scrumban_create_table_if_missing(
      $DB,
      'glpi_plugin_scrumban_columns',
      "CREATE TABLE `glpi_plugin_scrumban_columns` (
         `id` int unsigned NOT NULL auto_increment,
         `boards_id` int unsigned NOT NULL,
         `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL default '',
         `position` int NOT NULL default '0',
         `wip_limit` int NOT NULL default '0',
         `color` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL default '#6c757d',
         `is_active` tinyint NOT NULL default '1',
         `date_creation` timestamp NULL default NULL,
         `date_mod` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         KEY `boards_id` (`boards_id`),
         KEY `position` (`position`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;"
   );

   plugin_scrumban_create_table_if_missing(
      $DB,
      'glpi_plugin_scrumban_cards',
      "CREATE TABLE `glpi_plugin_scrumban_cards` (
         `id` int unsigned NOT NULL auto_increment,
         `boards_id` int unsigned NOT NULL,
         `columns_id` int unsigned NOT NULL,
         `sprints_id` int unsigned NOT NULL default '0',
         `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL default '',
         `description` text COLLATE utf8mb4_unicode_ci,
         `type` enum('story','task','bug','epic') NOT NULL default 'story',
         `priority` enum('trivial','menor','normal','maior','critico') NOT NULL default 'normal',
         `story_points` int NOT NULL default '0',
         `assignee` varchar(255) COLLATE utf8mb4_unicode_ci,
         `reporter` varchar(255) COLLATE utf8mb4_unicode_ci,
         `external_reference` varchar(255) COLLATE utf8mb4_unicode_ci,
         `position` int NOT NULL default '0',
         `due_date` date DEFAULT NULL,
         `planned_delivery_date` date DEFAULT NULL,
         `completed_at` datetime DEFAULT NULL,
         `is_active` tinyint NOT NULL default '1',
         `tickets_id` int unsigned NOT NULL default '0',
         `development_branch` varchar(255) COLLATE utf8mb4_unicode_ci,
         `development_pull_request` varchar(255) COLLATE utf8mb4_unicode_ci,
         `development_commits` text COLLATE utf8mb4_unicode_ci,
         `dor_percent` tinyint unsigned NOT NULL default '0',
         `dod_percent` tinyint unsigned NOT NULL default '0',
         `acceptance_criteria` longtext COLLATE utf8mb4_unicode_ci,
         `test_scenarios` longtext COLLATE utf8mb4_unicode_ci,
         `date_creation` timestamp NULL default NULL,
         `date_mod` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         KEY `boards_id` (`boards_id`),
         KEY `columns_id` (`columns_id`),
         KEY `sprints_id` (`sprints_id`),
         KEY `tickets_id` (`tickets_id`),
         KEY `assignee` (`assignee`),
         KEY `type` (`type`),
         KEY `priority` (`priority`),
         KEY `planned_delivery_date` (`planned_delivery_date`),
         KEY `completed_at` (`completed_at`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;"
   );

   plugin_scrumban_create_table_if_missing(
      $DB,
      'glpi_plugin_scrumban_card_criteria',
      "CREATE TABLE `glpi_plugin_scrumban_card_criteria` (
         `id` int unsigned NOT NULL auto_increment,
         `cards_id` int unsigned NOT NULL,
         `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL default '',
         `description` text COLLATE utf8mb4_unicode_ci,
         `is_completed` tinyint NOT NULL default '0',
         `position` int NOT NULL default '0',
         `date_creation` timestamp NULL default NULL,
         `date_mod` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         KEY `cards_id` (`cards_id`),
         KEY `is_completed` (`is_completed`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;"
   );

   plugin_scrumban_create_table_if_missing(
      $DB,
      'glpi_plugin_scrumban_card_test_scenarios',
      "CREATE TABLE `glpi_plugin_scrumban_card_test_scenarios` (
         `id` int unsigned NOT NULL auto_increment,
         `cards_id` int unsigned NOT NULL,
         `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL default '',
         `description` text COLLATE utf8mb4_unicode_ci,
         `expected_result` text COLLATE utf8mb4_unicode_ci,
         `status` enum('pending','passed','failed') NOT NULL default 'pending',
         `position` int NOT NULL default '0',
         `date_creation` timestamp NULL default NULL,
         `date_mod` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         KEY `cards_id` (`cards_id`),
         KEY `status` (`status`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;"
   );

   plugin_scrumban_create_table_if_missing(
      $DB,
      'glpi_plugin_scrumban_sprints',
      "CREATE TABLE `glpi_plugin_scrumban_sprints` (
         `id` int unsigned NOT NULL auto_increment,
         `boards_id` int unsigned NOT NULL,
         `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL default '',
         `objective` text COLLATE utf8mb4_unicode_ci,
         `start_date` date DEFAULT NULL,
         `end_date` date DEFAULT NULL,
         `status` enum('planejado','ativo','concluido') NOT NULL default 'planejado',
         `capacity` int NOT NULL default '0',
         `velocity` int NOT NULL default '0',
         `is_active` tinyint NOT NULL default '1',
         `date_creation` timestamp NULL default NULL,
         `date_mod` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         KEY `boards_id` (`boards_id`),
         KEY `status` (`status`),
         KEY `dates` (`start_date`, `end_date`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;"
   );

   plugin_scrumban_create_table_if_missing(
      $DB,
      'glpi_plugin_scrumban_labels',
      "CREATE TABLE `glpi_plugin_scrumban_labels` (
         `id` int unsigned NOT NULL auto_increment,
         `cards_id` int unsigned NOT NULL,
         `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL default '',
         `color` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL default '#007bff',
         `date_creation` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         KEY `cards_id` (`cards_id`),
         KEY `name` (`name`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;"
   );

   plugin_scrumban_create_table_if_missing(
      $DB,
      'glpi_plugin_scrumban_comments',
      "CREATE TABLE `glpi_plugin_scrumban_comments` (
         `id` int unsigned NOT NULL auto_increment,
         `cards_id` int unsigned NOT NULL,
         `users_id` int unsigned NOT NULL,
         `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
         `date_creation` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         KEY `cards_id` (`cards_id`),
         KEY `users_id` (`users_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;"
   );

   plugin_scrumban_create_table_if_missing(
      $DB,
      'glpi_plugin_scrumban_attachments',
      "CREATE TABLE `glpi_plugin_scrumban_attachments` (
         `id` int unsigned NOT NULL auto_increment,
         `cards_id` int unsigned NOT NULL,
         `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL default '',
         `filepath` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL default '',
         `filesize` int unsigned NOT NULL default '0',
         `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci,
         `users_id` int unsigned NOT NULL,
         `date_creation` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         KEY `cards_id` (`cards_id`),
         KEY `users_id` (`users_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;"
   );

   // Ensure newer card columns exist when upgrading from legacy versions.
   if ($DB->tableExists('glpi_plugin_scrumban_cards')) {
      $cardFields = $DB->listFields('glpi_plugin_scrumban_cards');
      $fieldsToAdd = [
         'external_reference'       => "varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL",
         'planned_delivery_date'    => "date DEFAULT NULL",
         'completed_at'             => "datetime DEFAULT NULL",
         'development_branch'       => "varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL",
         'development_pull_request' => "varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL",
         'development_commits'      => "text COLLATE utf8mb4_unicode_ci",
         'dor_percent'              => "tinyint unsigned NOT NULL DEFAULT '0'",
         'dod_percent'              => "tinyint unsigned NOT NULL DEFAULT '0'",
         'acceptance_criteria'      => "longtext COLLATE utf8mb4_unicode_ci",
         'test_scenarios'           => "longtext COLLATE utf8mb4_unicode_ci"
      ];

      foreach ($fieldsToAdd as $field => $definition) {
         if (!isset($cardFields[$field])) {
            $migration->addField('glpi_plugin_scrumban_cards', $field, $definition);
         }
      }
   }

   if ($DB->tableExists('glpi_plugin_scrumban_boards')) {
      $boardFields = $DB->listFields('glpi_plugin_scrumban_boards');
      if (!isset($boardFields['teams_id'])) {
         $migration->addField('glpi_plugin_scrumban_boards', 'teams_id', "int unsigned NOT NULL DEFAULT '0'");
      }
      if (!isset($boardFields['visibility'])) {
         $migration->addField('glpi_plugin_scrumban_boards', 'visibility', "enum('public','team','private') NOT NULL DEFAULT 'public'");
      }
      if (!isset($boardFields['users_id_created'])) {
         $migration->addField('glpi_plugin_scrumban_boards', 'users_id_created', "int unsigned NOT NULL DEFAULT '0'");
      }
   }
}

/**
 * Create table if it does not exist yet.
 */
function plugin_scrumban_create_table_if_missing($DB, $table, $createSql) {
   if (!$DB->tableExists($table)) {
      $DB->queryOrDie($createSql, $DB->error());
   }
}

/**
 * Create required indexes on plugin tables if missing.
 */
function plugin_scrumban_ensure_indexes($DB) {
   $indexMap = [
      'glpi_plugin_scrumban_teams' => [
         'idx_name' => ['name'],
         'idx_manager_active' => ['manager_id', 'is_active']
      ],
      'glpi_plugin_scrumban_team_members' => [
         'idx_team_role' => ['teams_id', 'role'],
         'idx_user_role' => ['users_id', 'role']
      ],
      'glpi_plugin_scrumban_team_boards' => [
         'idx_permissions' => ['teams_id', 'boards_id']
      ],
      'glpi_plugin_scrumban_boards' => [
         'idx_team_visibility' => ['teams_id', 'visibility'],
         'idx_creator' => ['users_id_created']
      ],
      'glpi_plugin_scrumban_cards' => [
         'idx_board_sprint' => ['boards_id', 'sprints_id'],
         'idx_plan_dates' => ['planned_delivery_date', 'completed_at']
      ],
      'glpi_plugin_scrumban_card_criteria' => [
         'idx_card_state' => ['cards_id', 'is_completed']
      ],
      'glpi_plugin_scrumban_card_test_scenarios' => [
         'idx_card_status' => ['cards_id', 'status']
      ]
   ];

   foreach ($indexMap as $table => $indexes) {
      if (!$DB->tableExists($table)) {
         continue;
      }

      foreach ($indexes as $indexName => $columns) {
         $columnsList = implode('`, `', $columns);
         $check = $DB->query("SHOW INDEX FROM `$table` WHERE Key_name = '$indexName'");
         if ($check && $check->num_rows > 0) {
            continue;
         }

         $DB->queryOrDie("ALTER TABLE `$table` ADD INDEX `$indexName` (`$columnsList`)", $DB->error());
      }
   }
}

/**
 * Ensure functional data consistency after schema changes.
 */
function plugin_scrumban_reconcile_data($DB, $isFreshInstall, $migratingFromLegacy) {
   if (!$DB->tableExists('glpi_plugin_scrumban_boards') || !$DB->tableExists('glpi_plugin_scrumban_teams')) {
      return;
   }

   $defaultTeam = plugin_scrumban_get_or_create_default_team($DB);

   if ($defaultTeam === null) {
      return;
   }

   plugin_scrumban_assign_boards_to_teams($DB, $defaultTeam, $migratingFromLegacy);

   if ($isFreshInstall) {
      plugin_scrumban_seed_initial_board($DB, $defaultTeam);
   }
}

/**
 * Guarantee that every board has a team and association record.
 */
function plugin_scrumban_assign_boards_to_teams($DB, array $defaultTeam, $forceTeamVisibility) {
   $now = date('Y-m-d H:i:s');
   $managerId = (int)$defaultTeam['manager_id'];

   $iterator = $DB->request([
      'FROM' => 'glpi_plugin_scrumban_boards'
   ]);

   foreach ($iterator as $board) {
      $boardId = (int)$board['id'];
      $teamId  = (int)$board['teams_id'];
      $update  = [];

      if ($teamId <= 0) {
         $teamId = (int)$defaultTeam['id'];
         $update['teams_id'] = $teamId;
      }

      if ($forceTeamVisibility || empty($board['visibility'])) {
         $update['visibility'] = 'team';
      }

      if (empty($board['users_id_created']) && $managerId > 0) {
         $update['users_id_created'] = $managerId;
      }

      if (!empty($update)) {
         $DB->update(
            'glpi_plugin_scrumban_boards',
            $update,
            ['id' => $boardId]
         );
      }

      plugin_scrumban_link_team_to_board($DB, $teamId, $boardId, true, $now);
   }
}

/**
 * Insert a demo board on brand new installations so the UI has content.
 */
function plugin_scrumban_seed_initial_board($DB, array $defaultTeam) {
   $countResult = $DB->query("SELECT COUNT(*) AS total FROM `glpi_plugin_scrumban_boards`");
   if ($countResult && ($row = $countResult->fetch_assoc()) && (int)$row['total'] > 0) {
      return;
   }

   $now       = date('Y-m-d H:i:s');
   $teamId    = (int)$defaultTeam['id'];
   $managerId = (int)$defaultTeam['manager_id'];

   $DB->insert('glpi_plugin_scrumban_boards', [
      'name'                 => 'Projeto Exemplo',
      'description'          => 'Quadro inicial criado automaticamente pelo plugin Scrumban.',
      'entities_id'          => 0,
      'teams_id'             => $teamId,
      'visibility'           => 'team',
      'users_id_created'     => $managerId,
      'is_active'            => 1,
      'date_creation'        => $now,
      'date_mod'             => $now
   ]);

   $boardId = (int)$DB->insertId();

   plugin_scrumban_link_team_to_board($DB, $teamId, $boardId, true, $now);

   $columns = [
      ['Backlog', '#6c757d'],
      ['Planejado', '#17a2b8'],
      ['Em execução', '#ffc107'],
      ['Concluído', '#28a745']
   ];

   foreach ($columns as $position => $column) {
      $DB->insert('glpi_plugin_scrumban_columns', [
         'boards_id'     => $boardId,
         'name'          => $column[0],
         'position'      => $position,
         'wip_limit'     => 0,
         'color'         => $column[1],
         'is_active'     => 1,
         'date_creation' => $now,
         'date_mod'      => $now
      ]);
   }
}

/**
 * Ensure a default team exists and return its identifiers.
 */
function plugin_scrumban_get_or_create_default_team($DB) {
   $iterator = $DB->request([
      'SELECT' => ['id', 'manager_id'],
      'FROM'   => 'glpi_plugin_scrumban_teams',
      'WHERE'  => ['name' => 'Default Team'],
      'LIMIT'  => 1
   ]);

   foreach ($iterator as $row) {
      return [
         'id' => (int)$row['id'],
         'manager_id' => (int)$row['manager_id']
      ];
   }

   $managerId = plugin_scrumban_guess_current_user();
   $now       = date('Y-m-d H:i:s');

   $DB->insert('glpi_plugin_scrumban_teams', [
      'name'          => 'Default Team',
      'description'   => 'Equipe padrão criada automaticamente durante a instalação.',
      'manager_id'    => $managerId,
      'is_active'     => 1,
      'entities_id'   => 0,
      'date_creation' => $now,
      'date_mod'      => $now
   ]);

   $teamId = (int)$DB->insertId();

   if ($teamId <= 0) {
      return null;
   }

   if ($managerId > 0) {
      plugin_scrumban_add_team_member($DB, $teamId, $managerId, 'admin', $now);
   }

   return [
      'id' => $teamId,
      'manager_id' => $managerId
   ];
}

/**
 * Link a team to a board, granting edit/manage permissions when desired.
 */
function plugin_scrumban_link_team_to_board($DB, $teamId, $boardId, $grantManage, $timestamp) {
   if ($teamId <= 0 || $boardId <= 0) {
      return;
   }

   $canManage = $grantManage ? 1 : 0;

   $DB->queryOrDie(
      "INSERT IGNORE INTO `glpi_plugin_scrumban_team_boards`
         (`teams_id`, `boards_id`, `can_edit`, `can_manage`, `date_creation`)
       VALUES
         (" . (int)$teamId . ", " . (int)$boardId . ", 1, $canManage, '" . addslashes($timestamp) . "')",
      $DB->error()
   );
}

function plugin_scrumban_update_201() {
   global $DB;

   $migration = new Migration(201);

   if ($DB->tableExists('glpi_plugin_scrumban_cards')) {
      $cards_fields = $DB->listFields('glpi_plugin_scrumban_cards');

      if (!isset($cards_fields['external_reference'])) {
         $migration->addField('glpi_plugin_scrumban_cards', 'external_reference', "varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL");
      }

      if (!isset($cards_fields['planned_delivery_date'])) {
         $migration->addField('glpi_plugin_scrumban_cards', 'planned_delivery_date', 'date DEFAULT NULL');
      }

      if (!isset($cards_fields['completed_at'])) {
         $migration->addField('glpi_plugin_scrumban_cards', 'completed_at', 'datetime DEFAULT NULL');
      }

      if (!isset($cards_fields['development_branch'])) {
         $migration->addField('glpi_plugin_scrumban_cards', 'development_branch', "varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL");
      }

      if (!isset($cards_fields['development_pull_request'])) {
         $migration->addField('glpi_plugin_scrumban_cards', 'development_pull_request', "varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL");
      }

      if (!isset($cards_fields['development_commits'])) {
         $migration->addField('glpi_plugin_scrumban_cards', 'development_commits', "text COLLATE utf8mb4_unicode_ci");
      }

      if (!isset($cards_fields['dor_percent'])) {
         $migration->addField('glpi_plugin_scrumban_cards', 'dor_percent', "tinyint unsigned NOT NULL DEFAULT '0'");
      }

      if (!isset($cards_fields['dod_percent'])) {
         $migration->addField('glpi_plugin_scrumban_cards', 'dod_percent', "tinyint unsigned NOT NULL DEFAULT '0'");
      }

      if (!isset($cards_fields['acceptance_criteria'])) {
         $migration->addField('glpi_plugin_scrumban_cards', 'acceptance_criteria', "longtext COLLATE utf8mb4_unicode_ci");
      }

      if (!isset($cards_fields['test_scenarios'])) {
         $migration->addField('glpi_plugin_scrumban_cards', 'test_scenarios', "longtext COLLATE utf8mb4_unicode_ci");
      }

      $migration->addKey('glpi_plugin_scrumban_cards', ['planned_delivery_date']);
      $migration->addKey('glpi_plugin_scrumban_cards', ['completed_at']);
   }

   $migration->executeMigration();

   if (!$DB->tableExists('glpi_plugin_scrumban_card_criteria')) {
      $DB->queryOrDie("CREATE TABLE `glpi_plugin_scrumban_card_criteria` (
         `id` int unsigned NOT NULL auto_increment,
         `cards_id` int unsigned NOT NULL,
         `title` varchar(255) collate utf8mb4_unicode_ci NOT NULL default '',
         `description` text collate utf8mb4_unicode_ci,
         `is_completed` tinyint NOT NULL default '0',
         `position` int NOT NULL default '0',
         `date_creation` timestamp NULL default NULL,
         `date_mod` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         KEY `cards_id` (`cards_id`),
         KEY `is_completed` (`is_completed`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;", $DB->error());
   }

   if (!$DB->tableExists('glpi_plugin_scrumban_card_test_scenarios')) {
      $DB->queryOrDie("CREATE TABLE `glpi_plugin_scrumban_card_test_scenarios` (
         `id` int unsigned NOT NULL auto_increment,
         `cards_id` int unsigned NOT NULL,
         `title` varchar(255) collate utf8mb4_unicode_ci NOT NULL default '',
         `description` text collate utf8mb4_unicode_ci,
         `expected_result` text collate utf8mb4_unicode_ci,
         `status` enum('pending','passed','failed') NOT NULL default 'pending',
         `position` int NOT NULL default '0',
         `date_creation` timestamp NULL default NULL,
         `date_mod` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         KEY `cards_id` (`cards_id`),
         KEY `status` (`status`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;", $DB->error());
   }

   plugin_scrumban_create_indexes();

   return true;
}

/**
 * Add a member to the default team ensuring uniqueness.
 */
function plugin_scrumban_add_team_member($DB, $teamId, $userId, $role, $timestamp) {
   $DB->queryOrDie(
      "INSERT IGNORE INTO `glpi_plugin_scrumban_team_members`
         (`teams_id`, `users_id`, `role`, `date_creation`)
       VALUES
         (" . (int)$teamId . ", " . (int)$userId . ", '" . addslashes($role) . "', '" . addslashes($timestamp) . "')",
      $DB->error()
   );
}

/**
 * Try to retrieve the GLPI user currently enabling the plugin.
 */
function plugin_scrumban_guess_current_user() {
   if (class_exists('Session')) {
      $userId = (int)Session::getLoginUserID(false);
      if ($userId > 0) {
         return $userId;
      }
   }

   if (isset($_SESSION['glpiID'])) {
      $userId = (int)$_SESSION['glpiID'];
      if ($userId > 0) {
         return $userId;
      }
   }

   return 0;
}