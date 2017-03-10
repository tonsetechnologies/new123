<?php
// Hands-on tutorial from User Manual for Forms Validation

class Form extends Controller {
	
	function index()
	{
		$this->load->helper(array('form', 'url'));
		
		$this->load->library('form_validation');
				
		$this->form_validation->set_rules('username', 'Username', 'callback_username_check');
        $this->form_validation->set_rules('password', 'Password', 'trim|required|matches[passconf]|md5');
        $this->form_validation->set_rules('passconf', 'Password Confirmation', 'trim|required');
        $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email');

		if ($this->form_validation->run() == FALSE)
		{
			$this->load->view('myform');
		}
		else
		{
			$this->load->view('formsuccess');
		}
	}


	function username_check($str)
	{
		if ($str == 'test')
		{
			$this->form_validation->set_message('username_check', 'The %s field can not be the word "test"');
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}

}
?>
