<?php
/**
 * @package WordPress
 * @subpackage BuddyForms
 * @author ThemKraft Dev Team
 * @copyright 2019, Themekraft
 * @link http://themkraft.com/
 * @license GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BuddyFormsFrontendTableElements {
	public function __construct() {
		add_filter( 'buddyforms_formbuilder_fields_options', array(
			$this,
			'buddyforms_frontend_table_formbuilder_fields_options'
		), 10, 4 );
	}

	public function buddyforms_frontend_table_formbuilder_fields_options( $form_fields, $field_type, $field_id, $form_slug = '' ) {
		global $buddyforms;

		// Add to the Table
		$frontend_table                                  = isset( $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['frontend_table'] ) ? $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['frontend_table'] : array();
		$form_fields['Frontend-Table']['frontend_table'] = new Element_Checkbox( '<b>' . __( 'Enable Frontend Table', 'buddyforms' ) . '</b>', "buddyforms_options[form_fields][" . $field_id . "][frontend_table]",
			array( 'enabled' => 'Use in the table' ),
			array(
				'value'    => $frontend_table,
				'field_id' => $field_id,
				'id'       => 'buddyforms_frontend_table_' . $field_id,
			) );

		// Filterable
		$frontend_table_filter                                  = isset( $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['frontend_table_filter'] ) ? $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['frontend_table_filter'] : array();
		$form_fields['Frontend-Table']['frontend_table_filter'] = new Element_Checkbox( '<b>' . __( 'Enable Frontend Table Filter', 'buddyforms' ) . '</b>', "buddyforms_options[form_fields][" . $field_id . "][frontend_table_filter]",
			array( 'enabled' => 'Filterable' ),
			array(
				'value'    => $frontend_table_filter,
				'field_id' => $field_id,
				'id'       => 'buddyforms_frontend_table_filter_' . $field_id,
			) );

		// Sortable
		$frontend_table_sortable                                  = isset( $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['frontend_table_sortable'] ) ? $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['frontend_table_sortable'] : array();
		$form_fields['Frontend-Table']['frontend_table_sortable'] = new Element_Checkbox( '<b>' . __( 'Enable Frontend Table Sortable', 'buddyforms' ) . '</b>', "buddyforms_options[form_fields][" . $field_id . "][frontend_table_sortable]",
			array( 'enabled' => 'Sortable' ),
			array(
				'value'    => $frontend_table_sortable,
				'field_id' => $field_id,
				'id'       => 'buddyforms_frontend_table_sortable_' . $field_id,
			) );

		return $form_fields;
	}
}

$GLOBALS['BuddyFormsFrontendTable'] = new BuddyFormsFrontendTableElements;