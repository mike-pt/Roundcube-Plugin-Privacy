<?php

/**
 * Privacy
 *
 * Plugin to add some simple privacy
 * settings to Roundcube
 *
 * @version @package_version@
 * @author Philip Weir
 */
class privacy extends rcube_plugin
{
	function init()
	{
		$this->add_hook('render_page', array($this, 'render_html'));
		$this->add_hook('render_response', array($this, 'render_js'));
		$this->add_hook('session_destroy', array($this, 'logout'));
		$this->add_hook('preferences_sections_list', array($this, 'settings_sections'));
		$this->add_hook('preferences_list', array($this, 'settings_list'));
		$this->add_hook('preferences_save', array($this, 'settings_save'));
	}

	function render_html($p)
	{
		$rcube = rcube::get_instance();
		$this->load_config();
		$this->add_texts('localization/');

		if (($rcube->config->get('privacy_hide_mboxes', false) && $rcube->action == '') ||
			($rcube->config->get('privacy_hide_subjects', false) && in_array($rcube->action, array('preview', 'show', 'get')))) {

			$output = $p['content'];
			$output = preg_replace('/<title>[^<]+<\/title>/', '<title>'. rcube_utils::rep_specialchars_output($rcube->config->get('product_name'), 'html', 'strict', true) .'</title>', $output);
			$p['content'] = $output;
		}

		return $p;
	}

	function render_js($p)
	{
		$rcube = rcube::get_instance();
		$this->load_config();
		$this->add_texts('localization/');

		if ($rcube->config->get('privacy_hide_mboxes', false) && $rcube->action == 'list') {
			$response = $p['response']['exec'];
			$response = preg_replace('/this\.set_pagetitle\("(.*?)"\);/', 'this.set_pagetitle("'. rcube_utils::rep_specialchars_output($rcube->config->get('product_name'), 'js') .'");', $response);
			$p['response']['exec'] = $response;
		}

		return $p;
	}

	function logout()
	{
		$this->load_config();

		if (rcube::get_instance()->config->get('privacy_clear_cookies', false)) {
			foreach ($_COOKIE as $name => $value) {
				setcookie($name, '', time()-1000);
			}
		}
	}

	function settings_sections($p)
	{
		$this->include_stylesheet($this->local_skin_path() . '/tabstyles.css');
		$this->add_texts('localization/');
		$p['list']['privacy'] = array('id' => 'privacy', 'section' => $this->gettext('privacysettings'));
		return $p;
	}

	function settings_list($p)
	{
		if ($p['section'] == 'privacy') {
			$rcube = rcube::get_instance();
			$this->load_config();
			$no_override = array_flip((array)$rcube->config->get('dont_override'));

			$p['blocks'] = array('main' => array('name' => rcmail::Q($this->gettext('mainoptions'))));

			if (!isset($no_override['privacy_hide_mboxes'])) {
				$field_id = 'rcmfd_privacy_hide_mboxes';
				$input = new html_checkbox(array('name' => '_privacy_hide_mboxes', 'id' => $field_id, 'value' => 1));

				$p['blocks']['main']['options']['mboxes'] = array(
					'title'   => html::label($field_id, rcmail::Q($this->gettext('hidemboxnames'))),
					'content' => $input->show($rcube->config->get('privacy_hide_mboxes', false) ? 1 : 0),
				);
			}

			if (!isset($no_override['privacy_hide_subjects'])) {
				$field_id = 'rcmfd_privacy_hide_subjects';
				$input = new html_checkbox(array('name' => '_privacy_hide_subjects', 'id' => $field_id, 'value' => 1));

				$p['blocks']['main']['options']['subjects'] = array(
					'title'   => html::label($field_id, rcmail::Q($this->gettext('hidesubjects'))),
					'content' => $input->show($rcube->config->get('privacy_hide_subjects', false) ? 1 : 0),
				);
			}

			if (!isset($no_override['privacy_clear_cookies'])) {
				$field_id = 'rcmfd_privacy_clear_cookies';
				$input = new html_checkbox(array('name' => '_privacy_clear_cookies', 'id' => $field_id, 'value' => 1));

				$p['blocks']['main']['options']['cookies'] = array(
					'title'   => html::label($field_id, rcmail::Q($this->gettext('clearcookies'))),
					'content' => $input->show($rcube->config->get('privacy_clear_cookies', false) ? 1 : 0),
				);
			}
		}

		return $p;
	}

	function settings_save($p)
	{
		if ($p['section'] == 'privacy') {
			$p['prefs'] = array(
			      'privacy_hide_mboxes' => isset($_POST['_privacy_hide_mboxes']) ? TRUE : FALSE,
			      'privacy_hide_subjects' => isset($_POST['_privacy_hide_subjects']) ? TRUE : FALSE,
			      'privacy_clear_cookies' => isset($_POST['_privacy_clear_cookies']) ? TRUE : FALSE
			    );
		}

		return $p;
	}
}

?>