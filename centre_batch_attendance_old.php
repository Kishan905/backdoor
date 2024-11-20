<?php
// load  Autoload
include_once '../../../Autoload.php';
include_once BASEPATH . 'model/academics/entry/Centre_attendance_modell_old.php';
include_once BASEPATH . 'model/Common_model.php';
include_once BASEPATH . 'model/academics/Common_controls_model.php';

## Form Validation
require BASEPATH . 'libs/form-validation/vendor/autoload.php';
//include_once BASEPATH.'model/UniqueRule.php';
use Rakit\Validation\Validator;
$form_validation = new Validator;

// instantiate user object
$attendance_model = new Centre_batch_attendance_model();
$common_model = new Common_model();
$common_controls_model = new Common_controls_model();

// allow http request method
if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'GET', 'PUT', 'DELETE'])) {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'unknown method!']);
    exit;
}
$input = json_decode(file_get_contents("php://input"));

$faculty_id = $log_data->user_id;

$session_year= isset($_GET['session_year']) ? $_GET['session_year'] :'';
$session_type= isset($_GET['session_type']) ? $_GET['session_type'] :'';

$batch_code = $common_controls_model->get_batch_code($params = '');
$paper = $common_controls_model->get_paper_details($params = '');
$semester = $common_controls_model->get_semester($params = '');

$params = array("user_id" => $faculty_id,"session_year" => $session_year,"session_type" => $session_type);

## get batch
if ($_SERVER['REQUEST_METHOD'] == 'GET' && $_GET['mode'] == 'batch-list') {
    $arr = [];
	$scheduled_data = $attendance_model->get_scheduled_faculty($params);
	$course_details = $common_model->get_course($params);
    foreach (array_keys($scheduled_data['scheduled_faculty']) as $key) {
        $code = $batch_code['batch_code'][$key]['batch_code'];
		$course_id = $batch_code['batch_code'][$key]['course_id'];
		$course_name = $course_details['course'][$course_id];
		//$std->$key=$code;
		$std=new stdclass(); 
		$std->batch_id=$key;
		$std->batch_code=$code;
		$std->course_id=$course_id;
		$std->course_name=$common_model->get_formatted_course($course_name['course_name']);
        array_push($arr , $std);
    }
	//array_push($arr , $arr1);
    echo json_encode(array("status" => "success", "data" => $arr));
}

## get semester
if ($_SERVER['REQUEST_METHOD'] == 'GET' && $_GET['mode'] == 'semester-list') {
    $arr = [];
	$scheduled_data = $attendance_model->get_scheduled_faculty($params);
    foreach (array_keys($scheduled_data['scheduled_faculty'][$_GET['batch_id']]) as $key) {
        $sem_name = $semester['semester'][$key]['module_semester_name'];
		//$std->$key=$sem_name;
		$std=new stdclass(); 
		$std->semester_id=$key;
		$std->semester_name=$sem_name;
        array_push($arr , $std);
    }
	//array_push($arr , $std);
    echo json_encode(array("status" => "success", "data" => $arr));
}

## get paper
if ($_SERVER['REQUEST_METHOD'] == 'GET' && $_GET['mode'] == 'paper-list') {
    $arr = [];
	$scheduled_data = $attendance_model->get_scheduled_faculty($params);
    foreach (array_keys($scheduled_data['scheduled_faculty'][$_GET['batch_id']][$_GET['module_semester_id']]) as $key) {
        $paper_name = $paper['topic_paper'][$key]['topic_paper_name'];
        $paper_code = $paper['topic_paper'][$key]['topic_paper_code'];
		
		//$std->$key = $paper_code . ' -> ' . $paper_name;
		
		$std=new stdclass(); 
		$std->paper_id=$key;
		$std->paper_name=$paper_code . ' -> ' . $paper_name;
        array_push($arr , $std);
        //$arr[$key] = $paper_code . ' -> ' . $paper_name;
    }
    echo json_encode(array("status" => "success", "data" => $arr));
}

