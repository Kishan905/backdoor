<?php

ini_set('memory_limit', '-1');
ini_set('max_execution_time', '3000000');
include_once BASEPATH . 'model/Common_model.php';
include_once BASEPATH . 'model/academics/Common_controls_model.php';

class Centre_batch_attendance_model
{
    private $db,$token_data;

    public function __construct()
    {
        global $dbConnect,$log_data;
        $this->db = $dbConnect;
		$this->token_data = $log_data;
        $this->common_model = new Common_model();
        $this->common_controls_model = new Common_controls_model();
        date_default_timezone_set('Asia/Kolkata');
    }

    public function get_batch($param = '')
    {
        $arr = [];

        $SQL = "SELECT * from trans_batch where complete_status is null";

        $query = $this->db->query($SQL);
        if ($query->num_rows > 0) {
            foreach ($query->fetch_all(MYSQLI_ASSOC) as $value) {

                $arr['batch'][$value['batch_id']] = $value;

            }
        }
        return $arr;
    }

    function get_batch_wise_course($param='') {

        $arr = [];
        $SQL = "SELECT * from trans_student_batch_history where batch_id=".$param['batch_id'];
        $query = $this->db->query($SQL);
        if ($query->num_rows > 0) {
             foreach ($query->fetch_all(MYSQLI_ASSOC) as $value) {

                  $arr['batch_wise_course'][$value['batch_id']] = $value;
             }
        }
        return $arr;
   }


    public function get_scheduled_faculty($param = '')
    {
		
		$user_id = $this->token_data->user_id;
		$role_code = $this->token_data->role_code;
		$department_id = $this->token_data->department_id;
        $school_id = $this->token_data->school_id;
		$system_control = $this->common_model->get_system_controller_info($params);

        if(strlen($param['session_year']) == 8 && ($param['session_type'] == "E" || $param['session_type'] == "O")){

            $cur_session_year=$param['session_year'];
		    $cur_session_type=$param['session_type'];
        }else{
		
            $cur_session_year=$system_control['sys_info']['CURR_ACADEMIC_SESSION_YEAR'];
            $cur_session_type=$system_control['sys_info']['CURR_ACADEMIC_SESSION_TYPE'];
        }


		$allow_role_code_hod = explode("|", $system_control['sys_info']['ROLE_LIKE_HOD']);
        $allow_role_code_dean = explode("|", $system_control['sys_info']['ROLE_LIKE_DEAN']);

        $arr = [];
		
		if($role_code == "FACU"){
            
			$SQL = "SELECT mast_scheduled_faculty.* from mast_scheduled_faculty inner join trans_batch on (trans_batch.batch_id = mast_scheduled_faculty.batch_id)
			where (complete_status is null or complete_status='N')
			and schedule_faculty_id = '" . $user_id . "' and att_session_year=".$cur_session_year." and att_session_type='".$cur_session_type."'";
		}
		elseif($role_code == "BUTC" || in_array($role_code,$allow_role_code_hod)){

			$SQL = "SELECT mast_scheduled_faculty.* from mast_scheduled_faculty inner join trans_batch on (trans_batch.batch_id = mast_scheduled_faculty.batch_id)
			where (complete_status is null or complete_status='N')
			and (mast_scheduled_faculty.course_id IN ( select id from mast_course where department_id='" . $department_id . "') or mast_scheduled_faculty.schedule_faculty_id IN (  select user_id from bwuniver_global.mast_user where department_id='" . $department_id . "')) and att_session_year=".$cur_session_year." and att_session_type='".$cur_session_type."'";
		}
        elseif(in_array($role_code,$allow_role_code_dean)){

			$SQL = "SELECT mast_scheduled_faculty.* from mast_scheduled_faculty inner join trans_batch on (trans_batch.batch_id = mast_scheduled_faculty.batch_id)
			where (complete_status is null or complete_status='N')
			and (schedule_faculty_id IN ( select user_id from bwuniver_global.mast_user where school_id='" . $school_id . "') OR mast_scheduled_faculty.course_id IN ( select id from mast_course where department_id IN ( select department_id from trans_school_wise_department where school_id='" . $school_id . "') )) and att_session_year=".$cur_session_year." and att_session_type='".$cur_session_type."'";
		}
		
		else{
			$SQL = "SELECT mast_scheduled_faculty.* from mast_scheduled_faculty inner join trans_batch on (trans_batch.batch_id = mast_scheduled_faculty.batch_id)
			where (complete_status is null or complete_status='N') and att_session_year=".$cur_session_year." and att_session_type='".$cur_session_type."'";
		}
		
        
    //  echo  $SQL;
        $query = $this->db->query($SQL);
        if ($query->num_rows > 0) {
			
			$std=new stdclass();
            foreach ($query->fetch_all(MYSQLI_ASSOC) as $value) {
				
                $arr['scheduled_faculty'][$value['batch_id']][$value['module_semester_id']][$value['topic_paper_id']][$value['group_no']] = $value;

            }
        }
        return $arr;
    }
	
