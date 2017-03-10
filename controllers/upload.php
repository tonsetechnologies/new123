<?php
// This is a testing controller to test out CI classes
class Upload extends CI_Controller {
	
	function Upload()
	{
		parent::Controller();
		$this->load->helper(array('form', 'url'));
	}
	
	function index()
	{	
		$this->load->view('test_upload', array('error' => ' ' ));
	}

	function do_upload()
	{
		$config['upload_path'] = './uploads/';
		$config['allowed_types'] = 'gif|jpg|png';
		$config['max_size']	= '2000';
		$config['max_width']  = '3200';
		$config['max_height']  = '2400';
		
		$this->load->library('upload', $config);
	
		if ( ! $this->upload->do_upload())
		{
			$error = array('error' => $this->upload->display_errors());
			
			$this->load->view('test_upload', $error);
		}	
		else
		{
			$data = array('upload_data' => $this->upload->data());

            // Image manipulation section
            $config['image_library'] = 'gd2';
            //$config['source_image'] = './uploads/dscn2377.jpg';
            $config['source_image'] = $data['upload_data']['full_path'];
            $config['create_thumb'] = TRUE;
            $config['maintain_ratio'] = TRUE;
            $config['width'] = 75;
            $config['height'] = 75;

            $this->load->library('image_lib', $config);

            $this->image_lib->resize();	
		
			$this->load->view('test_upload_success', $data);
		}
	}	
}
