<?php
	/* Time and date fields */
require_once("Class.BaseField.inc.php");

class HoursField extends IntField {
	public function detailQueryField(&$dbhandle){
		if ($this->fieldexpr)
			$fld= $this->fieldexpr;
		else
			$fld = $this->fieldname;
		return "format_hours($fld) AS " . $this->fieldname;
	}
	
	public function buildSumQuery(&$dbhandle, &$sum_fns,&$fields, &$fields_out, &$table,&$table_out,
		&$clauses, &$grps, &$form){
		if (!$this->does_list)
			return;
		
		// fields
		if ($this->fieldexpr)
			$fld = $this->fieldexpr;
		else
			$fld = $this->fieldname;
		
		if (isset($sum_fns[$this->fieldname]) && !is_null($sum_fns[$this->fieldname])){
			if ($sum_fns[$this->fieldname] === true){
				$grps[] = $this->fieldname;
				$fields[] = "$fld AS ". $this->fieldname;
			}
			elseif (is_string($sum_fns[$this->fieldname]))
				$fields[] = $sum_fns[$this->fieldname] ."($fld) AS ". $this->fieldname;
			
			$fields_out[] = array("format_hours($this->fieldname)", $this->fieldname);
			
		}
		
		$this->listQueryTable($table,$form);
		$tmp= $this->listQueryClause($dbhandle,$form);
		if ( is_string($tmp))
			$clauses[] = $tmp;
	}
};

/** Date and time from + to. 

ISO string + calendar */

class DateRangeField extends BaseField {
	public $def_date;
	static $sqlTimeFmt = null;
	public $fieldexpr = null;
	public $fieldexpr2 = null;
	public $def_value_to = '';
	
	function DateRangeField($fldtitle, $fldname, $fldexpr = null, $fldexpr2 = null, 
			$flddescr=null, $fldwidth = null){
		$this->fieldname = $fldname;
		$this->fieldexpr = $fldexpr;
		$this->fieldexpr2 = $fldexpr2;
		$this->fieldtitle = $fldtitle;
		$this->listWidth = $fldwidth;
		$this->editDescr = $flddescr;
	}

	public function DispList(array &$qrow,&$form){
		echo htmlspecialchars($qrow[$this->fieldname]);
	}
	
	public function renderSpecial(array &$qrow,&$form,$rmode, &$robj){
		return $qrow[$this->fieldname];
	}
	
	public function DispAdd(&$form){
		$v = $form->getpost_dirty($this->fieldname);
		$v2 = $form->getpost_dirty($this->fieldname .'_to');
		if (!isset($v))
			$v=$this->getDefault();
		if (!isset($v2))
			$v2=$this->getDefault2();
		$this->DispAddEdit($v,$form,$v2);
	}

	public function DispAddEdit($val,&$form, $val2=null){
	?><?= _("From:")?> <input type="text" name="<?= $form->prefix.$this->fieldname ?>" value="<?=
		htmlspecialchars($val);?>" />
	  <?= _("To:") ?>
	  <input type="text" name="<?= $form->prefix.$this->fieldname ?>_to" value="<?=
		htmlspecialchars($val2);?>" />
	<div class="descr"><?= $this->editDescr?></div>
	<?php
	}

	/** In list queries, we put both values in one field */
	public function listQueryField(&$dbhandle){
		if (!$this->does_list)
			return;

		if (!empty($this->fieldexpr))
			$fld1 = $this->fieldexpr;
		else
			$fld1 = $this->fieldname;
		
		return $this->fmtContent($fld1) ." || ' - ' || " .
			$this->fmtContent($this->fieldexpr2) . ' AS ' .$this->fieldname;

	}
	
	
	public function editQueryField(&$dbhandle){
		if (!$this->does_edit)
			return;
		if ($this->fieldexpr)
			$ret =  $this->fieldexpr ." AS ". $this->fieldname;
		else
			$ret = $this->fieldname;
		
		$ret .= ", " . $this->fieldexpr2 . " AS ". $this->fieldname . "_to";
	}

	/** Add this clause to the query */
	public function listQueryClause(&$dbhandle){
		return null;
	}
	
	public function detailQueryClause(&$dbhandle,&$form){
		return $this->editQueryClause($dbhandle,$form);
	}

	public function detailQueryField(&$dbhandle){
		if (!empty($this->fieldexpr))
			$fld1 = $this->fieldexpr;
		else
			$fld1 = $this->fieldname;
		
		return $this->fmtContent($fld1) . ' AS ' .$this->fieldname .', ' .
			$this->fmtContent($this->fieldexpr2) . ' AS ' .$this->fieldname .'_to';
	}
	
	protected function fmtContent($content){
		if (DateTimeField::$sqlTimeFmt == null)
			DateTimeField::$sqlTimeFmt= _("YYYY-MM-DD HH24:MI:SS TZ");
		return 'to_char(' . $content .', \''.DateTimeField::$sqlTimeFmt .
			'\')';
	}