## get topic/module
if ($_SERVER['REQUEST_METHOD'] == 'GET' && $_GET['mode'] == 'topic-list') {
    $arr = [];
    $params = array("batch_id" => $_GET['batch_id'],
        "module_semester_id" => $_GET['module_semester_id'],
        "topic_paper_id" => $_GET['topic_paper_id'],
    );
    $paper_wise_topic = $attendance_model->get_paper_wise_topic($params);
	
    foreach ($paper_wise_topic['paper_wise_topic'] as $key => $value) {

		//$std->$key = $value;
		$std=new stdclass();
		$std->module_no=$key;
		$std->module_name=$value['class_unit_code'] .'-' .$value['class_unit_name'];
        array_push($arr , $std);
		
    }

    echo json_encode(array("status" => "success", "data" => $arr));
}

## class type
if ($_SERVER['REQUEST_METHOD'] == 'GET' && $_GET['mode'] == 'class-type-list') {
    $arr = [];
    $paper_type = $paper['topic_paper'][$_GET['topic_paper_id']]['paper_type'];

    if ($paper_type == "T") {
        $arr = [
		["class_type" => "T" , "Class_type_name" => "Theory Class"] , 
		["class_type" => "A" , "Class_type_name" => "Additional Class" ] ,
		["class_type" => "R" , "Class_type_name" => "Remedial Class" ] ,
		["class_type" => "L" , "Class_type_name" => "Class For Advance Learners" ]
		
		];
	
    } elseif ($paper_type == "P") {
        $arr =[
		["class_type" => "P" , "Class_type_name" => "Practical Class"] , 
		["class_type" => "A" , "Class_type_name" => "Additional Class" ]
		
		];

    } elseif ($paper_type == "B") {
        $arr = [
		["class_type" => "T" , "Class_type_name" => "Theory Class"] , 
		["class_type" => "U" , "Class_type_name" => "Tutorial Class" ],
		["class_type" => "R" , "Class_type_name" => "Remedial Class" ],	
		["class_type" => "A" , "Class_type_name" => "Additional Class" ],
		["class_type" => "L" , "Class_type_name" => "Class For Advance Learners" ]
		
		];

    } elseif ($paper_type == "D") {
        $arr = [
		["class_type" => "T" , "Class_type_name" => "Theory Class"] , 
		["class_type" => "P" , "Class_type_name" => "Practical Class" ],
		["class_type" => "A" , "Class_type_name" => "Additional Class" ],
		["class_type" => "R" , "Class_type_name" => "Remedial Class" ]
		
		];
    }

    echo json_encode(array("status" => "success", "data" => $arr));
}

## get group
if ($_SERVER['REQUEST_METHOD'] == 'GET' && $_GET['mode'] == 'group-list') {
    $arr = [];
	$paper_type = $paper['topic_paper'][$_GET['topic_paper_id']]['paper_type'];
	
    $params = array("batch_id" => $_GET['batch_id'],
        "module_semester_id" => $_GET['module_semester_id'],
        "topic_paper_id" => $_GET['topic_paper_id'],
		"class_type"  => $_GET['class_type'],
		"paper_type" => $paper_type
    );
    $batch_wise_group = $attendance_model->get_batch_wise_group($params);
	
    foreach ($batch_wise_group['group_list'] as $key) {
        if ($key > 0) {
          
			$std=new stdclass();
			$std->group_no=$key;
			$std->group_name='Group-' . $key;
			array_push($arr , $std);
        }
    }
	//array_push($arr , $std);
    echo json_encode(array("status" => "success", "data" => $arr));
}

