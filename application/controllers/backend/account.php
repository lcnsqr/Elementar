<?php
/*
 *      account.php
 *      
 *      Copyright 2011 Luciano Siqueira <lcnsqr@gmail.com>
 *      
 *      This program is free software; you can redistribute it and/or modify
 *      it under the terms of the GNU General Public License as published by
 *      the Free Software Foundation; either version 2 of the License, or
 *      (at your option) any later version.
 *      
 *      This program is distributed in the hope that it will be useful,
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *      GNU General Public License for more details.
 *      
 *      You should have received a copy of the GNU General Public License
 *      along with this program; if not, write to the Free Software
 *      Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 *      MA 02110-1301, USA.
 */

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Account extends CI_Controller {

	/*
	 * i18n settings
	 */
	var $LANG;
	var $LANG_AVAIL = array();

	function __construct()
	{
		parent::__construct();

		/*
		 *  CI helpers
		 */
		$this->load->helper(array('string', 'security', 'cookie', 'form', 'html', 'text', 'url'));

		/*
		 * CI libraries
		 */
		$this->load->library('session');
		
		/*
		 * Elementar database
		 */
		$this->elementar = $this->load->database('elementar', TRUE);

		/*
		 * Access model
		 */
		$this->load->model('Access', 'access');
		
		/*
		 * Create, read, update and delete Model
		 */
		$this->load->model('Storage', 'storage');
		$this->storage->STATUS = 'all';

		/*
		 * Backend language file
		 */
		$this->lang->load('elementar', $this->config->item('language'));
		
		/*
		 * Load site config
		 */
		$settings = $this->storage->get_config();
		if ( ! is_array($settings) )
		{
			exit($this->lang->line('elementar_config_error'));
		}
		foreach($settings as $setting)
		{
			switch ( $setting['name'] )
			{
				case 'i18n' :
				/*
				 * Language settings
				 */
				$i18n_settings = json_decode($setting['value'], TRUE);
				foreach($i18n_settings as $i18n_setting)
				{
					if ( (bool) $i18n_setting['default'] )
					{
						$this->LANG = $i18n_setting['code'];
						/*
						 * Default language is first in array
						 */
						$this->LANG_AVAIL = array_merge(array($i18n_setting['code'] => $i18n_setting['name']), $this->LANG_AVAIL);
					}
					else
					{
						$this->LANG_AVAIL[$i18n_setting['code']] = $i18n_setting['name'];
					}
				}
				break;
			}
		}
		
		/*
		 * CMS Common Library
		 */
		$this->load->library('common', array(
			'lang' => $this->LANG, 
			'lang_avail' => $this->LANG_AVAIL, 
			'uri_prefix' => ''
		));

		$this->config->set_item('site_name', 'Elementar');

	}
	
	function index()
	{
		/*
		 * User info
		 */
		$user_id = $this->session->userdata('user_id');
		$is_logged = TRUE;
		$username = $this->access->get_user_name($user_id);

		/*
		 * client controller (javascript)
		 */
		$js = array(
			'/js/backend/jquery-1.6.2.min.js',
			'/js/backend/jquery.easing.1.3.js',
			'/js/backend/jquery.timers-1.2.js',
			'/js/backend/backend_account.js',
			'/js/backend/backend_account_tree.js',
			'/js/backend/tiny_mce/jquery.tinymce.js',
			'/js/backend/backend_client_warning.js',
			'/js/backend/backend_anchor.js'
		);
		
		/*
		 * Resource menu
		 */
		$resource_menu = array(
			'<strong>' . $this->lang->line('elementar_accounts') . '</strong>',
			span('&bull;', array('class' => 'top_menu_sep')),
			anchor($this->lang->line('elementar_contents'), array('href' => '/backend/content', 'title' => $this->lang->line('elementar_contents')))
		);

		$data = array(
			'title' => $this->config->item('site_name'),
			'js' => $js,
			'is_logged' => $is_logged,
			'username' => $username,
			'resource_menu' => ul($resource_menu)
		);

		$data['parent_id'] = 0;
		$data['parent'] = $this->lang->line('elementar_accounts');
		$data['account_hierarchy_group'] = $this->access->get_groups();
		$data['group_listing_id'] = NULL;
		$data['group_listing'] = NULL;

		/*
		 * Localized texts
		 */
		$data['elementar_delete'] = $this->lang->line('elementar_delete');
		$data['elementar_edit_group'] = $this->lang->line('elementar_edit_group');
		$data['elementar_new_group'] = $this->lang->line('elementar_new_group');

		$data['elementar_exit'] = $this->lang->line('elementar_exit');
		$data['elementar_finished_in'] = $this->lang->line('elementar_finished_in');
		$data['elementar_finished_elapsed'] = $this->lang->line('elementar_finished_elapsed');
		$data['elementar_copyright'] = $this->lang->line('elementar_copyright');

		$this->load->view('backend/backend_account', $data);

	}

	/*
	 * List accounts
	 */
	function xhr_render_tree_listing()
	{
		if ( ! $this->input->is_ajax_request() )
			exit($this->lang->line('elementar_no_direct_script_access'));

		$group_id = $this->input->post('id');

		if ( ! (bool) $group_id )
		{
			$response = array(
				'done' => FALSE,
				'message' => $this->lang->line('elementar_bad_request')
			);
			$this->common->ajax_response($response);
			return;
		}

		$html = $this->_render_tree_listing($group_id);

		$response = array(
			'done' => TRUE,
			'id' => $group_id,
			'html' => $html
		);
		$this->common->ajax_response($response);
		
	}
	
	function _render_tree_listing($id, $listing = NULL, $listing_id = NULL)
	{
		$data['parent_id'] = $id;
		
		/*
		 * Set default language for view
		 */
		$data['lang'] = $this->LANG;
		
		$data['account_hierarchy_account'] = $this->access->get_accounts($id);
		// Inner listings, if any
		$data['account_listing_id'] = $listing_id;
		$data['account_listing'] = $listing;
		
		/*
		 * Localized texts
		 */
		$data['elementar_edit'] = $this->lang->line('elementar_edit');
		$data['elementar_delete'] = $this->lang->line('elementar_delete');
		$data['elementar_new_account'] = $this->lang->line('elementar_new_account');
		$data['elementar_edit_account'] = $this->lang->line('elementar_edit_account');
		
		$html = $this->load->view('backend/backend_account_tree', $data, true);
		
		return $html;
	}
	
	/*
	 * Create/edit group
	 */
	function xhr_render_group_form()
	{
		if ( ! $this->input->is_ajax_request() )
			exit($this->lang->line('elementar_no_direct_script_access'));

		/*
		 * Create or update? Check for incoming group ID
		 */
		$group_id = $this->input->post('id', TRUE);

		/*
		 * Group ID (if any, hidden)
		 */
		$attributes = array(
			'class' => 'noform',
			'name' => 'group_id',
			'value'=> $group_id,
			'type' => 'hidden'
		);
		$form = form_input($attributes);

		/*
		 * Group name
		 */
		$value = $this->access->get_group_name($group_id);
		$form .= $this->_render_form_field('name', $this->lang->line('elementar_name'), 'name', NULL, $value, FALSE);

		/*
		 * Element type fields
		 */
		$fields = $this->storage->get_element_type_fields($type_id);
		foreach ( $fields as $field )
		{
			/*
			 * Field value
			 */
			$value = $this->storage->get_element_field($element_id, $field['id']);
			$form .= $this->_render_form_field($field['type'], $field['name'], $field['sname'], $field['description'], $value, $field['i18n']);
		}

		/*
		 * Spread
		 */
		$form .= div_open(array('class' => 'form_content_field'));
		$form .= div_open(array('class' => 'form_window_column_label'));
		if ( (bool) $element_id !== FALSE ) 
		{
			$checked = $this->storage->get_element_spread($element_id);
		}
		else
		{
			// Default new element to spread
			$checked = TRUE;
		}
		$attributes = array('class' => 'field_label');
		$form .= form_label($this->lang->line('elementar_element_spread'), "spread", $attributes);
		$form .= div_close("<!-- form_window_column_label -->");

		$form .= div_open(array('class' => 'form_window_column_input'));
		$attributes = array(
			'name'        => 'spread',
			'id'          => 'spread',
			'class' => 'noform',
			'value'       => 'true',
			'checked'     => $checked
		);
		$form .= form_checkbox($attributes);
		$form .= div_close("<!-- form_window_column_input -->");
		$form .= div_close("<!-- .form_content_field -->");

		/*
		 * status
		 */
		$form .= div_open(array('class' => 'form_content_field'));
		$form .= div_open(array('class' => 'form_window_column_label'));
		$attributes = array('class' => 'field_label');
		$form .= form_label($this->lang->line('elementar_status'), "status", $attributes);
		$form .= div_close("<!-- form_window_column_label -->");
		$form .= div_open(array('class' => 'form_window_column_input'));
		$form .= $this->_render_status_dropdown($this->storage->get_element_status($element_id));
		$form .= div_close("<!-- form_window_column_input -->");
		$form .= div_close("<!-- .form_content_field -->");

		$form .= div_open(array('class' => 'form_control_buttons'));

		/*
		 *  Botão envio
		 */
		$attributes = array(
		    'name' => 'button_element_save',
		    'id' => 'button_element_save',
		    'class' => 'noform',
		    'content' => $this->lang->line('elementar_save')
		);
		$form .= form_button($attributes);

		$form .= div_close();
		
		$data['element_form'] = $form;
		
		$html = $this->load->view('backend/backend_content_element_form', $data, true);

		$response = array(
			'done' => TRUE,
			'html' => $html
		);

		$this->common->ajax_response($response);

	}

	/**
	 * Remover conta
	 */
	function xhr_erase_user()
	{
		if ( ! $this->input->is_ajax_request() )
			exit('No direct script access allowed');

		$user_id = $this->input->post('id', TRUE);
		
		if ( $user_id > 1 )
		{
			$this->access->remove_user($user_id);
			$response = array('done' => TRUE);
		}
		else
		{
			$response = array('done' => FALSE);
		}
		
		// Enviar resposta
		$this->common->ajax_response($response);
	}

	/**
	 * Verficiar campos e criar conta 
	 */
	function xhr_write_user()
	{
		if ( ! $this->input->is_ajax_request() )
			exit('No direct script access allowed');

		/*
		 * Verificar se existem dados POST
		 */
		if ($_POST)
		{
			/*
			 * Verificação dos campos
			 */
			$response = array('done' => TRUE);
						 
			/*
			 * Verificação de usuário
			 */
			$username = $this->input->post('user_login', TRUE);
			$valid = $this->access->validate_username($username);
			
			if ( $valid !== TRUE )
			{
				$response['done'] = FALSE;
				$response['user_login_erro'] = $valid;
			}
			
			/*
			 * Verificação de email
			 */
			$email = $this->input->post('user_email', TRUE);
			$valid = $this->access->validate_email($email);
			
			if ( $valid !== TRUE )
			{
				$response['done'] = FALSE;
				$response['user_email_erro'] = $valid;
			}
			
			/*
			 * Verificação de senha
			 */
			$senha = $this->input->post('user_password', TRUE);
			$valid = $this->access->validate_password($senha);
			
			if ( $valid !== TRUE )
			{
				$response['done'] = FALSE;
				$response['user_password_erro'] = $valid;
			}
			
			/*
			 * Se tudo válido, registrar 
			 */
			if ( $response['done'] )
			{
				$user_id = $this->access->register_user($username, $email, $senha, '', TRUE);
				$response['html'] = $this->_get_users($user_id);
			}
			
			// Enviar resposta
			$this->common->ajax_response($response);
			
		}

	}
	
}
