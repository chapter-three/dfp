<?php

/**
 * @file
 * A custom Ctools Export UI class for DFP Tags.
 */

/**
 * Customizations of the DART Tags UI.
 */
class dfp_tag_ui extends ctools_export_ui {

  /**
   * Prepare the tag values before they are added to the database.
   */
  function edit_form_submit(&$form, &$form_state) {
    // Since the targeting form is reusable it isn't already in the settings
    // array so we grab it here.
    $form_state['values']['settings']['targeting'] = $form_state['values']['targeting'];
    $form_state['values']['settings']['breakpoints'] = $form_state['values']['breakpoints'];

    parent::edit_form_submit($form, $form_state);
  }

  /**
   * Build a row based on the item.
   *
   * By default all of the rows are placed into a table by the render
   * method, so this is building up a row suitable for theme('table').
   * This doesn't have to be true if you override both.
   */
  function list_build_row($item, &$form_state, $operations) {
    // Warn users if network id has no value. This can happen immediatly after
    // the module is installed.
    if (variable_get('dfp_network_id', '') == '') {
      drupal_set_message(t('DFP ad tags will not work until you have <a href="/admin/structure/dfp_ads/settings">set your network id</a> as provided by Google.'), 'warning', FALSE);
    }

    // Set up sorting
    $name = $item->{$this->plugin['export']['key']};
    $schema = ctools_export_get_schema($this->plugin['schema']);

    switch ($form_state['values']['order']) {
      case 'disabled':
        $this->sorts[$name] = empty($item->disabled) . $name;
        break;
      case 'name':
        $this->sorts[$name] = $name;
        break;
      case 'storage':
        $this->sorts[$name] = $item->{$schema['export']['export type string']} . $name;
        break;
    }

    $this->rows[$name]['data'] = array();
    $this->rows[$name]['class'] = !empty($item->disabled) ? array('ctools-export-ui-disabled') : array('ctools-export-ui-enabled');
    $this->rows[$name]['data'][] = array('data' => check_plain($item->slot), 'class' => array('ctools-export-ui-slot'));
    $this->rows[$name]['data'][] = array('data' => check_plain($item->size), 'class' => array('ctools-export-ui-size'));
    $this->rows[$name]['data'][] = array('data' => (check_plain($item->block) ? t('Yes') : t('No')), 'class' => array('ctools-export-ui-block'));
    $this->rows[$name]['data'][] = array('data' => check_plain($item->{$schema['export']['export type string']}), 'class' => array('ctools-export-ui-storage'));

    $ops = theme('links__ctools_dropbutton', array('links' => $operations, 'attributes' => array('class' => array('links', 'inline'))));

    $this->rows[$name]['data'][] = array('data' => $ops, 'class' => array('ctools-export-ui-operations'));
  }

  /**
   * Provide the table header.
   *
   * If you've added columns via list_build_row() but are still using a
   * table, override this method to set up the table header.
   */
  function list_table_header() {
    $header = array();

    $header[] = array('data' => t('Ad Slot'), 'class' => array('ctools-export-ui-slot'));
    $header[] = array('data' => t('Size'), 'class' => array('ctools-export-ui-size'));
    $header[] = array('data' => t('Block'), 'class' => array('ctools-export-ui-block'));
    $header[] = array('data' => t('Storage'), 'class' => array('ctools-export-ui-storage'));
    $header[] = array('data' => t('Operations'), 'class' => array('ctools-export-ui-operations'));

    return $header;
  }

  /**
   * Make certain that setting form_state['rebuild'] = TRUE in a submit function
   * will correctly rebuild the exportables item edit form for the user. This
   * function is needed until the patch at http://drupal.org/node/1524598 is
   * committed.
   */
  function edit_execute_form_standard(&$form_state) {
    $output = drupal_build_form('ctools_export_ui_edit_item_form', $form_state);

    if (!empty($form_state['executed']) && !$form_state['rebuild']) {
      // Interstitial slots are not displayed as a block.
      if (!empty($form_state['values']['settings']['out_of_page'])) {
        $form_state['item']->block = '0';
      }
      $this->edit_save_form($form_state);
    }
    else {
      unset($form_state['executed']);
    }
    return $output;
  }

  /**
   * Deletes any blocks associated with the exportable item being deleted.
   */
  function delete_page($js, $input, $item) {
    $delta = drupal_strlen($item->machinename) >= 32 ? md5($item->machinename) : $item->machinename;
    db_delete('block')
      ->condition('module', 'dfp')
      ->condition('delta', $delta)
      ->execute();

    return parent::delete_page($js, $input, $item);
  }

}
