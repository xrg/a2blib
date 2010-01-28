<?php

	/** Add button */

class AddNewButton extends ElemBase {
	protected $form = null;

	function AddNewButton(&$form){
		$this->form = &$form;
	}

	function Render(){
		$action = null;
		$item = _("item");
		if ($this->form){
			$action = $this->form->getAction();
			$item = $this->form->model_name_s;
		}
		else
			$action = getpost_single('action');
		if ($action == 'list'){ ?>
		<div>
		<a href="<?= $_SERVER['PHP_SELF']. 
			$this->form->gen_AllGetParams(
				array($this->form->prefix.'action' =>'ask-add'))
			?>"><?= str_params(_("Add a new %1"),array($item),1) ?></a>
		</div>
		<?php
		}
	}
	
};


	/** Export button */

class AddExportButton extends ElemBase {
	protected $url = null;
	protected $enabled = false;
	protected $caption;
	
	function AddExportButton(&$form,$caption,$eaction='export'){
		if ($form->getAction()=='list')
			$this->enabled = true;
			
// 		$this->form = &$form;
		$this->caption = $caption;
		$this->url=$form->selfUrl(array(action => $eaction, ndisp => 'all'));
	}

	function Render(){
		if (!$this->enabled)
			return;
		?>
	<div>
		<a target="_blank" href="<?= $this->url?>"><?= $this->caption ?></a>
	</div>
	<?php
	}
	
};

class PrintButton extends ElemBase {
	protected $form = null;

	function PrintButton(&$form){
		$this->form = &$form;
	}

	function Render(){
		?>
		<div>
		<a href="<?= $_SERVER['PHP_SELF']. 
			$this->form->gen_GetParams(array('printable'=>'true'))
			?>"><img src="Images/print.png"> <?= _("Printable version") ?></a>
		</div>
		<?php
	}
	
};

?>