	public function get_paper_wise_faculty($param=''){
		$arr = [];
        $query = $this->db->query("SELECT mast_user.* FROM mast_scheduled_faculty 
		INNER JOIN bwuniver_global.mast_user on(mast_user.user_id = mast_scheduled_faculty.schedule_faculty_id)
		where batch_id='".$param["batch_id"]."' and module_semester_id='".$param["module_semester_id"]."' and topic_paper_id='".$param["topic_paper_id"]."' group by schedule_faculty_id");

        if ($query->num_rows > 0) {
            $row = $query->fetch_all(MYSQLI_ASSOC);
			//$std=new stdclass();
            foreach ($row as $value) {
				
				$std=new stdclass();
				$user_id=$value['user_id'];
				$std->teacher_id=$user_id;
				$std->teacher_name=$value['full_name'];
				array_push($arr , $std);

				//$arr[$value['user_id']] = $value['full_name'];
            }
        }
		//print_r($arr);exit;
        return $arr;
	}
	

    public function get_paper_wise_topic($param = '')
    {
        $arr = [];
        $SQL = "SELECT module_no,class_unit_name,class_unit_code
        from mast_course_structure_details
        inner join trans_batch on(mast_course_structure_details.course_id = trans_batch.course_id
        and trans_batch.batch_id='" . $param['batch_id'] . "')
        where module_semester_id='" . $param['module_semester_id'] . "'
        and topic_paper_id='" . $param['topic_paper_id'] . "'
        order by module_no";
        //echo  $SQL;
        $query = $this->db->query($SQL);
        if ($query->num_rows > 0) {
            foreach ($query->fetch_all(MYSQLI_ASSOC) as $value) {

                $arr['paper_wise_topic'][$value['module_no']] = $value;

            }
        }
        return $arr;
    }

    public function get_batch_wise_group($param = '')
    {
        $arr = [];
		$arr1 = [];
		
		$SQL = "SELECT group_no from trans_student_assign_group
		where batch_id ='" . $param['batch_id'] . "'
		and topic_paper_id ='" . $param['topic_paper_id'] . "'
		group by group_no order by group_no";
		//echo  $SQL;
		$query = $this->db->query($SQL);
		if ($query->num_rows > 0) {
			foreach ($query->fetch_all(MYSQLI_ASSOC) as $value) {

				$arr['group_list'][$value['group_no']] = $value['group_no'];
			}
		}
			
		
		if($param['paper_type'] == "T"){
			
			 return $arr1;
			
		}elseif($param['paper_type'] == "B" || $param['paper_type'] == "D" ){
			
			if($param['class_type'] == "U" || $param['class_type'] == "P" ){
				
				return $arr;
			}else{
				
				return $arr1;
			}
		}else{
		
			 return $arr;
		}
    }

    public function get_lecture_plan_data($param = '')
    {
		
        $arr = [];
		$user_id = $this->token_data->user_id;
		$role_code = $this->token_data->role_code;

        $curdate=date('Y-m-d');
        $SQL = "SELECT * from trans_session_wise_attendance_period where start_date <='".$curdate ."' and end_date >='".$curdate ."' and course_category=(select course_category from mast_course inner join trans_batch on (trans_batch.course_id =mast_course.id and trans_batch.batch_id ='" . $param['batch_id'] . "' )) ";
        $query = $this->db->query($SQL);
        if ($query->num_rows > 0) {
            $is_open= 'Y';
        }else{
            $is_open= 'N';
        }

        $SQL2 = "SELECT * from mast_topic_paper where topic_paper_id ='". $param['topic_paper_id']."' ";
        $query2 = $this->db->query($SQL2);
        $rsitem2=$query2->fetch_ASSOC();


        if ($param['group_no'] > 0 || $rsitem2->paper_type =="P") {
            $group_no = " and group_no='" . $param['group_no'] . "'";
        } else {
            $group_no = "";
        }
		
		if($role_code == "FACU" || $role_code == "BUTC"){
			$where_faculty = " and FIND_IN_SET ('".$user_id."' ,schedule_faculty_id)";
		}else{
			$where_faculty = "";
		}

        $SQL = "SELECT trans_paperwise_lecture_plan_details.*,trans_paperwise_lecture_plan.is_plan_locked from trans_paperwise_lecture_plan_details
        inner join trans_paperwise_lecture_plan
        on (trans_paperwise_lecture_plan_details.lecture_plan_id = trans_paperwise_lecture_plan.id)
        where batch_id='" . $param['batch_id'] . "'
        and module_semester_id='" . $param['module_semester_id'] . "'
        and topic_paper_id='" . $param['topic_paper_id'] . "' 
		and class_type='".$param['class_type']."'
        " . $group_no . " ".$where_faculty."
		group by module_no, lecture_no
        order by module_no,lecture_no";
        //echo $SQL;
		$std=new stdclass();
		
        $query = $this->db->query($SQL);
        if ($query->num_rows > 0) {
            foreach ($query->fetch_all(MYSQLI_ASSOC) as $value) {

				if(strlen($value['topic_covered']) >0 && $value['proposed_date'] <>'0000-00-00' && !is_null($value['proposed_date'])){
					$arr['lecture_plan'][$value['module_no']][] =
					array(
						"lecture_no" => $value['lecture_no'],
						"plan_details_id" => $value['id'],
						"module_no" => $value['module_no'],
						"topic_covered" => $value['topic_covered'],
						"is_class_taken" => (is_null($value['is_lecture_taken']) || $value['is_lecture_taken'] == "N") ? 'N' : 'Y',
						"is_plan_locked" => $value['is_plan_locked'],
                        "is_open" => $is_open
					);
				}
            }
        }
        return $arr;
    }

    public function get_max_other_class($param = '')
    {
        $arr = [];

        if ($param['group_no'] > 0) {
            $group_no = " and group_no='" . $param['group_no'] . "'";
        } else {
            $group_no = "";
        }

        $SQL = "SELECT module_no , max(lecture_no) as 'max_class'
        from trans_batch_details where batch_id='" . $param['batch_id'] . "'
        and module_semester_id='" . $param['module_semester_id'] . "'
        and topic_paper_id='" . $param['topic_paper_id'] . "'
        " . $group_no . "
        and class_type='" . $param['class_type'] . "'
        group by module_no";
        //echo $SQL;

        $query = $this->db->query($SQL);
        if (isset($query->num_rows) && $query->num_rows > 0) {
            foreach ($query->fetch_all(MYSQLI_ASSOC) as $value) {
                $arr['max_class_no'][$value['module_no']] = $value['max_class'];
            }
        }
        return $arr;
    }

    public function get_student_wise_attendance($param = '')
    {
        $str_update_attendance = '';

        if ($param['group_no'] > 0) {
            $group_no = " and group_no='" . $param['group_no'] . "'";
        } else {
            $group_no = "";
        }

        $SQL = "SELECT batch_details_id,update_attendance,actual_faculty_id1,actual_date1,syllabus_completed,actual_learning_method_id,remarks from trans_batch_details
        where batch_id='" . $param['batch_id'] . "'
        and module_semester_id='" . $param['module_semester_id'] . "'
        and topic_paper_id='" . $param['topic_paper_id'] . "'
        and module_no='" . $param['module_no'] . "'
        and lecture_no='" . $param['lecture_no'] . "'
        and class_type='" . $param['class_type'] . "'
         " . $group_no . " limit 0,1";
        //echo $SQL;

        $query = $this->db->query($SQL);
        if (isset($query->num_rows) && $query->num_rows > 0) {
            foreach ($query->fetch_all(MYSQLI_ASSOC) as $value) {
                // $str_update_attendance = $value['update_attendance'];
                $str_update_attendance = array(
                    "id" => $value['batch_details_id'],
                    "update_attendance" => $value['update_attendance'],
                    "actual_faculty" => $value['actual_faculty_id1'],
                    "actual_date" => $value['actual_date1'],
					"syllabus_completed" => $value['syllabus_completed'],
					"actual_learning_method_id" => $value['actual_learning_method_id'],
					"remarks" => $value['remarks'],
                );	
            }
        }
        return $str_update_attendance;
    }
	
	public function user_modify_control($param = ''){
		
		//Receive from param : batch_details_id , entry_date ,batch_id //
		$curdate=date('Y-m-d');
		$curdatetime = date('Y-m-d H:i:s');
		$user_id = $this->token_data->user_id;
		$role_code = $this->token_data->role_code;
		$allow_back_date_entry=1;
		$show_save='Y';
		
		if($param['batch_details_id'] > 0){ // modify mode
		
			if($param['entry_date'] == $curdate){
				$show_save='Y';
				$allow_back_date_entry=1;
			}else{
				//$SQL = "SELECT * from trans_attendance_lock_unlock where batch_details_id='".$param['batch_details_id']."' and lock_date >='".$curdate."' and is_locked='N' and active='Y'";
				
				$SQL = "SELECT * from mast_attendance_lock_new_upgrade where (FIND_IN_SET( '".$user_id."',faculty_id) OR faculty_id='-1') and (FIND_IN_SET( '".$param['batch_id']."' ,batch_id) OR batch_id='-1') and allow_backdate_entry_from <= '".$param['entry_date']."' and '".$param['entry_date']."' <= allow_backdate_entry_upto and active='Y' order by id desc limit 0,1";
				//echo $SQL;
				$query = $this->db->query($SQL);
				
				if ($query->num_rows > 0) {
					$row = $query->fetch_assoc();
					if($curdatetime <= $row['withdraw_attendance_date_lock_up_to']){
						$show_save='Y';
						$allow_back_date_entry=1;
					}else{
						$show_save='Y';
						$allow_back_date_entry=1;
					}
					
				}else{
					$show_save='Y';
					$allow_back_date_entry=1;
				}
			}
		}else{ //insert mode
			
			$SQL = "SELECT * from mast_attendance_lock_new_upgrade where (FIND_IN_SET( '".$user_id."',faculty_id) OR faculty_id='-1') and (FIND_IN_SET( '".$param['batch_id']."' ,batch_id) OR batch_id='-1') and allow_backdate_entry_from <= '".$curdate."' and '".$curdate."' <= allow_backdate_entry_upto and active='Y' order by id desc limit 0,1";
			//echo $SQL;
			$query = $this->db->query($SQL);
			if ($query->num_rows > 0) {
				foreach ($query->fetch_all(MYSQLI_ASSOC) as $value) {
					
					if($curdatetime <= $value['withdraw_attendance_date_lock_up_to']){
						
						$from_date= $value['allow_backdate_entry_from'];
						$to_date= $value['allow_backdate_entry_upto'];
						$date1=date_create($from_date);
						$date2=date_create($curdate);
						
						$allow_back_date_entry = (date_diff($date1,$date2)->format("%a"));
						$show_save='Y';
						
					}else{
						$allow_back_date_entry = 1;
						$show_save='Y';
					}
				}
				
			}else{
				$allow_back_date_entry = 1;
				$show_save='Y';
			}
		}
		
		return $allow_back_date_entry.'|'.$show_save;
		
	}

    public function get_student_list($param = '')
    {
        $arr = [];
        $arr_additional_student = [];
		
		$get_student_wise_attendance = $this->get_student_wise_attendance($param);

        $updated_attendance_date='';
        $updated_attendance_faculty='';
        $updated_syllabus_completed='';
        $updated_actual_learning_method='';
        $remarks='';
        $batch_details_id='';
        if(is_array($get_student_wise_attendance)){

            $arr_updated_attendance = (array) json_decode($get_student_wise_attendance['update_attendance']);
            $updated_attendance_date = $get_student_wise_attendance['actual_date'];
            $updated_attendance_faculty = $get_student_wise_attendance['actual_faculty'];
            $updated_syllabus_completed = $get_student_wise_attendance['syllabus_completed'];
            $updated_actual_learning_method = $get_student_wise_attendance['actual_learning_method_id'];
            $remarks = $get_student_wise_attendance['remarks'];
            $batch_details_id = $get_student_wise_attendance['id'];
        }
		
		$params=Array(
		"batch_details_id" => $batch_details_id ,
		"batch_id" => $param['batch_id'],
		"entry_date" => $updated_attendance_date
		);
		
		list($allow_back_date_entry , $show_save)= explode("|" , $this->user_modify_control($params));

        $arr['info'] = [
            "batch_id" => $param['batch_id'],
            "module_semester_id" => $param['module_semester_id'],
            "topic_paper_id" => $param['topic_paper_id'],
            "module_no" => $param['module_no'],
            "lecture_no" => $param['lecture_no'],
            "group_no" => $param['group_no'],
            "attendance_date" => $updated_attendance_date,
            "faculty_id" => $updated_attendance_faculty,
			"syllabus_completed" => ($updated_syllabus_completed=="Y" ? "Y" :"N"),
			"actual_learning_method_id" => $updated_actual_learning_method,
			"remarks" => $remarks,
			"allow_back_date_entry" => $allow_back_date_entry,
			"show_save" => $show_save
        ];

		$arr['student_list'] =[];
        //add students whose attendace is already done//
        if (isset($arr_updated_attendance) && sizeof($arr_updated_attendance) > 0) {

            $SQL = "SELECT trans_student.id,student_code,first_name,middle_name,surname
               from bwuniver_student.trans_student where id IN(" . implode(',', array_keys($arr_updated_attendance)) . ")
               group by student_code order by student_code ";
            $query = $this->db->query($SQL);
            //echo  $SQL;
            if ($query->num_rows > 0) {
                foreach ($query->fetch_all(MYSQLI_ASSOC) as $value) {
                    $arr['student_list'][] = array(
                        "student_id" => $value['id'],
                        "student_code" => $value['student_code'],
                        "student_name" => $value['first_name'] . ' ' . $value['middle_name'] . ' ' . $value['surname'],
                        "attendance_status" => (($arr_updated_attendance[$value['id']] == 0) ? 'N' : (($arr_updated_attendance[$value['id']] == 1) ? 'P' : 'A'))
                    );
                }
            }

        } else {

            if ($param['group_no'] > 0) {

                $SQL = "SELECT trans_student.id,student_code,first_name,middle_name,surname
               from trans_student_assign_group
               inner join bwuniver_student.trans_student
               on (trans_student.id=trans_student_assign_group.student_id and(dropout='N' or dropout is null))
               where batch_id='" . $param['batch_id'] . "'
              
               and topic_paper_id='" . $param['topic_paper_id'] . "'
               and group_no='" . $param['group_no'] . "'
               group by student_code order by student_code";

            } else {

               /*$SQL = "SELECT trans_student.id,student_code,first_name,middle_name,surname
               from trans_student_batch_history
               inner join bwuniver_student.trans_student
               on (trans_student.id=trans_student_batch_history.student_id and(dropout='N' or dropout is null))
               where batch_id='" . $param['batch_id'] . "'
               order by student_code";*/
			   $SQL = "SELECT trans_student.id,student_code,first_name,middle_name,surname
               from trans_student_assign_paper
               inner join bwuniver_student.trans_student
               on (trans_student.id=trans_student_assign_paper.student_id and(dropout='N' or dropout is null))
               where batch_id='" . $param['batch_id'] . "' 
			   and topic_paper_id='" . $param['topic_paper_id'] . "'
			   and module_semester_id='" . $param['module_semester_id'] . "'
               group by student_code order by student_code";
            }
            //echo $SQL;
            $query = $this->db->query($SQL);
            if ($query->num_rows > 0) {
                foreach ($query->fetch_all(MYSQLI_ASSOC) as $value) {
                    $arr['student_list'][] = array(
                        "student_id" => $value['id'],
                        "student_code" => $value['student_code'],
                        "student_name" => $value['first_name'] . ' ' . $value['middle_name'] . ' ' . $value['surname'],
                        "attendance_status" => 'NA',
                    );
                }
            }
        }

        //print_r($arr_additional_student);

        return $arr;
    }

    public function get_paper_details($paper_id){
        $arr = [];
        $SQL = "SELECT * from mast_topic_paper where topic_paper_id=".$paper_id;
        $query = $this->db->query($SQL);
        if ($query->num_rows > 0) {
             foreach ($query->fetch_all(MYSQLI_ASSOC) as $value) {

                  $arr['paper_details'][$value['topic_paper_id']] = $value;
             }
        }
        return $arr;
    }
	
	public function get_session_info($course_id){
        $arr = [];
		$curdate=date('Y-m-d');
        $SQL = "SELECT * from trans_session_wise_attendance_period where start_date <='".$curdate ."' and end_date >='".$curdate ."' and course_category=(select course_category from mast_course where id='".$course_id."') ";
        $query = $this->db->query($SQL);
        if ($query->num_rows > 0) {
             foreach ($query->fetch_all(MYSQLI_ASSOC) as $value) {

                  $arr['session_info']= $value;
             }
        }
        return $arr;
    } 

    public function student_data_save($param = '')
    {
        $arr = [];
		$user_id = $this->token_data->user_id;
		
        $course_id = $this->get_batch_wise_course($param)['batch_wise_course'][$param['batch_id']]['course_structure_id']; 
        $paper_details = $this->get_paper_details($param['topic_paper_id']);
        $class_unit_code = $this->get_paper_wise_topic($param)['paper_wise_topic'][$param['module_no']]['class_unit_code']; 
        
		$session_info = $this->get_session_info($course_id);

        $update_attenance_save = $this->get_student_wise_attendance($param);
        if(is_array($update_attenance_save)){

            $arr_updated_attendance = (array) json_decode($this->get_student_wise_attendance($param)['update_attendance']);
            $updated_attendance_id = $this->get_student_wise_attendance($param)['id']; //auto id of trans_batch_details
        }
        
        $updated_attendance_date = $param['actual_date']; // actual date 
        $updated_attendance_faculty = $param['faculty_id']; // actual faculty 
        $paper_type = $paper_details['paper_details'][$param['topic_paper_id']]['paper_type'];
		$class_copy = $param['class_copy']; // class copy 
		$remarks = $param['remark'];   //remark 

        if ($updated_attendance_id > 0) {

            $stud_Array = [];

            //Delete then insert into attendance details table//

            $SQL = " Delete from  trans_attendance_details
            where batch_details_id = '" . $updated_attendance_id . "'"; //echo $SQL;exit;
            $query = $this->db->query($SQL);

            $arr_student =$param['student_info'];
            $present_student=0; $absent_student=0;$na_student=0;  //echo $arr_student; print_r($arr_student);exit;
            foreach ($arr_student as $std_data) {

                $std_id = substr($std_data ,0,-1);
                $std_info = substr($std_data ,-1);

				if($std_info =="P" || $std_info =="A"){
					
					$SQL = " INSERT INTO `trans_attendance_details`(`batch_details_id`, `topic_paper_id`, `course_id`, `student_id`, `attendance_status`, `group_no`) VALUES
					( '" . $updated_attendance_id . "' , '" . $param['topic_paper_id'] . "' , '" . $course_id . "' ,'" . $std_id . "' , '" . $std_info . "' , '" . $param['group_no'] . "' )";
					//echo $SQL;exit;
					$query = $this->db->query($SQL);
				}
				
				
                if ($std_info == "P") {
                    $present_student = $present_student + 1;
                }
                if ($std_info == "A") {
                    $absent_student = $absent_student + 1;
                }
				if ($std_info == "N") {
                    $na_student = $na_student + 1;
                }
                //$stud_Array[$std_id] = ($std_info == 'P' ? '1' : '0.0001');
				$stud_Array[$std_id] = ($std_info == 'P' ? '1' : ($std_info == 'N' ? '0':'0.0001'));

            }

            //update summery in batch_details table and lecture plan details table //

            $SQL = "update trans_batch_details set 
            update_attendance='" .json_encode($stud_Array) . "',
            total_present='" . $present_student . "',
            total_absent='" . $absent_student . "' ,
            plan_details_id = '".$param['plan_details_id']."',
            actual_faculty_id1 = '" . $updated_attendance_faculty . "',
            actual_date1 = '" . $updated_attendance_date . "',
            syllabus_completed = '" . $param['syllabus_completed'] . "', 
			actual_learning_method_id = '" . $param['actual_learning_method_id'] . "',
			remarks = '" . $remarks . "'
            where batch_details_id=" .  $updated_attendance_id;
            $query = $this->db->query($SQL);


            $SQL = "update trans_paperwise_lecture_plan_details set 
            is_lecture_taken ='Y',
            actual_faculty_id='" . $updated_attendance_faculty . "',
            actual_date='" . $updated_attendance_date . "' ,
            batch_details_id='" . $updated_attendance_id . "' ,
            actual_learning_method_id = '" . $param['actual_learning_method_id'] . "' 
            where id=" .   $param['plan_details_id'];
            $query = $this->db->query($SQL);
            //echo $SQL;exit();

            return Array("status" => "success");

        } else {
			
            $stud_Array = [];
			$update_attendance='';
			$arr_student = $param['student_info'];
            $present_student=0; $absent_student=0;$na_student=0;  //echo $arr_student; print_r($arr_student);exit;
			
			foreach ($arr_student as $std_data) {
				
				$std_id = substr($std_data ,0,-1);
                $std_info = substr($std_data ,-1);
				
				if ($std_info == "P") {
                    $present_student = $present_student + 1;
                }
                if ($std_info == "A") {
                    $absent_student = $absent_student + 1;
                }
				if ($std_info == "N") {
                    $na_student = $na_student + 1;
                }
                $stud_Array[$std_id] = ($std_info == 'P' ? '1' : ($std_info == 'N' ? '0':'0.0001'));
				
			}
			
			$update_attendance = json_encode($stud_Array);
			
			$att_session_year = $session_info['session_info']['session_year'];
			$att_session_type = $session_info['session_info']['odd_even'];
			
            $SQL = "INSERT INTO `trans_batch_details`(`batch_id`, `course_id`, `module_semester_id`, `topic_paper_id`, `class_unit_code`, `actual_faculty_id1`, `actual_date1`, `attendance_updated`, `class_no`, `class_type`, `module_no`, `lecture_no`, `plan_details_id`, `group_no`, `paper_type`, `theory_tutorial`, `syllabus_completed`, `actual_learning_method_id`, `last_modification_date_time`, `last_modified_by_user_id` , update_attendance, total_present ,total_absent,att_session_year ,att_session_type,remarks ) VALUES
            ( '" . $param['batch_id'] . "' , '" . $course_id . "' , '" . $param['module_semester_id'] . "', '" . $param['topic_paper_id'] . "' , '" . $class_unit_code . "' ,'" . $updated_attendance_faculty . "' , '" . $updated_attendance_date . "' , 'Y' ,'" . $param['lecture_no'] . "','" . $param['class_type'] . "','" . $param['module_no'] . "','" . $param['lecture_no'] . "','" . $param['plan_details_id'] . "' ,'" . $param['group_no'] . "','" . $paper_type . "','" .($paper_type == "B" ?($param['class_type'] =='U' ?'U':'T'):''). "' ,'" . $param['syllabus_completed'] . "','" . $param['actual_learning_method_id'] . "' , '".date('Y-m-d H:i:s')."' ,'".$user_id."' ,'".$update_attendance."', '".$present_student."', '".$absent_student."','".$att_session_year."','".$att_session_type."','".$remarks."')";
            //echo $SQL;exit;
            $query = $this->db->query($SQL);
			

            $SQL1 = "SELECT LAST_INSERT_ID() as 'lid' FROM trans_batch_details"; 
            $query1 = $this->db->query($SQL1);
            $rsitem1 = $query1->fetch_assoc();
            $last_insert_id = $rsitem1['lid'];
            //echo $last_insert_id;exit;
            
            foreach ($arr_student as $std_data) {

                $std_id = substr($std_data ,0,-1);
                $std_info = substr($std_data ,-1);

				if($std_info =="P" || $std_info =="A"){
					
					$SQL = " INSERT INTO `trans_attendance_details`(`batch_details_id`, `topic_paper_id`, `course_id`, `student_id`, `attendance_status`, `group_no`) VALUES
					( '" . $last_insert_id . "' , '" . $param['topic_paper_id'] . "' , '" . $course_id . "' ,'" . $std_id . "' , '" . $std_info . "' , '" . $param['group_no'] . "' )";
					//echo $SQL;
					$query = $this->db->query($SQL);
				}

            }

            $SQL3 = "update trans_paperwise_lecture_plan_details set 
            is_lecture_taken ='Y',
            actual_faculty_id='" . $updated_attendance_faculty . "',
            actual_date='" . $updated_attendance_date . "' ,
            batch_details_id='" . $last_insert_id . "' ,
            actual_learning_method_id = '" . $param['actual_learning_method_id'] . "' 
            where id=" . $param['plan_details_id'];
            $query = $this->db->query($SQL3);
            //echo $SQL;exit();
			
			
			
			//class copy if needed //
			
			if(sizeof($class_copy) >0){
				
				$batch_details_id_copy = $last_insert_id;
				foreach($class_copy as $value){
							
					list($lecture_no,$plan_details_id) = explode("|",$value);
					
					$sql = "select * from trans_batch_details where batch_details_id=".$batch_details_id_copy;
					$query = $this->db->query($sql);
					if( $query->num_rows > 0 ){    
						$rows = $query->fetch_all(MYSQLI_ASSOC);
						
						foreach($rows as $val){
							
							$sql_exist = "select batch_details_id from trans_batch_details where plan_details_id=".$plan_details_id;
							$query_exist = $this->db->query($sql_exist);
							if( $query_exist->num_rows == 0 ){    
							
								$SQL_FORM = "INSERT INTO `trans_batch_details`(`batch_id`, `course_id`, `module_semester_id`, `topic_paper_id`, `class_unit_code`, `actual_faculty_id1`, `actual_date1`, `attendance_updated`, `class_no`, `class_type`, `module_no`, `lecture_no`, `plan_details_id`, `group_no`, `paper_type`, `theory_tutorial`, `syllabus_completed`, `actual_learning_method_id`, `last_modification_date_time`, `last_modified_by_user_id` , update_attendance, total_present ,total_absent,att_session_year ,att_session_type,remarks) VALUES ( '" . $val['batch_id'] . "' , '" . $val['course_id'] . "' , '" . $val['module_semester_id'] . "', '" . $val['topic_paper_id'] . "' , '" . $val['class_unit_code'] . "' ,'" . $val['actual_faculty_id1'] . "' , '" . $val['actual_date1'] . "' , 'Y' ,'" . $lecture_no . "','" . $val['class_type'] . "','" . $val['module_no'] . "','" . $lecture_no . "','" . $plan_details_id . "' ,'" . $val['group_no'] . "','" . $val['paper_type'] . "','" .$val['theory_tutorial']. "' ,'" . $val['syllabus_completed'] . "','" . $val['actual_learning_method_id'] . "' , '".date('Y-m-d H:i:s')."' ,'".$val['last_modified_by_user_id']."' ,'".$val['update_attendance']."', '".$val['total_present']."', '".$val['total_absent']."','".$val['att_session_year']."','".$val['att_session_type']."','".$val['remarks']."')";
								
								$query = $this->db->query($SQL_FORM);
								
								$SQL1 = "SELECT LAST_INSERT_ID() as 'lid' FROM trans_batch_details"; 
								$query1 = $this->db->query($SQL1);
								$rsitem1 = $query1->fetch_assoc();
								$last_insert_id_copy = $rsitem1['lid'];
								
								
								$SQL = "update trans_paperwise_lecture_plan_details set 
								is_lecture_taken ='Y',
								actual_faculty_id='" . $val['actual_faculty_id1'] . "',
								actual_date='" . $val['actual_date1'] . "' ,
								batch_details_id='" . $last_insert_id_copy . "' ,
								actual_learning_method_id = '" . $val['actual_learning_method_id'] . "' 
								where id=" .  $plan_details_id;
								$query = $this->db->query($SQL);
				
				
								$sql = "select * from trans_attendance_details where batch_details_id=".$batch_details_id_copy;
								$query = $this->db->query($sql);
								if( $query->num_rows > 0 ){  
								
									$rows = $query->fetch_all(MYSQLI_ASSOC);
									$values='';
									foreach($rows as $val){
										
										$values .= " ( '" . $last_insert_id_copy . "' , '" . $val['topic_paper_id'] . "' , '" . $val['course_id'] . "' ,'" . $val['student_id'] . "' , '" . $val['attendance_status'] . "' , '" . $val['group_no'] . "' ),";
									}
								}
								
								
								$SQL = "INSERT INTO `trans_attendance_details`(`batch_details_id`, `topic_paper_id`, `course_id`, `student_id`, `attendance_status`, `group_no`) VALUES ".substr($values,0,-1)." ";
								
								$query = $this->db->query($SQL);
							}
							
						}
					}
				}
				
			}
			
            return Array("status" => "success");
        }

    }

}