	public function getDefault() {
		if(!empty($this->def_date)){
			$tstamp = strtotime($this->def_date);
			if ($tstamp !== false)
				return date('Y-m-d H:i:s',$tstamp);
		}
		return $this->def_value;
	}

	public function getDefault2() {
		if(!empty($this->def_date_to)){
			$now_t = null;
			if (!empty($this->def_date))
				$now_t = strtotime($this->def_date);
			$tstamp = strtotime($this->def_date_to, $now_t);
			if ($tstamp !== false)
				return date('Y-m-d H:i:s',$tstamp);
		}
		return $this->def_value_to;
	}

	public function buildSumQuery(&$dbhandle, &$sum_fns,&$fields,&$fields_out, &$table,&$table_out,
		&$clauses, &$grps, &$form){
		if (!$this->does_list)
			return;
		
		// fields
		if ($this->fieldexpr)
			$fld = $this->fieldexpr;
		else
			$fld = $this->fieldname;
		
		// TODO
		if (isset($sum_fns[$this->fieldname]) && !is_null($sum_fns[$this->fieldname])){
			if ($sum_fns[$this->fieldname] === true){
				if (!empty($this->fieldexpr))
					$grps[] = $this->fieldexpr;
				else
					$grps[] = $this->fieldname;
				$fields[] = $fld . " AS ". $this->fieldname;
			}
			elseif (is_string($sum_fns[$this->fieldname]))
				$fields[] = $sum_fns[$this->fieldname] .$fld. 
					" AS ". $this->fieldname;
						
			$fields_out[] = array($this->fmtContent($this->fieldname), 
				$this->fieldname);
			
		}
		
		$this->listQueryTable($table,$form);
		$tmp= $this->listQueryClause($dbhandle,$form);
		if ( is_string($tmp))
			$clauses[] = $tmp;
	}

	public function getOrder(&$form){
		if ($this->fieldexpr)
			return $this->fieldexpr;
		else
			return $form->model_table.'.'.$this->fieldname;
	}

	public function buildInsert(&$ins_arr,&$form){
		if (!$this->does_add)
			return;
		$ins_arr[] = array($this->fieldname,
			$this->buildValue($form->getpost_dirty($this->fieldname),$form));
		$ins_arr[] = array($this->fieldname2,
			$this->buildValue($form->getpost_dirty($this->fieldname.'_to'),$form));
	}

	public function buildUpdate(&$ins_arr,&$form){
		if (!$this->does_edit)
			return;
		$ins_arr[] = array($this->fieldname,
			$this->buildValue($form->getpost_dirty($this->fieldname),$form));
		$ins_arr[] = array($this->fieldname2,
			$this->buildValue($form->getpost_dirty($this->fieldname. '_to'),$form));
	}
	
	public function buildSearchClause(&$dbhandle,$form, $search_exprs){
		$val = $this->buildValue($form->getpost_dirty($this->fieldname),$form);
		$val2 = $this->buildValue($form->getpost_dirty($this->fieldname.'_to'),$form);
		if (empty($this->fieldexpr))
			$fldex = $this->fieldname;
		else
			$fldex = $this->fieldexpr;
		if (is_array($search_exprs) && (isset($search_exprs[$this->fieldname])))
			$sex =$search_exprs[$this->fieldname];
		else
			$sex = '@';
		
		if ($val == null)
			switch($sex) { // TODO
				// Assume NULL -> 0 ..
			case '<>':
			case '!=':
			case '>':
				return $fldex .' IS NOT NULL';
			case '<':
				return 'false';
			case '>=':
				return 'true';
			case '=':
			case '<=':
			default:
				return $fldex .' IS NULL';
			}
		else if (empty($this->fieldexpr2)) {
			switch($sex){
			case '@':
			case 'in':
				return str_dbparams($dbhandle, "$fldex BETWEEN TIMESTAMP %1 AND TIMESTAMP %2", 
						array($val, $val2));
			
			case '=':
				return str_dbparams($dbhandle,"$fldex = TIMESTAMP %1",array($val));
			}
		}else {
			switch($sex){
			case '@':
				return str_dbparams($dbhandle, "($fldex, ".$this->fieldexpr2 .") ".
							"OVERLAPS (TIMESTAMP %1, TIMESTAMP %2) ",
						array($val, $val2));
			case 'in':
				return str_dbparams($dbhandle, "($fldex BETWEEN TIMESTAMP %1 AND TIMESTAMP %2) " .
							" AND (".$this->fieldexpr2."BETWEEN TIMESTAMP %1 AND TIMESTAMP %2) ",
						array($val, $val2));
			
			case '=':
				return str_dbparams($dbhandle,"$fldex = TIMESTAMP %1 AND ".
						$this->fieldexpr2 ." = TIMESTAMP %2",array($val, $val2));
			}
		}
		return null;
	}

};


?>