<?php
	/* Advanced bool fields */
require_once("Class.BaseField.inc.php");

class BoolField2 extends BoolField {
	public $word_true;
	public $word_false;
	public $word_ignore;
	public $use_ignore = false;
	
	function BoolField2($fldtitle, $fldname, $flddescr=null, $fldwidth = null){
		$this->IntField($fldtitle, $fldname, $flddescr, $fldwidth);
		$this->word_false = _("False");
		$this->word_true = ("True");
		$this->word_ignore = ("Ignore");
	}

	
	public function DispAddEdit($val,&$form){
		$field_vals = array( array('1', $this->word_true),
				array('0', $this->word_false));
		if (($val == 't') || ($val == 1) || ($val == true) || ($val == 'true'))
			$val = '1';
		else
			$val = '0';
		gen_Combo($form->prefix.$this->fieldname,$val,$field_vals);
	?>
	<div class="descr"><?= $this->editDescr?></div>
	<?php
	}

	
	public function DispSearch(&$form){
		$val = $form->getpost_dirty($this->fieldname);
		$field_vals = array( array('1', $this->word_true), 
				array('0',$this->word_false));
		if ($this->use_ignore)
			array_unshift($field_vals, array('2', $this->word_ignore));
		gen_Combo($form->prefix.$this->fieldname,$val,$field_vals);
	}

	public function buildSearchClause(&$dbhandle,$form, $search_exprs){
		$dval = $form->getpost_dirty($this->fieldname);
		if ($dval == '2')
			return null;
		
		if ($dval == '1')
			$val = true;
		else
			$val = false;
	
		if (empty($this->fieldexpr))
			$fldex = $this->fieldname;
		else
			$fldex = $this->fieldexpr;
		if (is_array($search_exprs) && (isset($search_exprs[$this->fieldname])))
			$sex =$search_exprs[$this->fieldname];
		else
			$sex = '=';
			
		return str_dbparams($dbhandle,"$fldex $sex %1",array($val));
	}

};


class NullField extends BoolField2 {
	
	public function buildSearchClause(&$dbhandle,$form, $search_exprs){
		$dval = $form->getpost_dirty($this->fieldname);
		if ($dval == '2')
			return null;
		
		if (($dval == 't') || ($dval == true) || ($dval == 1) || 
			($dval == 'true') || ($dval == '1'))
			$val = true;
		else
			$val = false;
	
		if (empty($this->fieldexpr))
			$fldex = $this->fieldname;
		else
			$fldex = $this->fieldexpr;

		if ($val)
			return "$fldex IS NOT NULL";
		else
			return "$fldex IS NULL";
	}

};

?>