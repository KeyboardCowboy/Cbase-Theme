<?php
/*******************************************************************************
 * CORE HOOKS
 ******************************************************************************/
/**
 * Implementation of template_preprocess().
 */
function cbase_preprocess(&$vars, $hook) {
  _process_variables($vars, $hook, 'preprocessors');
}

/**
 * Implementation of template_process().
 */
function cbase_process(&$vars, $hook) {
  _process_variables($vars, $hook, 'processors');
}

/**
 * Helper function to process variables for the preprocessor and processor hooks.
 *
 * Maintains separate directories in the theme folder called 'preprocessors'
 * and 'processors' where all preprocess logic is stored.  Each hook called
 * corresponds to a file of the same name.
 *
 * @param &$vars
 *   The $variables array passed into the processing hooks.
 * @param $hook
 *   The processing hook.
 * @param $directory
 *   The directory in which to scan for processor files.
 */
function _process_variables(&$vars, $hook, $directory = 'preprocessors') {
  $_vars['pp'] = $_vars['dirs'] = array();

  // Define some variables for all preprocessors
  $vars['cbase_theme_path'] = drupal_get_path('theme', 'cbase');

  // Define the standard preprocessor hook.
  $_vars['pp'][] = $hook;

  // Merge template suggestions with the standard hook preprocessor file suggestion.
  if (is_array($vars['theme_hook_suggestions'])) {
    $_vars['pp'] = array_merge($_vars['pp'], $vars['theme_hook_suggestions']);
  }

  //kpr($_vars['pp']);
  // Load any available preprocessors
  foreach ($_vars['pp'] as $file) {
    $_vars['filepath'] = $vars['cbase_theme_path'] . "/$directory/$file" . '.inc';
    if (file_exists($_vars['filepath'])) {
      include($_vars['filepath']);
    }
  }
}

/**
 * Implements hook_theme().
 */
function cbase_theme($existing, $type, $theme, $path) {
  return array(
    'view_results_count' => array(
      'variables' => array('view' => NULL),
    ),
  );
}

/*******************************************************************************
 * THEME OVERRIDES
 ******************************************************************************/
/**
 * Theme messages.
 */
function cbase_status_messages($variables) {
  $display = $variables['display'];
  $output = '';

  $status_heading = array(
    'status' => t('Status message'),
    'error' => t('Error message'),
    'warning' => t('Warning message'),
  );
  foreach (drupal_get_messages($display) as $type => $messages) {
    $output .= "<div class=\"messages $type\">\n";
    $output .= '  <div class="inner">';
    if (!empty($status_heading[$type])) {
      $output .= '<h2 class="element-invisible">' . $status_heading[$type] . "</h2>\n";
    }
    if (count($messages) >= 1) {
      $output .= " <ul>\n";
      foreach ($messages as $message) {
        $output .= '  <li>' . $message . "</li>\n";
      }
      $output .= " </ul>\n";
    }
    else {
      $output .= $messages[0];
    }
    $output .= "</div>\n</div>\n";
  }
  return $output;
}

/*******************************************************************************
 * CUSTOM THEMES
 ******************************************************************************/
/**
 * Theme the result count for a view.
 */
function theme_view_results_count($variables) {
  $view = $variables['view'];

  $z = $view->total_rows;
  $x = $view->query->offset + 1;
  $y = min($x + $view->query->limit - 1, $z);
  return ($view->query->limit == 0 ? "Showing all $z results." : "Showing results $x - $y of $z");
}

/*******************************************************************************
 * THEME FUNCTIONS
 ******************************************************************************/
/**
 * Get ancestral data from info files in the entire family of themes stemming
 * from the active theme.
 *
 * @param $key
 *   The .info values to return.
 *
 * @return array()
 *   All $key values from all .info files in the ancestry, ordered oldest to
 *   newest.  Full info data if $key is NULL.
 */
function cbase_get_ancestral_info($key = NULL) {
  global $theme;

  $all_themes = list_themes();
  $base_themes = array_keys((isset($all_themes[$theme]->base_themes) ? $all_themes[$theme]->base_themes : (isset($all_themes[$theme]->base_theme) ? array($all_themes[$theme]->base_theme => 0) : array())));
  $theme_ancestry = array_merge($base_themes, array($theme));
  $info = array();

  // These keys are exclusive to each theme.  The value of the parent theme is
  // irrevelent to the child theme, and so they must be explicitly defined in
  // each child theme.
  $overrides = array('regions', 'features', 'overlay_regions', 'regions_hidden');

  // If the key is an overridden value, we don't need to combine all ancestral
  // values, just return the active theme's value.
  if (in_array($key, $overrides)) {
    $info[$key] = $all_themes[$theme]->$key;
  }
  else {
    foreach ($theme_ancestry as $_theme) {
      if (!$key) {
        $info[$_theme] = $all_themes[$_theme]->info;
      }
      elseif ($key == 'path') {
        $info[$_theme] = drupal_get_path('theme', $_theme);
      }
      else {
        if (isset($all_themes[$_theme]->info[$key]) && is_array($all_themes[$_theme]->info[$key])) {
          $info[$_theme] = $all_themes[$_theme]->info[$key];
        }
        elseif (isset($all_themes[$_theme]->info[$key]) && !is_array($all_themes[$_theme]->info[$key])) {
          $info[$_theme] = $all_themes[$_theme]->info[$key];
        }
      }
    }
  }

  return $info;
}

/**
 * Filter out and translate bad characters from the title tag.
 *
 * @param $string
 *   The raw title string.
 *
 * @return
 *   Clean, compliant and valid title string.
 */
function cbase_title_filter($string) {
  // Strip off any HTML elements that may have snuck in.
  $string = strip_tags($string);

  // List items to be translated in order that they should be replaced.
  $translate = array(
    '&amp;'   => 'and',
    '&nbsp;'  => ' ',
    '&ndash;' => '-',
    '&mdash;' => '-',
    '&'       => 'and',
  );

  // Run the replacements
  foreach ($translate as $from => $to) {
    $string = str_replace($from, $to, $string);
  }

  return $string;
}
