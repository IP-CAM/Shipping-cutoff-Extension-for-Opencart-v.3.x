<?php
class ControllerExtensionModuleShippingCutoff extends Controller {
	private $error = array();
	private $version = 1.0;

	public function index() {
		$this->load->language('extension/module/shipping_cutoff');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->upgrade();
			$this->model_setting_setting->editSetting('module_shipping_cutoff', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['message'])) {
			$data['error_message'] = $this->error['message'];
		} else {
			$data['error_message'] = '';
		}

		if (isset($this->error['time'])) {
			$data['error_time'] = $this->error['time'];
		} else {
			$data['error_time'] = '';
		}
		
		if (isset($this->error['days'])) {
			$data['error_days'] = $this->error['days'];
		} else {
			$data['error_days'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/shipping_cutoff', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/shipping_cutoff', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		if (isset($this->request->post['module_shipping_cutoff_message'])) {
			$data['module_shipping_cutoff_message'] = $this->request->post['module_shipping_cutoff_message'];
		} else {
			$data['module_shipping_cutoff_message'] = $this->config->get('module_shipping_cutoff_message');
		}

		if (isset($this->request->post['module_shipping_cutoff_time'])) {
			$data['module_shipping_cutoff_time'] = $this->request->post['module_shipping_cutoff_time'];
		} else {
			$data['module_shipping_cutoff_time'] = $this->config->get('module_shipping_cutoff_time');
		}
		
		if (isset($this->request->post['module_shipping_cutoff_format'])) {
			$data['module_shipping_cutoff_format'] = $this->request->post['module_shipping_cutoff_format'];
		} else {
			$data['module_shipping_cutoff_format'] = $this->config->get('module_shipping_cutoff_format');
		}

		if (isset($this->request->post['module_shipping_cutoff_days'])) {
			for($i = 1;$i<8;$i++) 
			$data['days'][] = array('id' => $i, 'label' => $this->language->get('day_'.$i), 'selected' => in_array($i,$this->request->post['module_shipping_cutoff_days']));
		} else {
			for($i = 1;$i<8;$i++) 
			$data['days'][] = array('id' => $i, 'label' => $this->language->get('day_'.$i), 'selected' => in_array($i,$this->config->get('module_shipping_cutoff_days')));
		}

		if (isset($this->request->post['module_shipping_cutoff_status'])) {
			$data['module_shipping_cutoff_status'] = $this->request->post['module_shipping_cutoff_status'];
		} else {
			$data['module_shipping_cutoff_status'] = $this->config->get('module_shipping_cutoff_status');
		}
		
		if (isset($this->request->post['module_shipping_cutoff_debug'])) {
			$data['module_shipping_cutoff_debug'] = $this->request->post['module_shipping_cutoff_debug'];
		} else {
			$data['module_shipping_cutoff_debug'] = $this->config->get('module_shipping_cutoff_debug');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/shipping_cutoff', $data));
	}
	
	public function install() {
		// set up event handlers
		$this->load->model('setting/event');
		// for checkout addresses - make sure runs first (so shipping closed can override)
		$this->model_setting_event->addEvent('shipping_cutoff', 'catalog/controller/checkout/shipping_method/before', 'extension/event/shipping_cutoff/before_index',1,-1);
		$this->model_setting_event->addEvent('shipping_cutoff', 'catalog/view/checkout/shipping_method/before', 'extension/event/shipping_cutoff/before_view',1,-1);
		$this->model_setting_event->addEvent('shipping_cutoff', 'catalog/controller/extension/payment/pp_express/expressConfirm/before', 'extension/event/shipping_cutoff/before_index',1,-1);
		$this->model_setting_event->addEvent('shipping_cutoff', 'catalog/view/extension/payment/pp_express_confirm/before', 'extension/event/shipping_cutoff/before_view',1,-1);
		$this->model_setting_event->addEvent('shipping_cutoff', 'catalog/controller/extension/module/paypal_smart_button/confirmOrder/before', 'extension/event/shipping_cutoff/before_index',1,-1);
		$this->model_setting_event->addEvent('shipping_cutoff', 'catalog/view/extension/module/paypal_smart_button/confirm/before', 'extension/event/shipping_cutoff/before_view',1,-1);
	}
	
	public function upgrade() {
		// upgrade if required
		$current = (float)$this->config->get('module_shipping_cutoff_version');
		if($current < $this->version) {
			
		}
		// update version number
		$this->request->post['module_shipping_cutoff_version'] = $this->version;
	}
	
	public function uninstall() {
		$this->load->model('setting/event');
		$this->model_setting_event->deleteEventByCode('shipping_cutoff');
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/shipping_cutoff')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['module_shipping_cutoff_message']) {
			$this->error['message'] = $this->language->get('error_message');
		}
		$time = explode(':',$this->request->post['module_shipping_cutoff_time']);
		if (!$this->request->post['module_shipping_cutoff_time'] || count($time) != 2 || (int)$time[0] < 0 || (int)$time[0] > 23 || (int)$time[1] < 0 || (int)$time[1] > 59) {
			$this->error['time'] = $this->language->get('error_time');
		}
		$this->request->post['module_shipping_cutoff_time'] = sprintf('%02d:%02d',(int)$time[0],(int)$time[1]);
		if (!count($this->request->post['module_shipping_cutoff_days'])) {
			$this->error['days'] = $this->language->get('error_days');
		}

		return !$this->error;
	}
}