<?php
class controllerExtensionEventShippingCutoff extends Controller {
    public function before_view(&$route, &$data, &$output) {
        // check if this module is enabled
        if(!$this->active()) {
            return;
        }
		// set message
		$message = $this->config->get('module_shipping_cutoff_message');
		if(strpos($message,'%s')) {
			$message = sprintf($this->config->get('module_shipping_cutoff_message'),$this->nextShipDay());
		}
		$data['error_warning'] .= $message;
		$data['shipping_cutoff'] = $message;
    }
    
    public function before_index(&$route, &$data) {
        // check if this module is enabled
        if(!$this->active()) {
            return;
        }
        // load all shipping methods
		$this->load->model('setting/extension');

		$results = $this->model_setting_extension->getExtensions('shipping');

		foreach ($results as $result) {
			if ($this->config->get('shipping_' . $result['code'] . '_status') && $this->config->has('shipping_' . $result['code'] . '_status')) {
				// offset shipping delay to reflect cutoff date
				$this->config->set('shipping_' . $result['code'] . '_delay',$this->getDelay());
			}
		}
    }
    
    protected function active() {
	    if($this->config->get('module_shipping_cutoff_status')) {
		    // check if this is a shipping day
			if(!in_array(date('N'),$this->config->get('module_shipping_cutoff_days'))) {
				if($this->config->get('module_shipping_cutoff_debug')) {
					$this->log->write(date('N').' (today) is not a shipping day');
				}
				return true;
			}
			// check if past cutoff time
			if(time() > strtotime($this->config->get('module_shipping_cutoff_time'))) {
				if($this->config->get('module_shipping_cutoff_debug')) {
					$this->log->write(time().' is after cutoff time of '.strtotime($this->config->get('module_shipping_cutoff_time')));
				}
				return true;
			}
	    }
	    return false;
    }
    
    protected function getDelay() {
		$day = (int)date('N');
		$i = 0;
		while(1) {
			$day++;
			$i++;
			if($day > 7) $day = 1;
			if($this->config->get('module_shipping_cutoff_debug')) {
				$this->log->write('Checking if shipping '.$day);
			}
			if(in_array($day,$this->config->get('module_shipping_cutoff_days'))) {
				if($this->config->get('module_shipping_cutoff_debug')) {
					$this->log->write($day.' is a shipping day');
				}
				break;
			}
		}
	    return $i;
    }
	
    protected function nextShipDay() {
		return date($this->config->get('module_shipping_cutoff_format'),strtotime($this->getDelay().' days'));
    }
}
