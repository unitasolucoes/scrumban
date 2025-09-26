<?php

if (!defined('GLPI_ROOT')) {
   die('Sorry. You cannot access directly to this file');
}

class PluginScrumbanUtils {

   const STATUSES = [
      'backlog'      => 'Backlog',
      'todo'         => 'A Fazer',
      'em-execucao'  => 'Em Execução',
      'review'       => 'Review',
      'done'         => 'Concluído'
   ];

   const TYPES = [
      'feature' => 'Feature',
      'bug'     => 'Bug',
      'task'    => 'Task',
      'story'   => 'Story'
   ];

   const PRIORITIES = [
      'LOW'      => 'Baixa',
      'NORMAL'   => 'Normal',
      'HIGH'     => 'Alta',
      'CRITICAL' => 'Crítica'
   ];

   const SCENARIO_STATUSES = [
      'pending' => 'Pendente',
      'passed'  => 'Passou',
      'failed'  => 'Falhou'
   ];

   static function dropdownStatuses($name, $value = null, array $options = []) {
      $translated = [];
      foreach (self::STATUSES as $key => $label) {
         $translated[$key] = __($label, 'scrumban');
      }
      Dropdown::showFromArray($name, $translated, $options + ['value' => $value]);
   }

   static function dropdownTypes($name, $value = null, array $options = []) {
      $translated = [];
      foreach (self::TYPES as $key => $label) {
         $translated[$key] = __($label, 'scrumban');
      }
      Dropdown::showFromArray($name, $translated, $options + ['value' => $value]);
   }

   static function dropdownPriorities($name, $value = null, array $options = []) {
      $translated = [];
      foreach (self::PRIORITIES as $key => $label) {
         $translated[$key] = __($label, 'scrumban');
      }
      Dropdown::showFromArray($name, $translated, $options + ['value' => $value]);
   }

   static function formatUserName($user_id) {
      if (!$user_id) {
         return __('Não definido', 'scrumban');
      }

      $user = new User();
      if ($user->getFromDB($user_id)) {
         return $user->getFriendlyName();
      }

      return __('Usuário removido', 'scrumban');
   }

   static function sanitizeLabels($labels) {
      if (empty($labels)) {
         return '';
      }

      if (is_array($labels)) {
         $labels = array_filter(array_map('trim', $labels));
         return implode(',', $labels);
      }

      return trim($labels);
   }

   static function parseLabels($labels) {
      if (empty($labels)) {
         return [];
      }

      if (is_array($labels)) {
         return $labels;
      }

      return array_filter(array_map('trim', explode(',', $labels)));
   }

   static function canManageTeam($team_id, $user_id = null) {
      if ($user_id === null) {
         $user_id = Session::getLoginUserID(false);
      }

      if (!$user_id) {
         return false;
      }

      $team = new PluginScrumbanTeam();
      if (!$team->getFromDB($team_id)) {
         return false;
      }

      return $team->userHasRole($user_id, ['lead', 'admin']);
   }

   static function requireAjax() {
      header('Content-Type: application/json; charset=UTF-8');
      Session::checkLoginUser();

      $token = $_POST['_glpi_csrf_token'] ?? $_GET['_glpi_csrf_token'] ?? '';
      if (!self::isValidCsrfToken($token)) {
         http_response_code(400);
         echo json_encode(['status' => 'error', 'message' => __('Token CSRF inválido', 'scrumban')]);
         exit;
      }
   }

   protected static function isValidCsrfToken($token) {
      if (empty($token)) {
         return false;
      }

      $sessionClass = 'Session';
      foreach (['validateCSRFToken', 'checkCSRFToken', 'isValidCSRFToken'] as $method) {
         if (method_exists($sessionClass, $method)) {
            return (bool)$sessionClass::$method($token);
         }
      }

      if (method_exists($sessionClass, 'validateCSRF')) {
         return (bool)$sessionClass::validateCSRF(['_glpi_csrf_token' => $token]);
      }

      if (method_exists($sessionClass, 'checkCSRF')) {
         try {
            $sessionClass::checkCSRF(['_glpi_csrf_token' => $token]);
            return true;
         } catch (Exception $e) {
            return false;
         } catch (Error $e) {
            return false;
         }
      }

      $storedToken = $_SESSION['glpicsrf_token'] ?? $_SESSION['_glpi_csrf_token'] ?? null;
      if ($storedToken) {
         return hash_equals($storedToken, $token);
      }

      return true;
   }

   static function jsonResponse($data = [], $status = 'ok', $code = 200) {
      http_response_code($code);
      echo json_encode(['status' => $status] + $data);
      exit;
   }

   static function recordHistory(CommonDBTM $item, $message) {
      global $DB;

      if (!$item->getID()) {
         return;
      }

      $user_id = Session::getLoginUserID(false);
      $user_name = Session::getLoginUserName(true);

      $DB->insert('glpi_logs', [
         'date_mod'       => date('Y-m-d H:i:s'),
         'user_name'      => $user_name,
         'user_id'        => $user_id,
         'itemtype'       => $item->getType(),
         'items_id'       => $item->getID(),
         'itemtype_link'  => '',
         'items_id_link'  => 0,
         'linked_action'  => 'plugin-scrumban',
         'changes'        => Toolbox::clean_cross_side_scripting_deep($message)
      ]);
   }
}
