<?php

function plugin_scrumban_build_html_attributes(array $attributes): string {
   $output = '';

   foreach ($attributes as $key => $value) {
      if ($value === null || $value === false || $value === '') {
         continue;
      }

      $output .= ' ' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . "='" . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . "'";
   }

   return $output;
}

function plugin_scrumban_render_team_selector($selected_team_id = null, array $options = []): string {
   $user_id = $options['user_id'] ?? ($_SESSION['glpiID'] ?? 0);
   $teams = $options['teams'] ?? PluginScrumbanTeam::getTeamsForUser($user_id);

   if (empty($teams)) {
      return '';
   }

   $always_show = $options['always_show'] ?? false;
   if (!$always_show && count($teams) <= 1) {
      return '';
   }

   $id = $options['id'] ?? 'scrumban-team-selector';
   $name = $options['name'] ?? 'team_id';

   $attributes = [
      'id'    => $id,
      'name'  => $name,
      'class' => trim('form-select form-select-sm ' . ($options['class'] ?? '')),
      'data-scrumban-team-selector' => '1'
   ];

   if (empty($attributes['class'])) {
      unset($attributes['class']);
   }

   if (!empty($options['board_selector_id'])) {
      $attributes['data-board-field'] = $options['board_selector_id'];
   }

   if (!empty($options['attributes']) && is_array($options['attributes'])) {
      foreach ($options['attributes'] as $attr => $value) {
         $attributes[$attr] = $value;
      }
   }

   $label = array_key_exists('label', $options) ? $options['label'] : __('Team', 'scrumban');
   $label_class = $options['label_class'] ?? 'form-label mb-0';
   $placeholder = $options['placeholder'] ?? __('All teams', 'scrumban');
   $wrapper_class = $options['wrapper_class'] ?? 'd-flex align-items-center gap-2 scrumban-selector';

   $html = "<div" . plugin_scrumban_build_html_attributes(['class' => $wrapper_class]) . ">";

   if ($label !== false) {
      $html .= "<label" . plugin_scrumban_build_html_attributes([
         'for' => $id,
         'class' => $label_class
      ]) . ">" . htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8') . "</label>";
   }

   $html .= "<select" . plugin_scrumban_build_html_attributes($attributes) . ">";
   $html .= "<option value=''>" . htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') . "</option>";

   foreach ($teams as $team) {
      $team_id = (int)$team['id'];
      $selected = ($selected_team_id !== null && (int)$selected_team_id === $team_id) ? ' selected' : '';
      $html .= "<option value='{$team_id}'{$selected}>" . htmlspecialchars($team['name'], ENT_QUOTES, 'UTF-8') . "</option>";
   }

   $html .= "</select>";
   $html .= "</div>";

   return $html;
}

function plugin_scrumban_render_board_selector($selected_board_id = null, array $options = []): string {
   $user_id = $options['user_id'] ?? ($_SESSION['glpiID'] ?? 0);
   $team_id = $options['team_id'] ?? null;
   $boards = $options['boards'] ?? PluginScrumbanTeam::getBoardsForUser($user_id, $team_id);

   if (empty($boards)) {
      return '';
   }

   $always_show = $options['always_show'] ?? false;
   if (!$always_show && count($boards) <= 1) {
      return '';
   }

   $id = $options['id'] ?? 'scrumban-board-selector';
   $name = $options['name'] ?? 'board_id';

   $attributes = [
      'id'    => $id,
      'name'  => $name,
      'class' => trim('form-select form-select-sm ' . ($options['class'] ?? '')),
      'data-scrumban-board-selector' => '1'
   ];

   if (empty($attributes['class'])) {
      unset($attributes['class']);
   }

   if (!empty($options['team_selector_id'])) {
      $attributes['data-team-field'] = $options['team_selector_id'];
   }

   if (!empty($options['attributes']) && is_array($options['attributes'])) {
      foreach ($options['attributes'] as $attr => $value) {
         $attributes[$attr] = $value;
      }
   }

   $label = array_key_exists('label', $options) ? $options['label'] : __('Board', 'scrumban');
   $label_class = $options['label_class'] ?? 'form-label mb-0';
   $placeholder = $options['placeholder'] ?? __('Select a board', 'scrumban');
   $wrapper_class = $options['wrapper_class'] ?? 'd-flex align-items-center gap-2 scrumban-selector';

   $html = "<div" . plugin_scrumban_build_html_attributes(['class' => $wrapper_class]) . ">";

   if ($label !== false) {
      $html .= "<label" . plugin_scrumban_build_html_attributes([
         'for' => $id,
         'class' => $label_class
      ]) . ">" . htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8') . "</label>";
   }

   $html .= "<select" . plugin_scrumban_build_html_attributes($attributes) . ">";

   foreach ($boards as $board) {
      $board_id = (int)$board['id'];
      $selected = ($selected_board_id !== null && (int)$selected_board_id === $board_id) ? ' selected' : '';
      $html .= "<option value='{$board_id}'{$selected}>" . htmlspecialchars($board['name'], ENT_QUOTES, 'UTF-8') . "</option>";
   }

   $html .= "</select>";
   $html .= "</div>";

   return $html;
}
