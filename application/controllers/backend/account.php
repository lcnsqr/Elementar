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
		 * Load encryption key before session library
		 */
		$this->config->set_item('encryption_key', $this->storage->get_config('encryption_key'));
		/*
		 * CI libraries
		 */
		$this->load->library('session');
		
		/*
		 * Load site i18n config
		 */
		$i18n_settings = json_decode($this->storage->get_config('i18n'), TRUE);
		foreach($i18n_settings as $i18n_setting)
		{
			if ( (bool) $i18n_setting['default'] )
			{
				$this->LANG = $i18n_setting['code'];
				/*
				 * Default language is the first in array
				 */
				$this->LANG_AVAIL = array_merge(array($i18n_setting['code'] => $i18n_setting['name']), $this->LANG_AVAIL);
			}
			else
			{
				$this->LANG_AVAIL[$i18n_setting['code']] = $i18n_setting['name'];
			}
		}
		
		/*
		 * Language related Settings
		 */
		$site_names = json_decode($this->storage->get_config('name'), TRUE);
		$this->config->set_item('site_name', (array_key_exists($this->LANG, $site_names)) ? $site_names[$this->LANG] : '');

		/*
		 * Email settings
		 */
		$email_settings = json_decode($this->storage->get_config('email') ,TRUE);
		$this->load->library('email', $email_settings);
		$this->email->set_newline("\r\n");

		/*
		 * CMS Common Library
		 */
		$this->load->library('common', array(
			'lang' => $this->LANG, 
			'lang_avail' => $this->LANG_AVAIL, 
			'uri_prefix' => ''
		));
		
		/*
		 * Backend language file
		 */
		$this->lang->load('elementar', $this->config->item('language'));
		
		/*
		 * Fields validation library
		 */
		$this->load->library('validation');

		/*
		 * Verificar sessão autenticada
		 * de usuário autorizado no admin
		 */
		$account_id = $this->session->userdata('account_id');
		if ( (int) $account_id != 1 )
		{
			$data = array(
				'is_logged' => FALSE,
				'title' => $this->config->item('site_name'),
				'js' => array(
					'/js/backend/jquery-1.7.1.min.js', 
					'/js/backend/backend_account.js', 
					'/js/backend/jquery.timers-1.2.js', 
					'/js/backend/backend_client_warning.js'
				),
				'action' => '/' . uri_string(),
				'elapsed_time' => $this->benchmark->elapsed_time('total_execution_time_start', 'total_execution_time_end')
			);

			/*
			 * Localized texts
			 */
			$data['elementar_authentication_title'] = $this->lang->line('elementar_authentication_title');
			$data['elementar_authentication_account'] = $this->lang->line('elementar_authentication_account');
			$data['elementar_authentication_password'] = $this->lang->line('elementar_authentication_password');
			$data['elementar_authentication_login'] = $this->lang->line('elementar_authentication_login');

			$data['elementar_exit'] = $this->lang->line('elementar_exit');
			$data['elementar_finished_in'] = $this->lang->line('elementar_finished_in');
			$data['elementar_finished_elapsed'] = $this->lang->line('elementar_finished_elapsed');
			$data['elementar_copyright'] = $this->lang->line('elementar_copyright');

			$login = $this->load->view('backend/backend_login', $data, TRUE);
			exit($login);
		}

	}
	
	function index()
	{
		/*
		 * User info
		 */
		$account_id = $this->session->userdata('account_id');
		$is_logged = TRUE;
		$username = $this->access->get_account_username($account_id);

		/*
		 * client controller (javascript)
		 */
		$js = array(
			'/js/backend/jquery-1.7.1.min.js',
			'/js/backend/jquery.easing.1.3.js',
			'/js/backend/jquery.timers-1.2.js',
			'/js/backend/tiny_mce/jquery.tinymce.js',
			'/js/backend/backend_composite_field.js',
			'/js/backend/backend_account.js',
			'/js/backend/backend_account_tree.js',
			'/js/backend/backend_account_window.js',
			'/js/backend/backend_client_warning.js',
			'/js/backend/backend_anchor.js'
		);
		
		/*
		 * Resource menu
		 */
		$resource_menu = array(
			anchor($this->lang->line('elementar_settings'), array('href' => '/backend', 'title' => $this->lang->line('elementar_settings'))),
			span('&bull;', array('class' => 'top_menu_sep')),
			'<strong>' . $this->lang->line('elementar_accounts') . '</strong>',
			span('&bull;', array('class' => 'top_menu_sep')),
			anchor($this->lang->line('elementar_editor'), array('href' => '/backend/editor', 'title' => $this->lang->line('elementar_contents')))
		);

		$data = array(
			'title' => $this->config->item('site_name'),
			'js' => $js,
			'is_logged' => $is_logged,
			'username' => $username,
			'resource_menu' => ul($resource_menu)
		);

		$data['parent'] = $this->lang->line('elementar_accounts');

		// load tree
		$data['backend_account_tree'] = $this->_render_tree_listing();
		
		$data['elementar_exit'] = $this->lang->line('elementar_exit');
		$data['elementar_finished_in'] = $this->lang->line('elementar_finished_in');
		$data['elementar_finished_elapsed'] = $this->lang->line('elementar_finished_elapsed');
		$data['elementar_copyright'] = $this->lang->line('elementar_copyright');

		$this->load->view('backend/backend_account', $data);

	}

	/*
	 * Render tree accounts by group
	 */
	function xhr_render_group_listing()
	{
		if ( ! $this->input->is_ajax_request() )
			exit($this->lang->line('elementar_no_direct_script_access'));

		$group_id = $this->input->post('group_id');

		if ( ! (bool) $group_id )
		{
			$response = array(
				'done' => FALSE,
				'message' => $this->lang->line('elementar_bad_request')
			);
			$this->output->set_output_json($response);
			return;
		}

		$accounts = $this->access->get_accounts($group_id);
		$group = array('accounts' => ( (bool) $accounts ) ? $accounts : array());

		$data = array('group' => $group);

		/*
		 * Localized texts
		 */
		$data['elementar_delete'] = $this->lang->line('elementar_delete');
		$data['elementar_edit'] = $this->lang->line('elementar_edit');
		$data['elementar_edit_group'] = $this->lang->line('elementar_edit_group');
		$data['elementar_new_group'] = $this->lang->line('elementar_new_group');
		$data['elementar_edit_account'] = $this->lang->line('elementar_edit_account');
		$data['elementar_new_account'] = $this->lang->line('elementar_new_account');

		$html = $this->load->view('backend/backend_account_tree_group', $data, TRUE);

		$response = array(
			'done' => TRUE,
			'id' => $group_id,
			'html' => $html
		);
		$this->output->set_output_json($response);
		
	}

	/*
	 * List accounts
	 */
	function xhr_render_tree_listing()
	{
		if ( ! $this->input->is_ajax_request() )
			exit($this->lang->line('elementar_no_direct_script_access'));

		$group_id = $this->input->post('group_id');

		if ( ! (bool) $group_id )
		{
			$response = array(
				'done' => FALSE,
				'message' => $this->lang->line('elementar_bad_request')
			);
			$this->output->set_output_json($response);
			return;
		}

		$html = $this->_render_tree_listing($group_id);

		$response = array(
			'done' => TRUE,
			'id' => $group_id,
			'html' => $html
		);
		$this->output->set_output_json($response);
		
	}

	function _render_tree_listing($group_id = NULL)
	{
		$groups = array();
		
		foreach ($this->access->get_groups() as $group)
		{
			$accounts = ( $group['id'] == $group_id ) ? $this->access->get_accounts($group['id']) : array();
			$display_accounts = ( $group['id'] == $group_id && count($accounts) > 0 ) ? TRUE : FALSE;
			$groups[] = array(
				'id' => $group['id'],
				'name' => ( $this->lang->line('elementar_group_' . $group['id']) != '' ) ? $this->lang->line('elementar_group_' . $group['id']) : $group['name'],
				'description' => ( $this->lang->line('elementar_group_' . $group['id'] . '_description') != '' ) ? $this->lang->line('elementar_group_' . $group['id'] . '_description') : $group['description'],
				'children' => $group['children'],
				'display_accounts' => $display_accounts,
				'accounts' => $accounts
			);
		}
		
		$data['groups'] = $groups;

		/*
		 * Localized texts
		 */
		$data['elementar_delete'] = $this->lang->line('elementar_delete');
		$data['elementar_edit'] = $this->lang->line('elementar_edit');
		$data['elementar_edit_group'] = $this->lang->line('elementar_edit_group');
		$data['elementar_new_group'] = $this->lang->line('elementar_new_group');
		$data['elementar_edit_account'] = $this->lang->line('elementar_edit_account');
		$data['elementar_new_account'] = $this->lang->line('elementar_new_account');

		
		/*
		 * Set default language for view
		 */
		$data['lang'] = $this->LANG;
		
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
		$group_id = $this->input->post('group_id', TRUE);

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
		$form .= $this->common->render_form_field('name', $this->lang->line('elementar_name'), 'name', NULL, $value, FALSE);

		/*
		 * Group description
		 */
		$value = $this->access->get_group_description($group_id);
		$form .= $this->common->render_form_field('line', $this->lang->line('elementar_group_description'), 'description', NULL, $value, FALSE);

		/*
		 *  Botão envio
		 */
		$form .= div_open(array('class' => 'form_control_buttons'));
		$attributes = array(
		    'name' => 'button_group_save',
		    'id' => 'button_group_save',
		    'class' => 'noform',
		    'content' => $this->lang->line('elementar_save')
		);
		$form .= form_button($attributes);

		$form .= div_close();
		
		if ( (bool) $group_id )
		{
			$data['header'] = $this->lang->line('elementar_edit_group');
		}
		else
		{
			$data['header'] = $this->lang->line('elementar_new_group');
		}
		
		$data['form'] = $form;
		
		$html = $this->load->view('backend/backend_account_form', $data, true);

		$response = array(
			'done' => TRUE,
			'html' => $html
		);

		$this->output->set_output_json($response);

	}

	/*
	 * Save group
	 */
	function xhr_write_group()
	{
		if ( ! $this->input->is_ajax_request() )
			exit($this->lang->line('elementar_no_direct_script_access'));

		/*
		 * Create or update? Check for incoming group ID
		 */
		$group_id = $this->input->post('group_id', TRUE);

		/*
		 * Other group fields
		 */
		$name = $this->input->post('name', TRUE);
		$description = $this->input->post('description', TRUE);
		
		/*
		 * Value verification
		 */
		if ( $name == '' )
		{
			$response = array(
				'done' => FALSE,
				'message' => $this->lang->line('elementar_return_name_error')
			);
			$this->output->set_output_json($response);
			return;
		}
		
		if ( (bool) $group_id )
		{
			/*
			 * Update group
			 */
			$this->access->put_group_name($group_id, $name);
			$this->access->put_group_description($group_id, $description);
		}
		else
		{
			/*
			 * Create group
			 */
			$group_id = $this->access->put_group($name, $description);
		}
		
		$response = array(
			'done' => TRUE,
			'group_id' => $group_id,
			'message' => $this->lang->line('elementar_xhr_write_group')
		);
		$this->output->set_output_json($response);

	}

	/*
	 * Save group
	 */
	function xhr_erase_group()
	{
		if ( ! $this->input->is_ajax_request() )
			exit($this->lang->line('elementar_no_direct_script_access'));

		$group_id = $this->input->post('id', TRUE);
		$name = $this->access->get_group_name($group_id);

		if ( (int) $group_id > 1 )
		{
			$this->access->delete_group($group_id);
			$response = array(
				'done' => TRUE,
				'message' => $name . ' ' . $this->lang->line('elementar_xhr_erase')
			);
		}
		else
		{
			$response = array(
				'done' => FALSE,
				'message' => $this->lang->line('elementar_xhr_erase_admin') . ' ' . $name
			);
		}
		
		// Enviar resposta
		$this->output->set_output_json($response);

	}

	/*
	 * Create/edit account
	 */
	function xhr_render_account_form()
	{
		if ( ! $this->input->is_ajax_request() )
			exit($this->lang->line('elementar_no_direct_script_access'));

		/*
		 * Create or update? Check for incoming account ID
		 */
		$account_id = $this->input->post('account_id', TRUE);

		/*
		 * Account ID (if any, hidden)
		 */
		$attributes = array(
			'class' => 'noform',
			'name' => 'account_id',
			'value'=> $account_id,
			'type' => 'hidden'
		);
		$form = form_input($attributes);

		/*
		 * Group ID (hidden)
		 */
		if ( (bool) $account_id )
		{
			$group_id = $this->access->get_account_group($account_id);
		}
		else
		{
			$group_id = $this->input->post('group_id', TRUE);
		}
		$attributes = array(
			'class' => 'noform',
			'name' => 'group_id',
			'value'=> $group_id,
			'type' => 'hidden'
		);
		$form .= form_input($attributes);

		/*
		 * Account name
		 */
		$value = $this->access->get_account_username($account_id);
		$form .= $this->common->render_form_field('name', $this->lang->line('elementar_account_username'), 'username', NULL, $value, FALSE);

		/*
		 * Account email
		 */
		$value = $this->access->get_account_email($account_id);
		$form .= $this->common->render_form_field('line', $this->lang->line('elementar_account_email'), 'email', NULL, $value, FALSE);

		/*
		 * Account password
		 */
		$value = '';
		$form .= $this->common->render_form_field('password', $this->lang->line('elementar_account_password'), 'password', NULL, $value, FALSE);

		/*
		 *  Botão envio
		 */
		$form .= div_open(array('class' => 'form_control_buttons'));
		$attributes = array(
		    'name' => 'button_account_save',
		    'id' => 'button_account_save',
		    'class' => 'noform',
		    'content' => $this->lang->line('elementar_save')
		);
		$form .= form_button($attributes);

		$form .= div_close();
		
		if ( (bool) $account_id )
		{
			$data['header'] = $this->lang->line('elementar_edit_account');
		}
		else
		{
			$data['header'] = $this->lang->line('elementar_new_account');
		}
		
		$data['form'] = $form;
		
		$html = $this->load->view('backend/backend_account_form', $data, true);

		$response = array(
			'done' => TRUE,
			'html' => $html
		);

		$this->output->set_output_json($response);

	}

	/*
	 * Save account
	 */
	function xhr_write_account()
	{
		if ( ! $this->input->is_ajax_request() )
			exit($this->lang->line('elementar_no_direct_script_access'));

		/*
		 * Create or update? Check for incoming group ID
		 */
		$account_id = $this->input->post('account_id', TRUE);

		/*
		 * Other account fields
		 */
		$username = $this->input->post('username', TRUE);
		$email = $this->input->post('email', TRUE);
		$password = $this->input->post('password', TRUE);

		/*
		 * Assess account username
		 */
		$response = $this->validation->assess_username($username);
		if ( (bool) $response['done'] == FALSE )
		{
			$this->output->set_output_json($response);
			return;
		}

		if ( ! (bool) $account_id )
		{
			if ( (bool) $this->access->get_account_by_username($username) )
			{
				$response = array(
					'done' => FALSE,
					'message' => $this->lang->line('elementar_xhr_username_field_used')
				);
				$this->output->set_output_json($response);
				return;
			}
		}
		else
		{
			if ( (bool) $this->access->get_account_by_username($username) && $username != $this->access->get_account_username($account_id) )
			{
				$response = array(
					'done' => FALSE,
					'message' => $this->lang->line('elementar_xhr_username_field_used')
				);
				$this->output->set_output_json($response);
				return;
			}
		}

		/*
		 * Assess email
		 */
		$response = $this->validation->assess_email($email);
		if ( (bool) $response['done'] == FALSE )
		{
			$this->output->set_output_json($response);
			return;
		}
		if ( ! (bool) $account_id )
		{
			if ( (bool) $this->access->get_account_by_email($email) )
			{
				$response = array(
					'done' => FALSE,
					'message' => $this->lang->line('elementar_xhr_email_field_used')
				);
				$this->output->set_output_json($response);
				return;
			}
		}
		else
		{
			if ( (bool) $this->access->get_account_by_email($email) && $email != $this->access->get_account_email($account_id) )
			{
				$response = array(
					'done' => FALSE,
					'message' => $this->lang->line('elementar_xhr_email_field_used')
				);
				$this->output->set_output_json($response);
				return;
			}
		}

		/*
		 * Assess password
		 */
		$response = $this->validation->assess_password($password);
		if ( (bool) $password )
		{
			if ( (bool) $response['done'] == FALSE )
			{
				$this->output->set_output_json($response);
				return;
			}
		}

		if ( (bool) $account_id )
		{
			/*
			 * Update account
			 */
			$this->access->put_account_username($account_id, $username);
			$this->access->put_account_email($account_id, $email);
			if ( (bool) $password )
			{
				/*
				 * Avoi writing empty password on update
				 */
				$this->access->put_account_password($account_id, $password);
			}
			$group_id = $this->input->post('group_id', TRUE);
		}
		else
		{
			/*
			 * Create account
			 */
			$account_id = $this->access->put_account($username, $email, $password);
			/*
			 * Add acount to group
			 */
			$group_id = $this->input->post('group_id', TRUE);
			$this->access->put_account_group($account_id, $group_id);
		}
		
		$response = array(
			'done' => TRUE,
			'group_id' => $group_id,
			'account_id' => $account_id,
			'message' => $this->lang->line('elementar_xhr_write_account')
		);
		$this->output->set_output_json($response);

	}

	/*
	 * Remove account
	 */
	function xhr_erase_account()
	{
		if ( ! $this->input->is_ajax_request() )
			exit($this->lang->line('elementar_no_direct_script_access'));

		$account_id = $this->input->post('id', TRUE);
		$username = $this->access->get_account_username($account_id);

		if ( (int) $account_id > 1 )
		{
			$this->access->delete_account($account_id);
			$response = array(
				'done' => TRUE,
				'message' => $username . ' ' . $this->lang->line('elementar_xhr_erase')
			);
		}
		else
		{
			$response = array(
				'done' => FALSE,
				'message' => $this->lang->line('elementar_xhr_erase_admin') . ' ' . $username
			);
		}
		
		// Enviar resposta
		$this->output->set_output_json($response);

	}

	/*
	 * Write account group
	 */
	function xhr_write_account_group()
	{
		if ( ! $this->input->is_ajax_request() )
			exit($this->lang->line('elementar_no_direct_script_access'));

		/*
		 * Group id
		 */
		$group_id = $this->input->post('group_id', TRUE);

		/*
		 * Account id
		 */
		$account_id = $this->input->post('account_id', TRUE);

		if ( (bool) $group_id && (bool) $account_id && ( $group_id != $account_id ) && ( 1 != (int) $account_id ) )
		{
			$this->access->put_account_group($account_id, $group_id);
			$response = array(
				'done' => TRUE,
				'group_id' => $group_id
			);
			$this->output->set_output_json($response);
		}
		else
		{
			$response = array(
				'done' => FALSE,
				'message' => $this->lang->line('elementar_bad_request')
			);
			$this->output->set_output_json($response);
		}

	}

}