## class name
if ($_SERVER['REQUEST_METHOD'] == 'GET' && $_GET['mode'] == 'class-name') {
    $arr = [];
	
	$paper_code = $paper['topic_paper'][$_GET['topic_paper_id']]['topic_paper_code'];
	$paper_name = $paper['topic_paper'][$_GET['topic_paper_id']]['topic_paper_name'];
	$is_preparatory = $paper['topic_paper'][$_GET['topic_paper_id']]['is_preparatory'];
	
	list($module_code , $module_name) = explode("-" ,$_GET['module_name']);
	
    $params = array("user_id" => $faculty_id,
        "class_type" => $_GET['class_type'],
        "batch_id" => $_GET['batch_id'],
        "module_semester_id" => $_GET['module_semester_id'],
        "topic_paper_id" => $_GET['topic_paper_id'],
        "group_no" => $_GET['group_no'],
    );
    $lecture_plan_data = $attendance_model->get_lecture_plan_data($params);
    $max_class_no = $attendance_model->get_max_other_class($params);

    $class_type = $params['class_type'];
    $arr_spl = ["U" => "Tutorial Class",
        "A" => "Additional Class",
        "R" => "Remedial Class",
        "L" => "Class For Advance Learners",
		"J" => "Adjustment Class"
    ];
	
	$comp_paper_code= Array(
	'SSU'=> 'Special Class',
	'SSD'=> 'Special Class',
	'SSP' => 'Special Class',
	'UNS' => 'Special Class',
	'BUP'=> 'Preparatory Class',
	'BUL'=> 'Practice Lab Class' ,
	'APT'=> 'Aptitude Class',
	'SBC' => 'Boot Camp Class',
	'TBC' => 'Boot Camp Class',
	'ABC' => 'Boot Camp Class'
	);

    if ($class_type == "A" || $class_type == "R" || $class_type == "L" || $class_type == "J") {

        for ($i = 1; $i <= 10; $i++) {
            $arr[] = [
				"lecture_no" => $i,
                "plan_details_id" => 'NA',
                "module_no" => $_GET['module_no'],
                "topic_covered" => $arr_spl[$class_type] . '-' . $i,
                "is_class_taken" => ($max_class_no['max_class_no'][$_GET['module_no']] >= $i ? 'Y' : 'N'),
                "is_open" => 'Y'
            ];
        }
    }elseif($is_preparatory <> "N" || in_array(substr($paper_code,0,3) , array_keys($comp_paper_code))){
		
		for ($i = 1; $i <= 50; $i++) {
            $arr[] = [
				"lecture_no" => $i,
                "plan_details_id" => 'NA',
                "module_no" => $_GET['module_no'],
                "topic_covered" => $module_name . '-' . $i,
                "is_class_taken" => ($max_class_no['max_class_no'][$_GET['module_no']] >= $i ? 'Y' : 'N'),
                "is_open" => 'Y'
            ];
        }
		
	}else {
        $arr = $lecture_plan_data['lecture_plan'][$_GET['module_no']];
    }
	
	if(count($arr) >0){
		echo json_encode(array("status" => "success", "data" => $arr));
	}else{
		echo json_encode(array("status" => "success", "data" =>[]));
	}
}

## student list
if ($_SERVER['REQUEST_METHOD'] == 'GET' && $_GET['mode'] == 'student-list') {
    $arr = [];
    $arr_student_list = [];
    $params = array("batch_id" => $_GET['batch_id'],
        "module_semester_id" => $_GET['module_semester_id'],
        "topic_paper_id" => $_GET['topic_paper_id'],
        "group_no" => $_GET['group_no'],
        "module_no" => $_GET['module_no'],
        "lecture_no" => $_GET['lecture_no'],
        "class_type" => $_GET['class_type']
    );

    $arr[] = $attendance_model->get_student_list($params);

    echo json_encode(array("status" => "success", "data" => $arr));
}


## Faculty List
if ($_SERVER['REQUEST_METHOD'] == 'GET' && $_GET['mode'] == 'teacher-list') {
    $arr = [];
	$params = array("batch_id" => $_GET['batch_id'],
        "module_semester_id" => $_GET['module_semester_id'],
        "topic_paper_id" => $_GET['topic_paper_id'],
    );
    $arr = $attendance_model->get_paper_wise_faculty($params);
	
    echo json_encode(array("status" => "success", "data" => $arr));
}



## Create And Update

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $input->mode == "save") {

    $params = array("batch_id" => $input->batch_id,
        "module_semester_id" => $input->module_semester_id,
        "topic_paper_id" => $input->topic_paper_id,
        "group_no" => $input->group_no,
        "module_no" => $input->module_no,
        "lecture_no" => $input->lecture_no,
        "class_type" => $input->class_type,
        "student_info" => $input->student_info,
        "plan_details_id" => $input->plan_details_id,
        "faculty_id" => $input->faculty_id,
        "actual_date" => $input->actual_date,
        "syllabus_completed" => $input->syllabus_completed,
        "actual_learning_method_id" => $input->actual_learning_method_id,
		"class_copy" => $input->class_copy,
		"remark" => $input->remark
    );

    $responce = $attendance_model->student_data_save($params);
    if ($responce['status'] == 'success') {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Insert successfully.']);
        exit;
    } else {
        http_response_code(304);
        echo json_encode(['status' => 'error', 'message' => 'Insert unsuccessfully!']);
        exit;
    }
   
}

